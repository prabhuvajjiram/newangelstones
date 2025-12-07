import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as path;

/// Hybrid asset management: bundled assets + dynamic downloads
/// 
/// Strategy:
/// 1. Load bundled assets instantly (offline-ready)
/// 2. Check server for new assets in background
/// 3. Download only new assets not in bundle
/// 4. Cache downloaded assets locally
/// 
/// This gives users instant access to bundled content while staying updated!
class HybridAssetService {
  static final HybridAssetService _instance = HybridAssetService._internal();
  factory HybridAssetService() => _instance;
  HybridAssetService._internal();

  // Bundled asset manifest (from compile-time bundled assets)
  Map<String, dynamic>? _bundledManifest;
  
  // Downloaded asset manifest (runtime downloads from server)
  Map<String, dynamic>? _downloadedManifest;
  
  bool _initialized = false;
  
  /// Directory for downloaded assets
  Directory? _downloadDir;

  /// Initialize the service
  Future<void> initialize() async {
    if (_initialized) return;
    
    try {
      // Load bundled manifest
      await _loadBundledManifest();
      
      // Setup download directory
      final appDir = await getApplicationDocumentsDirectory();
      _downloadDir = Directory(path.join(appDir.path, 'downloaded_assets'));
      if (!await _downloadDir!.exists()) {
        await _downloadDir!.create(recursive: true);
      }
      
      // Load downloaded manifest
      await _loadDownloadedManifest();
      
      _initialized = true;
      debugPrint('✅ HybridAssetService initialized');
      debugPrint('   Bundled: ${_getBundledImageCount()} images');
      debugPrint('   Downloaded: ${_getDownloadedImageCount()} images');
    } catch (e) {
      debugPrint('⚠️ Error initializing HybridAssetService: $e');
      _initialized = true; // Mark as initialized even on error
    }
  }

  /// Load the bundled product manifest
  Future<void> _loadBundledManifest() async {
    try {
      final manifestString = await rootBundle.loadString('assets/product_manifest.json');
      _bundledManifest = json.decode(manifestString) as Map<String, dynamic>;
    } catch (e) {
      debugPrint('⚠️ No bundled manifest found: $e');
      _bundledManifest = {'images': <dynamic>[]};
    }
  }

  /// Load the downloaded assets manifest
  Future<void> _loadDownloadedManifest() async {
    if (_downloadDir == null) return;
    
    try {
      final manifestFile = File(path.join(_downloadDir!.path, 'manifest.json'));
      if (await manifestFile.exists()) {
        final content = await manifestFile.readAsString();
        _downloadedManifest = json.decode(content) as Map<String, dynamic>;
      } else {
        _downloadedManifest = {'images': <dynamic>[], 'last_sync': null};
      }
    } catch (e) {
      debugPrint('⚠️ Error loading downloaded manifest: $e');
      _downloadedManifest = {'images': <dynamic>[], 'last_sync': null};
    }
  }

  /// Save the downloaded assets manifest
  Future<void> _saveDownloadedManifest() async {
    if (_downloadDir == null || _downloadedManifest == null) return;
    
    try {
      final manifestFile = File(path.join(_downloadDir!.path, 'manifest.json'));
      await manifestFile.writeAsString(
        const JsonEncoder.withIndent('  ').convert(_downloadedManifest),
      );
    } catch (e) {
      debugPrint('⚠️ Error saving downloaded manifest: $e');
    }
  }

  /// Get count of bundled images
  int _getBundledImageCount() {
    final images = _bundledManifest?['images'] as List?;
    return images?.length ?? 0;
  }

  /// Get count of downloaded images
  int _getDownloadedImageCount() {
    final images = _downloadedManifest?['images'] as List?;
    return images?.length ?? 0;
  }

  /// Check if an image is bundled with the app
  bool isBundled(String imageUrl) {
    if (_bundledManifest == null) return false;
    
    final images = _bundledManifest!['images'] as List?;
    if (images == null) return false;
    
    // Extract filename from URL
    final uri = Uri.parse(imageUrl);
    final filename = Uri.decodeComponent(uri.pathSegments.last.split('?').first);
    
    return images.any((img) {
      final imgName = (img as Map)['name'] as String?;
      return imgName == filename || imgName?.split('.').first == filename.split('.').first;
    });
  }

  /// Check if an image is downloaded (not bundled)
  bool isDownloaded(String imageUrl) {
    if (_downloadedManifest == null) return false;
    
    final images = _downloadedManifest!['images'] as List?;
    if (images == null) return false;
    
    final uri = Uri.parse(imageUrl);
    final filename = Uri.decodeComponent(uri.pathSegments.last.split('?').first);
    
    return images.any((img) {
      final imgName = (img as Map)['name'] as String?;
      return imgName == filename;
    });
  }

  /// Get local path for a bundled asset
  String? getBundledAssetPath(String imageUrl) {
    if (_bundledManifest == null) return null;
    
    final images = _bundledManifest!['images'] as List?;
    if (images == null) return null;
    
    final uri = Uri.parse(imageUrl);
    final filename = Uri.decodeComponent(uri.pathSegments.last.split('?').first);
    
    for (final img in images) {
      final imgMap = img as Map<String, dynamic>;
      final imgName = imgMap['name'] as String?;
      
      if (imgName == filename || imgName?.split('.').first == filename.split('.').first) {
        return imgMap['asset_path'] as String?;
      }
    }
    
    return null;
  }

