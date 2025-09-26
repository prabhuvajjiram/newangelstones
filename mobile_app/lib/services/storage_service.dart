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
  
  // Configure FlutterSecureStorage with iOS-specific options
  final _storage = const FlutterSecureStorage(
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
      synchronizable: false,
    ),
    aOptions: AndroidOptions(
      encryptedSharedPreferences: true,
    ),
  );
  
  /// Initialize the storage service with error handling and timeout
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      debugPrint('🔄 Starting StorageService initialization...');
      
      // Test FlutterSecureStorage access with longer timeout for iOS
      debugPrint('📝 Testing secure storage write...');
      await _storage.write(key: 'init_test', value: 'ok')
          .timeout(const Duration(seconds: 15), 
          onTimeout: () {
            debugPrint('⏰ FlutterSecureStorage write timeout after 15 seconds');
            throw TimeoutException('FlutterSecureStorage write timed out');
          });
      
      debugPrint('📖 Testing secure storage read...');
      // Verify we can read
      final testValue = await _storage.read(key: 'init_test')
          .timeout(const Duration(seconds: 10),
          onTimeout: () {
            debugPrint('⏰ FlutterSecureStorage read timeout after 10 seconds');
            throw TimeoutException('FlutterSecureStorage read timed out');
          });
      
      if (testValue != 'ok') {
        throw Exception('FlutterSecureStorage verification failed - got: $testValue');
      }
      
      // Clean up test key
      await _storage.delete(key: 'init_test');
      
      _isInitialized = true;
      debugPrint('✅ StorageService initialized successfully');
    } catch (e) {
      debugPrint('⚠️ StorageService initialization error: $e');
      // Continue without storage - app will work with reduced functionality
      _isInitialized = true;
      debugPrint('⚠️ Continuing without secure storage - using in-memory cache only');
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
      final List<dynamic> data = json.decode(jsonData) as List<dynamic>;
      return data.map((e) => Product.fromJson(e as Map<String, dynamic>)).toList();
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
