import 'package:flutter/material.dart';
import '../models/inventory_item.dart';
import '../services/inventory_service.dart';
import '../widgets/inventory_table_section.dart';

class InventoryScreen extends StatefulWidget {
  final InventoryService inventoryService;
  const InventoryScreen({super.key, required this.inventoryService});

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  late Future<List<InventoryItem>> _futureInventory;

  @override
  void initState() {
    super.initState();
    _futureInventory = widget.inventoryService.fetchInventory(pageSize: 100);
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: InventoryTableSection(
        title: 'Current Inventory',
        future: _futureInventory,
      ),
    );
  }
}
