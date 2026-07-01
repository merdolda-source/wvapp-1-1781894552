package com.appforge.webview

import android.content.Context
import android.graphics.Color
import android.os.Handler
import android.os.Looper
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

/**
 * Fetches this app's live configuration (target URL, colors, splash text,
 * font) from the WebView App Builder backend, so changes the site owner
 * makes on the website apply to already-installed apps without a new build.
 * Only the app name and launcher icon still require a rebuild, since those
 * are baked into the installed package itself.
 *
 * Results are cached in SharedPreferences so the last successfully fetched
 * configuration is used immediately on the next launch (and whenever the
 * network call fails), instead of falling back to the values baked in at
 * build time.
 */
object RemoteConfig {
    const val PREFS_NAME = "remote_config"
    const val KEY_TARGET_URL = "target_url"
    const val KEY_HEADER_COLOR = "header_color"
    const val KEY_SPLASH_BG = "splash_bg_color"
    const val KEY_SPLASH_TEXT_COLOR = "splash_text_color"
    const val KEY_SPLASH_TEXT = "splash_text"
    const val KEY_FONT_NAME = "font_name"

    fun fetch(context: Context, onDone: (() -> Unit)? = null) {
        val baseUrl = context.getString(R.string.config_base_url)
        if (baseUrl.isBlank()) {
            onDone?.invoke()
            return
        }

        val appContext = context.applicationContext

        Thread {
            runCatching {
                val url = URL("$baseUrl/api/config/${appContext.packageName}")
                val connection = url.openConnection() as HttpURLConnection
                connection.connectTimeout = 6000
                connection.readTimeout = 6000
                connection.requestMethod = "GET"

                if (connection.responseCode == 200) {
                    val body = connection.inputStream.bufferedReader().use { it.readText() }
                    val json = JSONObject(body)
                    val prefs = appContext.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
                    val editor = prefs.edit()

                    json.optString("target_url").takeIf { it.isNotBlank() }
                        ?.let { editor.putString(KEY_TARGET_URL, it) }
                    parseColorOrNull(json.optString("header_color"))
                        ?.let { editor.putInt(KEY_HEADER_COLOR, it) }
                    parseColorOrNull(json.optString("splash_bg_color"))
                        ?.let { editor.putInt(KEY_SPLASH_BG, it) }
                    parseColorOrNull(json.optString("splash_text_color"))
                        ?.let { editor.putInt(KEY_SPLASH_TEXT_COLOR, it) }
                    editor.putString(KEY_SPLASH_TEXT, json.optString("splash_text"))
                    editor.putString(KEY_FONT_NAME, json.optString("font_name"))
                    editor.apply()
                }
                connection.disconnect()
            }
            Handler(Looper.getMainLooper()).post { onDone?.invoke() }
        }.start()
    }

    private fun parseColorOrNull(value: String): Int? = runCatching { Color.parseColor(value) }.getOrNull()
}
