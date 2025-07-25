// File generated based on your Firebase configuration
import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

/// Default [FirebaseOptions] for use with your Firebase apps.
class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError(
        'DefaultFirebaseOptions have not been configured for web - '
        'you can create these using the Firebase console.',
      );
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      case TargetPlatform.macOS:
        throw UnsupportedError(
          'DefaultFirebaseOptions have not been configured for macOS - '
          'you can create these using the Firebase console.',
        );
      case TargetPlatform.windows:
        throw UnsupportedError(
          'DefaultFirebaseOptions have not been configured for Windows - '
          'you can create these using the Firebase console.',
        );
      case TargetPlatform.linux:
        throw UnsupportedError(
          'DefaultFirebaseOptions have not been configured for Linux - '
          'you can create these using the Firebase console.',
        );
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions are not supported for this platform.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyCmt7LZCHH1Y_ljJ8RMTz9QG3-hxKbuUYM',
    appId: '1:539498050706:android:6cd817c07835f5a012624b',
    messagingSenderId: '539498050706',
    projectId: 'angel-granites',
    storageBucket: 'angel-granites.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyCX_IrY9De3D_QzBlp0LqBeaO3ymk7CNFk',
    appId: '1:539498050706:ios:ad7565c92d3bbb5b12624b',
    messagingSenderId: '539498050706',
    projectId: 'angel-granites',
    storageBucket: 'angel-granites.firebasestorage.app',
    iosBundleId: 'com.angelgranites.com',
  );
}
