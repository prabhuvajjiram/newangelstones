import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

/// Service to fetch product images by design code (AG-###, AS-###)
class ProductImageService {
  static const String _baseUrl = 'https://theangelstones.com';
  
  /// Cache for product images by category
  final Map<String, List<Map<String, String>>> _imageCache = {};
  
  /// Cache timestamps to track when data was fetched
  final Map<String, DateTime> _cacheTimestamps = {};
  
  /// Cache duration - 1 hour
  static const Duration _cacheDuration = Duration(hours: 1);
  
  /// Extract design code from design or description field
  String? _extractDesignCode(String text) {
    final regex = RegExp(r'\b(AG|AS)-?\d+\b', caseSensitive: false);
    final match = regex.firstMatch(text);
    return match?.group(0)?.toUpperCase();
  }
  
  /// Search for product images by design code (static method for compatibility)
  static Future<List<String>> searchProductImages(String designCode) async {
    final service = ProductImageService();
    return service.fetchImagesForDesignCode(designCode);
  }
  
  /// Fetch product images for a specific design code
  Future<List<String>> fetchImagesForDesignCode(String designCode) async {
    debugPrint('📸 Fetching images for design code: $designCode');
    
    // Normalize design code (remove hyphens, uppercase)
    final normalizedCode = designCode.toUpperCase().replaceAll('-', '');
    
    // Check all cached categories
    for (final category in _imageCache.keys) {
      final images = _imageCache[category]!;
      final matchingImages = images
          .where((img) {
            final imgCode = img['code']?.toUpperCase().replaceAll('-', '');
            return imgCode == normalizedCode;
          })
          .map((img) => img['url']!)
          .toList();
      
      if (matchingImages.isNotEmpty) {
        debugPrint('📸 Found ${matchingImages.length} images for $designCode');
        return matchingImages;
      }
    }
    
    // If not in cache, fetch from all categories in parallel
    final categories = ['monuments', 'Monuments', 'columbarium', 'designs', 'benches', 'mbna_2025'];
    
    // Fetch all categories in parallel for better performance
    await Future.wait(
      categories.map((category) => _fetchCategoryImages(category)),
      eagerError: false, // Continue even if some fail
    );
    
    // Now check all cached categories for the design code
    for (final category in categories) {
      if (_imageCache.containsKey(category)) {
        final images = _imageCache[category]!;
        final matchingImages = images
            .where((img) {
              final imgCode = img['code']?.toUpperCase().replaceAll('-', '');
              return imgCode == normalizedCode;
            })
            .map((img) => img['url']!)
            .toList();
        
        if (matchingImages.isNotEmpty) {
          debugPrint('📸 Found ${matchingImages.length} images for $designCode');
          return matchingImages;
        }
      }
    }
    
    debugPrint('📸 No images found for $designCode');
    return [];
  }
  
  /// Check if cache is still valid for a category
  bool _isCacheValid(String category) {
    if (!_imageCache.containsKey(category)) return false;
    if (!_cacheTimestamps.containsKey(category)) return false;
    
    final cacheAge = DateTime.now().difference(_cacheTimestamps[category]!);
    return cacheAge < _cacheDuration;
  }
  
  /// Fetch all images from a category directory
  Future<void> _fetchCategoryImages(String category) async {
    // Check if cache is still valid
    if (_isCacheValid(category)) {
      debugPrint('✅ Using cached images for $category');
      return;
    }
    
    try {
      debugPrint('🌐 Fetching category images from: $_baseUrl/get_directory_files.php?directory=products/$category');
      
      final response = await http.get(
        Uri.parse('$_baseUrl/get_directory_files.php?directory=products/$category'),
      );
      
      if (response.statusCode == 200) {
        final dynamic responseData = json.decode(response.body);
        final List<dynamic> files = responseData is Map && responseData['files'] != null 
            ? responseData['files'] as List<dynamic>
            : responseData as List<dynamic>;
        final List<Map<String, String>> images = [];
        
        for (final file in files) {
          if (file is Map<String, dynamic>) {
            // Handle both 'url' and 'path' fields
            final String? imageUrl = file['url'] as String? ?? file['path'] as String?;
            final String? name = file['name'] as String?;
            
            // Check if it's an image file (handle URLs with or without version parameters)
            final bool isImage = imageUrl != null && 
                (imageUrl.contains('.jpg') || imageUrl.contains('.png') || imageUrl.contains('.jpeg'));
            
            if (isImage) {
              // Build full URL if it's a relative path
              final String fullUrl = imageUrl.startsWith('http') 
                  ? imageUrl 
                  : '$_baseUrl/$imageUrl';
              
              // Extract design code from filename or name field
              final String filename = name ?? imageUrl.split('/').last.split('?').first;
              final code = _extractDesignCode(filename);
              
              if (code != null) {
                images.add({
                  'url': fullUrl,
                  'code': code,
                });
                debugPrint('🎯 Found target product in API response: $code');
                debugPrint('  Image URL: $fullUrl');
              }
            }
          }
        }
        
        _imageCache[category] = images;
        _cacheTimestamps[category] = DateTime.now(); // Mark cache time
        debugPrint('✅ Successfully loaded ${images.length} product images with codes');
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching category images: $e');
    }
  }
}
