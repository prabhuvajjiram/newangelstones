import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/notification_payload.dart';
import '../config/notification_config.dart';
import '../models/notification_preferences.dart';
import 'navigation_service.dart';

class NotificationService {
  NotificationService._();
  static final NotificationService instance = NotificationService._();

  static bool get isSupportedPlatform {
    if (kIsWeb) return false;
    return defaultTargetPlatform == TargetPlatform.android ||
        defaultTargetPlatform == TargetPlatform.iOS ||
        defaultTargetPlatform == TargetPlatform.macOS;
  }

  FirebaseMessaging get _messaging => FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();
  static const FlutterSecureStorage _storage = FlutterSecureStorage();
  static const String _permissionPromptSeenKey =
      'notification_permission_prompt_seen';

  final Map<String, DateTime> _lastSent = {};
  NotificationPreferences preferences = NotificationPreferences();
  bool _initialized = false;
  bool _permissionPromptSeenInMemory = false;

  Future<void> initialize() async {
    if (!isSupportedPlatform) return;
    if (_initialized) return;

    // Initialize local notifications
    const AndroidInitializationSettings androidSettings =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    const DarwinInitializationSettings appleSettings =
        DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );
    const InitializationSettings initSettings = InitializationSettings(
      android: androidSettings,
      iOS: appleSettings,
      macOS: appleSettings,
    );

    await _localNotifications.initialize(
      settings: initSettings,
      onDidReceiveNotificationResponse: (response) {
        _navigateFromNotification(payload: response.payload);
      },
    );

    const channel = AndroidNotificationChannel(
      'default_channel',
      'Angel Granites Updates',
      description: 'Inventory, product, quote, and order updates.',
      importance: Importance.defaultImportance,
    );
    await _localNotifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(channel);

    // Handle foreground notifications
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      debugPrint(
        '📧 Notification received in foreground: ${message.notification?.title}',
      );
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
    RemoteMessage? initialMessage =
        await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      debugPrint(
        '🚀 App launched from notification: ${initialMessage.notification?.title}',
      );
      _handleNotificationTap(initialMessage);
    }

    _initialized = true;
  }

  Future<bool> shouldShowPermissionPrompt() async {
    if (!isSupportedPlatform) return false;
    final settings = await _messaging.getNotificationSettings();
    if (settings.authorizationStatus != AuthorizationStatus.notDetermined) {
      return false;
    }
    if (_permissionPromptSeenInMemory) return false;

    try {
      return await _storage.read(key: _permissionPromptSeenKey) != 'true';
    } catch (error) {
      // Unsigned simulator builds can lack the Keychain entitlement. Keep the
      // rationale flow usable there and rely on the in-memory guard.
      debugPrint('Notification prompt state unavailable: $error');
      return true;
    }
  }

  Future<void> markPermissionPromptSeen() async {
    _permissionPromptSeenInMemory = true;
    try {
      await _storage.write(key: _permissionPromptSeenKey, value: 'true');
    } catch (error) {
      debugPrint('Notification prompt state could not be saved: $error');
    }
  }

  Future<bool> requestPermission() async {
    if (!isSupportedPlatform) return false;
    await initialize();
    await markPermissionPromptSeen();

    final settings = await _messaging.requestPermission(
      alert: true,
      announcement: false,
      badge: true,
      carPlay: false,
      criticalAlert: false,
      provisional: false,
      sound: true,
    );
    final granted =
        settings.authorizationStatus == AuthorizationStatus.authorized ||
            settings.authorizationStatus == AuthorizationStatus.provisional;

    debugPrint('🔔 Notification permissions: ${settings.authorizationStatus}');
    if (granted && kDebugMode) {
      final token = await _messaging.getToken();
      debugPrint('🔑 FCM token available: ${token != null}');
    }
    return granted;
  }

  void _handleNotificationTap(RemoteMessage message) {
    final data = message.data;
    final type = data['type'];
    final explicitDeepLink = data['deepLink'] ?? data['deep_link'];

    if (_navigateFromNotification(payload: explicitDeepLink?.toString())) {
      return;
    }

    switch (type) {
      case 'inventory_update':
        final query = data['query'] ?? data['code'] ?? data['design'];
        _navigateFromNotification(
          payload: Uri(
            path: '/inventory',
            queryParameters: query == null ? null : {'query': query.toString()},
          ).toString(),
        );
        break;
      case 'promotion':
        NavigationService().navigateTo('/');
        break;
      case 'order_status':
        NavigationService().navigateTo('/?tab=3');
        break;
      default:
        NavigationService().navigateTo('/');
        break;
    }
  }

  bool _navigateFromNotification({String? payload}) {
    final path = _safeInternalPath(payload);
    if (path == null) return false;
    NavigationService().navigateTo(path);
    return true;
  }

  String? _safeInternalPath(String? value) {
    if (value == null || value.trim().isEmpty) return null;
    final uri = Uri.tryParse(value.trim());
    if (uri == null) return null;
    if (uri.hasScheme &&
        (uri.scheme != 'https' || uri.host != 'theangelstones.com')) {
      return null;
    }

    var path = uri.path;
    if (path == '/app') {
      path = '/';
    } else if (path.startsWith('/app/')) {
      path = path.substring(4);
    }

    const safePaths = {
      '/',
      '/inventory',
      '/colors',
      '/contact',
      '/search',
      '/cart',
      '/saved-items',
    };
    if (!safePaths.contains(path)) return null;

    final query = uri.hasQuery ? '?${uri.query}' : '';
    return '$path$query';
  }

  Future<String?> getToken() async {
    if (!isSupportedPlatform) return null;
    return _messaging.getToken();
  }

  Future<void> displayNotification(NotificationPayload payload) async {
    if (!isSupportedPlatform) return;
    final now = DateTime.now();
    final last = _lastSent[payload.title];
    if (last != null &&
        now.difference(last) < NotificationConfig.throttleDuration) {
      return;
    }

    _lastSent[payload.title] = now;

    const AndroidNotificationDetails androidDetails =
        AndroidNotificationDetails(
      'default_channel',
      'Angel Granites Updates',
      channelDescription: 'Inventory, product, quote, and order updates.',
      importance: Importance.defaultImportance,
      priority: Priority.defaultPriority,
    );
    const DarwinNotificationDetails appleDetails = DarwinNotificationDetails();
    const NotificationDetails details = NotificationDetails(
      android: androidDetails,
      iOS: appleDetails,
      macOS: appleDetails,
    );
    await _localNotifications.show(
      id: payload.title.hashCode & 0x7fffffff,
      title: payload.title,
      body: payload.body,
      notificationDetails: details,
      payload: payload.deepLink,
    );
  }
}
