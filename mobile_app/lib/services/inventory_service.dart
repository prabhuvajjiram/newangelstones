import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:path_provider/path_provider.dart';
import '../models/inventory_item.dart';
import '../utils/cache_entry.dart';
import '../config/security_config.dart';

class InventoryService {
  static String? _cachedApiKey;
  static int? _cachedOrgId;
  static String? _cachedBaseUrl;

  static Future<String> _getBaseUrl() async {
    _cachedBaseUrl ??= SecurityConfig.monumentBusinessBaseUrl;
    return _cachedBaseUrl!;
  }

  static Future<String> _getApiKey() async {
    _cachedApiKey ??= await SecurityConfig.getMonumentBusinessApiKey();
    return _cachedApiKey!;
  }
  
  static Future<int> _getOrgId() async {
    _cachedOrgId ??= await SecurityConfig.getMonumentBusinessOrgId();
    return _cachedOrgId!;
  }

  static const Duration _cacheTTL = Duration(hours: 2);

  CacheEntry<List<InventoryItem>>? _inventoryCache;
  
  // Cache for search queries to avoid repeated API calls
  final Map<String, CacheEntry<List<InventoryItem>>> _searchCache = {};
  
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
      
      // Sync full inventory data from API in background (non-blocking)
      _syncFullInventoryData();
      
