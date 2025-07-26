package com.angelgranites.app

import android.os.Bundle
import androidx.core.view.WindowCompat
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // Enable edge-to-edge display for Android 15+ compatibility
        // This replaces the deprecated window.setStatusBarColor, window.setNavigationBarColor, etc.
        WindowCompat.setDecorFitsSystemWindows(window, false)
    }
}
