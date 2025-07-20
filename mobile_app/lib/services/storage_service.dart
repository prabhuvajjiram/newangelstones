import 'dart:convert';
import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/product.dart';

class StorageService {
  static const _cacheKey = 'cached_products';
  bool _isInitialized = false;
  
  /// Initialize the storage service with error handling and timeout
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      // Test SharedPreferences access
      final prefs = await SharedPreferences.getInstance()
          .timeout(const Duration(seconds: 2), 
          onTimeout: () => throw TimeoutException('SharedPreferences initialization timed out'));
      
      // Verify we can read/write
      await prefs.setString('init_test', 'ok')
          .timeout(const Duration(seconds: 1));
      final testValue = prefs.getString('init_test');
      
      if (testValue != 'ok') {
        throw Exception('SharedPreferences verification failed');
      }
      
      _isInitialized = true;
      debugPrint('✅ StorageService initialized successfully');
    } catch (e) {
      debugPrint('⚠️ StorageService initialization error: $e');
      // Mark as initialized anyway to prevent repeated init attempts
      _isInitialized = true;
    }
  }

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
