import 'dart:io';
import 'package:path_provider/path_provider.dart';
import '../config/offline_config.dart';

/// Manages caching of images on the local file system.
class ImageCacheManager {
  static const String _folder = 'image_cache';

  Future<Directory> _getCacheDir() async {
    final dir = await getApplicationDocumentsDirectory();
    final cacheDir = Directory('${dir.path}/$_folder');
    if (!await cacheDir.exists()) {
      await cacheDir.create(recursive: true);
    }
    return cacheDir;
  }

  /// Save the image bytes to a file and return the file path.
  Future<String> saveImage(String fileName, List<int> bytes) async {
    final dir = await _getCacheDir();
    final file = File('${dir.path}/$fileName');
    await file.writeAsBytes(bytes, flush: true);
    return file.path;
  }

  /// Delete images older than the configured TTL.
  Future<void> clearExpired() async {
    final dir = await _getCacheDir();
    final now = DateTime.now();
    if (await dir.exists()) {
      final files = dir.listSync();
      for (final f in files) {
        if (f is File) {
          final stat = await f.stat();
          if (now.difference(stat.modified) > OfflineConfig.maxCacheAge) {
            await f.delete();
          }
        }
      }
    }
  }

  /// Remove all cached images.
  Future<void> clearAll() async {
    final dir = await _getCacheDir();
    if (await dir.exists()) {
      await dir.delete(recursive: true);
    }
  }
}
