import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/offline_catalog_service.dart';
import '../widgets/offline_banner.dart';
import '../widgets/sync_progress_indicator.dart';

class OfflineCatalogScreen extends StatefulWidget {
  final OfflineCatalogService catalogService;
  const OfflineCatalogScreen({super.key, required this.catalogService});

  @override
  State<OfflineCatalogScreen> createState() => _OfflineCatalogScreenState();
}

class _OfflineCatalogScreenState extends State<OfflineCatalogScreen> {
  late Future<List<Product>> _productsFuture;

  @override
  void initState() {
    super.initState();
    _productsFuture = widget.catalogService.getAllProducts();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Offline Catalog')),
      body: Column(
        children: [
          OfflineBanner(connectivityService: widget.catalogService.connectivityService),
          SyncProgressIndicator(statusStream: widget.catalogService.statusStream),
          Expanded(
            child: FutureBuilder<List<Product>>(
              future: _productsFuture,
              builder: (context, snapshot) {
                if (!snapshot.hasData) {
                  return const Center(child: CircularProgressIndicator());
                }
                final products = snapshot.data!;
                if (products.isEmpty) {
                  return const Center(child: Text('No offline data'));
                }
                return ListView.builder(
                  itemCount: products.length,
                  itemBuilder: (context, index) {
                    final p = products[index];
                    return ListTile(
                      title: Text(p.name),
                      subtitle: Text(p.description),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () async {
          await widget.catalogService.syncCatalog();
          setState(() {
            _productsFuture = widget.catalogService.getAllProducts();
          });
        },
        child: const Icon(Icons.sync),
      ),
    );
  }
}
