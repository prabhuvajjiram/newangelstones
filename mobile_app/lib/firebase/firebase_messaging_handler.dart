import 'package:firebase_messaging/firebase_messaging.dart';
import '../services/notification_service.dart';
import '../models/notification_payload.dart';

class FirebaseMessagingHandler {
  static Future<void> setup() async {
    FirebaseMessaging.onBackgroundMessage(_backgroundHandler);
    await NotificationService.instance.initialize();
  }

  static Future<void> _backgroundHandler(RemoteMessage message) async {
    final notification = message.notification;
    if (notification != null) {
      NotificationService.instance.displayNotification(
        NotificationPayload(
          title: notification.title ?? '',
          body: notification.body ?? '',
          data: message.data,
        ),
      );
    }
  }
}
