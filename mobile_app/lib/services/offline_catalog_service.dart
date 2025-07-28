import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:path/path.dart' as p;

import '../database/dao/product_dao.dart';
import '../models/product.dart';
import '../models/sync_status.dart';
import 'api_service.dart';
import 'connectivity_service.dart';
import 'image_cache_manager.dart';

class OfflineCatalogService {
  final ApiService apiService;
  final ConnectivityService connectivityService;
  final ProductDao _productDao = ProductDao();
  final ImageCacheManager _imageCache = ImageCacheManager();

  final StreamController<SyncStatus> _statusController =
      StreamController.broadcast();

  Stream<SyncStatus> get statusStream => _statusController.stream;

  OfflineCatalogService({required this.apiService, required this.connectivityService});

  Future<void> syncCatalog() async {
    await _productDao.clearExpiredCache();
    final online = await connectivityService.isOnline;
    if (!online) {
      _statusController.add(SyncStatus(state: SyncState.error, message: 'Offline'));
      return;
    }
    _statusController.add(SyncStatus(state: SyncState.syncing, progress: 0));
    try {
      final directories = await apiService.getProductDirectories();
      int count = 0;
      for (final dir in directories) {
        final dirName = dir.split('/').last;
        final images = await apiService.fetchProductImagesWithCodes(dirName);
        final List<Product> products = [];
        for (final img in images) {
          String localPath = '';
          try {
            final response = await http.get(Uri.parse(img.imageUrl));
            if (response.statusCode == 200) {
              final fileName = p.basename(img.imageUrl);
              localPath = await _imageCache.saveImage(fileName, response.bodyBytes);
            }
          } catch (_) {}

          products.add(Product(
            id: img.productCode,
            name: 'Product ${img.productCode}',
            description: 'Offline product from $dirName',
            imageUrl: localPath.isNotEmpty ? localPath : img.imageUrl,
            price: 0.0,
            localImagePath: localPath,
            category: dirName,
          ));
        }

        await _productDao.insertProducts(products);
        count++;
        _statusController.add(SyncStatus(state: SyncState.syncing, progress: count / directories.length));
      }
      await _productDao.setLastSync(DateTime.now());
      _statusController.add(SyncStatus(state: SyncState.success, progress: 1));
    } catch (e) {
      _statusController.add(SyncStatus(state: SyncState.error, message: e.toString()));
    }
  }

  Future<List<Product>> getAllProducts() async {
    await _productDao.clearExpiredCache();
    return _productDao.getAllProducts();
  }

  Future<DateTime?> getLastSync() {
    return _productDao.getLastSync();
  }
}
