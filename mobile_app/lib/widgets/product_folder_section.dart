import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/directory_service.dart';
import 'package:go_router/go_router.dart';
import '../utils/error_utils.dart';
import 'skeleton_loaders.dart';

class ProductFolderSection extends StatelessWidget {
  final String title;
  final Future<List<Product>> future;
  final ApiService apiService;
  final DirectoryService directoryService;
  const ProductFolderSection({
    super.key,
    required this.title,
    required this.future,
    required this.apiService,
    required this.directoryService,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          FutureBuilder<List<Product>>(
            future: future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return SkeletonLoaders.productGrid(itemCount: 4);
              } else if (snapshot.hasError) {
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  showErrorSnackBar(context, 'Failed to load categories');
                });
                return const Text('Failed to load categories');
              } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
                return const Text('No items found');
              }
              final categories = snapshot.data!;
              return GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  childAspectRatio: 0.75,
                ),
                itemCount: categories.length,
                itemBuilder: (context, index) {
                  final product = categories[index];
                  return GestureDetector(
                    onTap: () {
                      context.push(
                        '/gallery/${product.id}?title=${Uri.encodeComponent(product.name)}',
                      );
                    },
                    child: Card(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Expanded(
                            child: Stack(
                              children: [
                                Positioned.fill(
                                  child: CachedNetworkImage(
                                    imageUrl: product.imageUrl,
                                    fit: BoxFit.cover,
                                    memCacheWidth: 300,
                                    memCacheHeight: 300,
                                    placeholder: (context, url) => SkeletonLoaders.productCard(height: double.infinity),
                                    errorWidget: (context, url, error) => Container(
                                      color: Colors.grey.shade200,
                                      child: const Icon(Icons.broken_image, color: Colors.grey),
                                    ),
                                    fadeInDuration: const Duration(milliseconds: 200),
                                    fadeOutDuration: const Duration(milliseconds: 100),
                                  ),
                                ),
                                // Design count banner removed as requested
                                // The API doesn't consistently provide accurate counts
                                // and showing '0 Designs' is confusing
                              ],
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.all(8.0),
                            child: Text(product.name, style: const TextStyle(fontSize: 16)),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              );
            },
          ),
        ],
      ),
    );
  }
}

