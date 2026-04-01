import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'dart:io';
import '../services/hybrid_asset_service.dart';

/// Utility class for handling image loading in a consistent way
/// Hybrid strategy: bundled assets (instant) + dynamically downloaded (auto-sync new)
class ImageUtils {
  static final _hybridService = HybridAssetService();
  
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