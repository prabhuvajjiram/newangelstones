import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:http/http.dart' show Client;
import 'package:flutter/foundation.dart';
import '../models/inventory_item.dart';

class InventoryService {
  static const _baseUrl = 'https://monument.business';
  static const _token = '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A';
  static const _referer = 'https://monument.business/GV/GVOBPInventory/ShowInventoryAll/$_token';

  // List of location IDs to fetch inventory from
  final List<String> _locationIds = ['45587', '45555'];

  Future<List<InventoryItem>> fetchInventory({
    int page = 1,
    int pageSize = 1000,
    String? searchQuery,
    String? type,
    String? color,
  }) async {
    try {
      debugPrint('🔍 Fetching inventory from direct API endpoints');
      
      // Create a list to hold inventory items from all locations
      List<InventoryItem> allItems = [];
      
      // Fetch inventory from each location
      for (final locationId in _locationIds) {
        try {
          final uri = Uri.parse('$_baseUrl/GV/GVOBPInventory/GetAllStockdetailsSummaryforall');
          
          debugPrint('🌐 Fetching inventory for location $locationId');
          
          // Create form data with filters
          final Map<String, String> formData = {
            'sort': '',
            'page': page.toString(),
            'pageSize': pageSize.toString(),
            'group': '',
            'filter': searchQuery?.isNotEmpty == true ? searchQuery! : '',
            'token': _token,
            'hasdesc': 'true',
            'description': searchQuery ?? '',
            'ptype': type ?? '',
            'pcolor': color ?? '',
            'pdesign': '',
            'pfinish': '',
            'psize': '',
            'locid': locationId,
          };
          
          debugPrint('🔍 Applying filters - Search: $searchQuery, Type: $type, Color: $color');
          
          // Make the POST request
          final response = await http.post(
            uri,
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
              'X-Requested-With': 'XMLHttpRequest',
              'Cache-Control': 'no-cache',
              'Pragma': 'no-cache',
              'Referer': _referer,
              'User-Agent': 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',
            },
            body: formData,
          );
          
          debugPrint('📥 Response status for location $locationId: ${response.statusCode}');
          
          if (response.statusCode == 200) {
            // Log the raw response body for debugging
            final responseBody = utf8.decode(response.bodyBytes);
            debugPrint('📦 Raw response from location $locationId (first 500 chars): ${responseBody.length > 500 ? responseBody.substring(0, 500) + '...' : responseBody}');
            
            try {
              final dynamic data = json.decode(responseBody);
              
              // Log the parsed data structure
              debugPrint('🔍 Parsed response type: ${data.runtimeType}');
              if (data is Map) {
                debugPrint('📊 Response keys: ${data.keys.join(', ')}');
                if (data.containsKey('Data') || data.containsKey('data')) {
                  final itemsData = data['Data'] ?? data['data'];
                  if (itemsData is List) {
                    debugPrint('📊 Found ${itemsData.length} items in Data/data array');
                  }
                }
              }
              
              // Handle error response
              if (data is Map && data.containsKey('success') && data['success'] == false) {
                final error = data['error'] ?? 'Failed to load inventory';
                debugPrint('❌ API Error for location $locationId: $error');
                continue; // Skip this location and try the next one
              }
              
              // Process successful response - handle different response formats
              if (data is List) {
                // Direct array response
                final items = data.map((item) => InventoryItem.fromJson(item)).toList();
                allItems.addAll(items);
                debugPrint('✅ Successfully loaded ${items.length} items from direct array response');
              } else if (data is Map && (data['Data'] is List || data['data'] is List)) {
                // Response with Data/data array
                final itemsData = (data['Data'] ?? data['data']) as List;
                final items = itemsData.map((item) => InventoryItem.fromJson(item)).toList();
                allItems.addAll(items);
                debugPrint('✅ Successfully loaded ${items.length} items from Data/data array');
              } else {
                debugPrint('⚠️ Unexpected response format from location $locationId');
                debugPrint('Response type: ${data.runtimeType}');
                if (data is Map) {
                  debugPrint('Available keys: ${data.keys.join(', ')}');
                }
              }
            } catch (e) {
              debugPrint('⚠️ Error processing response from location $locationId: $e');
              continue; // Skip this location and try the next one
            }
          } else {
            debugPrint('❌ Failed to load inventory from location $locationId: ${response.statusCode}');
            debugPrint('Response body: ${response.body}');
          }
        } catch (e) {
          debugPrint('⚠️ Error fetching inventory from location $locationId: $e');
          // Continue to next location even if one fails
        }
      }
      
      if (allItems.isEmpty) {
        throw Exception('Failed to load inventory from any location');
      }
      
      debugPrint('🎉 Successfully loaded ${allItems.length} total items from all locations');
      return allItems;
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
