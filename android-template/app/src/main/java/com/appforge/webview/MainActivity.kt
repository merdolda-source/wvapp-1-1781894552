package com.appforge.webview

import android.annotation.SuppressLint
import android.app.DownloadManager
import android.content.ActivityNotFoundException
import android.content.Context
import android.content.Intent
import android.graphics.Color
import android.net.Uri
import android.os.Bundle
import android.os.Environment
import android.view.View
import android.webkit.CookieManager
import android.webkit.URLUtil
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Button
import android.widget.ProgressBar
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import androidx.webkit.WebSettingsCompat
import androidx.webkit.WebViewFeature

class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private lateinit var swipeRefresh: SwipeRefreshLayout
    private lateinit var offlineView: View
    private var homeHost: String? = null
    private var filePathCallback: ValueCallback<Array<Uri>>? = null

    private val fileChooserLauncher = registerForActivityResult(ActivityResultContracts.GetContent()) { uri: Uri? ->
        filePathCallback?.onReceiveValue(if (uri != null) arrayOf(uri) else null)
        filePathCallback = null
    }

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val prefs = getSharedPreferences(RemoteConfig.PREFS_NAME, MODE_PRIVATE)
        val headerColor = prefs.getInt(RemoteConfig.KEY_HEADER_COLOR, ContextCompat.getColor(this, R.color.header_color))
        val targetUrl = prefs.getString(RemoteConfig.KEY_TARGET_URL, null)?.takeIf { it.isNotBlank() }
            ?: getString(R.string.target_url)

        // Links to other domains (payment gateways, social apps, ...) and
        // non-http(s) schemes (tel:, mailto:, whatsapp:, intent:, ...) are
        // handed off to the system instead of failing inside the WebView.
        homeHost = runCatching { Uri.parse(targetUrl).host }.getOrNull()

        // No native title bar - the wrapped site provides its own header/nav.
        // Only the system status bar is tinted to match the chosen color.
        window.statusBarColor = headerColor

        val progressBar = findViewById<ProgressBar>(R.id.progressBar)
        val retryButton = findViewById<Button>(R.id.retryButton)
        offlineView = findViewById(R.id.offlineView)
        swipeRefresh = findViewById(R.id.swipeRefresh)
        webView = findViewById(R.id.webView)

        swipeRefresh.setColorSchemeColors(headerColor)
        swipeRefresh.setOnRefreshListener { webView.reload() }

        // Avoid a black flash/strip before the page paints, and stop the
        // system from algorithmically dark-mode-inverting page content.
        webView.setBackgroundColor(Color.WHITE)
        if (WebViewFeature.isFeatureSupported(WebViewFeature.ALGORITHMIC_DARKENING)) {
            WebSettingsCompat.setAlgorithmicDarkeningAllowed(webView.settings, false)
        }

        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            loadWithOverviewMode = true
            useWideViewPort = true
            setSupportZoom(true)
            builtInZoomControls = false
            // Some hosts/CDNs/WAFs respond differently (or reject) requests carrying
            // the "; wv" WebView marker that Android appends by default. Presenting
            // as a normal mobile Chrome browser avoids that class of failures.
            userAgentString = userAgentString.replace("; wv", "")
        }

        CookieManager.getInstance().setAcceptCookie(true)
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, true)

        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                val uri = request.url
                if (uri.scheme != "http" && uri.scheme != "https") {
                    return openExternally(uri)
                }
                val host = uri.host
                val home = homeHost
                if (home != null && host != null && !host.endsWith(home)) {
                    return openExternally(uri)
                }
                return false
            }

            override fun onReceivedError(view: WebView, request: WebResourceRequest, error: WebResourceError) {
                super.onReceivedError(view, request, error)
                if (request.isForMainFrame) {
                    showOffline()
                }
            }

            @Deprecated("Deprecated in Java", ReplaceWith(""))
            override fun onReceivedError(view: WebView, errorCode: Int, description: String?, failingUrl: String?) {
                super.onReceivedError(view, errorCode, description, failingUrl)
                showOffline()
            }

            override fun onPageFinished(view: WebView, url: String) {
                super.onPageFinished(view, url)
                swipeRefresh.isRefreshing = false
            }
        }

        webView.webChromeClient = object : WebChromeClient() {
            override fun onProgressChanged(view: WebView, newProgress: Int) {
                progressBar.progress = newProgress
                progressBar.visibility = if (newProgress >= 100) View.GONE else View.VISIBLE
            }

            override fun onShowFileChooser(
                view: WebView,
                callback: ValueCallback<Array<Uri>>,
                params: FileChooserParams
            ): Boolean {
                filePathCallback = callback
                return try {
                    fileChooserLauncher.launch("*/*")
                    true
                } catch (e: Exception) {
                    filePathCallback = null
                    false
                }
            }
        }

        webView.setDownloadListener { url, _, contentDisposition, mimeType, _ ->
            runCatching {
                val request = DownloadManager.Request(Uri.parse(url))
                    .setMimeType(mimeType)
                    .addRequestHeader("cookie", CookieManager.getInstance().getCookie(url))
                    .setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                    .setDestinationInExternalPublicDir(
                        Environment.DIRECTORY_DOWNLOADS,
                        URLUtil.guessFileName(url, contentDisposition, mimeType)
                    )
                (getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager).enqueue(request)
            }
        }

        retryButton.setOnClickListener {
            offlineView.visibility = View.GONE
            webView.reload()
        }

        webView.loadUrl(targetUrl)

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack()
                } else {
                    isEnabled = false
                    onBackPressedDispatcher.onBackPressed()
                }
            }
        })
    }

    private fun openExternally(uri: Uri): Boolean {
        return try {
            startActivity(Intent(Intent.ACTION_VIEW, uri))
            true
        } catch (e: ActivityNotFoundException) {
            true
        }
    }

    private fun showOffline() {
        offlineView.visibility = View.VISIBLE
        swipeRefresh.isRefreshing = false
    }

    override fun onDestroy() {
        webView.destroy()
        super.onDestroy()
    }
}
