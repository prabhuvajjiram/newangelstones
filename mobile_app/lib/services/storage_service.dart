import 'dart:convert';
import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/product.dart';

class StorageService {
  static const _cacheKey = 'cached_products';
  static const _timestampKey = 'cached_products_timestamp';
  static const Duration ttl = Duration(hours: 24);
  bool _isInitialized = false;
  final _storage = const FlutterSecureStorage();
  
  /// Initialize the storage service with error handling and timeout
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      // Test FlutterSecureStorage access
      await _storage.write(key: 'init_test', value: 'ok')
          .timeout(const Duration(seconds: 2), 
          onTimeout: () => throw TimeoutException('FlutterSecureStorage initialization timed out'));
      
      // Verify we can read
      final testValue = await _storage.read(key: 'init_test');
      
      if (testValue != 'ok') {
        throw Exception('FlutterSecureStorage verification failed');
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
        // Load any existing products so we don't overwrite previously saved data
    final existingProducts = await loadProducts() ?? [];

    // Use a map to merge by product ID and avoid duplicates
    final Map<String, Product> mergedMap = {
      for (var product in existingProducts) product.id: product,
    };

    for (var product in products) {
      mergedMap[product.id] = product;
    }

    final jsonData = json.encode(mergedMap.values.map((p) => {
          'id': p.id,
          'name': p.name,
          'description': p.description,
          'image': p.imageUrl,
          'price': p.price,
        }).toList());
    await _storage.write(key: _cacheKey, value: jsonData);
    await _storage.write(
        key: _timestampKey,
        value: DateTime.now().millisecondsSinceEpoch.toString());
  }

  Future<List<Product>?> loadProducts() async {
    final tsString = await _storage.read(key: _timestampKey);
    if (tsString != null) {
      final ts = DateTime.fromMillisecondsSinceEpoch(int.parse(tsString));
      if (DateTime.now().difference(ts) > ttl) {
        await clearProducts();
        return null;
      }
    }

    final jsonData = await _storage.read(key: _cacheKey);
    if (jsonData != null) {
      final List<dynamic> data = json.decode(jsonData);
      return data.map((e) => Product.fromJson(e)).toList();
    }
    return null;
  }

  Future<void> clearProducts() async {
    await _storage.delete(key: _cacheKey);
    await _storage.delete(key: _timestampKey);
  }

  /// Remove cached data if it has expired
  Future<void> clearExpiredCache() async {
    final tsString = await _storage.read(key: _timestampKey);
    if (tsString != null) {
      final ts = DateTime.fromMillisecondsSinceEpoch(int.parse(tsString));
      if (DateTime.now().difference(ts) > ttl) {
        await clearProducts();
      }
    }
  }
}
