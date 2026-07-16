import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import '../firebase_options.dart';
import '../services/firebase_service.dart' as app_firebase;
import '../services/notification_service.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
}

class FirebaseMessagingHandler {
  static Future<void> setup() async {
    // Startup is intentionally non-blocking, so messaging may be scheduled
    // while Firebase Core is still initializing. Await the shared guarded
    // initialization future before touching any Firebase-backed services.
    await app_firebase.FirebaseService.instance.initialize();
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
    await NotificationService.instance.initialize();
  }
}
