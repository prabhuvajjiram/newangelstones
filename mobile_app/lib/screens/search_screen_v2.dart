import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/inventory_item.dart';
import '../models/product.dart';
import '../services/inventory_service.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../navigation/app_router.dart' as router;
import '../utils/image_utils.dart';
import '../config/security_config.dart';

class SearchScreenV2 extends StatefulWidget {
  // Accept services directly
  final InventoryService? inventoryService;
  final ApiService? apiService;
  final StorageService? storageService;

  const SearchScreenV2({
    Key? key, 
    this.inventoryService,
    this.apiService,
    this.storageService,
  }) : super(key: key);

  @override
  State<SearchScreenV2> createState() => _SearchScreenV2State();
}

class _SearchScreenV2State extends State<SearchScreenV2> {
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _searchFocusNode = FocusNode();
  String _searchQuery = '';
  bool _isSearching = false;
  bool _hasResults = false;
  
  // Search results
  List<Product> _productResults = [];
  List<String> _colorResults = [];
  Map<String, List<InventoryItem>> _typeGroupedResults = {};
  
  Timer? _searchDebounce;
  
  @override
  void initState() {
    super.initState();
    // Auto-focus the search field when the screen opens
    WidgetsBinding.instance.addPostFrameCallback((_) {
      FocusScope.of(context).requestFocus(_searchFocusNode);
      
      // Load MBNA products to ensure they're in storage
      _loadMbnaProducts();
    });
    
    // Debug: Print storage contents
    _debugPrintAllSecureStorageContents();
  }
  
  // Load products from MBNA_2025 directory
  Future<void> _loadMbnaProducts() async {
    debugPrint('🔄 Loading MBNA products from directory...');
    final apiService = _getApiService();
    
    if (apiService == null) {
      debugPrint('❌ ApiService not available, cannot load MBNA products');
      return;
    }
    
    try {
      // Fetch product images from MBNA_2025 directory
      final productImages = await apiService.fetchProductImagesWithCodes('MBNA_2025');
      debugPrint('✅ Loaded ${productImages.length} products from MBNA_2025 directory');
      
      // Check for specific products
      for (var image in productImages) {
        if (image.productCode.contains('946') || image.productCode.contains('948')) {
          debugPrint('🎯 Found target product in MBNA directory: ${image.productCode}');
          debugPrint('  Image URL: ${image.imageUrl}');
        }
      }
    } catch (e) {
      debugPrint('❌ Error loading MBNA products: $e');
    }
  }

  @override
  void dispose() {
    _searchController.dispose();
    _searchFocusNode.dispose();
    _searchDebounce?.cancel();
    super.dispose();
  }

  void _onSearchChanged(String query) {
    // Cancel previous debounce timer if active
    if (_searchDebounce?.isActive ?? false) {
      _searchDebounce!.cancel();
    }
    
    // Clear results if query is empty
    if (query.isEmpty) {
      setState(() {
        _searchQuery = '';
        _isSearching = false;
        _productResults = [];
        _colorResults = [];
        _typeGroupedResults = {};
        _hasResults = false;
      });
      return;
    }
    
    // Start a new debounce timer
    _searchDebounce = Timer(const Duration(milliseconds: 500), () {
      final trimmedQuery = query.trim();
      setState(() {
        _searchQuery = trimmedQuery;
        _isSearching = true;
      });
      
      // If searching for target products, ensure MBNA products are loaded first
      if (trimmedQuery.contains('946') || trimmedQuery.contains('948')) {
        debugPrint('🔍 Target product search detected: "$trimmedQuery"');
        _loadMbnaProducts().then((_) {
          // Reload all products to ensure we have the latest from storage
          _loadAllLocalProducts().then((products) {
            debugPrint('🔄 Reloaded ${products.length} products after MBNA load');
            _performSearch(trimmedQuery);
          });
        });
      } else {
        _performSearch(trimmedQuery);
      }
    });
  }
  
  // Get the API service instance
  ApiService _getApiService() {
    return widget.apiService ?? Provider.of<ApiService>(context, listen: false);
  }
  
