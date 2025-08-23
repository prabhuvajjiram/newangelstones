import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../models/product.dart';
import '../models/inventory_item.dart';
import '../services/api_service.dart';
import '../services/inventory_service.dart';
import '../services/storage_service.dart';
import '../services/accessibility_service.dart';
import '../widgets/advanced_search_filters.dart';
import '../state/cart_state.dart';
import '../services/unified_saved_items_service.dart';
import '../utils/image_utils.dart';
import '../navigation/app_router.dart';

class SearchScreenV2 extends StatefulWidget {
  // Accept services directly
  final InventoryService? inventoryService;
  final ApiService? apiService;
  final StorageService? storageService;

  const SearchScreenV2({
    super.key,
    this.inventoryService,
    this.apiService,
    this.storageService,
  });

  @override
  State<SearchScreenV2> createState() => _SearchScreenV2State();
}

class _SearchScreenV2State extends State<SearchScreenV2> {
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _searchFocusNode = FocusNode();
  String _searchQuery = '';
  bool _hasResults = false;
  bool _isLoading = false;
  
  // Search results
  List<Product> _productResults = [];
  List<String> _colorResults = [];
  Map<String, List<InventoryItem>> _typeGroupedResults = {};
  
  Timer? _searchDebounce;
  
  // Advanced search filters
  SearchFilters _searchFilters = SearchFilters();
  List<String> _availableTypes = [];
  List<String> _availableColors = [];
  List<String> _availableLocations = [];
  List<String> _availableFinishes = [];
  
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      FocusScope.of(context).requestFocus(_searchFocusNode);
      _loadFilterOptions();
      _loadAllProductDirectories();
    });
  }
  
  // Load products from all available directories
  Future<void> _loadAllProductDirectories() async {
    final apiService = _getApiService();
    
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

  void _showAdvancedFilters() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.9,
        builder: (context, scrollController) => AdvancedSearchFilters(
          filters: _searchFilters,
          onFiltersChanged: (filters) {
            debugPrint('🔧 Filters changed: hasActiveFilters=${filters.hasActiveFilters}, activeCount=${filters.activeFilterCount}');
            setState(() {
              _searchFilters = filters;
            });
            _performSearch(_searchQuery.isEmpty ? '' : _searchQuery);
          },
          availableTypes: _availableTypes,
          availableColors: _availableColors,
          availableLocations: _availableLocations,
          availableFinishes: _availableFinishes,
        ),
      ),
    );
  }

  void _loadFilterOptions() async {
    try {
      if (widget.inventoryService != null) {
        final inventory = await widget.inventoryService!.fetchInventory();
        
        final types = <String>{};
        final colors = <String>{};
        final locations = <String>{};
        final finishes = <String>{};
        
        for (final item in inventory) {
          if (item.type.isNotEmpty) types.add(item.type);
          if (item.color.isNotEmpty) colors.add(item.color);
          if (item.location.isNotEmpty) locations.add(item.location);
          if (item.finish.isNotEmpty) finishes.add(item.finish);
        }
        
        setState(() {
          _availableTypes = types.toList()..sort();
          _availableColors = colors.toList()..sort();
          _availableLocations = locations.toList()..sort();
          _availableFinishes = finishes.toList()..sort();
        });
        
        debugPrint('📋 Loaded filter options:');
        debugPrint('  Types: ${_availableTypes.length} - ${_availableTypes.take(5).join(", ")}...');
        debugPrint('  Colors: ${_availableColors.length} - ${_availableColors.take(5).join(", ")}...');
        debugPrint('  Locations: ${_availableLocations.length} - ${_availableLocations.join(", ")}');
      }
    } catch (e) {
      debugPrint('❌ Error loading filter options: $e');
    }
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
        _isLoading = false;
        _productResults = [];
        _colorResults = [];
        _typeGroupedResults = {};
        _hasResults = false;
      });
      return;
    }
    
    // Start a new debounce timer
    _searchDebounce = Timer(const Duration(milliseconds: 300), () {
      final trimmedQuery = query.trim();
      setState(() {
        _searchQuery = trimmedQuery;
        _isLoading = true;
        _hasResults = false;
        _productResults = [];
        _colorResults = [];
        _typeGroupedResults = {};
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
    debugPrint('🔍 _performSearch called with query: "$query", hasActiveFilters: ${_searchFilters.hasActiveFilters}');
    
    if (query.isEmpty && !_searchFilters.hasActiveFilters) {
      debugPrint('🔍 No query and no filters - clearing results');
      setState(() {
        _productResults = [];
        _colorResults = [];
        _typeGroupedResults = {};
        _hasResults = false;
      });
      return;
    }
    
    setState(() {
      _hasResults = false;
    });
    
    bool hasAnyResults = false;
    final normalizedQuery = query.toLowerCase().trim();
    

    
    // STEP 1: Search for products in local storage (only if no filters are applied)
    if (!_searchFilters.hasActiveFilters) {
      try {
        // Load all products from local storage
        final allProducts = await _loadAllLocalProducts();
        
        // Filter products by search query
        final List<Product> matchingProducts = allProducts.where((product) {
          final name = product.name.toLowerCase();
          final description = product.description.toLowerCase();
          final id = product.id.toLowerCase();
          String numericId = '';
          
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
    } else {
      // Clear product results when filters are active
      setState(() {
        _productResults = [];
      });
    }
    
    // STEP 2: Search inventory items using InventoryService
    try {
      final inventoryService = _getInventoryService();

      // Fetch all inventory and apply filters
      List<InventoryItem> inventoryResults = await inventoryService.fetchInventory(
        pageSize: 10000,
      );
      
      // Apply text search and filters
      inventoryResults = inventoryResults.where((item) {
        // Text search
        bool matchesText = true;
        if (query.isNotEmpty) {
          final normalizedQuery = query.toLowerCase();
          matchesText = item.description.toLowerCase().contains(normalizedQuery) ||
                       item.code.toLowerCase().contains(normalizedQuery) ||
                       item.type.toLowerCase().contains(normalizedQuery) ||
                       item.color.toLowerCase().contains(normalizedQuery) ||
                       item.design.toLowerCase().contains(normalizedQuery) ||
                       item.finish.toLowerCase().contains(normalizedQuery) ||
                       item.size.toLowerCase().contains(normalizedQuery) ||
                       item.location.toLowerCase().contains(normalizedQuery);
        }
        
        // Apply advanced filters
        bool matchesFilters = true;
        if (_searchFilters.selectedType != null) {
          matchesFilters = matchesFilters && item.type == _searchFilters.selectedType;
        }
        if (_searchFilters.selectedColor != null) {
          matchesFilters = matchesFilters && item.color == _searchFilters.selectedColor;
        }
        if (_searchFilters.selectedLocation != null) {
          matchesFilters = matchesFilters && item.location == _searchFilters.selectedLocation;
        }
        if (_searchFilters.selectedFinish != null) {
          matchesFilters = matchesFilters && item.finish == _searchFilters.selectedFinish;
        }
        if (_searchFilters.inStockOnly) {
          matchesFilters = matchesFilters && item.quantity > 0;
        }
        
        return matchesText && matchesFilters;
      }).toList();
      
      debugPrint('🔍 Found ${inventoryResults.length} inventory items matching "$query" with filters');
      debugPrint('🔍 Active filters: type=${_searchFilters.selectedType}, color=${_searchFilters.selectedColor}, location=${_searchFilters.selectedLocation}, finish=${_searchFilters.selectedFinish}, inStock=${_searchFilters.inStockOnly}');

      final Set<String> colors = {};
      final Map<String, List<InventoryItem>> typeGroups = {};

      // First, collect all colors and types from matching items
      for (final item in inventoryResults) {
        if (item.color.isNotEmpty) {
          colors.add(item.color);
        }
        
        if (item.type.isNotEmpty) {
          if (!typeGroups.containsKey(item.type)) {
            typeGroups[item.type] = [];
          }
          typeGroups[item.type]!.add(item);
        }
      }
      
      // Update state with filtered results
      setState(() {
        _typeGroupedResults = typeGroups;
        _colorResults = colors.toList();
        _hasResults = inventoryResults.isNotEmpty || _productResults.isNotEmpty;
      });
      
      debugPrint('🔍 Updated UI state: _hasResults=$_hasResults, typeGroups=${typeGroups.length}, colors=${colors.length}, products=${_productResults.length}');
      
      if (inventoryResults.isNotEmpty) {
        hasAnyResults = true;
      }
    } catch (e) {
      debugPrint('Error searching inventory: $e');
    }
    
    // Update UI state based on search results
    setState(() {
      _hasResults = hasAnyResults;
      _searchQuery = query;
      _isLoading = false;
    });
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black, // Use dark theme background
      appBar: AppBar(
        title: const Text('Search'),
        elevation: 0,
        actions: [
          // Filter button with badge
          Stack(
            children: [
              IconButton(
                icon: const Icon(Icons.filter_list, color: Colors.white),
                onPressed: () {
                  AccessibilityService.provideFeedback();
                  _showAdvancedFilters();
                },
                tooltip: 'Open search filters',
              ),
              if (_searchFilters.hasActiveFilters)
                Positioned(
                  right: 8,
                  top: 8,
                  child: Container(
                    padding: const EdgeInsets.all(2),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.primary,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    constraints: const BoxConstraints(
                      minWidth: 16,
                      minHeight: 16,
                    ),
                    child: Text(
                      '${_searchFilters.activeFilterCount}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          // Accessible search bar copied from the original search screen
          Padding(
            padding: const EdgeInsets.all(16.0),
            child:
            Semantics(
              label: 'Search field for products, colors, and inventory items',
              child: TextField(
                controller: _searchController,
                focusNode: _searchFocusNode,
                style: const TextStyle(color: Colors.white),
                decoration: InputDecoration(
                  hintText: 'Search products, colors, or inventory...',
                  hintStyle: const TextStyle(color: Colors.white70),
                  prefixIcon: const Icon(Icons.search, color: Colors.white70),
                  suffixIcon: _searchQuery.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, color: Colors.white),
                          tooltip: 'Clear search',
                          onPressed: () {
                            AccessibilityService.provideFeedback();
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
                  fillColor: Colors.black.withValues(alpha: 0.3),
                ),
                onChanged: _onSearchChanged,
                textInputAction: TextInputAction.search,
                onSubmitted: (_) => _searchFocusNode.unfocus(),
              ),
            ),
          ),
          
          // Search results
          Expanded(
            child: (_searchQuery.isEmpty && !_searchFilters.hasActiveFilters)
                ? _buildEmptyState()
                : (_isLoading
                    ? const Center(child: CircularProgressIndicator())
                    : _buildSearchResults()),
          ),
        ],
      ),
    );
  }
  
  Widget _buildEmptyState() {
    return Center(
      child: Text(
        _searchFilters.hasActiveFilters 
          ? 'Apply filters to see results'
          : 'Enter a search term to find products, colors, or inventory items',
        style: const TextStyle(
          fontSize: 18,
          color: Colors.white,
          fontWeight: FontWeight.w600,
        ),
        textAlign: TextAlign.center,
      ),
    );
  }
  
  Widget _buildSearchResults() {
    if (!_hasResults) {
      String message;
      if (_searchQuery.isNotEmpty && _searchFilters.hasActiveFilters) {
        message = 'No results found for "$_searchQuery" with applied filters';
      } else if (_searchQuery.isNotEmpty) {
        message = 'No results found for "$_searchQuery"';
      } else if (_searchFilters.hasActiveFilters) {
        message = 'No results found with applied filters';
      } else {
        message = 'No results found';
      }
      
      return Center(
        child: Text(
          message,
          style: const TextStyle(color: Colors.white70),
          textAlign: TextAlign.center,
        ),
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
          color: Colors.white.withValues(alpha: 0.9),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.grey[300]!),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
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
