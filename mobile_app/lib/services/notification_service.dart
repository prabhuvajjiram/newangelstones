import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

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
    const AndroidInitializationSettings androidSettings =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    const DarwinInitializationSettings iosSettings = DarwinInitializationSettings();
    const InitializationSettings initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );
    await _localNotifications.initialize(initSettings,
        onDidReceiveNotificationResponse: (response) {
      final payload = response.payload;
      if (payload != null) {
        // handle deep link if needed
      }
    });

    await _messaging.requestPermission();
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
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
    await _localNotifications.show(0, payload.title, payload.body, details,
        payload: payload.deepLink);
  }
}
