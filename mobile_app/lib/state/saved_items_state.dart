import 'package:flutter/material.dart';
import 'package:collection/collection.dart';

/// Saved items state management using [ChangeNotifier].
class SavedItemsState extends ChangeNotifier {
  /// Internal list of saved items.
  final List<Map<String, dynamic>> _savedItems = [];

  /// Immutable list of items currently saved for later.
  List<Map<String, dynamic>> get items => List.unmodifiable(_savedItems);

  /// Total count of saved items.
  int get count => _savedItems.length;

  /// Add an item to saved items.
  void addItem(Map<String, dynamic> item) {
    // Check if item already exists in saved items
    final index = _savedItems.indexWhere((e) => e['id'] == item['id']);
    
    if (index < 0) {
      // Only add if it doesn't exist already
      _savedItems.add({...item});
      notifyListeners();
    }
  }

  /// Remove an item from saved items.
  void removeItem(Map<String, dynamic> item) {
    _savedItems.removeWhere((e) => e['id'] == item['id']);
    notifyListeners();
  }

  /// Clear all saved items.
  void clearSavedItems() {
    _savedItems.clear();
    notifyListeners();
  }

  /// Check if an item exists in saved items.
  bool hasItem(String itemId) {
    return _savedItems.any((item) => item['id'] == itemId);
  }

  /// Get a specific saved item by ID.
  Map<String, dynamic>? getItem(String itemId) {
    return _savedItems.firstWhereOrNull((item) => item['id'] == itemId);
  }
}
