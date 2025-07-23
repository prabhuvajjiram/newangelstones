import 'package:flutter/material.dart';
import 'package:collection/collection.dart';

/// Cart state management using [ChangeNotifier].
class CartState extends ChangeNotifier {
  /// Internal list of cart items.
  final List<Map<String, dynamic>> _items = [];

  /// Immutable list of items currently in the cart.
  List<Map<String, dynamic>> get items => List.unmodifiable(_items);

  /// Total quantity of all items.
  int get totalQuantity => 
      _items.fold(0, (sum, item) => sum + (item['quantity'] as int));

  /// Total price of all items.
  double get totalPrice => _items.fold(0.0, (sum, item) {
        final price = (item['price'] as num?)?.toDouble() ?? 0.0;
        final quantity = (item['quantity'] as int?) ?? 1;
        return sum + (price * quantity);
      });
      
  /// Total count of items in the cart (sum of quantities).
  int get count => _items.fold(0, (sum, item) => sum + (item['quantity'] as int? ?? 1));

  /// Add an item to the cart or increment quantity if it already exists.
  void addItem(Map<String, dynamic> item) {
    final index = _items.indexWhere((e) => e['id'] == item['id']);
    
    if (index >= 0) {
      // Item exists, update quantity
      _items[index]['quantity'] = (_items[index]['quantity'] as int) + 1;
    } else {
      // Add new item with quantity 1
      _items.add({...item, 'quantity': 1});
    }
    
    notifyListeners();
  }

  /// Remove an item from the cart.
  void removeItem(Map<String, dynamic> item) {
    _items.removeWhere((e) => e['id'] == item['id']);
    notifyListeners();
  }

  /// Update the quantity of a specific item in the cart.
  void updateQuantity(Map<String, dynamic> item, int newQuantity) {
    final index = _items.indexWhere((e) => e['id'] == item['id']);
    
    if (index >= 0) {
      if (newQuantity > 0) {
        _items[index]['quantity'] = newQuantity;
      } else {
        _items.removeAt(index);
      }
      notifyListeners();
    }
  }

  /// Clear all items from the cart.
  void clearCart() {
    _items.clear();
    notifyListeners();
  }

  /// Check if an item exists in the cart.
  bool hasItem(String itemId) {
    return _items.any((item) => item['id'] == itemId);
  }

  /// Get the quantity of a specific item in the cart.
  int getItemQuantity(String itemId) {
    final item = _items.firstWhereOrNull((item) => item['id'] == itemId);
    return item?['quantity'] as int? ?? 0;
  }

  /// Get a list of all unique product categories in the cart.
  List<String> getCategories() {
    final categories = <String>{};
    
    for (final item in _items) {
      if (item['category'] != null) {
        categories.add(item['category'] as String);
      }
    }
    
    return categories.toList();
  }

  /// Get items grouped by category.
  Map<String, List<Map<String, dynamic>>> getItemsByCategory() {
    final result = <String, List<Map<String, dynamic>>>{};
    
    for (final item in _items) {
      final category = item['category'] as String? ?? 'Uncategorized';
      if (!result.containsKey(category)) {
        result[category] = [];
      }
      result[category]!.add(item);
    }
    
    return result;
  }
}
