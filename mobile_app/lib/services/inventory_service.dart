import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:http/http.dart' show Client;
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart' show rootBundle;
import '../models/inventory_item.dart';

class InventoryService {
  static const _baseUrl = 'https://monument.business';
  static const _token = '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A';
  static const _referer = 'https://monument.business/GV/GVOBPInventory/ShowInventoryAll/$_token';
  
  // Sets to store unique filter values from API responses
  final Set<String> _availableTypes = {};
  final Set<String> _availableColors = {};

  // List of location IDs to fetch inventory from
  final List<String> _locationIds = ['45587', '45555'];
  
  // Map of location IDs to names
  final Map<String, String> _locationNames = {
    '45587': 'Main Warehouse',
    '45555': 'Secondary Location',
  };
  
  // Getter methods for available filter options
  List<String> get availableTypes => _availableTypes.toList()..sort();
  List<String> get availableColors => _availableColors.toList()..sort();

  // Load inventory data from local asset file
  Future<List<InventoryItem>> loadLocalInventory() async {
    try {
      debugPrint('📂 Loading inventory from local assets');
      final jsonString = await rootBundle.loadString('assets/inventory.json');
      final List<dynamic> jsonData = json.decode(jsonString);
      final items = jsonData
          .map((item) => InventoryItem.fromJson(item))
          .toList();
      debugPrint('✅ Successfully loaded ${items.length} items from local assets');
      return items;
    } catch (e) {
      debugPrint('⚠️ Error loading local inventory: $e');
      return [];
    }
  }

  Future<List<InventoryItem>> fetchInventory({
    int page = 1,
    int pageSize = 1000,
    String? searchQuery,
    String? type,
    String? color,
  }) async {
    // Convert search query to lowercase for case-insensitive search
    final String? normalizedSearchQuery = searchQuery?.toLowerCase().trim();
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
                final items = data.map((item) {
                  // Add location name to each item
                  final Map<String, dynamic> itemWithLocation = {...item as Map<String, dynamic>};
                  itemWithLocation['Location'] = _locationNames[locationId] ?? 'Unknown';
                  
                  // Extract type and color for filter options
                  if (itemWithLocation.containsKey('PColor') && itemWithLocation['PColor'] != null) {
                    final color = itemWithLocation['PColor'].toString().trim();
                    if (color.isNotEmpty) _availableColors.add(color);
                  }
                  
                  if (itemWithLocation.containsKey('PType') && itemWithLocation['PType'] != null) {
                    final type = itemWithLocation['PType'].toString().trim();
                    if (type.isNotEmpty) _availableTypes.add(type);
                  }
                  
                  return InventoryItem.fromJson(itemWithLocation);
                }).toList();
                allItems.addAll(items);
                debugPrint('✅ Successfully loaded ${items.length} items from direct array response for location ${_locationNames[locationId]}');
              } else if (data is Map && (data['Data'] is List || data['data'] is List)) {
                // Response with Data/data array
                final itemsData = (data['Data'] ?? data['data']) as List;
                final items = itemsData.map((item) {
                  // Add location name to each item
                  final Map<String, dynamic> itemWithLocation = {...item as Map<String, dynamic>};
                  itemWithLocation['Location'] = _locationNames[locationId] ?? 'Unknown';
                  
                  // Extract type and color for filter options
                  if (itemWithLocation.containsKey('PColor') && itemWithLocation['PColor'] != null) {
                    final color = itemWithLocation['PColor'].toString().trim();
                    if (color.isNotEmpty) _availableColors.add(color);
                  }
                  
                  if (itemWithLocation.containsKey('PType') && itemWithLocation['PType'] != null) {
                    final type = itemWithLocation['PType'].toString().trim();
                    if (type.isNotEmpty) _availableTypes.add(type);
                  }
                  
                  return InventoryItem.fromJson(itemWithLocation);
                }).toList();
                allItems.addAll(items);
                debugPrint('✅ Successfully loaded ${items.length} items from Data/data array for location ${_locationNames[locationId]}');
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
      
      // Apply client-side filtering for search query if needed
      List<InventoryItem> filteredItems = allItems;
      
      if (normalizedSearchQuery != null && normalizedSearchQuery.isNotEmpty) {
        debugPrint('🔍 Applying client-side search filter for: $normalizedSearchQuery');
        filteredItems = allItems.where((item) {
          // Check multiple fields for the search term
          return item.description.toLowerCase().contains(normalizedSearchQuery) ||
                 item.code.toLowerCase().contains(normalizedSearchQuery) ||
                 item.color.toLowerCase().contains(normalizedSearchQuery) ||
                 item.size.toLowerCase().contains(normalizedSearchQuery);
        }).toList();
        debugPrint('📊 Found ${filteredItems.length} items matching search query');
      }
      
      // If we have any items, return them
      if (filteredItems.isNotEmpty) {
        debugPrint('📊 Returning ${filteredItems.length} items from API');
        return filteredItems;
      } else {
        debugPrint('⚠️ Failed to load inventory from API, falling back to local data');
        return loadLocalInventory();
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
