import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/app_store_utils.dart';

/// Review Prompt Service
/// 
/// PRODUCTION MODE: Shows review prompt after 3 launches
/// Shows prompt 5 seconds after home screen loads, regardless of current screen
/// 
/// To reset review state for testing, call: ReviewPromptService.resetReviewState()
class ReviewPromptService {
  static const String _keyFirstLaunch = 'first_launch_date';
  static const String _keyLaunchCount = 'app_launch_count';
  static const String _keyLastPrompt = 'last_review_prompt';
  static const String _keyUserDeclined = 'user_declined_review';
  static const String _keyUserRated = 'user_already_rated';
  static const String _keySignificantEvents = 'significant_events_count';

  static final ReviewPromptService _instance = ReviewPromptService._internal();
  factory ReviewPromptService() => _instance;
  ReviewPromptService._internal();

  /// Track app launches
  static Future<void> trackAppLaunch() async {
    const storage = FlutterSecureStorage();
    
    // Set first launch date if not exists
    final firstLaunch = await storage.read(key: _keyFirstLaunch);
    if (firstLaunch == null) {
      await storage.write(key: _keyFirstLaunch, value: DateTime.now().toIso8601String());
    }
    
    // Increment launch count
    final launchCountStr = await storage.read(key: _keyLaunchCount);
    final launchCount = int.tryParse(launchCountStr ?? '0') ?? 0;
    final newCount = launchCount + 1;
    await storage.write(key: _keyLaunchCount, value: newCount.toString());
  }

  /// Track significant user events (quote requests, saved items, etc.)
  static Future<void> trackSignificantEvent() async {
    const storage = FlutterSecureStorage();
    final eventCountStr = await storage.read(key: _keySignificantEvents);
    final eventCount = int.tryParse(eventCountStr ?? '0') ?? 0;
    await storage.write(key: _keySignificantEvents, value: (eventCount + 1).toString());
  }

  /// Check if we should show review prompt
  static Future<bool> shouldShowReviewPrompt() async {
    const storage = FlutterSecureStorage();
    
    // Don't show if user already rated or permanently declined
    final userRatedStr = await storage.read(key: _keyUserRated);
    final userDeclinedStr = await storage.read(key: _keyUserDeclined);
    final userRated = userRatedStr == 'true';
    final userDeclined = userDeclinedStr == 'true';
    
    if (userRated || userDeclined) return false;
    
    // Get user activity data
    final firstLaunchStr = await storage.read(key: _keyFirstLaunch);
    final launchCountStr = await storage.read(key: _keyLaunchCount);
    final lastPromptStr = await storage.read(key: _keyLastPrompt);
    
    final launchCount = int.tryParse(launchCountStr ?? '0') ?? 0;
    
    if (firstLaunchStr == null) return false;
    
    // Check if enough time passed since last prompt (minimum 7 days)
    if (lastPromptStr != null) {
      final lastPrompt = DateTime.parse(lastPromptStr);
      final daysSinceLastPrompt = DateTime.now().difference(lastPrompt).inDays;
      if (daysSinceLastPrompt < 7) return false;
    }
    
    // Show prompt after user has launched app 3+ times
    return launchCount >= 3;
  }

  /// Show review prompt with smart timing
  static Future<void> showReviewPromptIfAppropriate(BuildContext context) async {
    if (await shouldShowReviewPrompt()) {
      if (context.mounted) {
        await _showSmartReviewDialog(context);
      }
    }
  }

  /// Show the actual review dialog
  static Future<void> _showSmartReviewDialog(BuildContext context) async {
    const storage = FlutterSecureStorage();
    
    // Record that we showed the prompt
    await storage.write(key: _keyLastPrompt, value: DateTime.now().toIso8601String());
    
    if (!context.mounted) return;
    
    showDialog<void>(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text(
            '⭐ Enjoying Angel Granites?',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w600,
            ),
          ),
          content: const Text(
            'Your feedback helps us improve and reach more customers who need quality stone solutions. Would you mind rating us?',
            style: TextStyle(fontSize: 16, height: 1.4),
          ),
          actions: [
            TextButton(
              onPressed: () async {
                Navigator.of(context).pop();
                // Don't ask again for 30 days
                await storage.write(key: _keyLastPrompt, 
                  value: DateTime.now().add(const Duration(days: 23)).toIso8601String());
              },
              child: const Text('Maybe Later'),
            ),
            TextButton(
              onPressed: () async {
                Navigator.of(context).pop();
                // Mark as permanently declined
                await storage.write(key: _keyUserDeclined, value: 'true');
              },
              child: const Text('No Thanks'),
            ),
            ElevatedButton(
              onPressed: () async {
                Navigator.of(context).pop();
                // Mark as rated
                await storage.write(key: _keyUserRated, value: 'true');
                // Open app store with error handling
                try {
                  await AppStoreUtils.openAppInStore();
                } catch (e) {
                  debugPrint('Error opening app store: $e');
                  // Show a fallback message
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Please search for "Angel Granites" in your app store'),
                        duration: Duration(seconds: 3),
                      ),
                    );
                  }
                }
              },
              child: const Text('Rate Now ⭐'),
            ),
          ],
        );
      },
    );
  }

  /// Manual trigger for testing or specific events
  static Future<void> showReviewDialog(BuildContext context) async {
    await _showSmartReviewDialog(context);
  }

  /// Reset review prompt state (for testing)
  static Future<void> resetReviewState() async {
    const storage = FlutterSecureStorage();
    await storage.delete(key: _keyFirstLaunch);
    await storage.delete(key: _keyLaunchCount);
    await storage.delete(key: _keyLastPrompt);
    await storage.delete(key: _keyUserDeclined);
    await storage.delete(key: _keyUserRated);
    await storage.delete(key: _keySignificantEvents);
  }

  /// Get review statistics (for debugging)
  static Future<Map<String, dynamic>> getReviewStats() async {
    const storage = FlutterSecureStorage();
    
    final launchCountStr = await storage.read(key: _keyLaunchCount);
    final significantEventsStr = await storage.read(key: _keySignificantEvents);
    final userDeclinedStr = await storage.read(key: _keyUserDeclined);
    final userRatedStr = await storage.read(key: _keyUserRated);
    
    return {
      'firstLaunch': await storage.read(key: _keyFirstLaunch),
      'launchCount': int.tryParse(launchCountStr ?? '0') ?? 0,
      'significantEvents': int.tryParse(significantEventsStr ?? '0') ?? 0,
      'lastPrompt': await storage.read(key: _keyLastPrompt),
      'userDeclined': userDeclinedStr == 'true',
      'userRated': userRatedStr == 'true',
      'shouldShow': await shouldShowReviewPrompt(),
    };
  }
}
