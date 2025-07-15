import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/services.dart' show rootBundle;
import '../models/product.dart';

class ApiService {
  static const _baseUrl = 'https://theangelstones.com';

  Future<List<Product>> fetchProducts() async {
    final uri = Uri.parse('$_baseUrl/api/color.json');
    final response = await http.get(uri);
    if (response.statusCode == 200) {
      final Map<String, dynamic> jsonData = json.decode(response.body);
      final List<dynamic> items = jsonData['itemListElement'] ?? [];
      return items
          .whereType<Map<String, dynamic>>()
          .map((e) => Product.fromJson(e['item'] as Map<String, dynamic>))
          .toList();
    } else {
      throw Exception('Failed to load products');
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
