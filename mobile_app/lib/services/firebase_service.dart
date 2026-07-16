import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';
import 'package:flutter/foundation.dart';
import '../firebase_options.dart';

/// Service class to handle Firebase initialization and provide access to Firebase services
class FirebaseService {
  static FirebaseService? _instance;
  bool _isInitialized = false;
  Future<void>? _initializationFuture;

  // Private constructor
  FirebaseService._();

  /// Get the singleton instance of FirebaseService
  static FirebaseService get instance {
    _instance ??= FirebaseService._();
    return _instance!;
  }

  /// Initialize Firebase services
  Future<void> initialize() {
    if (_isInitialized) return Future<void>.value();
    return _initializationFuture ??= _initialize();
  }

  Future<void> _initialize() async {
    try {
      // Initialize Firebase Core with platform-specific options
      if (Firebase.apps.isEmpty) {
        await Firebase.initializeApp(
          options: DefaultFirebaseOptions.currentPlatform,
        );
      }

      // Initialize Crashlytics (except in debug mode)
      if (!kDebugMode) {
        await FirebaseCrashlytics.instance
            .setCrashlyticsCollectionEnabled(true);
      } else {
        await FirebaseCrashlytics.instance
            .setCrashlyticsCollectionEnabled(false);
      }

      _isInitialized = true;
      debugPrint('Firebase core initialized successfully');
    } catch (e, stackTrace) {
      _isInitialized = false;
      _initializationFuture = null;
      debugPrint('Failed to initialize Firebase: $e');
      Error.throwWithStackTrace(e, stackTrace);
    }
  }

  /// Record a non-fatal error to Crashlytics
  void recordError(dynamic exception, StackTrace? stack) {
    if (!_isInitialized) return;
    FirebaseCrashlytics.instance.recordError(exception, stack, fatal: false);
  }
}
