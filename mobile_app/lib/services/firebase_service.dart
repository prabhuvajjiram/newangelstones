import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_analytics/firebase_analytics.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import '../firebase_options.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:permission_handler/permission_handler.dart'; // optional but helps on Android 13+


/// Service class to handle Firebase initialization and provide access to Firebase services
class FirebaseService {
  static FirebaseService? _instance;
  late final FirebaseAnalytics _analytics;
  late final FirebaseMessaging _messaging;

  
  // Private constructor
  FirebaseService._();
  
  /// Get the singleton instance of FirebaseService
  static FirebaseService get instance {
    _instance ??= FirebaseService._();
    return _instance!;
  }
  
  /// Initialize Firebase services
  Future<void> initialize() async {
    try {
      // Initialize Firebase Core with platform-specific options
      await Firebase.initializeApp(
        options: DefaultFirebaseOptions.currentPlatform,
      );
      
      // Initialize FCM
      _messaging = FirebaseMessaging.instance;

      // iOS: Request permission to show notifications
      NotificationSettings settings = await _messaging.requestPermission(
        alert: true,
        announcement: false,
        badge: true,
        carPlay: false,
        criticalAlert: false,
        provisional: false,
        sound: true,
      );
      debugPrint('FCM Permission: ${settings.authorizationStatus}');

      // Android 13+: ask runtime permission (optional, use if you want control)
      if (defaultTargetPlatform == TargetPlatform.android) {
        if (await Permission.notification.isDenied) {
          await Permission.notification.request();
        }
      }

      try {
        final fcmToken = await _messaging.getToken();
        debugPrint('✅ FCM Token: $fcmToken');
      } catch (e) {
        debugPrint('❌ Failed to get FCM token: $e');
      }

      await _messaging.subscribeToTopic('all');
      debugPrint('📡 Subscribed to topic: all');


      FirebaseMessaging.onMessage.listen((RemoteMessage message) {
        debugPrint('📩 FCM Foreground Message: ${message.notification?.title}');
      });

      // Initialize Analytics
      _analytics = FirebaseAnalytics.instance;
      
      // Initialize Crashlytics (except in debug mode)
      if (!kDebugMode) {
        await FirebaseCrashlytics.instance.setCrashlyticsCollectionEnabled(true);
        
        // Pass all uncaught errors to Crashlytics
        FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterFatalError;
      } else {
        await FirebaseCrashlytics.instance.setCrashlyticsCollectionEnabled(false);
      }
      
      debugPrint('Firebase initialized successfully');
    } catch (e) {
      debugPrint('Failed to initialize Firebase: $e');
      // Handle initialization errors gracefully
    }
  }
  
  /// Get a NavigatorObserver for tracking screen changes automatically
  NavigatorObserver getAnalyticsObserver() {
    return FirebaseAnalyticsObserver(analytics: _analytics);
  }
  
  /// Log a custom analytics event
  Future<void> logEvent({required String name, Map<String, Object>? parameters}) async {
    await _analytics.logEvent(name: name, parameters: parameters);
  }
  
  /// Log when a screen is viewed
  Future<void> logScreenView({required String screenName}) async {
    await _analytics.logScreenView(screenName: screenName);
  }
  
  /// Record a non-fatal error to Crashlytics
  void recordError(dynamic exception, StackTrace? stack) {
    FirebaseCrashlytics.instance.recordError(exception, stack, fatal: false);
  }
  
  // ===== SPECIFIC APP SECTION TRACKING =====
  
  /// Track main page view and interactions
  Future<void> trackMainPageView() async {
    await logScreenView(screenName: 'main_page');
  }
  
  /// Track PDF viewer usage
  Future<void> trackPdfView({required String pdfName, required int pageCount}) async {
    await logEvent(
      name: 'pdf_view',
      parameters: {
        'pdf_name': pdfName,
        'page_count': pageCount,
      },
    );
  }
  
  /// Track featured products section
  Future<void> trackFeaturedProductView({required String productId, required String productName}) async {
    await logEvent(
      name: 'featured_product_view',
      parameters: {
        'product_id': productId,
        'product_name': productName,
      },
    );
  }
  
  /// Track colors section interactions
  Future<void> trackColorView({required String colorId, required String colorName}) async {
    await logEvent(
      name: 'color_view',
      parameters: {
        'color_id': colorId,
        'color_name': colorName,
      },
    );
  }
  
  /// Track inventory section interactions
  Future<void> trackInventoryView({required String category, required int itemCount}) async {
    await logEvent(
      name: 'inventory_view',
      parameters: {
        'category': category,
        'item_count': itemCount,
      },
    );
  }
  
  /// Track cart interactions
  Future<void> trackCartAction({
    required String action, // add, remove, checkout, clear
    String? productId,
    String? productName,
    double? price,
    int? quantity,
  }) async {
    final Map<String, Object> parameters = <String, Object>{
      'action': action,
    };
    
    if (productId != null) parameters['product_id'] = productId;
    if (productName != null) parameters['product_name'] = productName;
    if (price != null) parameters['price'] = price;
    if (quantity != null) parameters['quantity'] = quantity;
    
    await logEvent(name: 'cart_action', parameters: parameters);
  }
  
  /// Track contact section interactions
  Future<void> trackContactAction({required String method, required bool successful}) async {
    await logEvent(
      name: 'contact_action',
      parameters: <String, Object>{
        'method': method, // email, phone, form
        'successful': successful,
      },
    );
  }
  
  /// Track search actions
  Future<void> trackSearch({required String query, required int resultCount}) async {
    await logEvent(
      name: 'search',
      parameters: {
        'query': query,
        'result_count': resultCount,
      },
    );
  }
  
  /// Track app performance metrics
  Future<void> trackPerformanceMetric({required String metricName, required double valueMs}) async {
    await logEvent(
      name: 'performance_metric',
      parameters: {
        'metric_name': metricName,
        'value_ms': valueMs,
      },
    );
  }}
