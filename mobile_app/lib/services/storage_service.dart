import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/product.dart';

class StorageService {
  static const _cacheKey = 'cached_products';

  Future<void> saveProducts(List<Product> products) async {
    final prefs = await SharedPreferences.getInstance();
    final jsonData = json.encode(products.map((p) => {
          'id': p.id,
          'name': p.name,
          'description': p.description,
          'image': p.imageUrl,
          'price': p.price,
        }).toList());
    await prefs.setString(_cacheKey, jsonData);
  }

  Future<List<Product>?> loadProducts() async {
    final prefs = await SharedPreferences.getInstance();
    final jsonData = prefs.getString(_cacheKey);
    if (jsonData != null) {
      final List<dynamic> data = json.decode(jsonData);
      return data.map((e) => Product.fromJson(e)).toList();
    }
    return null;
  }
}
