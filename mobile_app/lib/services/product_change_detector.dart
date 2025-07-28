import '../models/inventory_item.dart';
import 'inventory_service.dart';
import '../models/notification_payload.dart';
import '../config/notification_config.dart';

class ProductChangeDetector {
  final InventoryService inventoryService;
  List<InventoryItem> _knownInventory = [];

  ProductChangeDetector({required this.inventoryService});

  Future<List<NotificationPayload>> checkForUpdates() async {
    final List<NotificationPayload> notifications = [];
    final currentInventory = await inventoryService.fetchInventory();

    final newItems = currentInventory
        .where((item) => !_knownInventory.any((p) => p.code == item.code))
        .toList();

    if (newItems.length >= NotificationConfig.inventoryGroupThreshold) {
      notifications.add(
        NotificationPayload(
          title: 'New inventory items',
          body: 'More than ${newItems.length} new items arrived!',
          deepLink: '/inventory',
        ),
      );
    }

    _knownInventory = currentInventory;
    return notifications;
  }
}
