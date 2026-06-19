package com.ere.derws
import android.annotation.SuppressLint
import android.content.Intent
import android.graphics.Bitmap
import android.net.Uri
import android.os.Bundle
import android.view.KeyEvent
import android.view.View
import android.webkit.*
import android.widget.ProgressBar
import androidx.appcompat.app.AppCompatActivity
class MainActivity : AppCompatActivity() {
    private lateinit var webView: WebView
    private lateinit var progressBar: ProgressBar
    private val startUrl = "https://creatorapp24.com/"
    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        webView = findViewById(R.id.webView)
        progressBar = findViewById(R.id.progressBar)
        webView.settings.apply {
            javaScriptEnabled = true; domStorageEnabled = true
            loadWithOverviewMode = true; useWideViewPort = true
            builtInZoomControls = false; displayZoomControls = false
            setSupportZoom(true); allowFileAccess = true
            mediaPlaybackRequiresUserGesture = false
            mixedContentMode = WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
        }
        webView.webViewClient = object : WebViewClient() {
            override fun onPageStarted(v: WebView?, url: String?, f: Bitmap?) { progressBar.visibility = View.VISIBLE }
            override fun onPageFinished(v: WebView?, url: String?) { progressBar.visibility = View.GONE }
            override fun shouldOverrideUrlLoading(v: WebView?, r: WebResourceRequest?): Boolean {
                val url = r?.url?.toString() ?: return false
                return when {
                    url.startsWith("tel:")||url.startsWith("mailto:")||url.startsWith("whatsapp:") ->
                        { startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url))); true }
                    url.contains(Uri.parse(startUrl).host ?: "") -> false
                    else -> { startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url))); true }
                }
            }
        }
        webView.webChromeClient = object : WebChromeClient() {
            override fun onProgressChanged(v: WebView?, p: Int) {
                progressBar.progress = p; if(p==100) progressBar.visibility = View.GONE
            }
        }
        if (savedInstanceState != null) webView.restoreState(savedInstanceState)
        else webView.loadUrl(startUrl)
    }
    override fun onKeyDown(k: Int, e: KeyEvent?): Boolean {
        if (k == KeyEvent.KEYCODE_BACK && webView.canGoBack()) { webView.goBack(); return true }
        return super.onKeyDown(k, e)
    }
    override fun onSaveInstanceState(out: Bundle) { super.onSaveInstanceState(out); webView.saveState(out) }
}