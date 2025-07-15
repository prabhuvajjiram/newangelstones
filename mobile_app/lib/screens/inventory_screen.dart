import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../widgets/product_section.dart';

class InventoryScreen extends StatefulWidget {
  final ApiService apiService;
  const InventoryScreen({super.key, required this.apiService});

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  late Future<List<Product>> _futureInventory;

  @override
  void initState() {
    super.initState();
    _futureInventory = widget.apiService.loadLocalProducts('assets/inventory.json');
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: ProductSection(
        title: 'Current Inventory',
        future: _futureInventory,
      ),
    );
  }
}
