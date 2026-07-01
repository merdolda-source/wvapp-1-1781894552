plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
}

fun env(name: String, fallback: String): String = System.getenv(name)?.takeIf { it.isNotBlank() } ?: fallback

android {
    namespace = "com.appforge.webview"
    compileSdk = 34

    defaultConfig {
        applicationId = env("APP_PACKAGE_ID", "com.appforge.webview")
        minSdk = 21
        targetSdk = 34
        versionCode = env("APP_VERSION_CODE", "1").toInt()
        versionName = env("APP_VERSION_NAME", "1.0.0")

        resValue("string", "app_name", env("APP_NAME", "WebView App"))
        resValue("string", "target_url", env("APP_TARGET_URL", "https://www.google.com"))
        resValue("string", "splash_text", env("APP_SPLASH_TEXT", ""))
        resValue("string", "splash_font", env("APP_FONT_NAME", ""))
        resValue("color", "header_color", env("APP_HEADER_COLOR", "#2563EB"))
        resValue("color", "splash_bg_color", env("APP_SPLASH_BG_COLOR", "#2563EB"))
        resValue("color", "splash_text_color", env("APP_SPLASH_TEXT_COLOR", "#FFFFFF"))
        // Base URL of the WebView App Builder site this app was generated from.
        // Used at runtime to fetch the latest target URL/colors/splash text/font
        // (see RemoteConfig.kt) so those can be changed without a new release.
        resValue("string", "config_base_url", env("APP_CONFIG_BASE_URL", ""))
    }

    signingConfigs {
        create("release") {
            storeFile = file("../keystore/release.jks")
            storeType = "JKS"
            storePassword = System.getenv("KEYSTORE_PASSWORD") ?: "android"
            keyAlias = System.getenv("KEY_ALIAS") ?: "release"
            keyPassword = System.getenv("KEY_PASSWORD") ?: "android"
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
            signingConfig = signingConfigs.getByName("release")
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions { jvmTarget = "17" }

    bundle {
        language { enableSplit = true }
        density { enableSplit = true }
        abi { enableSplit = true }
    }
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.appcompat)
    implementation(libs.material)
    implementation(libs.androidx.splashscreen)
    implementation(libs.androidx.webkit)
}
