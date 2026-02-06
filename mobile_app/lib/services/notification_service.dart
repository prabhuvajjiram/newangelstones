import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../models/notification_payload.dart';
import '../config/notification_config.dart';
import '../models/notification_preferences.dart';

class NotificationService {
  NotificationService._();
  static final NotificationService instance = NotificationService._();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  final Map<String, DateTime> _lastSent = {};
  NotificationPreferences preferences = NotificationPreferences();

  Future<void> initialize() async {
    // Request notification permissions
    NotificationSettings settings = await _messaging.requestPermission(
      alert: true,
      announcement: false,
      badge: true,
      carPlay: false,
      criticalAlert: false,
      provisional: false,
      sound: true,
    );

    debugPrint('🔔 Notification permissions: ${settings.authorizationStatus}');

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      debugPrint('✅ User granted notification permission');
    } else if (settings.authorizationStatus == AuthorizationStatus.provisional) {
      debugPrint('⚠️ User granted provisional notification permission');
    } else {
      debugPrint('❌ User declined or has not accepted notification permission');
    }

    // Get and print FCM token for Firebase Console
    String? token = await _messaging.getToken();
    if (token != null) {
      debugPrint('🔑 FCM Token: $token');
      debugPrint('📱 Copy this token to Firebase Console for testing');
    }

    // No topic subscription needed - using User segment targeting in Firebase Console

    // Initialize local notifications
    const AndroidInitializationSettings androidSettings =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    const DarwinInitializationSettings iosSettings = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );
    const InitializationSettings initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );
    
    await _localNotifications.initialize(
      settings: initSettings,
      onDidReceiveNotificationResponse: (response) {
        final payload = response.payload;
        if (payload != null) {
          debugPrint('📱 Notification tapped with payload: $payload');
          // Handle deep linking or navigation here
        }
      },
    );

    // Handle foreground notifications
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      debugPrint('📧 Notification received in foreground: ${message.notification?.title}');
      final notification = message.notification;
      if (notification != null) {
        displayNotification(
          NotificationPayload(
            title: notification.title ?? '',
            body: notification.body ?? '',
            data: message.data,
          ),
        );
      }
    });

    // Handle notification taps when app is in background
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      debugPrint('🔔 Notification tapped: ${message.notification?.title}');
      // Handle navigation based on notification data
      _handleNotificationTap(message);
    });

    // Handle notification when app is opened from terminated state
    RemoteMessage? initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      debugPrint('🚀 App launched from notification: ${initialMessage.notification?.title}');
      _handleNotificationTap(initialMessage);
    }
  }

  void _handleNotificationTap(RemoteMessage message) {
    // Handle different notification types
    final data = message.data;
    final type = data['type'];
    
    switch (type) {
      case 'inventory_update':
        // Navigate to inventory screen
        debugPrint('🏪 Navigate to inventory screen');
        break;
      case 'promotion':
        // Navigate to promotions/specials
        debugPrint('🎉 Navigate to promotions screen');
        break;
      case 'order_status':
        // Navigate to orders
        debugPrint('📦 Navigate to orders screen');
        break;
      default:
        // Navigate to home or handle generic notification
        debugPrint('🏠 Navigate to home screen');
        break;
    }
  }

  Future<String?> getToken() async {
    return _messaging.getToken();
  }

  Future<void> displayNotification(NotificationPayload payload) async {
    final now = DateTime.now();
    final last = _lastSent[payload.title];
    if (last != null && now.difference(last) < NotificationConfig.throttleDuration) {
      return;
    }

    _lastSent[payload.title] = now;

    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'general_channel',
      'General Notifications',
      importance: Importance.defaultImportance,
      priority: Priority.defaultPriority,
    );
    const NotificationDetails details = NotificationDetails(android: androidDetails);
    await _localNotifications.show(
      id: 0,
      title: payload.title,
      body: payload.body,
      notificationDetails: details,
      payload: payload.deepLink,
    );
  }
}