      _isInitialized = true;
      debugPrint('✅ InventoryService initialized successfully');
    } catch (e) {
      debugPrint('⚠️ InventoryService initialization error: $e');
      // Mark as initialized anyway to prevent repeated init attempts
      _isInitialized = true;
    }
  }
  
  /// Sync full inventory data from API and cache locally
  Future<void> _syncFullInventoryData() async {
    try {
      debugPrint('🔄 Syncing full inventory data from API...');
      
      // Fetch all inventory items from API with full details
      final allItems = await fetchInventory(
        pageSize: 1000,
        forceRefresh: true,
      );
      
      if (allItems.isNotEmpty) {
        // Save to local storage for offline access
        await saveInventoryToLocal(allItems);
        debugPrint('✅ Synced ${allItems.length} inventory items with full details');
      }
    } catch (e) {
      debugPrint('⚠️ Error syncing inventory data: $e');
    }
  }
  
  /// Save inventory data to local storage
  Future<void> saveInventoryToLocal(List<InventoryItem> items) async {
    try {
      final appDir = await getApplicationDocumentsDirectory();
      final file = File('${appDir.path}/cached_inventory.json');
      
      final jsonData = items.map((item) => {
        'code': item.code,
        'description': item.description,
        'color': item.color,
        'size': item.size,
        'design': item.design,
        'finish': item.finish,
        'type': item.type,
        'location': item.location,
        'quantity': item.quantity,
        'productId': item.productId,
      }).toList();
      
      await file.writeAsString(json.encode(jsonData));
      debugPrint('💾 Saved ${items.length} items to local cache');
    } catch (e) {
      debugPrint('⚠️ Error saving inventory to local: $e');
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
      final apiKey = await _getApiKey();
      final orgId = await _getOrgId();
      final uri = Uri.parse('$baseUrl/Api/Inventory/GetAllStock');
      
      final requestBody = json.encode({
        'orgid': orgId,
        'hasdesc': false,
        'description': '',
        'ptype': '',
        'pcolor': '',
        'pdesign': '',
        'pfinish': '',
        'psize': '',
        'locid': '',
        'page': 1,
        'pagesize': 10
      });
      
      final response = await http.post(
        uri,
        headers: {
          'Content-Type': 'application/json',
          'X-API-Key': apiKey,
        },
        body: requestBody,
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

  // Map of location IDs to names (for display purposes)
  final Map<String, String> _locationNames = {
    '45587': 'Main Warehouse',
    '45555': 'Secondary Location',
  };
  
  /// Fetch inventory from new API with pagination support
  /// Returns all items by fetching multiple pages if needed
  Future<List<InventoryItem>> _fetchFromNewApi({
    String? locationId,
    String? searchQuery,
    String? type,
    String? color,
    int initialPageSize = 1000,
  }) async {
    final baseUrl = await _getBaseUrl();
    final apiKey = await _getApiKey();
    final orgId = await _getOrgId();
    final uri = Uri.parse('$baseUrl/Api/Inventory/GetAllStock');
    
    List<InventoryItem> allItems = [];
    int currentPage = 1;
    bool hasMorePages = true;
    
    while (hasMorePages) {
      try {
        final requestBody = json.encode({
          'orgid': orgId,
          'hasdesc': false,
          'description': searchQuery ?? '',
          'ptype': type ?? '',
          'pcolor': color ?? '',
          'pdesign': '',
          'pfinish': '',
          'psize': '',
          'locid': locationId ?? '', // Empty string for all locations
          'page': currentPage,
          'pagesize': initialPageSize
        });
        
        debugPrint('🌐 Fetching page $currentPage for location ${locationId ?? "all"} (pagesize: $initialPageSize)');
        
        final response = await http.post(
          uri,
          headers: {
            'Content-Type': 'application/json',
            'X-API-Key': apiKey,
          },
          body: requestBody,
        ).timeout(const Duration(seconds: 30));
        
        if (response.statusCode == 200) {
          final responseBody = utf8.decode(response.bodyBytes);
          final dynamic data = json.decode(responseBody);
          
          if (data is Map && data['Data'] is List) {
            final List<dynamic> pageItems = data['Data'] as List<dynamic>;
            debugPrint('📦 Retrieved ${pageItems.length} items from page $currentPage');
            
            // Add items to our collection
            for (final item in pageItems) {
              final Map<String, dynamic> itemMap = {...item as Map<String, dynamic>};
              
              // Add location name if we're fetching from specific location
              if (locationId != null && locationId.isNotEmpty) {
                itemMap['Location'] = _locationNames[locationId] ?? 'Unknown';
              }
              
              // Extract type and color for filter options
              if (itemMap.containsKey('PColor') && itemMap['PColor'] != null) {
                final color = itemMap['PColor'].toString().trim();
                if (color.isNotEmpty) _availableColors.add(color);
              }
              
              if (itemMap.containsKey('PType') && itemMap['PType'] != null) {
                final type = itemMap['PType'].toString().trim();
                if (type.isNotEmpty) _availableTypes.add(type);
              }
              
              allItems.add(InventoryItem.fromJson(itemMap));
            }
            
            // Check if we need to fetch more pages
            // If we got fewer items than pageSize, we've reached the last page
            if (pageItems.length < initialPageSize) {
              hasMorePages = false;
              debugPrint('✅ Reached last page. Total items: ${allItems.length}');
            } else {
              currentPage++;
            }
          } else {
            debugPrint('⚠️ Unexpected response format from new API');
            hasMorePages = false;
          }
        } else {
          debugPrint('❌ API returned status ${response.statusCode}');
          hasMorePages = false;
        }
      } catch (e) {
        debugPrint('⚠️ Error fetching page $currentPage: $e');
        hasMorePages = false;
      }
    }
    
    return allItems;
  }
  
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
      // Try to load from cached full data first (has all fields)
      final appDir = await getApplicationDocumentsDirectory();
      final cachedFile = File('${appDir.path}/cached_inventory.json');
      
      if (await cachedFile.exists()) {
        debugPrint('📂 Loading inventory from cached full data');
        final jsonString = await cachedFile.readAsString();
        final List<dynamic> jsonData = json.decode(jsonString) as List<dynamic>;
        final items = jsonData
            .map((item) => InventoryItem.fromJson(item as Map<String, dynamic>))
            .toList();
        debugPrint('✅ Successfully loaded ${items.length} items from cached data');
        return items;
      }
      
      // Fallback to bundled assets if no cached data
      debugPrint('📂 Loading inventory from bundled assets');
      try {
        final jsonString = await rootBundle.loadString('assets/inventory.json');
        final List<dynamic> jsonData = json.decode(jsonString) as List<dynamic>;
        final items = jsonData
            .map((item) => InventoryItem.fromJson(item as Map<String, dynamic>))
            .toList();
        debugPrint('✅ Successfully loaded ${items.length} items from bundled assets');
        
        // Populate default filter options if none have been collected
        if (_availableTypes.isEmpty) {
          _availableTypes.addAll(_defaultTypes);
        }
        if (_availableColors.isEmpty) {
          _availableColors.addAll(_defaultColors);
        }
        
        return items;
      } catch (e) {
        debugPrint('⚠️ Bundled assets have wrong format or missing: $e');
        // Return empty list if bundled assets are not in correct format
        return [];
      }
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

    // Check base cache first
    if (!forceRefresh && isBaseRequest &&
        _inventoryCache != null && !_inventoryCache!.isExpired(_cacheTTL)) {
      debugPrint('📦 Using cached inventory');
      return _inventoryCache!.data;
    }
    
    // Check search cache for specific queries
    if (!forceRefresh && normalizedSearchQuery != null && normalizedSearchQuery.isNotEmpty) {
      final searchCacheKey = '$normalizedSearchQuery|$type|$color';
      if (_searchCache.containsKey(searchCacheKey) && 
          !_searchCache[searchCacheKey]!.isExpired(_cacheTTL)) {
        debugPrint('📦 Using cached search results for: $normalizedSearchQuery');
        return _searchCache[searchCacheKey]!.data;
      }
    }
    
    try {
      debugPrint('🔍 Fetching inventory from new API endpoint');
      
      // Fetch all items using new API (fetches all locations if no specific locid)
      // The new API with empty locid returns all locations in one call
      final allItems = await _fetchFromNewApi(
        searchQuery: searchQuery,
        type: type,
        color: color,
        initialPageSize: pageSize,
      );
      
      debugPrint('📊 Retrieved ${allItems.length} total items from API');
      
      // Apply client-side filtering for search query if needed (backup)
      List<InventoryItem> filteredItems = allItems;
      
      if (normalizedSearchQuery != null && normalizedSearchQuery.isNotEmpty) {
        debugPrint('🔍 Applying client-side search filter for: $normalizedSearchQuery');
        
        // Optimize: Use where() which is lazy and stops early if needed
        filteredItems = allItems.where((item) {
          // Check multiple fields for the search term
          // Order by most likely to match first for early exit
          final descLower = item.description.toLowerCase();
          final codeLower = item.code.toLowerCase();
          
          if (descLower.contains(normalizedSearchQuery)) return true;
          if (codeLower.contains(normalizedSearchQuery)) return true;
          if (item.design.toLowerCase().contains(normalizedSearchQuery)) return true;
          if (item.color.toLowerCase().contains(normalizedSearchQuery)) return true;
          if (item.size.toLowerCase().contains(normalizedSearchQuery)) return true;
          
          return false;
        }).toList();
        
        debugPrint('📊 Found ${filteredItems.length} items matching search query');
        if (filteredItems.isNotEmpty) {
          debugPrint('📦 Sample match: ${filteredItems.first.description}');
        }
      }

      // Sort results by description for consistent ordering
      filteredItems.sort((a, b) => a.description.compareTo(b.description));
      
      // If we have any items, return them
      if (filteredItems.isNotEmpty) {
        debugPrint('📊 Returning ${filteredItems.length} items from API');
        
        // Cache the results
        if (isBaseRequest) {
          _inventoryCache = CacheEntry(filteredItems);
        } else if (normalizedSearchQuery != null && normalizedSearchQuery.isNotEmpty) {
          // Cache search results
          final searchCacheKey = '$normalizedSearchQuery|$type|$color';
          _searchCache[searchCacheKey] = CacheEntry(filteredItems);
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

  /// Fetch detailed information for a specific inventory item
  /// Uses GetAllStockDetailedSummary endpoint which returns individual stone records
  /// Each record represents a physical stone with its specific Container and CrateNo
  Future<List<InventoryItem>> getItemDetailedRecords(String endProductCode) async {
    try {
      final baseUrl = await _getBaseUrl();
      final apiKey = await _getApiKey();
      final orgId = await _getOrgId();
      final uri = Uri.parse('$baseUrl/Api/Inventory/GetAllStockDetailedSummary');
      
      debugPrint('🔍 Fetching detailed records for: $endProductCode');
      
      final requestBody = json.encode({
        'orgid': orgId,
        'locid': '',
        'container': '',
        'epcode': endProductCode,
        'page': 1,
        'pagesize': 100,
      });

      final response = await http.post(
        uri,
        headers: {
          'Content-Type': 'application/json',
          'X-API-Key': apiKey,
        },
        body: requestBody,
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final items = data['Data'] as List?;
        
        if (items != null && items.isNotEmpty) {
          debugPrint('✅ Found ${items.length} stone records for $endProductCode');
          // Return all stone records (each with its own Container/CrateNo)
          return items.map((item) => InventoryItem.fromJson(item as Map<String, dynamic>)).toList();
        } else {
          debugPrint('⚠️ No detailed records found for $endProductCode');
          return [];
        }
      } else {
        debugPrint('❌ Failed to fetch item details: ${response.statusCode}');
        return [];
      }
    } catch (e) {
      debugPrint('❌ Error fetching item details: $e');
      return [];
    }
  }

  /// Clear cached inventory if expired
  void clearExpiredCache() {
    if (_inventoryCache != null && _inventoryCache!.isExpired(_cacheTTL)) {
      _inventoryCache = null;
    }
  }
}
