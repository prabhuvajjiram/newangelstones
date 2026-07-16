import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:path_provider/path_provider.dart';
import '../models/inventory_item.dart';
import '../utils/cache_entry.dart';
import '../utils/inventory_search.dart';
import '../config/security_config.dart';

class InventoryService {
  InventoryService({
    http.Client? httpClient,
    Future<Directory> Function()? documentsDirectoryProvider,
  })  : _httpClient = httpClient ?? http.Client(),
        _documentsDirectoryProvider =
            documentsDirectoryProvider ?? getApplicationDocumentsDirectory;

  static const Duration _cacheTTL = Duration(hours: 2);

  final http.Client _httpClient;
  final Future<Directory> Function() _documentsDirectoryProvider;
  CacheEntry<List<InventoryItem>>? _inventoryCache;
  Future<List<InventoryItem>>? _memoryHydrationRequest;
  Future<List<InventoryItem>>? _fullInventoryRequest;
  Future<List<InventoryItem>>? _refreshRequest;
  Future<void>? _initializationRequest;
  DateTime? _lastServerRefresh;

  // Cache for search queries to avoid repeated API calls
  final Map<String, CacheEntry<List<InventoryItem>>> _searchCache = {};

  // Sets to store unique filter values from API responses
  final Set<String> _availableTypes = {};
  final Set<String> _availableColors = {};
  final Set<String> _availableLocations = {};
  bool _isInitialized = false;

  void _collectFilterOptions(Iterable<InventoryItem> items) {
    for (final item in items) {
      if (item.type.trim().isNotEmpty) {
        _availableTypes.add(item.type.trim());
      }
      if (item.color.trim().isNotEmpty) {
        _availableColors.add(item.color.trim());
      }
      if (item.location.trim().isNotEmpty) {
        _availableLocations.add(item.location.trim());
      }
    }
  }

  /// Hydrate the in-memory cache first, then refresh it once from the server.
  /// Concurrent startup callers share this same initialization request.
  Future<void> initialize() {
    if (_isInitialized) return Future<void>.value();
    final pendingRequest = _initializationRequest;
    if (pendingRequest != null) return pendingRequest;

    final request = _initialize();
    _initializationRequest = request;
    return request;
  }

  Future<void> _initialize() async {
    try {
      await _hydrateMemoryCacheFromDisk().timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          debugPrint('⚠️ Saved inventory cache loading timed out');
          return [];
        },
      );

