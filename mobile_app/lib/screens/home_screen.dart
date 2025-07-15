import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../widgets/product_section.dart';
import 'cart_screen.dart';
import 'contact_screen.dart';

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

  @override
  void initState() {
    super.initState();
    _futureFeatured =
        widget.apiService.loadLocalProducts('assets/featured_products.json');
    _futureInventory =
        widget.apiService.loadLocalProducts('assets/inventory.json');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Angel Stones'),
        actions: [
          IconButton(
            icon: const Icon(Icons.shopping_cart),
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const CartScreen()));
            },
          ),
          IconButton(
            icon: const Icon(Icons.contact_page),
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const ContactScreen()));
            },
          )
        ],
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ProductSection(
              title: 'Featured Products',
              future: _futureFeatured,
            ),
            ProductSection(
              title: 'Current Inventory (Recently Updated)',
              future: _futureInventory,
            ),
          ],
        ),
      ),
    );
  }
}
