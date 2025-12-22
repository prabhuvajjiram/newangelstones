import 'package:flutter/material.dart';
import '../models/product.dart';
import '../models/inventory_item.dart';
import '../services/inventory_service.dart';
import '../utils/image_utils.dart';
import '../navigation/app_router.dart';
import 'package:go_router/go_router.dart';

class ProductDetailScreen extends StatefulWidget {
  final Product product;

  const ProductDetailScreen({super.key, required this.product});

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  final InventoryService _inventoryService = InventoryService();
  List<InventoryItem> _inventoryItems = [];
  bool _isLoadingInventory = true;
  
  // Static cache to share across all color detail screens
  static final Map<String, List<InventoryItem>> _colorInventoryCache = {};
  static final Map<String, DateTime> _cacheTimestamps = {};
  static const Duration _cacheDuration = Duration(minutes: 10);

  @override
  void initState() {
    super.initState();
    _loadInventoryForColor();
  }
  
  /// Check if cache is valid for a color
  bool _isCacheValid(String colorKey) {
    if (!_colorInventoryCache.containsKey(colorKey)) return false;
    if (!_cacheTimestamps.containsKey(colorKey)) return false;
    
    final age = DateTime.now().difference(_cacheTimestamps[colorKey]!);
    return age < _cacheDuration;
  }

  /// Normalize color name for matching (remove spaces, lowercase)
  String _normalizeColorName(String name) {
    return name.toLowerCase().replaceAll(' ', '').replaceAll('granite', '').trim();
  }

  /// Extract key color words from name (e.g., "Bahama Blue" -> ["bahama", "blue"])
  List<String> _extractColorWords(String name) {
    return name
        .toLowerCase()
        .replaceAll('granite', '')
        .split(' ')
        .where((word) => word.isNotEmpty && word.length > 2)
        .toList();
  }

  /// Load inventory items that match this color
  Future<void> _loadInventoryForColor() async {
    // Get the color name from the product
    final colorName = widget.product.name.toLowerCase();
    final normalizedColorName = _normalizeColorName(colorName);
    final cacheKey = normalizedColorName;
    
    // Check cache first
    if (_isCacheValid(cacheKey)) {
      setState(() {
        _inventoryItems = _colorInventoryCache[cacheKey]!;
        _isLoadingInventory = false;
      });
      return;
    }

    setState(() {
      _isLoadingInventory = true;
    });

    try {
      final colorWords = _extractColorWords(widget.product.name);

      // Try to load from local cache first
      List<InventoryItem> allItems = await _inventoryService.loadLocalInventory();
      
      // If local data is empty or incomplete, fetch from API
      if (allItems.isEmpty || allItems.first.code.isEmpty) {
        // Fetch from API with full details
        final searchTerm = colorWords.isNotEmpty ? colorWords.first : normalizedColorName;
        allItems = await _inventoryService.fetchInventory(
          searchQuery: searchTerm,
          pageSize: 200,
        );
      }

      // Filter items that match the color using flexible matching
      final matchingItems = allItems.where((item) {
        final itemColor = _normalizeColorName(item.color);
        final itemDesc = _normalizeColorName(item.description);
        final itemColorLower = item.color.toLowerCase();
        final itemDescLower = item.description.toLowerCase();
        
        // Strategy 1: Exact normalized match
        if (itemColor.contains(normalizedColorName) || 
            normalizedColorName.contains(itemColor)) {
          return true;
        }
        
        // Strategy 2: Check if all color words appear in item
        bool allWordsMatch = true;
        for (final word in colorWords) {
          if (!itemColorLower.contains(word) && !itemDescLower.contains(word)) {
            allWordsMatch = false;
            break;
          }
        }
        if (allWordsMatch && colorWords.isNotEmpty) {
          return true;
        }
        
        // Strategy 3: Check if any significant color word matches
        for (final word in colorWords) {
          if (word.length > 3) { // Only significant words
            if (itemColorLower.contains(word) || itemDescLower.contains(word)) {
              return true;
            }
          }
        }
        
        return false;
      }).toList();

      // Cache the results
      _colorInventoryCache[cacheKey] = matchingItems;
      _cacheTimestamps[cacheKey] = DateTime.now();

      setState(() {
        _inventoryItems = matchingItems;
        _isLoadingInventory = false;
      });
    } catch (e) {
      setState(() {
        _isLoadingInventory = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final screenHeight = MediaQuery.of(context).size.height;
    final screenWidth = MediaQuery.of(context).size.width;
    
    // Responsive sizing based on screen dimensions
    final bool isSmallScreen = screenHeight < 700 || screenWidth < 400;
    final double toolbarHeight = isSmallScreen ? 48.0 : 56.0;
    final double titleFontSize = isSmallScreen ? 14.0 : 16.0;
    final int maxTitleLength = isSmallScreen ? 20 : 30;
    
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: toolbarHeight,
        title: Text(
          widget.product.name.length > maxTitleLength ? 
            '${widget.product.name.substring(0, maxTitleLength)}...' : 
            widget.product.name,
          style: TextStyle(fontSize: titleFontSize),
        ),
        actions: const [],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Semantics(
              label: widget.product.name,
              child: ImageUtils.buildImage(
                imageUrl: widget.product.imageUrl,
                height: 200,
                fit: BoxFit.cover,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              widget.product.name,
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(widget.product.description),
            const SizedBox(height: 24),
            
            // Inventory items section
            if (_isLoadingInventory)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(32.0),
                  child: CircularProgressIndicator(),
                ),
              ),
            if (_inventoryItems.isNotEmpty) ...[
              const Divider(),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Available in Stock',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    '${_inventoryItems.length} items',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              ..._inventoryItems.map((item) => _buildInventoryCard(item)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildInventoryCard(InventoryItem item) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {
          context.pushNamed(
            AppRouter.inventoryItemDetails,
            extra: item,
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      item.description,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.green.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      'In Stock',
                      style: TextStyle(
                        color: Colors.green[700],
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.palette, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    item.color,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey[700],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Icon(Icons.straighten, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    item.size,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey[700],
                    ),
                  ),
                ],
              ),
              if (item.design.isNotEmpty) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(Icons.category, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Text(
                      item.design,
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[700],
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

