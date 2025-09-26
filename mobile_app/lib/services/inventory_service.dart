import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart' show rootBundle;
import '../models/inventory_item.dart';
import '../utils/cache_entry.dart';
import '../config/security_config.dart';

class InventoryService {
  static String? _cachedToken;
  static String? _cachedReferer;
  static String? _cachedBaseUrl;

  static Future<String> _getBaseUrl() async {
    _cachedBaseUrl ??= SecurityConfig.monumentBusinessBaseUrl;
    return _cachedBaseUrl!;
  }

  static Future<String> _getToken() async {
    _cachedToken ??= await SecurityConfig.getMonumentBusinessToken();
    return _cachedToken!;
  }

  static Future<String> _getReferer() async {
    if (_cachedReferer == null) {
      final baseUrl = await _getBaseUrl();
      final token = await _getToken();
      _cachedReferer = '$baseUrl/GV/GVOBPInventory/ShowInventoryAll/$token';
    }
    return _cachedReferer!;
  }

  static const Duration _cacheTTL = Duration(hours: 2);

  CacheEntry<List<InventoryItem>>? _inventoryCache;
  
  // Sets to store unique filter values from API responses
  final Set<String> _availableTypes = {};
  final Set<String> _availableColors = {};
  bool _isInitialized = false;
  
