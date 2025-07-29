import 'dart:io';
import 'package:sqflite/sqflite.dart';

import '../app_database.dart';
import '../../models/product.dart';
import '../../services/image_cache_manager.dart';
import '../../config/offline_config.dart';

class ProductDao {
  final ImageCacheManager _imageCache = ImageCacheManager();

  Future<void> insertProducts(List<Product> products) async {
    final db = await AppDatabase.database;
    final batch = db.batch();
    for (final product in products) {
      final data = Map<String, dynamic>.from(product.toJson());
      // Remove fields not present in the products table
      data.remove('label');
      data.remove('pdfUrl');
      batch.insert(
        'products',
        data,
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    }
    await batch.commit(noResult: true);
  }

  Future<List<Product>> getAllProducts() async {
    final db = await AppDatabase.database;
    final maps = await db.query('products');
    return maps.map((e) => Product.fromJson(e)).toList();
  }

  Future<void> clearProducts() async {
    final db = await AppDatabase.database;
    final products = await db.query('products');
    for (final p in products) {
      final path = p['localImagePath'] as String?;
      if (path != null && path.isNotEmpty) {
        final file = File(path);
        if (await file.exists()) {
          await file.delete();
        }
      }
    }
    await db.delete('products');
  }

  Future<void> setLastSync(DateTime time) async {
    final db = await AppDatabase.database;
    await db.insert(
      'metadata',
      {'key': 'last_sync', 'value': time.millisecondsSinceEpoch.toString()},
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<DateTime?> getLastSync() async {
    final db = await AppDatabase.database;
    final result = await db.query('metadata', where: 'key=?', whereArgs: ['last_sync']);
    if (result.isNotEmpty) {
      final ts = int.tryParse(result.first['value'] as String);
      if (ts != null) {
        return DateTime.fromMillisecondsSinceEpoch(ts);
      }
    }
    return null;
  }

  Future<void> clearExpiredCache() async {
    final lastSync = await getLastSync();
    if (lastSync != null && DateTime.now().difference(lastSync) > OfflineConfig.maxCacheAge) {
      await clearProducts();
      final db = await AppDatabase.database;
      await db.delete('metadata', where: 'key=?', whereArgs: ['last_sync']);
      await _imageCache.clearAll();
    } else {
      await _imageCache.clearExpired();
    }
  }
}
