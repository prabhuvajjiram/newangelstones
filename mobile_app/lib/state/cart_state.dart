import 'package:flutter/material.dart';
import '../models/cart_item.dart';
import '../models/product.dart';

/// Simple cart model using [ChangeNotifier].
class CartState extends ChangeNotifier {
  /// Internal list of cart items.
  final List<CartItem> _items = [];

  /// Immutable list of items currently in the cart.
  List<CartItem> get items => List.unmodifiable(_items);

  /// Total quantity of all items.
  int get count => _items.fold(0, (sum, item) => sum + item.quantity);

  /// Add a product to the cart or increment quantity.
  void addProduct(Product product) {
    final index = _items.indexWhere((e) => e.product.id == product.id);
    if (index >= 0) {
      _items[index].quantity++;
    } else {
      _items.add(CartItem(product: product));
    }
    notifyListeners();
  }

  /// Remove a product entirely from the cart.
  void removeProduct(Product product) {
    _items.removeWhere((e) => e.product.id == product.id);
    notifyListeners();
  }

  /// Remove all products from the cart.
  void clear() {
    _items.clear();
    notifyListeners();
  }
}
