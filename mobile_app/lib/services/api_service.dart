import 'dart:convert';
import 'dart:async';
import 'dart:io';
import 'package:path/path.dart' as path;
import 'package:http/http.dart' as http;
import '../utils/secure_http_client.dart';
import '../config/security_config.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:flutter/foundation.dart';
import 'package:path_provider/path_provider.dart';
import '../models/product.dart';
import '../models/product_image.dart';
import 'storage_service.dart';

class ApiService {
  bool _isInitialized = false;
  final Map<String, List<String>> _categoryCache = {};
  List<Product>? _productCache;
  StorageService? _storageService;
  
  /// Initialize the API service with error handling and timeout
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      // Preload essential assets
      await Future.wait([
        _preloadAsset('assets/featured_products.json'),
        _preloadAsset('assets/colors.json'),
      ]).timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ Asset preloading timed out');
          return [];
        },
      );
      
      _isInitialized = true;
      debugPrint('✅ ApiService initialized successfully');
    } catch (e) {
      debugPrint('⚠️ ApiService initialization error: $e');
      // Mark as initialized anyway to prevent repeated init attempts
      _isInitialized = true;
    }
  }
  
  /// Preload an asset to ensure it's available
  Future<void> _preloadAsset(String assetPath) async {
    try {
      await rootBundle.loadString(assetPath);
      debugPrint('✅ Successfully preloaded $assetPath');
    } catch (e) {
      debugPrint('⚠️ Failed to preload $assetPath: $e');
      // Don't rethrow - we want to continue even if one asset fails
    }
  }
  late final SecureHttpClient _secureClient;
  
  ApiService({StorageService? storageService}) {
    _secureClient = SecureHttpClient();
    _secureClient.initialize();
    _storageService = storageService;
  }
  
  /// Set the storage service instance
  void setStorageService(StorageService storageService) {
    _storageService = storageService;
  }

  // Map to cache product images with their codes
  final Map<String, List<ProductImage>> _productImageCache = {};
  
  /// Extract product code from fullname
  String _extractProductCode(String fullname) {
    // Remove file extension if present
    final withoutExtension = fullname.split('.').first;
    // Extract product code (usually last part after slash or backslash)
    final parts = withoutExtension.split(RegExp(r'[/\\]'));
    final code = parts.last;
    
    // List of known product code prefixes
    final List<String> knownPrefixes = ['AG', 'AS'];
    
    // Normalize the code format for any known prefix (e.g., ensure PREFIX-XXX format is consistent)
    for (final prefix in knownPrefixes) {
      if (code.startsWith(prefix) && !code.startsWith('$prefix-') && code.length > prefix.length) {
        return '$prefix-${code.substring(prefix.length)}';
      }
    }
    
    return code;
  }
  
  /// Update the local featured_products.json file with the latest data
  Future<void> _updateLocalFeaturedProductsJson(List<Product> products) async {
    try {
      // Convert products to JSON format
      final List<dynamic> productsJson = products.map((product) => product.toJson()).toList();
      
      // Convert to JSON string with pretty printing
      final String jsonString = const JsonEncoder.withIndent('  ').convert(productsJson);
      
      // Get the application documents directory
      final directory = await getApplicationDocumentsDirectory();
      final path = '${directory.path}/featured_products.json';
      
      // Write the JSON string to the file
      final file = File(path);
      await file.writeAsString(jsonString);
      
      debugPrint('✅ Successfully updated local featured_products.json');
    } catch (e) {
      debugPrint('❌ Failed to update local featured_products.json: $e');
    }
  }

  Future<List<String>> fetchCategoryImages(String category) async {
  // ✅ Use cache if available
  if (_categoryCache.containsKey(category)) {
    debugPrint('📦 Using cached category images for: $category');
    return _categoryCache[category]!;
  }

  // ⏬ Fetch from network
  final productImages = await fetchProductImagesWithCodes(category);
  final imageUrls = productImages.map((img) => img.imageUrl).toList();

  // 🧠 Save to cache
  _categoryCache[category] = imageUrls;
  return imageUrls;
}

  
  Future<List<ProductImage>> fetchProductImagesWithCodes(String category, {String? searchQuery}) async {
    // If search query is provided, we'll filter the results later
    final bool isSearching = searchQuery != null && searchQuery.isNotEmpty;
    final String normalizedSearchQuery = isSearching ? searchQuery.toLowerCase() : '';
    
    // Use cache if available and not searching
    if (!isSearching && _productImageCache.containsKey(category)) {
      debugPrint('📦 Using cached product images for category: $category');
      return _productImageCache[category]!;
    }
    
    try {
      List<ProductImage> productImages;
      
      // If searching but we have the category cached, filter from cache
      if (isSearching && _productImageCache.containsKey(category)) {
        debugPrint('🔍 Filtering cached products for query: "$searchQuery"');
        productImages = _filterProductImages(_productImageCache[category]!, normalizedSearchQuery);
        return productImages;
      }
      
      // Otherwise fetch from network
      final url = '${SecurityConfig.angelStonesBaseUrl}/get_directory_files.php?directory=products/${SecurityConfig.sanitizeInput(category)}';
      debugPrint('🌐 Fetching category images from: $url');
      
      final response = await _secureClient.secureGet(url);
      
      if (response.statusCode == 200) {
        final Map<String, dynamic> jsonData = json.decode(response.body);
        final List<dynamic> files = jsonData['files'] ?? [];
        productImages = files
            .whereType<Map<String, dynamic>>()
            .map((e) => ProductImage(
                  imageUrl: '${SecurityConfig.angelStonesBaseUrl}/${e['path'] ?? ''}',
                  productCode: _extractProductCode(e['fullname'] ?? e['name'] ?? ''),
                ))
            .toList();
            
        // Save product images as products to secure storage
        if (_storageService != null && productImages.isNotEmpty) {
          try {
            // Convert ProductImage objects to Product objects for storage
            final products = productImages.map((image) => Product(
                  id: image.productCode,
                  name: 'Product ${image.productCode}',
                  description: 'Product ${image.productCode} from ${category} category',
                  imageUrl: image.imageUrl,
                  price: 0.0,
                )).toList();
                
            await _storageService!.initialize();
            await _storageService!.saveProducts(products);
            debugPrint('✅ Saved ${products.length} product images to secure storage');
            
            // Debug log for products with codes 946 or 948
            for (var product in products) {
              if (product.id.contains('946') || product.id.contains('948')) {
                debugPrint('🎯 Found target product in API response: ${product.id}');
                debugPrint('  Image URL: ${product.imageUrl}');
              }
            }
          } catch (e) {
            debugPrint('⚠️ Error saving product images to secure storage: $e');
          }
        }
        debugPrint('✅ Successfully loaded ${productImages.length} product images with codes');
        
        // Cache the full results
        _productImageCache[category] = productImages;
        
        // If searching, filter the results
        if (isSearching) {
          debugPrint('🔍 Filtering products for query: "$searchQuery"');
          return _filterProductImages(productImages, normalizedSearchQuery);
        }
        
        return productImages;
      } else {
        debugPrint('❌ Failed to load product images: ${response.statusCode}');
        throw Exception('Failed to load product images: ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching product images: $e');
      // Return empty list instead of throwing to prevent app crashes
      return [];
    }
  }
  
  /// Filter product images based on a search query
  List<ProductImage> _filterProductImages(List<ProductImage> images, String searchQuery) {
    if (searchQuery.isEmpty) return images;
    
    // Normalize the search query by removing spaces and dashes
    final normalizedQuery = searchQuery.toLowerCase().replaceAll(RegExp(r'[-\s]'), '');
    debugPrint('🔍 Searching with normalized query: "$normalizedQuery" (original: "$searchQuery")');
    
    return images.where((image) {
      final code = image.productCode.toLowerCase();
      // Normalize the product code by removing spaces and dashes
      final normalizedCode = code.replaceAll(RegExp(r'[-\s]'), '');
      
      // Case 1: Direct match (e.g., "ag950" matches normalized "AG-950")
      if (normalizedCode == normalizedQuery) {
        debugPrint('✅ Direct normalized match: $code → $normalizedCode for query "$searchQuery"');
        return true;
      }
      
      // Case 2: Normalized code contains query (e.g., "950" matches within normalized "AG950")
      if (normalizedCode.contains(normalizedQuery)) {
        debugPrint('✅ Normalized contains match: $code → $normalizedCode contains "$normalizedQuery"');
        return true;
      }
      
      // Case 3: Query contains normalized code (e.g., "ag950x" contains "ag950")
      if (normalizedQuery.contains(normalizedCode)) {
        debugPrint('✅ Query contains code match: "$normalizedQuery" contains $normalizedCode');
        return true;
      }
      
      // Case 4: Match without prefix (e.g., "950" matches "AG-950")
      if (code.contains('-')) {
        final parts = code.split('-');
        for (final part in parts) {
          if (part == searchQuery || normalizedQuery.contains(part)) {
            debugPrint('✅ Part match: $code (part: $part) for query "$searchQuery"');
            return true;
          }
        }
      }
      
      // Case 5: Numeric-only match (for queries like "950")
      if (RegExp(r'^\d+$').hasMatch(searchQuery)) {
        // Extract numeric part from product code
        final numericPart = code.replaceAll(RegExp(r'[^0-9]'), '');
        if (numericPart.contains(searchQuery) || searchQuery.contains(numericPart)) {
          debugPrint('✅ Numeric match: $code (numeric: $numericPart) for query "$searchQuery"');
          return true;
        }
      }
      
      // Case 6: Original contains match (for backward compatibility)
      if (code.contains(searchQuery)) {
        debugPrint('✅ Original contains match: $code contains "$searchQuery"');
        return true;
      }
      
      return false;
    }).toList();
  }

  Future<List<Product>> fetchProducts() async {
    if (_productCache != null) return _productCache!;
    try {
      final url = '${SecurityConfig.angelStonesBaseUrl}/api/color.json';
      debugPrint('🌐 Fetching products from: $url');
      
      final response = await _secureClient.secureGet(url);
      
      if (response.statusCode == 200) {
        final Map<String, dynamic> jsonData = json.decode(response.body);
        final List<dynamic> items = jsonData['itemListElement'] ?? [];
        final products = items
            .whereType<Map<String, dynamic>>()
            .map((e) => Product.fromJson(e['item'] as Map<String, dynamic>))
            .toList();
        debugPrint('✅ Successfully loaded ${products.length} products');
        _productCache = products;
        return products;
      } else {
        debugPrint('❌ Failed to load products: ${response.statusCode}');
        // Fall back to local data
        _productCache = await loadLocalProducts('assets/colors.json');
        return _productCache!;
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching products: $e');
      // Fall back to local data
      _productCache = await loadLocalProducts('assets/colors.json');
      return _productCache!;
    }
  }

  Future<List<Product>> loadLocalProducts(String assetPath) async {
    final data = await rootBundle.loadString(assetPath);
    final dynamic decoded = json.decode(data);
    List<dynamic> items;
    if (decoded is Map<String, dynamic> && decoded['itemListElement'] != null) {
      items = (decoded['itemListElement'] as List)
          .map((e) => e is Map<String, dynamic> ? e['item'] ?? e : e)
          .toList();
    } else if (decoded is List) {
      items = decoded;
    } else {
      items = [];
    }
    
    final products = items
        .whereType<Map<String, dynamic>>()
        .map((e) => Product.fromJson(e))
        .toList();
    
    // Save products to secure storage if available
    if (_storageService != null) {
      try {
        await _storageService!.initialize();
        await _storageService!.saveProducts(products);
        debugPrint('✅ Saved ${products.length} products to secure storage');
      } catch (e) {
        debugPrint('⚠️ Error saving products to secure storage: $e');
      }
    }
    
    return products;
  }
  
  // Cache for featured products
  List<Product>? _featuredProductsCache;
  
  // Cache for colors products
  List<Product>? _colorsCache;
  
  /// Get product directory paths from featured_products.json
  /// Returns a list of directory paths that should be searched for products
  Future<List<String>> getProductDirectories() async {
    try {
      // Get featured products which contain the category information
      final featuredProducts = await fetchFeaturedProducts();
      
      // Extract directory paths from product IDs
      // Convert IDs to lowercase for consistency in directory paths
      final List<String> directories = featuredProducts
          .map((product) => 'products/${product.id.toLowerCase()}')
          .toList();
      
      // Add special case for mbna_2025 which contains products like AG-946
      if (!directories.contains('products/mbna_2025')) {
        directories.add('products/mbna_2025');
      }
      
      debugPrint('📂 Found ${directories.length} product directories: ${directories.join(', ')}');
      return directories;
    } catch (e) {
      debugPrint('❌ Error getting product directories: $e');
      // Fallback to default directories if there's an error
      return ['products', 'products/benches', 'products/mbna_2025'];
    }
  }
  
  /// Fetch featured products from the server and update local JSON
  /// Returns the list of products (either from server or local fallback)
  /// Set forceRefresh to true to bypass the cache (used for pull-to-refresh)
  Future<List<Product>> fetchFeaturedProducts({bool forceRefresh = false}) async {
    debugPrint('🌐 Fetching featured products from server');
    
    // Return cached products if available and not forcing refresh
    if (_featuredProductsCache != null && !forceRefresh) {
      debugPrint('💾 Using cached featured products');
      return _featuredProductsCache!;
    }
    
    // Clear cache if forcing refresh
    if (forceRefresh) {
      debugPrint('🔄 Force refreshing featured products');
      _featuredProductsCache = null;
    }
    
    try {
      // First load local products to get the current structure
      final localProducts = await loadLocalProducts('assets/featured_products.json');
      final Map<String, Product> localProductMap = {};
      for (final product in localProducts) {
        localProductMap[product.id] = product;
      }
      
      // Instead of using a separate featured_products endpoint,
      // we'll fetch the main product categories (not all images)
      final url = '${SecurityConfig.angelStonesBaseUrl}/get_directory_files.php?directory=products';
      debugPrint('🌐 Fetching product categories');
      
      final response = await _secureClient.secureGet(url)
          .timeout(const Duration(seconds: 5));
      
      if (response.statusCode == 200) {
        final responseBody = utf8.decode(response.bodyBytes);
        debugPrint('✅ Successfully fetched featured products from server');
        
        // Parse the response
        final dynamic data = json.decode(responseBody);
        List<dynamic> items = [];
        
        // Handle directory files API response format
        if (data is Map<String, dynamic> && data['files'] != null && data['files'] is List) {
          // This is the get_directory_files.php API response format
          final filesList = data['files'] as List;
          debugPrint('📁 Found ${filesList.length} files in directory response');
          
          // Convert directory files to product format
          // For top-level categories, we only want directories, not individual files
          items = filesList.where((file) {
            // Only include directories, not individual image files
            if (file is Map<String, dynamic>) {
              final String path = file['path'] ?? '';
              final bool isDirectory = file['isDirectory'] == true;
              final bool isProductCategory = path.startsWith('products/') && 
                  !path.contains('.jpg') && !path.contains('.png') && 
                  path.split('/').length == 2;
              
              return isDirectory || isProductCategory;
            }
            return false;
          }).map((file) {
            if (file is Map<String, dynamic>) {
              final String path = file['path'] ?? '';
              final String name = file['name'] ?? path.split('/').last.replaceAll('.jpg', '').replaceAll('_', ' ');
              
              return {
                'id': path.split('/').last,
                'name': name,
                'description': 'Premium $name collection',
                'image': '${SecurityConfig.angelStonesBaseUrl}/$path',
                'price': 0.0, // Price not available in directory listing
                'featured': true
              };
            }
            return file;
          }).toList();
          
          debugPrint('🏷️ Filtered to ${items.length} product categories');
          
          debugPrint('💾 Converted ${items.length} directory files to featured products');
        }
        // Handle inventory API response format
        else if (data is Map<String, dynamic> && data['Data'] != null && data['Data'] is Map<String, dynamic>) {
          final dataMap = data['Data'] as Map<String, dynamic>;
          if (dataMap['data'] != null && dataMap['data'] is List) {
            // Extract items from inventory data
            final inventoryItems = dataMap['data'] as List;
            
            // Convert inventory items to product format
            items = inventoryItems.map((item) {
              if (item is Map<String, dynamic>) {
                return {
                  'id': item['ProductId'] ?? item['Id'] ?? 'unknown-${DateTime.now().millisecondsSinceEpoch}',
                  'name': item['ProductDescription'] ?? item['EndProductDescription'] ?? 'Unknown Product',
                  'description': item['EndProductDescription'] ?? '',
                  'image': item['ImageUrl'] ?? 'assets/images/placeholder.png',
                  'price': item['Price'] ?? 0.0,
                };
              }
              return item;
            }).toList();
            
            debugPrint('💾 Converted ${items.length} inventory items to featured products');
          }
        } else if (data is Map<String, dynamic> && data['itemListElement'] != null) {
          items = (data['itemListElement'] as List)
              .map((e) => e is Map<String, dynamic> ? e['item'] ?? e : e)
              .toList();
        } else if (data is List) {
          items = data;
        } else {
          debugPrint('⚠️ Unexpected API response format: ${data.runtimeType}');
          throw Exception('Invalid response format');
        }
        
        // Convert to products
        final serverProducts = items
            .whereType<Map<String, dynamic>>()
            .map((e) => Product.fromJson(e))
            .toList();
        
        // Merge server products with local products, ensuring uniqueness by name
        final List<Product> mergedProducts = [];
        final Map<String, Product> serverProductMap = {};
        final Set<String> categoryNames = {}; // Track unique category names
        
        // Create a map of server products by ID
        for (final product in serverProducts) {
          serverProductMap[product.id] = product;
          // Only add if we haven't seen this category name before
          if (!categoryNames.contains(product.name.toLowerCase())) {
            categoryNames.add(product.name.toLowerCase());
            mergedProducts.add(product);
          } else {
            debugPrint('🔍 Skipping duplicate category: ${product.name}');
          }
        }
        
        // Then add any local products that weren't in the server response
        // and aren't duplicates of categories we already have
        for (final localProduct in localProducts) {
          if (!serverProductMap.containsKey(localProduct.id) && 
              !categoryNames.contains(localProduct.name.toLowerCase())) {
            categoryNames.add(localProduct.name.toLowerCase());
            mergedProducts.add(localProduct);
          }
        }
        
        // Cache the merged products
        _featuredProductsCache = mergedProducts;
        
        // Update the local JSON file with the latest data
        _updateLocalFeaturedProductsJson(mergedProducts);
        
        // Return the merged products
        return mergedProducts;
      } else {
        debugPrint('❌ Failed to fetch featured products: ${response.statusCode}');
        throw Exception('Failed to fetch featured products');
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching featured products: $e');
      // Fall back to local data
      return loadLocalProducts('assets/featured_products.json');
    }
  }
  
  /// Fetch colors from the enhanced website API and update local JSON
  /// Returns the list of products (either from server or local fallback)
  /// Set forceRefresh to true to bypass the cache (used for pull-to-refresh)
  Future<List<Product>> fetchColors({bool forceRefresh = false}) async {
    debugPrint('🌐 Fetching colors from enhanced website API');
    
    // Return cached products if available and not forcing refresh
    if (_colorsCache != null && !forceRefresh) {
      debugPrint('💾 Using cached colors');
      return _colorsCache!;
    }
    
    // Clear cache if forcing refresh
    if (forceRefresh) {
      debugPrint('🔄 Force refreshing colors');
      _colorsCache = null;
    }
    
    try {
      // First load local products as fallback
      await loadLocalProducts('assets/colors.json'); // Load but don't need to store the result
      
      // Try to fetch from enhanced website API endpoint
      // Note: Change to get_color_images_enhanced.php when deployed to production
      final url = 'https://theangelstones.com/get_color_images.php';
      final response = await http.get(Uri.parse(url))
          .timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200) {
        final responseBody = utf8.decode(response.bodyBytes);
        debugPrint('✅ Successfully fetched colors from website API');
        
        // Parse the response
        final dynamic data = json.decode(responseBody);
        
        if (data is Map<String, dynamic> && data['colors'] != null && data['success'] == true) {
          List<Product> serverProducts = [];
          Map<String, dynamic>? schemaData;
          
          // Check if the enhanced API is being used (has schema field)
          if (data['schema'] != null && data['schema'] is Map<String, dynamic>) {
            // Use the schema data directly from the enhanced API
            schemaData = data['schema'] as Map<String, dynamic>;
            
            // Convert schema items to products
            final List<dynamic> itemListElements = schemaData['itemListElement'] as List? ?? [];
            serverProducts = itemListElements
                .where((e) => e is Map<String, dynamic> && e['item'] != null)
                .map((e) => Product.fromJson(e['item'] as Map<String, dynamic>))
                .toList();
                
            debugPrint('✅ Using enhanced API schema data with ${serverProducts.length} products');
          } else {
            // Legacy API format - build schema ourselves
            final List<dynamic> colorItems = data['colors'] as List;
            debugPrint('⚠️ Using legacy API format with ${colorItems.length} colors');
            
            // Create a schema.org compatible structure for the local JSON
            schemaData = {
              "@context": "https://schema.org",
              "@type": "ItemList",
              "name": "Angel Stones Granite Color Varieties",
              "description": "Explore our premium granite colors for monuments and headstones.",
              "itemListOrder": "Unordered",
              "url": "https://www.theangelstones.com/colors",
              "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": "https://www.theangelstones.com/colors"
              },
              "itemListElement": []
            };
            
            // Convert API color items to schema.org format
            List<Map<String, dynamic>> itemListElements = [];
            int position = 1;
            
            for (var colorItem in colorItems) {
              if (colorItem is Map<String, dynamic>) {
                final String name = colorItem['name'] ?? 'Unknown Color';
                final String path = colorItem['path'] ?? '';
                final String description = colorItem['description'] ?? 
                    "Premium $name granite for monuments and memorials.";
                
                // Create schema.org ListItem
                final Map<String, dynamic> listItem = {
                  "@type": "ListItem",
                  "position": position++,
                  "item": {
                    "@type": "Product",
                    "name": "$name Granite",
                    "description": description,
                    "image": [
                      {
                        "@type": "ImageObject",
                        "url": "https://www.theangelstones.com/$path",
                        "width": "800",
                        "height": "800",
                        "caption": "$name Granite Color Sample"
                      }
                    ],
                    "category": ["Granite Colors", "Memorial Stones"],
                    "material": "Granite",
                    "additionalProperty": [
                      {
                        "@type": "PropertyValue",
                        "name": "Material Type",
                        "value": "Granite"
                      },
                      {
                        "@type": "PropertyValue",
                        "name": "Color",
                        "value": name
                      }
                    ]
                  }
                };
                
                itemListElements.add(listItem);
              }
            }
            
            // Update the schema data with the new items
            schemaData['itemListElement'] = itemListElements;
            schemaData['numberOfItems'] = itemListElements.length;
            
            // Convert to products
            serverProducts = itemListElements
                .map((e) => Product.fromJson(e['item'] as Map<String, dynamic>))
                .toList();
          }
          
          // Update local JSON file with new data
          await _updateLocalColorsJson(schemaData);
          
          // Cache the products
          _colorsCache = serverProducts;
          
          // Return the products
          return serverProducts;
        } else {
          debugPrint('⚠️ Unexpected API response format: ${data.runtimeType}');
          throw Exception('Invalid response format');
        }
      } else {
        debugPrint('❌ Failed to fetch colors: ${response.statusCode}');
        throw Exception('Failed to fetch colors');
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching colors: $e');
      // Fall back to local data
      final localProducts = await loadLocalProducts('assets/colors.json');
      _colorsCache = localProducts;
      return localProducts;
    }
  }
  
  /// Update the local colors JSON file with new data from the API
  Future<void> _updateLocalColorsJson(Map<String, dynamic> data) async {
    try {
      final directory = await getApplicationDocumentsDirectory();
      // Use path.join to properly construct file paths and avoid URI issues
      final file = File(path.join(directory.path, 'colors.json'));
      final jsonString = json.encode(data);
      await file.writeAsString(jsonString);
      debugPrint('✅ Successfully updated local colors JSON file');
    } catch (e) {
      debugPrint('⚠️ Error updating local colors JSON file: $e');
    }
  }
}
