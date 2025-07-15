import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../widgets/flyer_section.dart';
import '../widgets/product_folder_section.dart';
import '../models/inventory_item.dart';
import '../services/inventory_service.dart';

class HomeScreen extends StatefulWidget {
  final ApiService apiService;
  final StorageService storageService;
  final InventoryService inventoryService;
  final VoidCallback onViewFullInventory;

  const HomeScreen({
    super.key,
    required this.apiService,
    required this.storageService,
    required this.inventoryService,
    required this.onViewFullInventory,
  });

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<List<Product>> _futureFeatured;
  late Future<List<InventoryItem>> _futureInventorySummary;
  late Future<List<Product>> _futureSpecials;

  @override
  void initState() {
    super.initState();
    _futureFeatured =
        widget.apiService.loadLocalProducts('assets/featured_products.json');
    _futureInventorySummary =
        widget.inventoryService.fetchInventory(pageSize: 3);
    _futureSpecials =
        widget.apiService.loadLocalProducts('assets/specials.json');
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          FlyerSection(
            title: 'Flyers',
            future: _futureSpecials,
          ),
          ProductFolderSection(
            title: 'Featured Products',
            future: _futureFeatured,
            apiService: widget.apiService,
          ),
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: FutureBuilder<List<InventoryItem>>( 
              future: _futureInventorySummary,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                } else if (snapshot.hasError) {
                  return Text('Error: ${snapshot.error}');
                } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
                  return const Text('No inventory found');
                }
                final items = snapshot.data!;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Latest Inventory',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 8),
                    ...items.map((e) => Text(e.code)).toList(),
                    TextButton(
                      onPressed: widget.onViewFullInventory,
                      child: const Text('View Full Inventory'),
                    ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
