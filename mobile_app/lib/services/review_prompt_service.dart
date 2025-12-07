import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:in_app_review/in_app_review.dart';

/// Review Prompt Service
/// 
/// PRODUCTION MODE: Shows native review prompt after 3 launches
/// Uses in_app_review package for native iOS/Android store review dialog
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
  
  static final InAppReview _inAppReview = InAppReview.instance;

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
        await _showNativeReviewDialog();
      }
    }
  }

  /// Show the native in-app review dialog
  static Future<void> _showNativeReviewDialog() async {
    const storage = FlutterSecureStorage();
    
    try {
      // Check if in-app review is available on this device
      if (await _inAppReview.isAvailable()) {
        // Record that we showed the prompt
        await storage.write(key: _keyLastPrompt, value: DateTime.now().toIso8601String());
        
        // Request the native review dialog
        await _inAppReview.requestReview();
        
        // Mark as rated (user saw the prompt)
        await storage.write(key: _keyUserRated, value: 'true');
        
        debugPrint('✅ Native review dialog shown successfully');
      } else {
        debugPrint('⚠️ In-app review not available on this device');
        // Fall back to opening the store
        await _inAppReview.openStoreListing();
      }
    } catch (e) {
      debugPrint('❌ Error showing review dialog: $e');
      // Don't show error to user, just log it
    }
  }

  /// Manual trigger for testing or specific events
  static Future<void> showReviewDialog(BuildContext context) async {
    await _showNativeReviewDialog();
  }
  
  /// Open app store directly (for manual "Rate App" buttons)
  static Future<void> openAppStore() async {
    try {
      await _inAppReview.openStoreListing();
    } catch (e) {
      debugPrint('❌ Error opening app store: $e');
    }
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
