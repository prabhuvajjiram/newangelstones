import '../services/offline_catalog_service.dart';

class SyncWorker {
  final OfflineCatalogService catalogService;
  SyncWorker({required this.catalogService});

  Future<void> performSync() async {
    await catalogService.syncCatalog();
  }
}
