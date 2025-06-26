import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/product.dart';

class ApiService {
  static const _baseUrl = 'https://theangelstones.com';

  Future<List<Product>> fetchProducts() async {
    final uri = Uri.parse('$_baseUrl/api/color.json');
    final response = await http.get(uri);
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data.map((e) => Product.fromJson(e)).toList();
    } else {
      throw Exception('Failed to load products');
    }
  }
}
