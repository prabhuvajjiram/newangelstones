import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../widgets/product_section.dart';

class HomeScreen extends StatefulWidget {
  final ApiService apiService;
  final StorageService storageService;

  const HomeScreen({super.key, required this.apiService, required this.storageService});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<List<Product>> _futureFeatured;
  late Future<List<Product>> _futureInventory;
  late Future<List<Product>> _futureSpecials;

  @override
  void initState() {
    super.initState();
    _futureFeatured =
        widget.apiService.loadLocalProducts('assets/featured_products.json');
    _futureInventory =
        widget.apiService.loadLocalProducts('assets/inventory.json');
    _futureSpecials =
        widget.apiService.loadLocalProducts('assets/specials.json');
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ProductSection(
            title: 'Flyers',
            future: _futureSpecials,
          ),
          ProductSection(
            title: 'Featured Products',
            future: _futureFeatured,
          ),
          ProductSection(
            title: 'Current Inventory',
            future: _futureInventory,
          ),
        ],
      ),
    );
  }
}
