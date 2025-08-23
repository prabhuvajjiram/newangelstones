import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'dart:io';

/// Utility class for handling image loading in a consistent way
class ImageUtils {
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
  /// with proper error handling
  static Widget buildImage({
    required String imageUrl,
    double? width,
    double? height,
    BoxFit fit = BoxFit.cover,
    Widget? errorWidget,
    Widget? placeholderWidget,
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

    // For network images with caching
    if (isNetworkImage(imageUrl)) {
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
          return errorWidget ?? defaultErrorWidget;
        },
        memCacheWidth: width?.toInt(),
        memCacheHeight: height?.toInt(),
        maxWidthDiskCache: 800,
        maxHeightDiskCache: 800,
      );
    }
    // For asset images
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
}