  // Get the inventory service instance
  InventoryService _getInventoryService() {
    return widget.inventoryService ?? Provider.of<InventoryService>(context, listen: false);
  }
  
  // Get the storage service instance
  StorageService? _getStorageService() {
    // First try to use the service passed directly to the widget
    if (widget.storageService != null) {
      return widget.storageService!;
    }
    
    // If not available, try to get from provider, but don't throw if not found
    try {
      return Provider.of<StorageService>(context, listen: false);
    } catch (e) {
      debugPrint('⚠️ StorageService not available: $e');
      return null;
    }
  }
  
  // Debug method to print all secure storage contents
  Future<void> _debugPrintAllSecureStorageContents() async {
    debugPrint('\n🔍 DEBUG: Inspecting secure storage contents...');
    
    final storageService = _getStorageService();
    if (storageService == null) {
      debugPrint('❌ DEBUG: StorageService not available');
      return;
    }
    
    try {
      // Access the FlutterSecureStorage directly
      final storage = const FlutterSecureStorage();
      
      // Try to read all values
      debugPrint('📋 DEBUG: Reading all secure storage keys...');
      final allValues = await storage.readAll();
      
      if (allValues.isEmpty) {
        debugPrint('⚠️ DEBUG: Secure storage is empty');
      } else {
        debugPrint('✅ DEBUG: Found ${allValues.length} items in secure storage');
        debugPrint('🔑 DEBUG: Keys: ${allValues.keys.join(', ')}');
        
        // Check for cached products
        if (allValues.containsKey('cached_products')) {
          debugPrint('\n📦 DEBUG: Found cached_products key');
          final productsJson = allValues['cached_products'];
          debugPrint('📄 DEBUG: cached_products value: $productsJson');
          
          // Try to parse the JSON
          if (productsJson != null && productsJson.isNotEmpty) {
            try {
              final jsonData = json.decode(productsJson);
              debugPrint('🔢 DEBUG: Parsed ${jsonData.length} products from JSON');
              
              // Check for specific products
              for (var item in jsonData) {
                final id = item['id']?.toString() ?? '';
                if (id.contains('946') || id.contains('948')) {
                  debugPrint('\n🎯 DEBUG: FOUND TARGET PRODUCT: $id');
                  debugPrint('  Name: ${item['name']}');
                  debugPrint('  Description: ${item['description']}');
                  debugPrint('  Image: ${item['image']}');
                }
              }
            } catch (e) {
              debugPrint('❌ DEBUG: Error parsing JSON: $e');
            }
          }
        } else {
          debugPrint('⚠️ DEBUG: No cached_products key found');
        }
      }
      
      // Try loading products through the service
      debugPrint('\n🔄 DEBUG: Loading products through StorageService...');
      final products = await storageService.loadProducts();
      
      if (products == null || products.isEmpty) {
        debugPrint('⚠️ DEBUG: No products loaded through StorageService');
      } else {
        debugPrint('✅ DEBUG: Loaded ${products.length} products through StorageService');
        
        // Check for specific products
        for (var product in products) {
          if (product.id.contains('946') || product.id.contains('948')) {
            debugPrint('\n🎯 DEBUG: FOUND TARGET PRODUCT: ${product.id}');
            debugPrint('  Name: ${product.name}');
            debugPrint('  Description: ${product.description}');
            debugPrint('  Image URL: ${product.imageUrl}');
          }
        }
      }
    } catch (e) {
      debugPrint('❌ DEBUG: Error accessing secure storage: $e');
    }
  }
  
