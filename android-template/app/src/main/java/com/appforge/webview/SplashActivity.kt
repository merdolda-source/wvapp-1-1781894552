package com.appforge.webview

import android.content.Intent
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.View
import android.widget.FrameLayout
import android.widget.ImageView
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.content.res.ResourcesCompat

class SplashActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_splash)

        val prefs = getSharedPreferences(RemoteConfig.PREFS_NAME, MODE_PRIVATE)
        val root = findViewById<FrameLayout>(R.id.splashRoot)
        val iconView = findViewById<ImageView>(R.id.splashIcon)
        val textView = findViewById<TextView>(R.id.splashText)

        val bgColor = prefs.getInt(RemoteConfig.KEY_SPLASH_BG, ContextCompat.getColor(this, R.color.splash_bg_color))
        val textColor = prefs.getInt(RemoteConfig.KEY_SPLASH_TEXT_COLOR, ContextCompat.getColor(this, R.color.splash_text_color))
        val text = prefs.getString(RemoteConfig.KEY_SPLASH_TEXT, null)?.takeIf { it.isNotEmpty() }
            ?: getString(R.string.splash_text)
        val fontName = prefs.getString(RemoteConfig.KEY_FONT_NAME, null)?.takeIf { it.isNotBlank() }
            ?: getString(R.string.splash_font)
        val showIcon = prefs.getBoolean(RemoteConfig.KEY_SPLASH_SHOW_ICON, resources.getBoolean(R.bool.splash_show_icon))
        val durationMs = prefs.getInt(RemoteConfig.KEY_SPLASH_DURATION_MS, resources.getInteger(R.integer.splash_duration_ms))
            .coerceIn(500, 15000)

        root.setBackgroundColor(bgColor)
        iconView.visibility = if (showIcon) View.VISIBLE else View.GONE
        textView.setTextColor(textColor)
        textView.text = text
        applyDynamicFont(textView, fontName)

        // Refresh the cached config in the background so the very next launch
        // (and, if this finishes in time, the transition to MainActivity below)
        // reflects any changes made on the website since the last app open.
        RemoteConfig.fetch(this)

        Handler(Looper.getMainLooper()).postDelayed({
            startActivity(Intent(this, MainActivity::class.java))
            finish()
        }, durationMs.toLong())
    }

    private fun applyDynamicFont(textView: TextView, fontName: String) {
        if (fontName.isBlank()) {
            return
        }

        val fontResId = resources.getIdentifier(fontName, "font", packageName)
        if (fontResId == 0) {
            return
        }

        runCatching {
            textView.typeface = ResourcesCompat.getFont(this, fontResId)
        }
    }
}
