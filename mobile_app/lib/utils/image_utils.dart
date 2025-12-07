import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'dart:io';
import 'dart:convert';
import '../services/hybrid_asset_service.dart';

/// Utility class for handling image loading in a consistent way
/// Hybrid strategy: bundled assets (instant) + dynamically downloaded (auto-sync new)
class ImageUtils {
  static final _hybridService = HybridAssetService();
  
  // Cache for product manifest (fallback if hybrid service not initialized)
  static Map<String, dynamic>? _manifestCache;
  static bool _manifestLoaded = false;

  /// Initialize the hybrid asset service
  static Future<void> initialize() async {
    await _hybridService.initialize();
  }

  /// Get image paths from both bundled and downloaded sources
  static Future<Map<String, String?>> _getHybridImagePaths(String imageUrl) async {
    await _hybridService.initialize();
    
    return {
      'bundled': _hybridService.getBundledAssetPath(imageUrl),
      'downloaded': _hybridService.getDownloadedAssetPath(imageUrl),
    };
  }

  /// Sync new assets from server in background
  /// Call this periodically or on app start
  static Future<int> syncNewAssets() async {
    return await _hybridService.syncNewAssets();
  }

  /// Get sync status
  static Map<String, dynamic> getSyncStatus() {
    return _hybridService.getSyncStatus();
  }

  /// Load the product manifest from assets (fallback method)
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
  /// Hybrid Strategy:
  /// 1. Try bundled asset first (instant, offline-ready)
  /// 2. Try downloaded asset (from previous sync)
  /// 3. Fall back to network with caching (for brand new images)
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

    // For network images: use hybrid loading strategy
    if (isNetworkImage(imageUrl)) {
      return FutureBuilder<Map<String, String?>>(
        future: _getHybridImagePaths(imageUrl),
        builder: (context, snapshot) {
          // While checking for assets, show placeholder
          if (snapshot.connectionState == ConnectionState.waiting) {
            return placeholderWidget ??
                Container(
                  width: width,
                  height: height,
                  color: Colors.grey[200],
                  child: const Center(
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                );
          }
          
          if (snapshot.hasError) {
            debugPrint('❌ Error in hybrid image loading: ${snapshot.error}');
          }
          
          final paths = snapshot.data ?? {};
          final bundledPath = paths['bundled'];
          final downloadedPath = paths['downloaded'];
          
          // Priority 1: Use bundled asset (instant)
          if (bundledPath != null) {
            return Image.asset(
              bundledPath,
              width: width,
              height: height,
              fit: fit,
              errorBuilder: (context, error, stackTrace) {
                debugPrint('❌ Error loading bundled asset: $error');
                // Try downloaded or network
                if (downloadedPath != null) {
                  return Image.file(
                    File(downloadedPath),
                    width: width,
                    height: height,
                    fit: fit,
                    errorBuilder: (_, __, ___) => 
                        _buildNetworkImage(imageUrl, width, height, fit, placeholderWidget, errorWidget ?? defaultErrorWidget),
                  );
                }
                return _buildNetworkImage(imageUrl, width, height, fit, placeholderWidget, errorWidget ?? defaultErrorWidget);
              },
            );
          }
          
          // Priority 2: Use downloaded asset (from previous sync)
          if (downloadedPath != null) {
            return Image.file(
              File(downloadedPath),
              width: width,
              height: height,
              fit: fit,
              errorBuilder: (context, error, stackTrace) {
                debugPrint('❌ Error loading downloaded file: $error');
                // Fall back to network
                if (!forceBundledOnly) {
                  return _buildNetworkImage(imageUrl, width, height, fit, placeholderWidget, errorWidget ?? defaultErrorWidget);
                }
                return errorWidget ?? defaultErrorWidget;
              },
            );
          }
          
          // Priority 3: Load from network (will be cached by CachedNetworkImage)
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