  // Load all products from local storage
  Future<List<Product>> _loadAllLocalProducts() async {
    List<Product> allProducts = [];
    final apiService = _getApiService();
    final storageService = _getStorageService();
    
    try {
      // Load featured products from local JSON
      final featuredProducts = await apiService.loadLocalProducts('assets/featured_products.json');
      allProducts.addAll(featuredProducts);
      
      // Load color products from local JSON
      final colorProducts = await apiService.loadLocalProducts('assets/colors.json');
      allProducts.addAll(colorProducts);
      
      // Load products from secure storage (if available)
      if (storageService != null) {
        try {
          final secureStorageProducts = await storageService.loadProducts();
          if (secureStorageProducts != null && secureStorageProducts.isNotEmpty) {
            debugPrint('✅ Found ${secureStorageProducts.length} products in secure storage');
            
            // Print detailed information about each product
            debugPrint('🔍 SECURE STORAGE PRODUCTS DETAILS:');
            for (var product in secureStorageProducts) {
              debugPrint('-----------------------------------');
              debugPrint('ID: ${product.id}');
              debugPrint('Name: ${product.name}');
              debugPrint('Description: ${product.description}');
              debugPrint('Image URL: ${product.imageUrl}');
              debugPrint('Price: ${product.price}');
              
              // Check if this product contains '946' or '948' in its ID
              if (product.id.contains('946') || product.id.contains('948')) {
                debugPrint('🎯 FOUND TARGET PRODUCT: ${product.id}');
              }
            }
            debugPrint('-----------------------------------');
            
            allProducts.addAll(secureStorageProducts);
          } else {
            debugPrint('⚠️ No products found in secure storage (null or empty list)');
          }
        } catch (storageError) {
          debugPrint('⚠️ Error accessing secure storage: $storageError');
        }
      } else {
        debugPrint('⚠️ StorageService is not available, skipping secure storage products');
      }
      
      debugPrint('✅ Loaded ${allProducts.length} total products from all sources');
    } catch (e) {
      debugPrint('❌ Error loading local products: $e');
    }
    
    return allProducts;
  }
  
  // No special product handling - we only use what's in local storage
  