  /// Initialize the inventory service with error handling and timeout
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      // Preload local inventory data for offline support
      await _loadLocalInventory().timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          debugPrint('⚠️ Local inventory loading timed out');
          return [];
        },
      );
      
      // Test API connectivity with a lightweight request (but don't block app startup on this)
      _testApiConnection();
      
      _isInitialized = true;
      debugPrint('✅ InventoryService initialized successfully');
    } catch (e) {
      debugPrint('⚠️ InventoryService initialization error: $e');
      // Mark as initialized anyway to prevent repeated init attempts
      _isInitialized = true;
    }
  }
  
  /// Load inventory data from local assets
  Future<List<InventoryItem>> _loadLocalInventory() async {
    try {
      final jsonString = await rootBundle.loadString('assets/inventory.json');
      final List<dynamic> jsonData = json.decode(jsonString) as List<dynamic>;
      final items = jsonData.map((item) => InventoryItem.fromJson(item as Map<String, dynamic>)).toList();
      
      // Extract filter values from local data
      for (final item in items) {
        // Use description field to extract type information
        final description = item.description.toLowerCase();
        for (final type in _defaultTypes) {
          if (description.contains(type.toLowerCase())) {
            _availableTypes.add(type);
            break;
          }
        }
        
        // Color is directly available in the InventoryItem class
        if (item.color.isNotEmpty) {
          _availableColors.add(item.color);
        }
      }
      
      return items;
    } catch (e) {
      debugPrint('⚠️ Error loading local inventory: $e');
      return [];
    }
  }
  
  /// Test API connection without blocking app startup
  Future<void> _testApiConnection() async {
    try {
      final baseUrl = await _getBaseUrl();
      final token = await _getToken();
      final referer = await _getReferer();
      final uri = Uri.parse('$baseUrl/GV/GVOBPInventory/ShowInventoryAll/$token');
      final response = await http.get(
        uri,
        headers: {'Referer': referer},
      ).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        debugPrint('✅ Inventory API connection successful');
      } else {
        debugPrint('⚠️ Inventory API returned status code: ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('⚠️ Inventory API connection test failed: $e');
    }
  }

  // Complete list of filter options based on the screenshot
  final List<String> _defaultTypes = [
    'All',
    'Base',
    'Bench Seat',
    'Bevel Marker',
    'Cap',
    'Ledger',
    'Legs',
    'Marker',
    'Panel',
    'Pedestal',
    'Piece',
    'Slab',
    'Slant',
    'Support',
    'Tablet',
    'Vase',
    'Design',
    'Monument'
  ];
  final List<String> _defaultColors = [
    'Gray',
    'Black',
    'Red',
    'Brown',
    'Green',
    'Blue Pearl'
  ];

  // List of location IDs to fetch inventory from
  final List<String> _locationIds = ['45587', '45555'];
  
  // Map of location IDs to names
  final Map<String, String> _locationNames = {
    '45587': 'Main Warehouse',
    '45555': 'Secondary Location',
  };
  
  // Getter methods for available filter options
  List<String> get availableTypes {
    if (_availableTypes.isEmpty) {
      _availableTypes.addAll(_defaultTypes);
    }
    return _availableTypes.toList()..sort();
  }

  List<String> get availableColors {
    if (_availableColors.isEmpty) {
      _availableColors.addAll(_defaultColors);
    }
    return _availableColors.toList()..sort();
  }

  // Load inventory data from local asset file
  Future<List<InventoryItem>> loadLocalInventory() async {
    try {
      debugPrint('📂 Loading inventory from local assets');
      final jsonString = await rootBundle.loadString('assets/inventory.json');
      final List<dynamic> jsonData = json.decode(jsonString) as List<dynamic>;
      final items = jsonData
          .map((item) => InventoryItem.fromJson(item as Map<String, dynamic>))
          .toList();
      debugPrint('✅ Successfully loaded ${items.length} items from local assets');

      // Populate default filter options if none have been collected
      if (_availableTypes.isEmpty) {
        _availableTypes.addAll(_defaultTypes);
      }
      if (_availableColors.isEmpty) {
        _availableColors.addAll(_defaultColors);
      }

      return items;
    } catch (e) {
      debugPrint('⚠️ Error loading local inventory: $e');
      return [];
    }
  }

  // Maximum number of retries for API requests
  static const int _maxRetries = 2;
  
  Future<List<InventoryItem>> fetchInventory({
    int page = 1,
    int pageSize = 1000,
    String? searchQuery,
    String? type,
    String? color,
    int retryCount = 0,
    bool forceRefresh = false,
  }) async {
    // Convert search query to lowercase for case-insensitive search
    final String? normalizedSearchQuery = searchQuery?.toLowerCase().trim();
    final bool isBaseRequest =
        searchQuery == null && type == null && color == null && page == 1;

    if (!forceRefresh && isBaseRequest &&
        _inventoryCache != null && !_inventoryCache!.isExpired(_cacheTTL)) {
      debugPrint('📦 Using cached inventory');
      return _inventoryCache!.data;
    }
    try {
      debugPrint('🔍 Fetching inventory from direct API endpoints');
      
      // Create a list to hold inventory items from all locations
      List<InventoryItem> allItems = [];
      
      // Fetch inventory from each location - API only accepts one location at a time
      for (final locationId in _locationIds) {
        try {
          final baseUrl = await _getBaseUrl();
          final uri = Uri.parse('$baseUrl/GV/GVOBPInventory/GetAllStockdetailsSummaryforall');
          
          debugPrint('🌐 Fetching inventory for location $locationId');
          
          // Create form data with filters - API only accepts one locid parameter
          final Map<String, String> formData = {
            'sort': '',
            'page': page.toString(),
            'pageSize': pageSize.toString(),
            'group': '',
            'filter': '',  // Don't use the filter parameter as it may cause issues
            'token': await _getToken(),
            'hasdesc': type != null && type.isNotEmpty ? 'false' : 'true',  // Set to false for type filters
            'description': searchQuery ?? '',
            'ptype': type ?? '',
            'pcolor': color ?? '',
            'pdesign': '',
            'pfinish': '',
            'psize': '',
            'locid': locationId,  // Only one location ID per request
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
              'Referer': await _getReferer(),
              'User-Agent': 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36',
            },
            body: formData,
          );
          
          debugPrint('📥 Response status for location $locationId: ${response.statusCode}');
          
          if (response.statusCode == 200) {
            // Log the raw response body for debugging
            final responseBody = utf8.decode(response.bodyBytes);
            debugPrint('📦 Raw response from location $locationId (first 500 chars): ${responseBody.length > 500 ? '${responseBody.substring(0, 500)}...' : responseBody}');
            
            // Check for empty response body before attempting to parse
            if (responseBody.trim().isEmpty) {
              debugPrint('⚠️ Empty response body from location $locationId');
              continue; // Skip this location and try the next one
            }
            
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
              debugPrint('! Error processing response from location $locationId: $e');
              // Don't immediately continue - try to handle common error cases
              
              // If it's a format exception, try to handle empty or malformed JSON
              if (e is FormatException) {
                debugPrint('⚠️ JSON parsing error. Checking if response is usable in any way...');
                
                // Check if response contains any useful text that might be parseable
                if (responseBody.contains('{') && responseBody.contains('}')) {
                  debugPrint('🔄 Attempting to clean and parse malformed JSON');
                  try {
                    // Try to extract valid JSON by finding the first { and last }
                    final startIndex = responseBody.indexOf('{');
                    final endIndex = responseBody.lastIndexOf('}') + 1;
                    if (startIndex >= 0 && endIndex > startIndex) {
                      final cleanedJson = responseBody.substring(startIndex, endIndex);
                      final data = json.decode(cleanedJson);
                      
                      // Process the cleaned JSON
                      if (data is Map) {
                        if (data.containsKey('Data') || data.containsKey('data')) {
                          final itemsData = data['Data'] ?? data['data'];
                          if (itemsData is List) {
                            debugPrint('📊 Found ${itemsData.length} items in cleaned JSON');
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
                            debugPrint('✅ Successfully recovered ${items.length} items from cleaned JSON');
                          }
                        }
                      }
                    }
                  } catch (cleaningError) {
                    debugPrint('⚠️ Failed to clean malformed JSON: $cleaningError');
                  }
                }
              }
              
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

      // Sort results by description for consistent ordering
      filteredItems.sort((a, b) => a.description.compareTo(b.description));
      
      // If we have any items, return them
      if (filteredItems.isNotEmpty) {
        debugPrint('📊 Returning ${filteredItems.length} items from API');
        if (isBaseRequest) {
          _inventoryCache = CacheEntry(filteredItems);
        }
        return filteredItems;
      } else {
        // If this is a search or type filter and we got no results, try a retry with modified parameters
        if (retryCount < _maxRetries && (searchQuery?.isNotEmpty == true || type?.isNotEmpty == true)) {
          debugPrint('🔄 Retry attempt ${retryCount + 1} for search/type filter');
          
          // For search queries, try with different parameters
          if (searchQuery?.isNotEmpty == true) {
            // Try with just the color filter if both search and color are specified
            if (color?.isNotEmpty == true) {
              debugPrint('🔄 Retrying with only color filter: $color');
              return fetchInventory(
                page: page,
                pageSize: pageSize,
                searchQuery: null,  // Remove search query
                type: type,
                color: color,
                retryCount: retryCount + 1,
              );
            }
            // Try with a simpler search query (first word only)
            else if (searchQuery!.contains(' ')) {
              final simpleQuery = searchQuery.split(' ').first;
              debugPrint('🔄 Retrying with simplified search query: $simpleQuery');
              return fetchInventory(
                page: page,
                pageSize: pageSize,
                searchQuery: simpleQuery,
                type: type,
                color: color,
                retryCount: retryCount + 1,
              );
            }
          }
          
          // For type filters, try with just the type
          else if (type?.isNotEmpty == true && color?.isNotEmpty == true) {
            debugPrint('🔄 Retrying with only type filter: $type');
            return fetchInventory(
              page: page,
              pageSize: pageSize,
              searchQuery: searchQuery,
              type: type,
              color: null,  // Remove color filter
              retryCount: retryCount + 1,
            );
          }
        }
        
        debugPrint('⚠️ Failed to load inventory from API, falling back to local data');
        final localItems = await loadLocalInventory();
        
        // Apply client-side filtering to local items
        List<InventoryItem> filteredLocalItems = localItems;
        
        if (normalizedSearchQuery != null && normalizedSearchQuery.isNotEmpty) {
          filteredLocalItems = localItems.where((item) {
            return item.description.toLowerCase().contains(normalizedSearchQuery) ||
                   item.code.toLowerCase().contains(normalizedSearchQuery) ||
                   item.color.toLowerCase().contains(normalizedSearchQuery) ||
                   item.size.toLowerCase().contains(normalizedSearchQuery);
          }).toList();
          debugPrint('📊 Found ${filteredLocalItems.length} local items matching search query');
        }
        
        if (type != null && type.isNotEmpty) {
          final normalizedType = type.toLowerCase();
          filteredLocalItems = filteredLocalItems.where((item) {
            return item.description.toLowerCase().contains(normalizedType);
          }).toList();
          debugPrint('📊 Found ${filteredLocalItems.length} local items matching type filter');
        }
        
        if (color != null && color.isNotEmpty) {
          final normalizedColor = color.toLowerCase();
          filteredLocalItems = filteredLocalItems.where((item) {
            return item.color.toLowerCase().contains(normalizedColor);
          }).toList();
          debugPrint('📊 Found ${filteredLocalItems.length} local items matching color filter');
        }
        
        if (isBaseRequest) {
          _inventoryCache = CacheEntry(filteredLocalItems);
        }
        return filteredLocalItems;
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

  /// Clear cached inventory if expired
  void clearExpiredCache() {
    if (_inventoryCache != null && _inventoryCache!.isExpired(_cacheTTL)) {
      _inventoryCache = null;
    }
  }
}
