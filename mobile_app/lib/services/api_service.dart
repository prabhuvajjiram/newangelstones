import 'dart:convert';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter/services.dart' show rootBundle;
import 'package:flutter/foundation.dart';
import '../models/product.dart';

class ApiService {
  static const _baseUrl = 'https://theangelstones.com';

  Future<List<String>> fetchCategoryImages(String category) async {
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
            .map((e) => '$_baseUrl/' + (e['path'] ?? '').toString())
            .toList();
        debugPrint('✅ Successfully loaded ${imageUrls.length} category images');
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
        return products;
      } else {
        debugPrint('❌ Failed to load products: ${response.statusCode}');
        // Fall back to local data
        return loadLocalProducts('assets/colors.json');
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching products: $e');
      // Fall back to local data
      return loadLocalProducts('assets/colors.json');
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
