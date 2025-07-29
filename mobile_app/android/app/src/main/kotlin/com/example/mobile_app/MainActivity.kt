package com.angelgranites.app

import android.os.Bundle
import androidx.activity.enableEdgeToEdge
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        // Enable edge-to-edge display using the modern API. This replaces
        // deprecated calls such as window.setStatusBarColor and
        // window.setNavigationBarColor.
        enableEdgeToEdge()

        super.onCreate(savedInstanceState)
    }
}
