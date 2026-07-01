package com.appforge.webview

import android.content.Intent
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.res.ResourcesCompat

class SplashActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_splash)

        applyDynamicFont()

        Handler(Looper.getMainLooper()).postDelayed({
            startActivity(Intent(this, MainActivity::class.java))
            finish()
        }, SPLASH_DURATION_MS)
    }

    private fun applyDynamicFont() {
        val fontName = getString(R.string.splash_font)
        if (fontName.isBlank()) {
            return
        }

        val fontResId = resources.getIdentifier(fontName, "font", packageName)
        if (fontResId == 0) {
            return
        }

        runCatching {
            val typeface = ResourcesCompat.getFont(this, fontResId)
            findViewById<TextView>(R.id.splashText).typeface = typeface
        }
    }

    companion object {
        private const val SPLASH_DURATION_MS = 1400L
    }
}
