import 'dart:convert';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter/services.dart' show rootBundle;
import 'package:flutter/foundation.dart';
import '../models/product.dart';

class ApiService {
  bool _isInitialized = false;
  final Map<String, List<String>> _categoryCache = {};
  List<Product>? _productCache;
  
  /// Initialize the API service with error handling and timeout
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      // Preload essential assets
      await Future.wait([
        _preloadAsset('assets/featured_products.json'),
        _preloadAsset('assets/colors.json'),
      ]).timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ Asset preloading timed out');
          return [];
        },
      );
      
      _isInitialized = true;
      debugPrint('✅ ApiService initialized successfully');
    } catch (e) {
      debugPrint('⚠️ ApiService initialization error: $e');
      // Mark as initialized anyway to prevent repeated init attempts
      _isInitialized = true;
    }
  }
  
  /// Preload an asset to ensure it's available
  Future<void> _preloadAsset(String assetPath) async {
    try {
      await rootBundle.loadString(assetPath);
      debugPrint('✅ Successfully preloaded $assetPath');
    } catch (e) {
      debugPrint('⚠️ Failed to preload $assetPath: $e');
      // Don't rethrow - we want to continue even if one asset fails
    }
  }
  static const _baseUrl = 'https://theangelstones.com';

  Future<List<String>> fetchCategoryImages(String category) async {
    if (_categoryCache.containsKey(category)) {
      return _categoryCache[category]!;
    }
    try {
      final uri = Uri.parse('$_baseUrl/get_directory_files.php?directory=products/$category');
      debugPrint('🌐 Fetching category images from: $uri');
      
      final response = await http.get(uri).timeout(
        const Duration(seconds: 10),
        onTimeout: () => throw TimeoutException('Connection timed out'),
      );
      
      if (response.statusCode == 200) {
        final Map<String, dynamic> jsonData = json.decode(response.body);
        final List<dynamic> files = jsonData['files'] ?? [];
        final imageUrls = files
            .whereType<Map<String, dynamic>>()
            .map((e) => '$_baseUrl/${e['path'] ?? ''}')
            .toList();
        debugPrint('✅ Successfully loaded ${imageUrls.length} category images');
        _categoryCache[category] = imageUrls;
        return imageUrls;
      } else {
        debugPrint('❌ Failed to load category images: ${response.statusCode}');
        throw Exception('Failed to load category images: ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching category images: $e');
      // Return empty list instead of throwing to prevent app crashes
      return [];
    }
  }

  Future<List<Product>> fetchProducts() async {
    if (_productCache != null) return _productCache!;
    try {
      final uri = Uri.parse('$_baseUrl/api/color.json');
      debugPrint('🌐 Fetching products from: $uri');
      
      final response = await http.get(uri).timeout(
        const Duration(seconds: 10),
        onTimeout: () => throw TimeoutException('Connection timed out'),
      );
      
      if (response.statusCode == 200) {
        final Map<String, dynamic> jsonData = json.decode(response.body);
        final List<dynamic> items = jsonData['itemListElement'] ?? [];
        final products = items
            .whereType<Map<String, dynamic>>()
            .map((e) => Product.fromJson(e['item'] as Map<String, dynamic>))
            .toList();
        debugPrint('✅ Successfully loaded ${products.length} products');
        _productCache = products;
        return products;
      } else {
        debugPrint('❌ Failed to load products: ${response.statusCode}');
        // Fall back to local data
        _productCache = await loadLocalProducts('assets/colors.json');
        return _productCache!;
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching products: $e');
      // Fall back to local data
      _productCache = await loadLocalProducts('assets/colors.json');
      return _productCache!;
    }
  }

  Future<List<Product>> loadLocalProducts(String assetPath) async {
    final data = await rootBundle.loadString(assetPath);
    final dynamic decoded = json.decode(data);
    List<dynamic> items;
    if (decoded is Map<String, dynamic> && decoded['itemListElement'] != null) {
      items = (decoded['itemListElement'] as List)
          .map((e) => e is Map<String, dynamic> ? e['item'] ?? e : e)
          .toList();
    } else if (decoded is List) {
      items = decoded;
    } else {
      items = [];
    }
    return items
        .whereType<Map<String, dynamic>>()
        .map((e) => Product.fromJson(e))
        .toList();
  }
}
