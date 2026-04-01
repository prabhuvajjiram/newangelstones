import 'package:flutter/foundation.dart';

/// Service to pre-populate image cache from bundled assets on first launch
class CachePreloaderService {
  
  /// Pre-load bundled images to cache directory
  /// This prevents unnecessary downloads of images already in the app bundle
  static Future<int> preloadBundledImages() async {
    try {
      debugPrint('🔄 Pre-loading bundled images to cache...');
      
      int copiedCount = 0;
      
      // Pre-load category product images from assets
      copiedCount += await _preloadCategoryImages();
      
      // Pre-load color images from assets
      copiedCount += await _preloadColorImages();
      
      debugPrint('✅ Pre-loaded $copiedCount images to cache');
      return copiedCount;
    } catch (e) {
      debugPrint('❌ Error pre-loading images: $e');
      return 0;
    }
  }
  
  /// Pre-load category product images from bundled assets
  static Future<int> _preloadCategoryImages() async {
    // All category images are bundled in assets/products/[category]/
    // No pre-loading needed — images are served directly from the asset bundle.
    debugPrint('📦 Category images will be served from bundled assets');
    return 0;
  }
  
  /// Pre-load color images from bundled assets
  static Future<int> _preloadColorImages() async {
    // Color images are bundled in assets/colors/ — no pre-loading needed.
    debugPrint('📦 Color images are bundled - no pre-loading needed');
    return 0;
  }
  
}