      await refreshInventory();
      _isInitialized = true;
      debugPrint('✅ InventoryService initialized successfully');
    } catch (e) {
      debugPrint('⚠️ InventoryService initialization error: $e');
      // Mark as initialized anyway to prevent repeated init attempts
      _isInitialized = true;
    }
  }

  /// Refresh once in the background while every caller continues to use the
  /// hydrated memory cache. Concurrent refresh requests share one network call.
  Future<List<InventoryItem>> refreshInventory({bool force = false}) {
    final pendingRequest = _refreshRequest;
    if (pendingRequest != null) return pendingRequest;

    final lastRefresh = _lastServerRefresh;
    if (!force &&
        lastRefresh != null &&
        DateTime.now().difference(lastRefresh) < _cacheTTL &&
        _inventoryCache != null) {
      return Future<List<InventoryItem>>.value(_inventoryCache!.data);
    }

    final request = _refreshInventory();
    _refreshRequest = request;
    unawaited(request.whenComplete(() {
      if (identical(_refreshRequest, request)) {
        _refreshRequest = null;
      }
    }));
    return request;
  }

  Future<List<InventoryItem>> _refreshInventory() async {
    try {
      debugPrint('🔄 Syncing full inventory data from API...');
      final allItems = await fetchInventory(
        pageSize: 1000,
        forceRefresh: true,
      );

      if (allItems.isNotEmpty) {
        // Save to local storage for offline access
        await saveInventoryToLocal(allItems);
        debugPrint(
            '✅ Synced ${allItems.length} inventory items with full details');
      }
      return allItems;
    } catch (e) {
      debugPrint('⚠️ Error syncing inventory data: $e');
      return _inventoryCache?.data ?? <InventoryItem>[];
    }
  }

  /// Save inventory data to local storage
  Future<void> saveInventoryToLocal(List<InventoryItem> items) async {
    try {
      final appDir = await _documentsDirectoryProvider();
      final file = File('${appDir.path}/cached_inventory.json');

      final jsonData = items
          .map((item) => {
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
              })
          .toList();

      await file.writeAsString(json.encode(jsonData));
      debugPrint('💾 Saved ${items.length} items to local cache');
    } catch (e) {
      debugPrint('⚠️ Error saving inventory to local: $e');
    }
  }

  Future<List<InventoryItem>> _hydrateMemoryCacheFromDisk() {
    final cached = _inventoryCache;
    if (cached != null) {
      return Future<List<InventoryItem>>.value(cached.data);
    }

    final pendingRequest = _memoryHydrationRequest;
    if (pendingRequest != null) return pendingRequest;

    final request = _readSavedInventory();
    _memoryHydrationRequest = request;
    unawaited(request.whenComplete(() {
      if (identical(_memoryHydrationRequest, request)) {
        _memoryHydrationRequest = null;
      }
    }));
    return request;
  }

  Future<List<InventoryItem>> _readSavedInventory() async {
    try {
      final appDir = await _documentsDirectoryProvider();
      final cachedFile = File('${appDir.path}/cached_inventory.json');
      if (!await cachedFile.exists()) return <InventoryItem>[];

      final jsonString = await cachedFile.readAsString();
      final List<dynamic> jsonData = json.decode(jsonString) as List<dynamic>;
      final items = jsonData
          .map((item) => InventoryItem.fromJson(item as Map<String, dynamic>))
          .toList()
        ..sort((a, b) =>
            a.description.toLowerCase().compareTo(b.description.toLowerCase()));

      if (items.isNotEmpty && _inventoryCache == null) {
        _inventoryCache = CacheEntry(items);
        _collectFilterOptions(items);
        debugPrint('📦 Hydrated ${items.length} inventory items into memory');
      }
      return items;
    } catch (e) {
      debugPrint('⚠️ Error reading saved inventory cache: $e');
      return <InventoryItem>[];
    }
  }

  /// Load the minimal bundled inventory used only as an offline fallback when
  /// the app has never completed a full inventory sync.
  Future<List<InventoryItem>> _loadBundledInventory() async {
    try {
      final jsonString = await rootBundle.loadString('assets/inventory.json');
      final List<dynamic> jsonData = json.decode(jsonString) as List<dynamic>;
      final items = jsonData
          .map((item) => InventoryItem.fromJson(item as Map<String, dynamic>))
          .toList();

      // Extract filter values from local data. Older bundled data may not
      // include a structured product type, so infer one only in that case.
      for (final item in items) {
        if (item.type.isEmpty) {
          final description = item.description.toLowerCase();
          for (final type in _defaultTypes) {
            if (description.contains(type.toLowerCase())) {
              _availableTypes.add(type);
              break;
            }
          }
        }
      }
      _collectFilterOptions(items);

      return items;
    } catch (e) {
      debugPrint('⚠️ Error loading local inventory: $e');
      return [];
    }
  }

  // Used only to infer a type from older offline records that lack Ptype.
  final List<String> _defaultTypes = [
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

  /// Fetch inventory from new API with pagination support
  /// Returns all items by fetching multiple pages if needed
  Future<List<InventoryItem>> _fetchFromNewApi({
    int initialPageSize = 1000,
    bool shareFullInventoryRequest = true,
  }) async {
    if (shareFullInventoryRequest) {
      final pendingRequest = _fullInventoryRequest;
      if (pendingRequest != null) {
        debugPrint('📦 Joining inventory request already in progress');
        return pendingRequest;
      }

      final request = _fetchFromNewApi(
        initialPageSize: initialPageSize,
        shareFullInventoryRequest: false,
      );
      _fullInventoryRequest = request;
      try {
        return await request;
      } finally {
        // Keep the completed request briefly so callers resumed in the same
        // event cycle can reuse it while the base cache is being populated.
        unawaited(Future<void>.delayed(const Duration(milliseconds: 100), () {
          if (identical(_fullInventoryRequest, request)) {
            _fullInventoryRequest = null;
          }
        }));
      }
    }

    // Inventory credentials stay on the Angel Stones server. The public mobile
    // config intentionally does not expose the Monument Business API key.
    final proxyUri =
        Uri.parse('${SecurityConfig.angelStonesBaseUrl}/inventory-proxy.php');

    final List<InventoryItem> allItems = [];
    int currentPage = 1;

    while (true) {
      try {
        final uri = proxyUri.replace(queryParameters: {
          'hasdesc': 'false',
          // Fetch the complete candidate set and apply the field-aware search
          // locally. This also supports dimensions entered in a different
          // order from the upstream inventory representation.
          'description': '',
          'ptype': '',
          'pcolor': '',
          'pdesign': '',
          'pfinish': '',
          'psize': '',
          'locid': '', // Empty string fetches both Barre and Elberton
          'page': '$currentPage',
          'pageSize': '$initialPageSize',
        });

        debugPrint(
            '🌐 Fetching inventory page $currentPage (pagesize: $initialPageSize)');

        final response = await _httpClient
            .get(
              uri,
              headers: SecurityConfig.getSecurityHeaders(),
            )
            .timeout(const Duration(seconds: 30));

        if (response.statusCode == 200) {
          final responseBody = utf8.decode(response.bodyBytes);
          final dynamic data = json.decode(responseBody);

          final dynamic responseItems =
              data is Map ? (data['Data'] ?? data['data']) : null;
          if (responseItems is List) {
            final List<dynamic> pageItems = responseItems;
            debugPrint(
                '📦 Retrieved ${pageItems.length} items from page $currentPage');

            // Add items to our collection
            for (final item in pageItems) {
              final Map<String, dynamic> itemMap = {
                ...item as Map<String, dynamic>
              };

              final inventoryItem = InventoryItem.fromJson(itemMap);
              allItems.add(inventoryItem);
            }

            // Check if we need to fetch more pages
            // If we got fewer items than pageSize, we've reached the last page
            if (pageItems.length < initialPageSize) {
              debugPrint(
                  '✅ Reached last page. Total items: ${allItems.length}');
              return allItems;
            } else {
              currentPage++;
            }
          } else {
            throw const FormatException(
              'Inventory API response did not contain an item list',
            );
          }
        } else {
          throw HttpException(
            'Inventory API returned status ${response.statusCode}',
            uri: uri,
          );
        }
      } catch (e) {
        debugPrint('⚠️ Error fetching page $currentPage: $e');
        rethrow;
      }
    }
  }

  // Getter methods for available filter options
  List<String> get availableTypes => _availableTypes.toList()..sort();

  List<String> get availableColors => _availableColors.toList()..sort();

  List<String> get availableLocations => _availableLocations.toList()..sort();

  // Load inventory data from local asset file
  Future<List<InventoryItem>> loadLocalInventory() async {
    final savedItems = await _hydrateMemoryCacheFromDisk();
    if (savedItems.isNotEmpty) return savedItems;

    debugPrint('📂 Loading inventory from bundled assets');
    return _loadBundledInventory();
  }

  Future<List<InventoryItem>> fetchInventory({
    int page = 1,
    int pageSize = 1000,
    String? searchQuery,
    String? type,
    String? color,
    String? location,
    bool forceRefresh = false,
  }) async {
    if (!forceRefresh) {
      await _hydrateMemoryCacheFromDisk();
    }

    final normalizedSearchQuery =
        InventorySearch.normalizeQuery(searchQuery ?? '');
    final normalizedType = InventorySearch.normalizeQuery(type ?? '');
    final normalizedColor = InventorySearch.normalizeQuery(color ?? '');
    final normalizedLocation = InventorySearch.normalizeQuery(location ?? '');
    final hasCriteria = normalizedSearchQuery.isNotEmpty ||
        normalizedType.isNotEmpty ||
        normalizedColor.isNotEmpty ||
        normalizedLocation.isNotEmpty;
    final isBaseRequest = !hasCriteria && page == 1;
    final cacheKey = [
      normalizedSearchQuery,
      normalizedType,
      normalizedColor,
      normalizedLocation,
    ].join('|');

    // Check base cache first
    if (!forceRefresh && isBaseRequest && _inventoryCache != null) {
      debugPrint('📦 Using cached inventory');
      return _inventoryCache!.data;
    }

    // Check search cache for specific queries
    if (!forceRefresh && normalizedSearchQuery.isNotEmpty) {
      if (_searchCache.containsKey(cacheKey) &&
          !_searchCache[cacheKey]!.isExpired(_cacheTTL)) {
        debugPrint(
            '📦 Using cached search results for: $normalizedSearchQuery');
        return _searchCache[cacheKey]!.data;
      }
    }

    // The base cache contains the complete inventory. Searching it locally is
    // both faster and more accurate than relying on the API's description-only
    // search because we can rank size, code, design, color, and other fields.
    if (!forceRefresh && !isBaseRequest && _inventoryCache != null) {
      final cachedMatches = InventorySearch.filterAndRank(
        _inventoryCache!.data,
        query: searchQuery,
        type: type,
        color: color,
        location: location,
      );

      if (normalizedSearchQuery.isNotEmpty) {
        _searchCache[cacheKey] = CacheEntry(cachedMatches);
      }

      debugPrint(
          '📦 Found ${cachedMatches.length} ranked matches in cached inventory');
      return cachedMatches;
    }

    try {
      debugPrint('🔍 Fetching inventory from new API endpoint');

      // Always fetch the complete dataset. Server-side filters have historically
      // varied in matching behavior; applying every criterion to one canonical
      // dataset keeps type, color, location, and search combinations exact.
      final allItems = await _fetchFromNewApi(
        initialPageSize: pageSize < 1000 ? 1000 : pageSize,
      );

      debugPrint('📊 Retrieved ${allItems.length} total items from API');

      if (allItems.isNotEmpty) {
        final sortedAllItems = List<InventoryItem>.of(allItems)
          ..sort((a, b) => a.description
              .toLowerCase()
              .compareTo(b.description.toLowerCase()));
        // Search results are derived from the canonical inventory snapshot.
        // They must never outlive the snapshot that produced them.
        _searchCache.clear();
        _inventoryCache = CacheEntry(sortedAllItems);
        _lastServerRefresh = DateTime.now();
        _collectFilterOptions(sortedAllItems);

        if (!hasCriteria) {
          debugPrint('📊 Returning ${sortedAllItems.length} items from API');
          return sortedAllItems;
        }

        debugPrint(
            '🔍 Applying exact type/color/location filters and search ranking');

        final filteredItems = InventorySearch.filterAndRank(
          sortedAllItems,
          query: searchQuery,
          type: type,
          color: color,
          location: location,
        );

        debugPrint(
            '📊 Found ${filteredItems.length} items matching all criteria');
        if (filteredItems.isNotEmpty) {
          debugPrint('📦 Sample match: ${filteredItems.first.description}');
        }

        if (normalizedSearchQuery.isNotEmpty) {
          _searchCache[cacheKey] = CacheEntry(filteredItems);
        }
        return filteredItems;
      }

      debugPrint(
          '⚠️ Inventory API unavailable; falling back to locally cached data');
      final localItems = await loadLocalInventory();
      _collectFilterOptions(localItems);

      if (hasCriteria) {
        final filteredLocalItems = InventorySearch.filterAndRank(
          localItems,
          query: searchQuery,
          type: type,
          color: color,
          location: location,
        );
        debugPrint(
            '📊 Found ${filteredLocalItems.length} ranked local matches');
        return filteredLocalItems;
      }

      final sortedLocalItems = List<InventoryItem>.of(localItems)
        ..sort((a, b) =>
            a.description.toLowerCase().compareTo(b.description.toLowerCase()));
      if (isBaseRequest) {
        _inventoryCache = CacheEntry(sortedLocalItems);
      }
      return sortedLocalItems;
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
  Future<List<InventoryItem>> getItemDetailedRecords(
      String endProductCode) async {
    try {
      final uri =
          Uri.parse('${SecurityConfig.angelStonesBaseUrl}/inventory-proxy.php')
              .replace(queryParameters: {
        'action': 'getDetails',
        'epcode': endProductCode,
      });

      debugPrint('🔍 Fetching detailed records for: $endProductCode');

      final response = await _httpClient
          .get(
            uri,
            headers: SecurityConfig.getSecurityHeaders(),
          )
          .timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final items = (data['Data'] ?? data['data'] ?? data['stones']) as List?;

        if (items != null && items.isNotEmpty) {
          debugPrint(
              '✅ Found ${items.length} stone records for $endProductCode');
          // Return all stone records (each with its own Container/CrateNo)
          return items
              .map((item) =>
                  InventoryItem.fromJson(item as Map<String, dynamic>))
              .toList();
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

  /// Keep stale inventory searchable while refreshing it in the background.
  void clearExpiredCache() {
    if (_inventoryCache != null && _inventoryCache!.isExpired(_cacheTTL)) {
      _searchCache.clear();
      unawaited(refreshInventory());
    }
  }
}
