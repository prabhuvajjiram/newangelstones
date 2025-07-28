import 'dart:async';

import '../database/dao/product_dao.dart';
import '../models/product.dart';
import '../models/sync_status.dart';
import 'api_service.dart';
import 'connectivity_service.dart';

class OfflineCatalogService {
  final ApiService apiService;
  final ConnectivityService connectivityService;
  final ProductDao _productDao = ProductDao();

  final StreamController<SyncStatus> _statusController =
      StreamController.broadcast();

  Stream<SyncStatus> get statusStream => _statusController.stream;

  OfflineCatalogService({required this.apiService, required this.connectivityService});

  Future<void> syncCatalog() async {
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
        final products = await apiService.fetchProductImagesWithCodes(dirName);
        await _productDao.insertProducts(products);
        count++;
        _statusController.add(SyncStatus(
            state: SyncState.syncing,
            progress: count / directories.length));
      }
      _statusController.add(SyncStatus(state: SyncState.success, progress: 1));
    } catch (e) {
      _statusController.add(SyncStatus(state: SyncState.error, message: e.toString()));
    }
  }

  Future<List<Product>> getAllProducts() {
    return _productDao.getAllProducts();
  }
}
