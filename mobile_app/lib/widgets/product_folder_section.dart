import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/directory_service.dart';
import 'package:go_router/go_router.dart';
import '../utils/error_utils.dart';

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
                return const Center(child: CircularProgressIndicator());
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
                                  child: Image.network(
                                    product.imageUrl,
                                    fit: BoxFit.cover,
                                    cacheWidth: 300,
                                    cacheHeight: 300,
                                    loadingBuilder: (context, child, loadingProgress) {
                                      if (loadingProgress == null) return child;
                                      return Container(
                                        color: Colors.grey.shade200,
                                        child: Center(
                                          child: CircularProgressIndicator(
                                            value: loadingProgress.expectedTotalBytes != null
                                                ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                                                : null,
                                            strokeWidth: 2,
                                          ),
                                        ),
                                      );
                                    },
                                    errorBuilder: (context, error, stack) => Container(
                                      color: Colors.grey.shade200,
                                      child: const Icon(Icons.broken_image, color: Colors.grey),
                                    ),
                                  ),
                                ),
                                Positioned(
                                  bottom: 0,
                                  left: 0,
                                  right: 0,
                                  child: FutureBuilder<int>(
                                    future: directoryService.fetchDesignCount(product.id),
                                    builder: (context, countSnapshot) {
                                      if (countSnapshot.connectionState == ConnectionState.waiting) {
                                        return const SizedBox.shrink();
                                      }
                                      if (countSnapshot.hasError) {
                                        debugPrint('Failed to load count for \${product.id}: \${countSnapshot.error}');
                                        return const SizedBox.shrink();
                                      }
                                      final count = countSnapshot.data ?? 0;
                                      return Container(
                                        color: Colors.blueAccent,
                                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                                        child: Text(
                                          '${count} Designs',
                                          style: const TextStyle(color: Colors.white, fontSize: 12),
                                        ),
                                      );
                                    },
                                  ),
                                ),
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

