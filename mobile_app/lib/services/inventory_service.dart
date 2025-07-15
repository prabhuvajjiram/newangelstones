import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import '../models/inventory_item.dart';

class InventoryService {
  static const _baseUrl = 'https://theangelstones.com';

  Future<List<InventoryItem>> fetchInventory({int page = 1, int pageSize = 100}) async {
    final uri = Uri.parse('$_baseUrl/inventory-proxy.php?page=$page&pageSize=$pageSize');
    try {
      final response = await http.get(uri);
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final items = (data['data'] ?? data['Data'] ?? []) as List<dynamic>;
        return items
            .whereType<Map<String, dynamic>>()
            .map((e) => InventoryItem.fromJson(e))
            .toList();
      } else {
        throw HttpException('Status code ${response.statusCode}');
      }
    } on SocketException catch (e) {
      debugPrint('SocketException while loading inventory: $e');
      throw Exception('Unable to load inventory');
    } on HttpException catch (e) {
      debugPrint('HttpException while loading inventory: $e');
      throw Exception('Unable to load inventory');
    } on FormatException catch (e) {
      debugPrint('FormatException while loading inventory: $e');
      throw Exception('Unable to load inventory');
    } catch (e) {
      debugPrint('Unknown error while loading inventory: $e');
      throw Exception('Unable to load inventory');
    }
  }
}
