import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'dart:io';
import 'dart:convert';

/// Utility class for handling image loading in a consistent way
/// Prioritizes bundled assets for offline support, falls back to network
class ImageUtils {
  // Cache for product manifest
  static Map<String, dynamic>? _manifestCache;
  static bool _manifestLoaded = false;

  /// Load the product manifest from assets
  static Future<Map<String, dynamic>> _loadManifest() async {
    if (_manifestCache != null) return _manifestCache!;
    
    try {
      final manifestString = await rootBundle.loadString('assets/product_manifest.json');
      _manifestCache = json.decode(manifestString) as Map<String, dynamic>;
      _manifestLoaded = true;
      return _manifestCache!;
    } catch (e) {
      debugPrint('⚠️  Product manifest not found (images not bundled yet): $e');
      _manifestLoaded = true; // Mark as attempted
      _manifestCache = <String, dynamic>{'categories': <Map<String, dynamic>>[]};
      return _manifestCache!;
    }
  }

  /// Check if an image exists as a bundled asset
  /// Returns asset path if found, null otherwise
  static Future<String?> _findBundledAsset(String imageUrl) async {
    if (!_manifestLoaded) {
      await _loadManifest();
    }
    
    if (_manifestCache == null || _manifestCache!['categories'] == null) {
      return null;
    }
    
    try {
      // Extract filename from URL
      final uri = Uri.parse(imageUrl);
      final pathParts = uri.path.split('/');
      // URL decode the filename to handle spaces (e.g., "Baltic%20Green.jpg" -> "Baltic Green.jpg")
      final filename = Uri.decodeComponent(pathParts.last);
      
      // Try to find category from URL (usually in format: /products/CategoryName/image.jpg)
      String? categoryName;
      final productIndex = pathParts.indexOf('products');
      if (productIndex >= 0 && productIndex < pathParts.length - 1) {
        categoryName = pathParts[productIndex + 1];
      }
      
      // Search through manifest
      final categories = _manifestCache!['categories'] as List;
      for (final cat in categories) {
        final catMap = cat as Map<String, dynamic>;
        final safeName = catMap['safe_name'] as String;
        final images = catMap['images'] as List;
        
        // If we know the category, check only that category
        if (categoryName != null) {
          final normalizedCategory = categoryName.replaceAll('_', ' ').toLowerCase();
          final normalizedCatName = (catMap['name'] as String).toLowerCase();
          
          if (normalizedCategory != normalizedCatName && 
              safeName != categoryName) {
            continue;
          }
        }
        
        // Search for matching image
        for (final img in images) {
          final imgMap = img as Map<String, dynamic>;
          final assetFilename = imgMap['filename'] as String;
          
          // Check if filenames match (with or without extension variations)
          final filenameNoExt = filename.split('.').first;
          final assetNoExt = assetFilename.split('.').first;
          
          if (assetFilename == filename || assetNoExt == filenameNoExt) {
            final assetPath = imgMap['asset_path'] as String;
            
            // Verify asset exists
            try {
              await rootBundle.load(assetPath);
              return assetPath;
            } catch (e) {
              // Asset not found, continue searching
              continue;
            }
          }
        }
      }
    } catch (e) {
      debugPrint('⚠️  Error searching bundled assets: $e');
    }
    
    return null;
  }

  /// Determines if a path is a network URL
  static bool isNetworkImage(String path) {
    return path.startsWith('http://') || path.startsWith('https://');
  }

  /// Determines if a path points to a local file on disk
  static bool isFileImage(String path) {
    return path.startsWith('/') || path.startsWith('file://');
  }

  /// Returns the appropriate ImageProvider based on the path
  static ImageProvider getImageProvider(String path) {
    if (isNetworkImage(path)) {
      return NetworkImage(path);
    } else if (isFileImage(path)) {
      return FileImage(File(path));
    } else {
      // For local assets, use AssetImage
      return AssetImage(path);
    }
  }

