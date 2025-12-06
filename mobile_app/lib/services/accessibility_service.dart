import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter/semantics.dart';

class AccessibilityService {
  static const AccessibilityService _instance = AccessibilityService._internal();
  factory AccessibilityService() => _instance;
  const AccessibilityService._internal();

  /// Provides haptic feedback for better accessibility
  static void provideFeedback() {
    HapticFeedback.selectionClick();
  }

  /// Announces text to screen readers
  static void announce(BuildContext context, String message) {
    // Use the non-deprecated sendAnnouncement API introduced in Flutter 3.35+
    // Get the FlutterView for the current context
    final view = View.of(context);
    SemanticsService.sendAnnouncement(
      view,
      message,
      TextDirection.ltr,
      assertiveness: Assertiveness.polite,
    );
  }

  /// Creates semantic labels for images
  static String getImageSemanticLabel(String? productName, String? category) {
    if (productName != null && category != null) {
      return '$productName in $category category';
    } else if (productName != null) {
      return 'Product image for $productName';
    } else if (category != null) {
      return 'Product image from $category';
    }
    return 'Product image';
  }

  /// Creates semantic labels for buttons
  static String getButtonSemanticLabel(String action, String? target) {
    if (target != null) {
      return '$action $target';
    }
    return action;
  }

  /// Creates semantic labels for navigation items
  static String getNavigationSemanticLabel(String destination, {bool isSelected = false}) {
    final status = isSelected ? 'selected' : 'not selected';
    return '$destination tab, $status';
  }

  /// Creates semantic labels for inventory items
  static String getInventorySemanticLabel(String type, String color, String location, int quantity) {
    return '$type in $color color, located at $location, quantity $quantity';
  }

  /// Creates semantic labels for search results
  static String getSearchResultSemanticLabel(String title, String type, {int? count}) {
    if (count != null) {
      return '$title, $type, $count items';
    }
    return '$title, $type';
  }
}
