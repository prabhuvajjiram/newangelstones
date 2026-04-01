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
  Completer<void>? _initCompleter;

  // Configure FlutterSecureStorage with platform-specific options
  final _storage = const FlutterSecureStorage(
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.unlocked,
      synchronizable: false,
    ),
    aOptions: AndroidOptions(
      resetOnError: true, // Auto-recover from encryption errors
    ),
  );
  
  /// Initialize the storage service with error handling and timeout.
  /// Concurrent callers wait for the first in-progress call to complete.
  Future<void> initialize() async {
    if (_isInitialized) return;
    if (_initCompleter != null) {
      return _initCompleter!.future;
    }
    _initCompleter = Completer<void>();

    try {
      // Test FlutterSecureStorage access with a single write+read.
      // 5 s is generous but won't hang indefinitely on first launch.
      await _storage.write(key: 'init_test', value: 'ok')
          .timeout(const Duration(seconds: 5),
          onTimeout: () {
            throw TimeoutException('FlutterSecureStorage write timed out');
          });

      final testValue = await _storage.read(key: 'init_test')
          .timeout(const Duration(seconds: 5),
          onTimeout: () {
            throw TimeoutException('FlutterSecureStorage read timed out');
          });

      if (testValue == null) {
        debugPrint('⚠️ FlutterSecureStorage read returned null (common on emulators)');
      } else if (testValue != 'ok') {
        throw Exception('FlutterSecureStorage verification failed: $testValue');
      }

      try {
        await _storage.delete(key: 'init_test');
      } catch (_) {}

      _isInitialized = true;
      debugPrint('✅ Secure storage initialized');
      _initCompleter!.complete();
    } catch (e) {
      debugPrint('❌ Secure storage init failed: $e — continuing in-memory only');
      _isInitialized = true;
      _initCompleter!.complete();
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
