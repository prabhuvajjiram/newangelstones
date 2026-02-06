import 'dart:io';
import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import '../models/product.dart';
import 'api_service.dart';

/// Service to sync and cache images locally for better performance
class ImageSyncService {
  static const String _productsDir = 'products';
  static const String _colorsDir = 'colors';
  
  final ApiService _apiService;
  Completer<void>? _syncCompleter;
  
  ImageSyncService({required ApiService apiService}) : _apiService = apiService;
  
  /// Sync all images on app launch
  /// Only downloads missing or updated images (not already in assets)
  Future<void> syncAllImages() async {
    // Prevent concurrent syncs using Completer for thread-safe locking
    if (_syncCompleter != null && !_syncCompleter!.isCompleted) {
      debugPrint('⏭️ Image sync already in progress, skipping...');
      return;
    }
    
    _syncCompleter = Completer<void>();
    
    try {
      debugPrint('🔄 Starting image sync...');
      
      // Sync featured products images (only missing/new)
      await _syncFeaturedProductsImages();
      
      // Sync category images (benches, monuments, columbarium, designs, etc.)
      await _syncCategoryImages();
      
      // Sync color images
      await _syncColorImages();
      
      debugPrint('✅ Image sync completed successfully');
      _syncCompleter!.complete();
    } catch (e) {
      debugPrint('❌ Error during image sync: $e');
      if (!_syncCompleter!.isCompleted) {
        _syncCompleter!.completeError(e);
      }
    }
  }
  
  /// Check if image exists in assets
  Future<bool> _imageExistsInAssets(String fileName, String category) async {
    try {
      // Try category-based path first
      await rootBundle.load('assets/products/$category/$fileName');
      return true;
    } catch (e) {
      try {
        // Fallback to flat products path
        await rootBundle.load('assets/products/$fileName');
        return true;
      } catch (e2) {
        // For colors, try the colors directory
        if (category == 'colors') {
          try {
            await rootBundle.load('assets/colors/$fileName');
            return true;
          } catch (e3) {
            return false;
          }
        }
        return false;
      }
    }
  }
  
  /// Sync featured product images
  Future<void> _syncFeaturedProductsImages() async {
    try {
      debugPrint('� Syncing featured product images...');
      
      final featuredProducts = await _apiService.fetchFeaturedProducts();
      final cacheDir = await _getCacheDirectory(_productsDir);
      
      int downloaded = 0;
      int skipped = 0;
      int bundled = 0;
      
      for (final product in featuredProducts) {
        if (product.imageUrl.isEmpty) continue;
        
        final fileName = _extractFileName(product.imageUrl);
        
        try {
          // Priority 1: Check if image exists in bundled assets - SKIP if found
          final inAssets = await _imageExistsInAssets(fileName, 'products');
          if (inAssets) {
            bundled++;
            continue; // Skip download if in assets
          }
          
          // Priority 2: Check if image already cached and is valid - SKIP if valid
          final filePath = '${cacheDir.path}/$fileName';
          final file = File(filePath);
          
          if (await file.exists()) {
            final isValid = await _validateImageFile(file);
            if (isValid) {
              skipped++;
              continue; // Skip download if file is valid
            } else {
              // Delete invalid cached file and re-download
              await file.delete();
            }
          }
          
          // Priority 3: Download the image only if not bundled or cached
          await _downloadImage(product.imageUrl, filePath);
          downloaded++;
        } catch (e) {
          debugPrint('⚠️ Error syncing featured product: $e');
        }
      }
      
      debugPrint('✅ Featured images: $downloaded downloaded, $skipped cached, $bundled bundled');
    } catch (e) {
      debugPrint('❌ Error syncing featured products: $e');
    }
  }
  
  /// Sync category images (benches, monuments, columbarium, designs, etc.)
  /// Note: All category images are bundled in assets/products/[category]/, so we skip downloading
  Future<void> _syncCategoryImages() async {
    try {
      debugPrint('🔄 Syncing category images...');
      
      final categories = ['benches', 'monuments', 'columbarium', 'designs', 'mbna_2025'];
      
      int totalImages = 0;
      for (final category in categories) {
        try {
          final categoryImages = await _apiService.fetchProductImagesWithCodes(category);
          totalImages += categoryImages.length;
        } catch (e) {
          debugPrint('⚠️ Error syncing $category: $e');
        }
      }
      
      // All category images are bundled in assets/products/[category]/ - no need to download
      debugPrint('✅ Category images: 0 downloaded, 0 cached, $totalImages bundled');
    } catch (e) {
      debugPrint('❌ Error syncing category images: $e');
    }
  }
  
  /// Sync color images
  /// Note: All 40 color images are bundled in assets/colors/, so we skip downloading
  Future<void> _syncColorImages() async {
    try {
      debugPrint('🔄 Syncing color images...');
      
      final colors = await _apiService.fetchColors();
      
      // All color images are bundled in assets/colors/ - no need to download
      debugPrint('✅ Color images: 0 downloaded, 0 cached, ${colors.length} bundled');
    } catch (e) {
      debugPrint('❌ Error syncing color images: $e');
    }
  }
  
