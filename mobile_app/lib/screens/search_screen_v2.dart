import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../models/inventory_item.dart';
import '../models/product.dart';
import '../services/inventory_service.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../navigation/app_router.dart';
import '../state/cart_state.dart';
import '../services/unified_saved_items_service.dart';
import '../utils/image_utils.dart';

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
  List<InventoryItem> _inventoryResults = [];
  
  Timer? _searchDebounce;
  
  @override
  void initState() {
    super.initState();
    // Auto-focus the search field when the screen opens
    WidgetsBinding.instance.addPostFrameCallback((_) {
      FocusScope.of(context).requestFocus(_searchFocusNode);
      
      // Load all product directories to ensure they're in storage
      _loadAllProductDirectories();
    });
  }
  
  // Load products from all available directories
  Future<void> _loadAllProductDirectories() async {
    final apiService = _getApiService();
    if (apiService == null) return;
    
    try {
      // Get all available product directories
      final directories = await apiService.getProductDirectories();
      
      // Load products from each directory
      for (final directory in directories) {
        // Extract the directory name from the path (e.g., 'products/mbna_2025' -> 'mbna_2025')
        final dirName = directory.split('/').last;
        if (dirName.isNotEmpty) {
          try {
            await apiService.fetchProductImagesWithCodes(dirName);
          } catch (e) {
            debugPrint('Error loading products from $dirName: $e');
          }
        }
      }
    } catch (e) {
      debugPrint('Error loading product directories: $e');
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
        _inventoryResults = [];
        _hasResults = false;
      });
      return;
    }
    
    // Start a new debounce timer
    _searchDebounce = Timer(const Duration(milliseconds: 300), () {
      final trimmedQuery = query.trim();
      setState(() {
        _searchQuery = trimmedQuery;
        _isSearching = true;
      });
      
      // For any search, ensure all product directories are loaded first
      _loadAllProductDirectories().then((_) {
        // Reload all products to ensure we have the latest from storage
        _loadAllLocalProducts().then((products) {
          _performSearch(trimmedQuery);
        });
      });
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
            allProducts.addAll(secureStorageProducts);
          }
        } catch (storageError) {
          debugPrint('Error accessing secure storage: $storageError');
        }
      }
      
      // All products loaded successfully
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
    
    // Check if this is a search for special product codes
    bool isTargetSearch = query.contains('946') || query.contains('948');
    
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
        
        // Check if this is a target product we're specifically looking for
        bool isTargetProduct = id.contains('946') || id.contains('948');
        
        // Extract numeric part from ID if possible
        if (id.isNotEmpty) {
          final RegExp numericRegex = RegExp(r'\d+');
          final Match? numericMatch = numericRegex.firstMatch(id);
          if (numericMatch != null) {
            numericId = numericMatch.group(0) ?? "";
          }
        }
        
        // Match by name, description, or ID (full or numeric part)
        bool matches = name.contains(normalizedQuery) || 
                      description.contains(normalizedQuery) || 
                      id.contains(normalizedQuery);
        
        // No debug logs needed here
        
        // Direct match for product codes (case insensitive)
        if (!matches) {
          // Check if product ID equals the search query (ignoring case and with/without prefix)
          String normalizedId = id.toLowerCase().replaceAll(RegExp(r'[\s-]'), '');
          
          // No debug logs needed
          
          // Handle AG prefix in query or product ID
          if (normalizedId.startsWith("ag") && !normalizedQuery.startsWith("ag")) {
            // Query doesn't have prefix but product does - compare without prefix
            if (normalizedId.substring(2) == normalizedQuery) {
              matches = true;
              // Match found without AG prefix
            }
          } else if (!normalizedId.startsWith("ag") && normalizedQuery.startsWith("ag")) {
            // Query has prefix but product doesn't - compare without prefix
            if (normalizedId == normalizedQuery.substring(2)) {
              matches = true;
            }
          }
        }
        
        // Also check for numeric part matches (e.g., "950" matches "AG-950")
        if (!matches && numericId.isNotEmpty && normalizedQuery.isNotEmpty) {
          if (numericId.contains(normalizedQuery) || normalizedQuery.contains(numericId)) {
            matches = true;
          }
        }
        
        return matches;
      }).toList();
      
      if (matchingProducts.isNotEmpty) {
        setState(() {
          _productResults = matchingProducts;
        });
        hasAnyResults = hasAnyResults || matchingProducts.isNotEmpty;
      }
    } catch (e) {
      debugPrint('Error during search: $e');
    }
    
    // STEP 2: Search inventory items using InventoryService
    try {
      final inventoryService = _getInventoryService();

      // First search directly by query
      List<InventoryItem> inventoryResults = await inventoryService.fetchInventory(
        searchQuery: query,
        pageSize: 10000,
      );

      // If no results, try as a color search
      if (inventoryResults.isEmpty) {
        inventoryResults = await inventoryService.fetchInventory(
          color: query,
          pageSize: 10000,
        );
      }

      // If still none, try as a type search
      if (inventoryResults.isEmpty) {
        inventoryResults = await inventoryService.fetchInventory(
          type: query,
          pageSize: 10000,
        );
      }

      // Fetch all items once to collect colors and types
      final allItems = await inventoryService.fetchInventory(pageSize: 10000);

      final Set<String> colors = {};
      final Map<String, List<InventoryItem>> typeGroups = {};

      for (final item in allItems) {
        if (item.color.toLowerCase().contains(normalizedQuery)) {
          colors.add(item.color);
        }

        if (item.type.toLowerCase().contains(normalizedQuery)) {
          if (!typeGroups.containsKey(item.type)) {
            typeGroups[item.type] = [];
          }
        }

        if (colors.contains(item.color) || typeGroups.containsKey(item.type)) {
          inventoryResults.add(item);
        }
      }

      // Remove duplicates
      inventoryResults = inventoryResults.toSet().toList();

      // Populate type groups with items
      for (final item in allItems) {
        if (typeGroups.containsKey(item.type)) {
          typeGroups[item.type]!.add(item);
        }
      }

      if (inventoryResults.isNotEmpty || colors.isNotEmpty || typeGroups.isNotEmpty) {
        setState(() {
          _inventoryResults = inventoryResults;
          _colorResults = colors.toList();
          _typeGroupedResults = typeGroups;
        });

        hasAnyResults = true;
      }
    } catch (e) {
      debugPrint('Error searching inventory: $e');
    }
    
    // Update UI state based on search results
    setState(() {
      _hasResults = hasAnyResults;
      _searchQuery = query;
    });
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
          // Accessible search bar copied from the original search screen
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              focusNode: _searchFocusNode,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 16.0,
              ),
              decoration: InputDecoration(
                hintText: 'Search products, colors, inventory...',
                hintStyle: const TextStyle(color: Colors.white70),
                prefixIcon: const Icon(Icons.search, color: Colors.white),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear, color: Colors.white),
                        onPressed: () {
                          _searchController.clear();
                          _onSearchChanged('');
                        },
                      )
                    : null,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Colors.white30),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Colors.white30),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Colors.white),
                ),
                filled: true,
                fillColor: Colors.black.withOpacity(0.3),
              ),
              onChanged: _onSearchChanged,
              textInputAction: TextInputAction.search,
              onSubmitted: (_) => _searchFocusNode.unfocus(),
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
    
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_productResults.isNotEmpty)
            _buildProductSection('Products', _productResults),

          if (_colorResults.isNotEmpty)
            _buildColorsSection(),

          if (_typeGroupedResults.isNotEmpty)
            _buildInventoryTypeSection(),

          if (_inventoryResults.isNotEmpty)
            _buildInventoryItemsSection(),
        ],
      ),
    );
  }
  
  Widget _buildProductSection(String title, List<Product> products) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
          child: Text(
            '$title (${products.length})',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
        ),
        ExpansionTile(
          title: const Text('View Products'),
          initiallyExpanded: true,
          children: [
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: products.length,
              itemBuilder: (context, index) {
                return _buildProductTile(products[index]);
              },
            ),
          ],
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
        SizedBox(
          height: 50,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16.0),
            itemCount: _colorResults.length,
            itemBuilder: (context, index) {
              return _buildColorChip(_colorResults[index]);
            },
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
            return _buildTypeSection(type, items);
          },
        ),
      ],
    );
  }

  Widget _buildTypeSection(String type, List<InventoryItem> items) {
    return ExpansionTile(
      title: Text(
        '$type (${items.length})',
        style: const TextStyle(fontWeight: FontWeight.bold),
      ),
      initiallyExpanded: false,
      children: [
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: items.length,
          itemBuilder: (context, index) {
            return _buildInventoryItemTile(items[index]);
          },
        ),
      ],
    );
  }

  Widget _buildInventoryItemsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
          child: Text(
            'Inventory Items (${_inventoryResults.length})',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
        ),
        ExpansionTile(
          title: const Text('View Results'),
          initiallyExpanded: false,
          children: [
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _inventoryResults.length,
              itemBuilder: (context, index) {
                return _buildInventoryItemTile(_inventoryResults[index]);
              },
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildProductTile(Product product) {
    return ListTile(
      leading: product.imageUrl.isNotEmpty
          ? ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: ImageUtils.buildImage(
                imageUrl: product.imageUrl,
                width: 50,
                height: 50,
                fit: BoxFit.cover,
              ),
            )
          : Container(
              width: 50,
              height: 50,
              color: Colors.grey[300],
              child: const Icon(Icons.image_not_supported, color: Colors.grey),
            ),
      title: Text(product.name),
      subtitle: Text(product.description),
      onTap: () => _navigateToProductDetail(product),
    );
  }

  Widget _buildColorChip(String color) {
    return GestureDetector(
      onTap: () => _navigateToInventoryWithColorFilter(color),
      child: Container(
        margin: const EdgeInsets.only(right: 8),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.9),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.grey[300]!),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.1),
              blurRadius: 2,
              offset: const Offset(0, 1),
            ),
          ],
        ),
        child: Text(
          color,
          style: const TextStyle(
            color: Colors.black,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _buildInventoryItemTile(InventoryItem item) {
    return ListTile(
      title: Text(item.description),
      subtitle: Text('${item.code} • ${item.size} • ${item.color}'),
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            icon: const Icon(Icons.bookmark_border),
            onPressed: () {
              final itemMap = {
                'id': item.code,
                'code': item.code,
                'description': item.description,
                'color': item.color,
                'size': item.size,
                'location': item.location,
                'quantity': item.quantity,
                'type': item.type,
              };

              UnifiedSavedItemsService.saveItem(context, itemMap);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('${item.description} saved for later'),
                  duration: const Duration(seconds: 2),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.shopping_cart_outlined),
            onPressed: () => _showQuantityDialog(context, item),
          ),
        ],
      ),
      onTap: () => _navigateToInventoryItemDetail(item),
    );
  }

  void _addToCart(BuildContext context, InventoryItem item, int quantity) {
    try {
      final cartState = Provider.of<CartState>(context, listen: false);

      final itemMap = {
        'id': item.code,
        'code': item.code,
        'description': item.description,
        'color': item.color,
        'size': item.size,
        'location': item.location,
        'quantity': item.quantity,
        'type': item.type,
      };

      cartState.addItemWithQuantity(itemMap, quantity);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${quantity}x ${item.description} added to cart'),
          action: SnackBarAction(
            label: 'VIEW CART',
            onPressed: () {
              GoRouter.of(context).pushNamed(AppRouter.cart);
            },
          ),
          duration: const Duration(seconds: 2),
        ),
      );
    } catch (e) {
      debugPrint('Error adding to cart: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error adding to cart: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _showQuantityDialog(BuildContext context, InventoryItem item) {
    int quantity = 1;

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return AlertDialog(
              title: const Text('Select Quantity'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(item.description),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.remove),
                        onPressed: quantity > 1
                            ? () {
                                setState(() {
                                  quantity--;
                                });
                              }
                            : null,
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          quantity.toString(),
                          style: const TextStyle(fontSize: 16),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.add),
                        onPressed: () {
                          setState(() {
                            quantity++;
                          });
                        },
                      ),
                    ],
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.of(context).pop();
                  },
                  child: const Text('CANCEL'),
                ),
                TextButton(
                  onPressed: () {
                    _addToCart(context, item, quantity);
                    Navigator.of(context).pop();
                  },
                  child: const Text('ADD TO CART'),
                ),
              ],
            );
          },
        );
      },
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
