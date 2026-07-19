import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

/// Receives inventory searches handed to the app by Apple App Intents on iOS
/// and macOS.
class SiriInventoryService {
  SiriInventoryService._();

  static const MethodChannel _channel =
      MethodChannel('com.angelgranites.app/siri_inventory');

  static Future<String?> takePendingSearch() async {
    try {
      final query = await _channel.invokeMethod<String>('takePendingSearch');
      final trimmed = query?.trim();
      return trimmed == null || trimmed.isEmpty ? null : trimmed;
    } on MissingPluginException {
      // Expected on Android, web, Windows, Linux, and Apple test hosts that do
      // not include the native Runner bridge.
      return null;
    } on PlatformException catch (error) {
      debugPrint('Unable to receive Siri inventory search: $error');
      return null;
    }
  }
}