  /// Get local cache directory for images
  Future<Directory> _getCacheDirectory(String subDir) async {
    final appDir = await getApplicationDocumentsDirectory();
    final cacheDir = Directory('${appDir.path}/image_sync/$subDir');
    
    if (!await cacheDir.exists()) {
      await cacheDir.create(recursive: true);
    }
    
    return cacheDir;
  }
  
  /// Download image from URL to local file
  Future<void> _downloadImage(String imageUrl, String filePath) async {
    try {
      final response = await http.get(Uri.parse(imageUrl))
          .timeout(const Duration(seconds: 30));
      
      if (response.statusCode == 200) {
        // Validate response has content
        if (response.bodyBytes.isEmpty) {
          throw Exception('Downloaded file is empty');
        }
        
        final file = File(filePath);
        await file.writeAsBytes(response.bodyBytes);
        
        // Validate written file is not corrupted
        final isValid = await _validateImageFile(file);
        if (!isValid) {
          await file.delete();
          throw Exception('Downloaded file failed validation');
        }
        
        debugPrint('💾 Saved: $filePath');
      } else {
        throw Exception('Failed to download: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Download error: $e');
    }
  }
  
  /// Validate if cached image file is valid (not corrupted)
  /// Only reads first 12 bytes for efficiency
  Future<bool> _validateImageFile(File file) async {
    try {
      // Read only first 12 bytes needed for magic byte validation
      final bytes = await file.readAsBytes().then((fullBytes) {
        // Return only first 12 bytes to minimize memory usage
        return fullBytes.take(12).toList();
      });
      
      if (bytes.isEmpty) return false;
      
      // Check for common image magic bytes
      // JPEG: FF D8 FF
      if (bytes.length >= 3 && bytes[0] == 0xFF && bytes[1] == 0xD8 && bytes[2] == 0xFF) {
        return true;
      }
      // PNG: 89 50 4E 47
      if (bytes.length >= 4 && bytes[0] == 0x89 && bytes[1] == 0x50 && bytes[2] == 0x4E && bytes[3] == 0x47) {
        return true;
      }
      // GIF: 47 49 46
      if (bytes.length >= 3 && bytes[0] == 0x47 && bytes[1] == 0x49 && bytes[2] == 0x46) {
        return true;
      }
      // WebP: RIFF...WEBP
      if (bytes.length >= 12 && bytes[0] == 0x52 && bytes[1] == 0x49 && bytes[2] == 0x46 && bytes[3] == 0x46 &&
          bytes[8] == 0x57 && bytes[9] == 0x45 && bytes[10] == 0x42 && bytes[11] == 0x50) {
        return true;
      }
      
      return false;
    } catch (e) {
      debugPrint('⚠️ Invalid cache file: $e');
      return false;
    }
  }
  
  /// Extract filename from URL
  String _extractFileName(String url) {
    try {
      // Remove query parameters
      final cleanUrl = url.split('?').first;
      // Get last part of path
      return cleanUrl.split('/').last;
    } catch (e) {
      return 'image_${DateTime.now().millisecondsSinceEpoch}';
    }
  }
  
  /// Get local image path if cached, otherwise return original URL
  Future<String> getImagePath(String imageUrl, {String imageType = 'products'}) async {
    try {
      final fileName = _extractFileName(imageUrl);
      final subDir = imageType == 'colors' ? _colorsDir : _productsDir;
      
      final cacheDir = await _getCacheDirectory(subDir);
      final filePath = '${cacheDir.path}/$fileName';
      final file = File(filePath);
      
      if (await file.exists()) {
        return filePath; // Return local file path
      }
    } catch (e) {
      debugPrint('⚠️ Error getting cached image path: $e');
    }
    
    return imageUrl; // Return original URL as fallback
  }
  
  /// Clean up old/unused cached images
  Future<void> cleanupOldImages() async {
    try {
      debugPrint('🧹 Cleaning up old cached images...');
      
      final appDir = await getApplicationDocumentsDirectory();
      final cacheDir = Directory('${appDir.path}/image_sync');
      
      if (await cacheDir.exists()) {
        // Get all cached images
        final files = cacheDir.listSync(recursive: true);
        
        int deleted = 0;
        for (final file in files) {
          if (file is File) {
            try {
              // Delete files older than 30 days
              final lastModified = await file.lastModified();
              final age = DateTime.now().difference(lastModified);
              
              if (age.inDays > 30) {
                await file.delete();
                deleted++;
              }
            } catch (e) {
              debugPrint('⚠️ Error deleting old file: $e');
            }
          }
        }
        
        debugPrint('✅ Deleted $deleted old cached images');
      }
    } catch (e) {
      debugPrint('❌ Error cleaning up images: $e');
    }
  }
  
  /// Get cache size in MB
  Future<double> getCacheSize() async {
    try {
      final appDir = await getApplicationDocumentsDirectory();
      final cacheDir = Directory('${appDir.path}/image_sync');
      
      if (!await cacheDir.exists()) return 0.0;
      
      int totalBytes = 0;
      final files = cacheDir.listSync(recursive: true);
      
      for (final file in files) {
        if (file is File) {
          totalBytes += await file.length();
        }
      }
      
      return totalBytes / (1024 * 1024); // Convert to MB
    } catch (e) {
      debugPrint('⚠️ Error calculating cache size: $e');
      return 0.0;
    }
  }
}
