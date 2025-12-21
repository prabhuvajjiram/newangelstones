import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

/// Service to fetch product images by design code (AG-###, AS-###)
/// Learned from desktop implementation:
/// 1. Extract design codes from both design field AND description
/// 2. Cache results to avoid repeated API calls
/// 3. API returns MIME types, not 'file' type
class ProductImageService {
  static const String _baseUrl = 'https://theangelstones.com';
  
  // Cache to store image results by design code
  static final Map<String, List<ProductImage>> _imageCache = {};
  
  /// Extract design code from inventory item
  /// Checks both design field and description (AG-###, AS-###)
  static String? extractDesignCode(String? design, String? description) {
    // Try design field first
    if (design != null && design.isNotEmpty) {
      final match = RegExp(r'\b(AG|AS)-?\d+\b', caseSensitive: false).firstMatch(design);
      if (match != null) {
        return match.group(0)!.toUpperCase();
      }
    }
    
    // Fall back to description field
    if (description != null && description.isNotEmpty) {
      final match = RegExp(r'\b(AG|AS)-?\d+\b', caseSensitive: false).firstMatch(description);
      if (match != null) {
        return match.group(0)!.toUpperCase();
      }
    }
    
    return null;
  }
  
  /// Search for product images by design code
  /// Returns list of images or empty list if none found
  static Future<List<ProductImage>> searchProductImages(String designCode) async {
    if (designCode.isEmpty) {
      return [];
    }
    
    // Check cache first
    if (_imageCache.containsKey(designCode)) {
      debugPrint('📸 Image cache hit for $designCode');
      return _imageCache[designCode]!;
    }
    
    try {
      debugPrint('📸 Fetching images for design code: $designCode');
      
      final url = Uri.parse('$_baseUrl/get_directory_files.php?search=${Uri.encodeComponent(designCode)}');
      final response = await http.get(url).timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          throw Exception('Image search timeout');
        },
      );
      
      if (response.statusCode != 200) {
        debugPrint('❌ Image search failed: ${response.statusCode}');
        _imageCache[designCode] = [];
        return [];
      }
      
      final data = json.decode(response.body) as Map<String, dynamic>;
      
      if (data['success'] != true || data['files'] == null) {
        debugPrint('📸 No images found for $designCode');
        _imageCache[designCode] = [];
        return [];
      }
      
      final files = data['files'] as List<dynamic>;
      final images = <ProductImage>[];
      final seenPaths = <String>{};
      
      for (final file in files) {
        final fileMap = file as Map<String, dynamic>;
        final path = fileMap['path'] as String?;
        
        // Deduplicate by path
        if (path != null && path.isNotEmpty && !seenPaths.contains(path)) {
          seenPaths.add(path);
          images.add(ProductImage(
            path: '$_baseUrl/$path',
            name: fileMap['name'] as String? ?? '',
            category: fileMap['category'] as String? ?? '',
          ));
        }
      }
      
      debugPrint('📸 Found ${images.length} images for $designCode');
      
      // Cache the result (even if empty to avoid repeated failed searches)
      _imageCache[designCode] = images;
      
      return images;
    } catch (e) {
      debugPrint('❌ Error searching images for $designCode: $e');
      // Cache empty result to avoid retry
      _imageCache[designCode] = [];
      return [];
    }
  }
  
  /// Clear the image cache
  static void clearCache() {
    _imageCache.clear();
    debugPrint('🗑️ Image cache cleared');
  }
  
  /// Get cache statistics
  static Map<String, int> getCacheStats() {
    return {
      'cachedDesignCodes': _imageCache.length,
      'totalImages': _imageCache.values.fold(0, (sum, images) => sum + images.length),
    };
  }
}

/// Model for product image
class ProductImage {
  final String path;
  final String name;
  final String category;
  
  ProductImage({
    required this.path,
    required this.name,
    required this.category,
  });
  
  @override
  String toString() => 'ProductImage(path: $path, name: $name, category: $category)';
}
