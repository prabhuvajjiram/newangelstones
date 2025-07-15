import 'dart:convert';
import 'package:http/http.dart' as http;
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
}
