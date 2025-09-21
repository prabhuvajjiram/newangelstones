import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';

class ImagePreloaderService {
  static final Set<String> _preloadedImages = <String>{};
  static const int _maxPreloadedImages = 50; // Limit to prevent memory issues

  /// Preload images for better performance
  static Future<void> preloadImages(BuildContext context, List<String> imageUrls) async {
    // Limit the number of images to preload
    final urlsToPreload = imageUrls.take(10).toList();
    
    for (final imageUrl in urlsToPreload) {
      if (_preloadedImages.contains(imageUrl)) continue;
      
      try {
        // Use CachedNetworkImage's cache manager to preload
        await CachedNetworkImage.evictFromCache(imageUrl);
        
        // Preload the image
        final imageProvider = CachedNetworkImageProvider(imageUrl);
        if (context.mounted) {
          await precacheImage(imageProvider, context);
          _preloadedImages.add(imageUrl);
        }
        
        // Limit cache size
        if (_preloadedImages.length > _maxPreloadedImages) {
          final oldestUrl = _preloadedImages.first;
          _preloadedImages.remove(oldestUrl);
        }
      } catch (e) {
        // Silently handle preload errors
        debugPrint('Failed to preload image: $imageUrl');
      }
    }
  }

  /// Preload a single image
  static Future<void> preloadImage(BuildContext context, String imageUrl) async {
    if (_preloadedImages.contains(imageUrl)) return;

    try {
      final imageProvider = CachedNetworkImageProvider(imageUrl);
      if (context.mounted) {
        await precacheImage(imageProvider, context);
        _preloadedImages.add(imageUrl);
      }
    } catch (e) {
      debugPrint('Failed to preload single image: $imageUrl');
    }
  }

  /// Check if an image is already preloaded
  static bool isPreloaded(String imageUrl) {
    return _preloadedImages.contains(imageUrl);
  }

  /// Clear preloaded images cache
  static void clearPreloadedCache() {
    _preloadedImages.clear();
  }

  /// Get cache statistics
  static Map<String, int> getCacheStats() {
    return {
      'preloaded_count': _preloadedImages.length,
      'max_cache_size': _maxPreloadedImages,
    };
  }
}
