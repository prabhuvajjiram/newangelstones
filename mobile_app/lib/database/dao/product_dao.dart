import 'package:sqflite/sqflite.dart';

import '../app_database.dart';
import '../../models/product.dart';

class ProductDao {
  Future<void> insertProducts(List<Product> products) async {
    final db = await AppDatabase.database;
    final batch = db.batch();
    for (final product in products) {
      batch.insert(
        'products',
        product.toJson(),
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
    await db.delete('products');
  }
}
