import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/inventory_item.dart';

class InventoryService {
  static const _baseUrl = 'https://theangelstones.com';

  Future<List<InventoryItem>> fetchInventory({int page = 1, int pageSize = 100}) async {
    final uri = Uri.parse('$_baseUrl/inventory-proxy.php?page=$page&pageSize=$pageSize');
    final response = await http.get(uri);
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      final items = (data['data'] ?? data['Data'] ?? []) as List<dynamic>;
      return items
          .whereType<Map<String, dynamic>>()
          .map((e) => InventoryItem.fromJson(e))
          .toList();
    } else {
      throw Exception('Failed to load inventory');
    }
  }
}