  /// Creates a widget that displays an image from a URL or asset path
  /// with proper error handling and offline support
  /// 
  /// Strategy: 
  /// 1. Try bundled asset first (instant, offline-ready)
  /// 2. Fall back to network with caching (for new images not yet bundled)
  /// 3. Show error placeholder if both fail
  static Widget buildImage({
    required String imageUrl,
    double? width,
    double? height,
    BoxFit fit = BoxFit.cover,
    Widget? errorWidget,
    Widget? placeholderWidget,
    bool forceBundledOnly = false, // For testing offline mode
  }) {
    final defaultErrorWidget = Container(
      width: width,
      height: height,
      color: Colors.grey[300],
      child: const Icon(Icons.image_not_supported, color: Colors.grey),
    );

    // Handle empty URLs
    if (imageUrl.isEmpty) {
      return defaultErrorWidget;
    }

    // For network images: try bundled asset first, then network with caching
    if (isNetworkImage(imageUrl)) {
      return FutureBuilder<String?>(
        future: _findBundledAsset(imageUrl),
        builder: (context, snapshot) {
          // While checking for bundled asset, show placeholder
          if (snapshot.connectionState == ConnectionState.waiting) {
            return placeholderWidget ??
                Container(
                  width: width,
                  height: height,
                  color: Colors.grey[200],
                  child: const Center(
                    child: CircularProgressIndicator(),
                  ),
                );
          }
          // If bundled asset found, use it (offline-ready)
          if (snapshot.hasData && snapshot.data != null) {
            return Image.asset(
              snapshot.data!,
              width: width,
              height: height,
              fit: fit,
              errorBuilder: (context, error, stackTrace) {
                debugPrint('❌ Error loading bundled asset: $error');
                // Fall back to network if asset fails and not forcing bundled-only
                if (!forceBundledOnly) {
                  return _buildNetworkImage(
                    imageUrl, width, height, fit,
                    placeholderWidget, errorWidget ?? defaultErrorWidget,
                  );
                }
                return errorWidget ?? defaultErrorWidget;
              },
            );
          }
          
          // No bundled asset found
          // If forcing bundled-only (offline mode), show error
          if (forceBundledOnly) {
            return Container(
              width: width,
              height: height,
              color: Colors.grey[300],
              child: const Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.cloud_off, color: Colors.grey, size: 32),
                  SizedBox(height: 8),
                  Text('Offline', style: TextStyle(color: Colors.grey, fontSize: 12)),
                ],
              ),
            );
          }
          
          // Otherwise, fetch from network (for newly added images)
          return _buildNetworkImage(
            imageUrl, width, height, fit,
            placeholderWidget, errorWidget ?? defaultErrorWidget,
          );
        },
      );
    }
    // For asset images (already bundled)
    else {
      return Image.asset(
        imageUrl,
        width: width,
        height: height,
        fit: fit,
        errorBuilder: (context, error, stackTrace) {
          debugPrint('❌ Error loading asset image: $error');
          return errorWidget ?? defaultErrorWidget;
        },
      );
    }
  }

  /// Build network image with caching
  static Widget _buildNetworkImage(
    String imageUrl,
    double? width,
    double? height,
    BoxFit fit,
    Widget? placeholderWidget,
    Widget errorWidget,
  ) {
    return CachedNetworkImage(
      imageUrl: imageUrl,
      width: width,
      height: height,
      fit: fit,
      placeholder: (context, url) => placeholderWidget ??
          Container(
            width: width,
            height: height,
            color: Colors.grey[200],
            child: const Center(
              child: CircularProgressIndicator(),
            ),
          ),
      errorWidget: (context, url, error) {
        debugPrint('❌ Error loading cached network image: $error');
        return errorWidget;
      },
      memCacheWidth: width?.toInt(),
      memCacheHeight: height?.toInt(),
      maxWidthDiskCache: 800,
      maxHeightDiskCache: 800,
    );
  }
}