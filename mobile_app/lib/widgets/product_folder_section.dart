import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/directory_service.dart';
import 'package:go_router/go_router.dart';
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
  
  // Helper method to extract color name from product name
  String _extractColorName(String productName) {
    // Common color names to look for
    final List<String> commonColors = [
      'Bahama Blue',
      'Baltic Green',
      'Dark Barre Gray',
      'Forest Green',
      'Blue Pearl',
      'Black',
      'Gray',
      'Red',
      'Brown',
      'Green',
    ];
    
    // Try to find a color in the product name
    for (final color in commonColors) {
      if (productName.contains(color)) {
        return color;
      }
    }
    
    // If no color found, return the first part of the name
    final parts = productName.split(' ');
    if (parts.length > 1) {
      return parts.take(2).join(' '); // Return first two words
    }
    return productName;
  }
  
  // Helper method to extract product type from product name
  String _extractProductType(String productName) {
    // Common product types to look for
    final List<String> commonTypes = [
      'Granite',
      'Monument',
      'Base',
      'Bench Seat',
      'Bevel Marker',
      'Cap',
      'Ledger',
      'Legs',
      'Marker',
      'Panel',
      'Pedestal',
      'Piece',
      'Slab',
      'Slant',
      'Support',
      'Tablet',
      'Vase',
      'Design',
    ];
    
    // Try to find a type in the product name
    for (final type in commonTypes) {
      if (productName.contains(type)) {
        return type;
      }
    }
    
    // Default type if none found
    return 'Granite';
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16.0, horizontal: 12.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                title, 
                style: const TextStyle(
                  fontSize: 20, 
                  fontWeight: FontWeight.bold,
                  letterSpacing: 0.2,
                ),
              ),
              TextButton(
                onPressed: () {
                  // View all products
                  context.push('/categories');
                },
                child: const Text('View All'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          FutureBuilder<List<Product>>(
            future: future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return Container(
                  height: 200,
                  alignment: Alignment.center,
                  child: const Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      CircularProgressIndicator(),
                      SizedBox(height: 16),
                      Text(
                        'Loading featured products...',
                        style: TextStyle(fontWeight: FontWeight.w500),
                      ),
                    ],
                  ),
                );
              } else if (snapshot.hasError) {
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  showErrorSnackBar(context, 'Failed to load featured products');
                });
                return Container(
                  height: 200,
                  alignment: Alignment.center,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error_outline, size: 40, color: Colors.red[300]),
                      const SizedBox(height: 16),
                      const Text(
                        'Failed to load featured products',
                        style: TextStyle(fontSize: 16),
                      ),
                      const SizedBox(height: 8),
                      ElevatedButton(
                        onPressed: () {
                          // Refresh the page
                          Navigator.of(context).pushReplacement(
                            MaterialPageRoute(builder: (_) => Navigator.of(context).widget),
                          );
                        },
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                );
              } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
                return Container(
                  height: 200,
                  alignment: Alignment.center,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.inbox, size: 40, color: Colors.grey[400]),
                      const SizedBox(height: 16),
                      const Text(
                        'No featured products available',
                        style: TextStyle(fontSize: 16),
                      ),
                    ],
                  ),
                );
              }
              final categories = snapshot.data!;
              return GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  childAspectRatio: 0.75,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                ),
                itemCount: categories.length,
                itemBuilder: (context, index) {
                  final product = categories[index];
                  // Implement staggered loading for better performance
                  return AnimatedOpacity(
                    duration: const Duration(milliseconds: 300),
                    opacity: 1.0,
                    curve: Curves.easeInOut,
                    child: GestureDetector(
                      onTap: () {
                        context.push(
                          '/gallery/${product.id}?title=${Uri.encodeComponent(product.name)}',
                        );
                      },
                      child: Card(
                        clipBehavior: Clip.antiAlias,
                        elevation: 2.0,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8.0)),
                        child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Expanded(
                            child: Stack(
                              children: [
                                // Placeholder shimmer effect while loading
                                Positioned.fill(
                                  child: Container(color: Colors.grey.shade200),
                                ),
                                // Actual image with fade-in effect
                                Positioned.fill(
                                  child: Image.network(
                                    product.imageUrl,
                                    fit: BoxFit.cover,
                                    frameBuilder: (context, child, frame, wasSynchronouslyLoaded) {
                                      if (wasSynchronouslyLoaded) return child;
                                      return AnimatedOpacity(
                                        opacity: frame == null ? 0 : 1,
                                        duration: const Duration(milliseconds: 300),
                                        curve: Curves.easeOut,
                                        child: child,
                                      );
                                    },
                                    errorBuilder: (context, error, stack) => const Center(
                                      child: Icon(Icons.broken_image, size: 40, color: Colors.grey),
                                    ),
                                    // Add caching for better performance
                                    cacheHeight: 300,
                                    cacheWidth: 300,
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
                                        debugPrint('Failed to load count for ${product.id}: ${countSnapshot.error}');
                                        return const SizedBox.shrink();
                                      }
                                      final count = countSnapshot.data ?? 0;
                                      return Container(
                                        decoration: BoxDecoration(
                                          gradient: LinearGradient(
                                            colors: [Colors.blueAccent.withOpacity(0.7), Colors.blueAccent],
                                            begin: Alignment.topLeft,
                                            end: Alignment.bottomRight,
                                          ),
                                          borderRadius: const BorderRadius.only(
                                            topLeft: Radius.circular(4),
                                          ),
                                          boxShadow: [
                                            BoxShadow(
                                              color: Colors.black.withOpacity(0.2),
                                              blurRadius: 2,
                                              offset: const Offset(0, 1),
                                            ),
                                          ],
                                        ),
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        child: Text(
                                          '${count} Designs',
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontSize: 12,
                                            fontWeight: FontWeight.w600,
                                            letterSpacing: 0.3,
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            decoration: BoxDecoration(
                              color: Colors.white,
                              border: Border(
                                top: BorderSide(color: Colors.grey.shade200, width: 1),
                              ),
                            ),
                            padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                            child: Column(  
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                // Display the product name or a formatted version
                                Text(
                                  product.name,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    letterSpacing: 0.2,
                                  ),
                                  textAlign: TextAlign.center,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 4),
                                // Display the product ID (which appears to be the code shown in screenshots)
                                Text(
                                  product.id,
                                  style: const TextStyle(
                                    fontSize: 14,
                                    color: Colors.black87,
                                    fontWeight: FontWeight.w500,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                              ],
                            ),
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

