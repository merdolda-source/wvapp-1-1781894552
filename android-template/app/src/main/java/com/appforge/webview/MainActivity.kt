package com.appforge.webview

import android.annotation.SuppressLint
import android.graphics.Color
import android.os.Bundle
import android.view.View
import android.webkit.WebChromeClient
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.ProgressBar
import androidx.appcompat.app.AppCompatActivity
import androidx.activity.OnBackPressedCallback
import androidx.core.content.ContextCompat
import androidx.webkit.WebSettingsCompat
import androidx.webkit.WebViewFeature

class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val prefs = getSharedPreferences(RemoteConfig.PREFS_NAME, MODE_PRIVATE)
        val headerColor = prefs.getInt(RemoteConfig.KEY_HEADER_COLOR, ContextCompat.getColor(this, R.color.header_color))
        val targetUrl = prefs.getString(RemoteConfig.KEY_TARGET_URL, null)?.takeIf { it.isNotBlank() }
            ?: getString(R.string.target_url)

        // No native title bar - the wrapped site provides its own header/nav.
        // Only the system status bar is tinted to match the chosen color.
        window.statusBarColor = headerColor

        val progressBar = findViewById<ProgressBar>(R.id.progressBar)
        webView = findViewById(R.id.webView)

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

        webView.webViewClient = WebViewClient()
        webView.webChromeClient = object : WebChromeClient() {
            override fun onProgressChanged(view: WebView, newProgress: Int) {
                progressBar.progress = newProgress
                progressBar.visibility = if (newProgress >= 100) View.GONE else View.VISIBLE
            }
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

    override fun onDestroy() {
        webView.destroy()
        super.onDestroy()
    }
}
