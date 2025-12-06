import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:url_launcher/url_launcher.dart';
import '../config/app_config.dart';

class AppStoreUtils {
  /// Launch the app in Apple App Store
  static Future<void> openAppStore() async {
    final Uri appStoreUri = Uri.parse('https://apps.apple.com/us/app/angel-granites/id${AppConfig.appStoreId}');
    if (await canLaunchUrl(appStoreUri)) {
      await launchUrl(appStoreUri, mode: LaunchMode.externalApplication);
    }
  }

  /// Launch the app in Google Play Store
  static Future<void> openPlayStore() async {
    final Uri playStoreUri = Uri.parse('https://play.google.com/store/apps/details?id=${AppConfig.playStoreId}');
    if (await canLaunchUrl(playStoreUri)) {
      await launchUrl(playStoreUri, mode: LaunchMode.externalApplication);
    }
  }

  /// Open appropriate store based on platform
  static Future<void> openAppInStore() async {
    try {
      if (defaultTargetPlatform == TargetPlatform.iOS) {
        await openAppStore();
      } else {
        await openPlayStore();
      }
    } catch (e) {
      debugPrint('Error opening app store: $e');
      // Fallback: try to open both stores
      try {
        await openAppStore();
      } catch (e2) {
        await openPlayStore();
      }
    }
  }

  /// Show "Rate App" dialog
  static void showRateAppDialog(BuildContext context) {
    showDialog<void>(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Rate Our App'),
          content: const Text('If you enjoy using Angel Granites, please take a moment to rate us on the app store. Your feedback helps us improve!'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Maybe Later'),
            ),
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                openAppInStore();
              },
              child: const Text('Rate Now'),
            ),
          ],
        );
      },
    );
  }

  /// Share app with others
  static Future<void> shareApp() async {
    // When share_plus package is added, uncomment the lines below:
    // final String shareText = defaultTargetPlatform == TargetPlatform.iOS
    //     ? 'Check out Angel Granites app: https://apps.apple.com/us/app/angel-granites/id${AppConfig.appStoreId}'
    //     : 'Check out Angel Granites app: https://play.google.com/store/apps/details?id=${AppConfig.playStoreId}';
    // Share.share(shareText);
    
    // For now, open the store directly
    await openAppInStore();
  }
}

// Navigation service for context access
class NavigationService {
  static GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
}
