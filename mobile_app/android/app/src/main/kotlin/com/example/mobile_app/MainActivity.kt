package com.angelgranites.app

import android.os.Build
import android.os.Bundle
import android.content.pm.ApplicationInfo
import android.webkit.WebView
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsControllerCompat
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // Never expose WebView inspection in production builds.
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.KITKAT) {
            val isDebuggable = applicationInfo.flags and ApplicationInfo.FLAG_DEBUGGABLE != 0
            WebView.setWebContentsDebuggingEnabled(isDebuggable)
        }
        
        // Enable edge-to-edge display for all Android versions
        WindowCompat.setDecorFitsSystemWindows(window, false)
        
        // For Android 15+ (API 35), ensure proper edge-to-edge handling
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.VANILLA_ICE_CREAM) {
            // Enable edge-to-edge using the new Android 15 API
            window.isNavigationBarContrastEnforced = false
        }
        
        // Set system bar appearance
        val windowInsetsController = WindowCompat.getInsetsController(window, window.decorView)
        windowInsetsController.systemBarsBehavior = WindowInsetsControllerCompat.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE
    }
}
