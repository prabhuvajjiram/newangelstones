import 'package:flutter/material.dart';
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

    // For network images
    if (isNetworkImage(imageUrl)) {
      return Image.network(
        imageUrl,
        width: width,
        height: height,
        fit: fit,
        errorBuilder: (context, error, stackTrace) {
          debugPrint('❌ Error loading network image: $error');
          return errorWidget ?? defaultErrorWidget;
        },
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) return child;
          return placeholderWidget ??
              Container(
                width: width,
                height: height,
                color: Colors.grey[200],
                child: Center(
                  child: CircularProgressIndicator(
                    value: loadingProgress.expectedTotalBytes != null
                        ? loadingProgress.cumulativeBytesLoaded /
                            loadingProgress.expectedTotalBytes!
                        : null,
                  ),
                ),
              );
        },
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
