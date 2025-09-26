import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Service to handle system UI configuration for Android 15+ edge-to-edge compatibility
class SystemUIService {
  static SystemUIService? _instance;
  static SystemUIService get instance => _instance ??= SystemUIService._();
  
  SystemUIService._();

  /// Configure system UI for edge-to-edge display with Android 15+ compatibility
  void configureEdgeToEdge({
    Color statusBarColor = Colors.transparent,
    Brightness statusBarIconBrightness = Brightness.light,
  }) {
    // Use SystemUiOverlayStyle instead of deprecated Window APIs
    SystemChrome.setSystemUIOverlayStyle(
      SystemUiOverlayStyle(
        // System bar colors - handled programmatically for Android 15+ compatibility
        statusBarColor: statusBarColor,
        
        // Icon brightness
        statusBarIconBrightness: statusBarIconBrightness,
        
        // System bar behavior - use available properties only
        systemNavigationBarDividerColor: Colors.transparent,
      ),
    );
    
    // Enable edge-to-edge mode
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
  }

  /// Configure system UI for immersive mode (full-screen gallery)
  void configureImmersiveMode({
    Brightness statusBarIconBrightness = Brightness.light,
  }) {
    SystemChrome.setSystemUIOverlayStyle(
      SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: statusBarIconBrightness,
        systemNavigationBarDividerColor: Colors.transparent,
      ),
    );
    
    // Enable edge-to-edge for immersive experience
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
  }

  /// Configure system UI for normal app mode with proper insets
  void configureNormalMode({
    Color backgroundColor = const Color(0xFF121212),
    Brightness statusBarIconBrightness = Brightness.light,
  }) {
    SystemChrome.setSystemUIOverlayStyle(
      SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: statusBarIconBrightness,
        systemNavigationBarDividerColor: Colors.transparent,
      ),
    );
    
    // Enable edge-to-edge but with proper SafeArea handling
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
  }

  /// Reset system UI to default state
  void resetToDefault() {
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarDividerColor: Colors.transparent,
      ),
    );
    
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
  }

  /// Configure system UI for specific screen contexts
  void configureForScreen(String screenName) {
    switch (screenName) {
      case 'gallery':
        configureImmersiveMode();
        break;
      case 'home':
      case 'inventory':
      case 'directory':
      case 'saved':
        configureNormalMode();
        break;
      default:
        configureNormalMode();
    }
  }
}