  /// Get local path for a downloaded asset
  String? getDownloadedAssetPath(String imageUrl) {
    if (_downloadedManifest == null || _downloadDir == null) return null;
    
    final images = _downloadedManifest!['images'] as List?;
    if (images == null) return null;
    
    final uri = Uri.parse(imageUrl);
    final filename = Uri.decodeComponent(uri.pathSegments.last.split('?').first);
    
    for (final img in images) {
      final imgMap = img as Map<String, dynamic>;
      final imgName = imgMap['name'] as String?;
      
      if (imgName == filename) {
        final localPath = path.join(_downloadDir!.path, filename);
        final file = File(localPath);
        if (file.existsSync()) {
          return localPath;
        }
      }
    }
    
    return null;
  }

  /// Sync new assets from server (non-blocking background operation)
  /// Returns count of new assets downloaded
  Future<int> syncNewAssets() async {
    if (!_initialized) await initialize();
    
    try {
      debugPrint('🔄 Syncing new assets from server...');
      
      // Fetch server manifest
      final response = await http.get(
        Uri.parse('https://theangelstones.com/get_directory_files.php?directory=products'),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 10));
      
      if (response.statusCode != 200) {
        debugPrint('⚠️ Server returned ${response.statusCode}');
        return 0;
      }
      
      final data = json.decode(response.body) as Map<String, dynamic>;
      final serverFiles = data['files'] as List? ?? [];
      
      // Find new files not in bundle or already downloaded
      int newCount = 0;
      final newImages = <Map<String, dynamic>>[];
      
      for (final file in serverFiles) {
        final fileMap = file as Map<String, dynamic>;
        final filePath = fileMap['path'] as String?;
        
        if (filePath == null) continue;
        
        // Check if it's an image
        if (!filePath.toLowerCase().endsWith('.jpg') &&
            !filePath.toLowerCase().endsWith('.jpeg') &&
            !filePath.toLowerCase().endsWith('.png') &&
            !filePath.toLowerCase().endsWith('.webp')) {
          continue;
        }
        
        final url = 'https://theangelstones.com/$filePath';
        
        // Skip if already bundled or downloaded
        if (isBundled(url) || isDownloaded(url)) {
          continue;
        }
        
        // Download new image
        if (await _downloadImage(url)) {
          newCount++;
          newImages.add({
            'name': fileMap['name'],
            'url': url,
            'downloaded_at': DateTime.now().toIso8601String(),
          });
        }
      }
      
      // Update downloaded manifest
      if (newImages.isNotEmpty) {
        final currentImages = _downloadedManifest!['images'] as List;
        currentImages.addAll(newImages);
        _downloadedManifest!['last_sync'] = DateTime.now().toIso8601String();
        await _saveDownloadedManifest();
      }
      
      debugPrint('✅ Sync complete: $newCount new assets downloaded');
      return newCount;
      
    } catch (e) {
      debugPrint('⚠️ Error syncing assets: $e');
      return 0;
    }
  }

  /// Download a single image
  Future<bool> _downloadImage(String url) async {
    if (_downloadDir == null) return false;
    
    try {
      final uri = Uri.parse(url);
      final filename = Uri.decodeComponent(uri.pathSegments.last.split('?').first);
      final filePath = path.join(_downloadDir!.path, filename);
      final file = File(filePath);
      
      // Delete existing to prevent duplicates
      if (await file.exists()) {
        await file.delete();
      }
      
      final response = await http.get(Uri.parse(url)).timeout(
        const Duration(seconds: 30),
      );
      
      if (response.statusCode == 200) {
        await file.writeAsBytes(response.bodyBytes);
        debugPrint('   ✅ Downloaded: $filename');
        return true;
      } else {
        debugPrint('   ❌ Failed: $filename (${response.statusCode})');
        return false;
      }
    } catch (e) {
      debugPrint('   ❌ Error downloading image: $e');
      return false;
    }
  }

  /// Get all available asset URLs (bundled + downloaded)
  List<String> getAllAssetUrls() {
    final urls = <String>[];
    
    // Add bundled assets
    final bundledImages = _bundledManifest?['images'] as List?;
    if (bundledImages != null) {
      for (final img in bundledImages) {
        final url = (img as Map)['url'] as String?;
        if (url != null) urls.add(url);
      }
    }
    
    // Add downloaded assets
    final downloadedImages = _downloadedManifest?['images'] as List?;
    if (downloadedImages != null) {
      for (final img in downloadedImages) {
        final url = (img as Map)['url'] as String?;
        if (url != null) urls.add(url);
      }
    }
    
    return urls;
  }

  /// Clear all downloaded assets (keep bundled)
  Future<void> clearDownloaded() async {
    if (_downloadDir == null) return;
    
    try {
      if (await _downloadDir!.exists()) {
        await _downloadDir!.delete(recursive: true);
        await _downloadDir!.create(recursive: true);
      }
      
      _downloadedManifest = {'images': <dynamic>[], 'last_sync': null};
      await _saveDownloadedManifest();
      
      debugPrint('✅ Cleared downloaded assets');
    } catch (e) {
      debugPrint('⚠️ Error clearing downloaded assets: $e');
    }
  }

  /// Get sync status
  Map<String, dynamic> getSyncStatus() {
    return {
      'bundled_count': _getBundledImageCount(),
      'downloaded_count': _getDownloadedImageCount(),
      'total_count': _getBundledImageCount() + _getDownloadedImageCount(),
      'last_sync': _downloadedManifest?['last_sync'],
      'initialized': _initialized,
    };
  }
}
