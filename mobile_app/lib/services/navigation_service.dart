import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

/// A service that provides app-wide navigation capabilities without requiring a BuildContext.
/// This is especially useful for navigating from places where context might be unavailable,
/// such as in snackbar actions or async callbacks.
class NavigationService {
  /// Singleton instance
  static final NavigationService _instance = NavigationService._internal();
  
  /// Factory constructor to return the singleton instance
  factory NavigationService() => _instance;
  
  /// Internal constructor
  NavigationService._internal();
  
  /// Global router configuration reference
  GoRouter? _router;
  
  /// Initialize the service with the app's router
  void initialize(GoRouter router) {
    _router = router;
  }
  
  /// Navigate to a named route
  void navigateToNamed(String routeName, {Object? extra}) {
    if (_router != null) {
      _router!.pushNamed(routeName, extra: extra);
    } else {
      debugPrint('⚠️ NavigationService: Router not initialized');
    }
  }
  
  /// Navigate to a path
  void navigateTo(String path, {Object? extra}) {
    if (_router != null) {
      _router!.push(path, extra: extra);
    } else {
      debugPrint('⚠️ NavigationService: Router not initialized');
    }
  }
}