  Future<void> _performSearch(String query) async {
    if (query.isEmpty) {
      setState(() {
        _productResults = [];
        _colorResults = [];
        _typeGroupedResults = {};
        _isSearching = false;
        _hasResults = false;
      });
      return;
    }
    
    setState(() {
      _isSearching = true;
      _hasResults = false;
    });
    
    bool hasAnyResults = false;
    final normalizedQuery = query.toLowerCase().trim();
    
    // Special debug for target product codes
    bool isTargetSearch = query.contains('946') || query.contains('948');
    
    debugPrint('🔍 Performing search for: "$query" (normalized: "${query.toLowerCase().trim()}")');
    
    debugPrint('🔎 SEARCH QUERY: "$normalizedQuery"');
    debugPrint('🔍 Performing search for: "$query" (normalized: "$normalizedQuery")');
    
    // STEP 1: Search for products in local storage
    try {
      // Load all products from local storage
      final allProducts = await _loadAllLocalProducts();
      
      // Filter products by search query
      final List<Product> matchingProducts = allProducts.where((product) {
        final name = product.name?.toLowerCase() ?? '';
        final description = product.description?.toLowerCase() ?? '';
        final id = product.id?.toLowerCase() ?? '';
        String numericId = '';
        
        // Debug specific products we're looking for
        bool isTargetProduct = id.contains('946') || id.contains('948');
        if (isTargetProduct) {
          debugPrint('\n🔍 CHECKING TARGET PRODUCT: $id');
          debugPrint('  Name: ${product.name}');
          debugPrint('  Description: ${product.description}');
        }
        
        // Extract numeric part from ID if possible
        if (id.isNotEmpty) {
          final RegExp numericRegex = RegExp(r'\d+');
          final Match? numericMatch = numericRegex.firstMatch(id);
          if (numericMatch != null) {
            numericId = numericMatch.group(0) ?? "";
            if (isTargetProduct) {
              debugPrint('  Extracted numeric ID: $numericId');
            }
          }
        }
        
        // Match by name, description, or ID (full or numeric part)
        bool matches = name.contains(normalizedQuery) || 
                      description.contains(normalizedQuery) || 
                      id.contains(normalizedQuery);
        
        if (isTargetProduct) {
          debugPrint('  Basic match result: $matches');
          debugPrint('    - name.contains("$normalizedQuery"): ${name.contains(normalizedQuery)}');
          debugPrint('    - description.contains("$normalizedQuery"): ${description.contains(normalizedQuery)}');
          debugPrint('    - id.contains("$normalizedQuery"): ${id.contains(normalizedQuery)}');
        }
        
        // Direct match for product codes (case insensitive)
        if (!matches) {
          // Check if product ID equals the search query (ignoring case and with/without prefix)
          String normalizedId = id.toLowerCase().replaceAll(RegExp(r'[\s-]'), '');
          
          if (isTargetProduct) {
            debugPrint('  Normalized ID: $normalizedId');
          }
          
          // Handle AG prefix in query or product ID
          if (normalizedId.startsWith("ag") && !normalizedQuery.startsWith("ag")) {
            // Query doesn't have prefix but product does - compare without prefix
            if (normalizedId.substring(2) == normalizedQuery) {
              matches = true;
              if (isTargetProduct) {
                debugPrint('  ✅ Matched without AG prefix: ${normalizedId.substring(2)} == $normalizedQuery');
              }
            } else if (isTargetProduct) {
              debugPrint('  ❌ No match without AG prefix: ${normalizedId.substring(2)} != $normalizedQuery');
            }
          } else if (!normalizedId.startsWith("ag") && normalizedQuery.startsWith("ag")) {
            // Query has prefix but product doesn't - compare without prefix
            if (normalizedId == normalizedQuery.substring(2)) {
              matches = true;
              if (isTargetProduct) {
                debugPrint('  ✅ Matched with AG prefix: $normalizedId == ${normalizedQuery.substring(2)}');
              }
            } else if (isTargetProduct) {
              debugPrint('  ❌ No match with AG prefix: $normalizedId != ${normalizedQuery.substring(2)}');
            }
          }
        }
        
        // Also check for numeric part matches (e.g., "950" matches "AG-950")
        if (!matches && numericId.isNotEmpty && normalizedQuery.isNotEmpty) {
          if (numericId.contains(normalizedQuery) || normalizedQuery.contains(numericId)) {
            matches = true;
            if (isTargetProduct) {
              debugPrint('  ✅ Matched by numeric part: $numericId contains/is contained in $normalizedQuery');
            }
          } else if (isTargetProduct) {
            debugPrint('  ❌ No numeric match: $numericId not related to $normalizedQuery');
          }
        }
        
        if (isTargetProduct) {
          debugPrint('  FINAL MATCH RESULT: $matches\n');
        }
        
        return matches;
      }).toList();
      
      if (matchingProducts.isNotEmpty) {
        setState(() {
          _productResults = matchingProducts;
        });
        debugPrint('✅ Found ${matchingProducts.length} matching products');
        hasAnyResults = true;
      }
    } catch (e) {
      debugPrint('❌ Error searching products: $e');
    }
    
    // STEP 2: Search inventory items
    try {
      final inventoryService = _getInventoryService();
      
      // Get all inventory items
      final allItems = await inventoryService.loadLocalInventory();
      
      // Filter inventory items by search query
      final List<InventoryItem> inventoryResults = allItems.where((item) {
        final String itemText = '${item.code} ${item.description} ${item.type} ${item.color}'.toLowerCase();
        return itemText.contains(normalizedQuery);
      }).toList();
      
      // Update state with inventory results
      if (inventoryResults.isNotEmpty) {
        // Extract unique colors and types for grouping
        final Set<String> colors = {};
        final Map<String, List<InventoryItem>> typeGroups = {};
        
        for (final item in inventoryResults) {
          // Add color to colors list if not empty
          if (item.color.isNotEmpty) {
            colors.add(item.color);
          }
          
          // Group by type
          if (item.type.isNotEmpty) {
            if (!typeGroups.containsKey(item.type)) {
              typeGroups[item.type] = [];
            }
            typeGroups[item.type]!.add(item);
          }
        }
        
        setState(() {
          _colorResults = colors.toList();
          _typeGroupedResults = typeGroups;
        });
        
        debugPrint('✅ Found ${inventoryResults.length} matching inventory items');
        hasAnyResults = true;
      }
    } catch (e) {
      debugPrint('❌ Error searching inventory: $e');
    }
    
    // Update UI state based on search results
    setState(() {
      _hasResults = hasAnyResults;
      _searchQuery = query;
    });
    
    // Log search completion
    debugPrint(hasAnyResults ? '✅ Search complete with results' : '⚠️ Search complete with no results');
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Search'),
        elevation: 0,
      ),
      body: Column(
        children: [
          // Search bar
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              focusNode: _searchFocusNode,
              decoration: InputDecoration(
                hintText: 'Search for products, colors, or inventory...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8.0),
                ),
                contentPadding: const EdgeInsets.symmetric(vertical: 12.0),
                filled: true,
                fillColor: Colors.grey[100],
              ),
              onChanged: _onSearchChanged,
            ),
          ),
          
          // Search results
          Expanded(
            child: _isSearching ? _buildSearchResults() : _buildEmptyState(),
          ),
        ],
      ),
    );
  }
  
  Widget _buildEmptyState() {
    return const Center(
      child: Text('Enter a search term to find products, colors, or inventory items'),
    );
  }
  
  Widget _buildSearchResults() {
    if (!_hasResults) {
      return Center(
        child: Text('No results found for "$_searchQuery"'),
      );
    }
    
    return ListView(
      children: [
        // Products Section
        if (_productResults.isNotEmpty)
          _buildProductSection('Products', _productResults),
        
        // Colors Section
        if (_colorResults.isNotEmpty)
          _buildColorsSection(),
        
        // Inventory by Type Section
        if (_typeGroupedResults.isNotEmpty)
          _buildInventoryTypeSection(),
      ],
    );
  }
  
  Widget _buildProductSection(String title, List<Product> products) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.all(16.0),
          child: Text(
            title,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ),
        SizedBox(
          height: 200,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: products.length,
            itemBuilder: (context, index) {
              final product = products[index];
              return GestureDetector(
                onTap: () => _navigateToProductDetail(product),
                child: Container(
                  width: 160,
                  margin: const EdgeInsets.only(left: 16.0, bottom: 16.0),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(8.0),
                    border: Border.all(color: Colors.grey[300]!),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      ClipRRect(
                        borderRadius: const BorderRadius.only(
                          topLeft: Radius.circular(8.0),
                          topRight: Radius.circular(8.0),
                        ),
                        child: ImageUtils.buildImage(
                          imageUrl: product.imageUrl,
                          height: 120,
                          width: double.infinity,
                          fit: BoxFit.cover,
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.all(8.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              product.name,
                              style: const TextStyle(fontWeight: FontWeight.bold),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            Text(
                              product.id,
                              style: TextStyle(color: Colors.grey[600], fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
  
  Widget _buildColorsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.all(16.0),
          child: Text(
            'Colors',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0),
          child: Wrap(
            spacing: 8.0,
            runSpacing: 8.0,
            children: _colorResults.map((color) {
              return GestureDetector(
                onTap: () => _navigateToInventoryWithColorFilter(color),
                child: Chip(
                  label: Text(color),
                  backgroundColor: Colors.grey[200],
                ),
              );
            }).toList(),
          ),
        ),
        const SizedBox(height: 16),
      ],
    );
  }
  
  Widget _buildInventoryTypeSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.all(16.0),
          child: Text(
            'Inventory',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ),
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: _typeGroupedResults.length,
          itemBuilder: (context, index) {
            final type = _typeGroupedResults.keys.elementAt(index);
            final items = _typeGroupedResults[type]!;
            
            return ExpansionTile(
              title: Text('$type (${items.length})'),
              children: items.map((item) {
                return ListTile(
                  title: Text(item.code),
                  subtitle: Text(item.description),
                  trailing: Text(item.color),
                  onTap: () => _navigateToInventoryItemDetail(item),
                );
              }).toList(),
            );
          },
        ),
      ],
    );
  }
  
  void _navigateToProductDetail(Product product) {
    // Use go_router's push method instead of pushNamed to avoid route name issues
    context.push('/product', extra: product);
  }
  
  void _navigateToInventoryWithColorFilter(String color) {
    // Navigate to inventory screen with color filter
    context.push('/inventory', extra: {'color': color});
  }
  
  void _navigateToInventoryItemDetail(InventoryItem item) {
    // Navigate to inventory item detail screen
    context.push('/inventory-item-details', extra: item);
  }
}
