import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'dart:io';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/directory_service.dart';
import '../services/image_share_service.dart';
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

  /// Build product image with priority: bundled assets → cached local → network
  Widget _buildProductImage(Product product) {
    final fileName = _extractFileName(product.imageUrl);
    
    // Priority 1: Try bundled asset first (fastest, always available)
    // Try assets/products/[category]/ structure
    // Extract category from URL path (e.g., products/benches/image.jpg)
    final urlParts = product.imageUrl.split('/');
    String assetPath = 'assets/products/$fileName'; // fallback
    
    if (urlParts.length >= 2) {
      final possibleCategory = urlParts[urlParts.length - 2];
      // Try category-based path first
      assetPath = 'assets/products/$possibleCategory/$fileName';
    }
    
    return Image.asset(
      assetPath,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) {
        // Fallback to flat assets/products/ if category path doesn't exist
        return Image.asset(
          'assets/products/$fileName',
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) {
            // Priority 2: Try cached local file
            if (product.localImagePath != null && product.localImagePath!.isNotEmpty) {
              return Image.file(
                File(product.localImagePath!),
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) {
                  // Priority 3: Fallback to network
                  return _buildNetworkImage(product.imageUrl);
                },
              );
            }
            // Priority 3: Fallback to network if no local cache
            return _buildNetworkImage(product.imageUrl);
          },
        );
      },
    );
  }
  
  /// Build network image with caching
  Widget _buildNetworkImage(String imageUrl) {
    return CachedNetworkImage(
      imageUrl: imageUrl,
      fit: BoxFit.cover,
      memCacheWidth: 300,
      memCacheHeight: 300,
      placeholder: (context, url) => SkeletonLoaders.productCard(height: double.infinity),
      errorWidget: (context, url, error) => Container(
        color: Colors.grey.shade200,
        child: const Icon(Icons.broken_image, size: 32, color: Colors.grey),
      ),
      fadeInDuration: const Duration(milliseconds: 200),
      fadeOutDuration: const Duration(milliseconds: 100),
    );
  }
  
  /// Extract filename from URL
  String _extractFileName(String url) {
    try {
      return url.split('/').last.split('?').first;
    } catch (e) {
      return 'image.jpg';
    }
  }

  /// Share product image
  Future<void> _shareProduct(BuildContext context, Product product) async {
    try {
      if (product.imageUrl.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No image to share')),
        );
        return;
      }

      final fileName = _extractFileName(product.imageUrl);
      
      final success = await ImageShareService.shareImage(
        imageUrl: product.imageUrl,
        fileName: fileName,
        productName: product.name,
        productCode: product.id,
      );
      
      if (!success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to share image')),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Error sharing image')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isSmallScreen = screenWidth < 375;
    
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: isSmallScreen ? 6.0 : 8.0,
        vertical: 6.0,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4.0),
            child: Text(
              title,
              style: TextStyle(
                fontSize: isSmallScreen ? 16 : 17,
                fontWeight: FontWeight.bold,
                letterSpacing: 0.3,
              ),
            ),
          ),
          const SizedBox(height: 10),
          FutureBuilder<List<Product>>(
            future: future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return SkeletonLoaders.productGrid(itemCount: 6);
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
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  // 3 columns for portrait, 4 for landscape
                  crossAxisCount: MediaQuery.of(context).orientation == Orientation.portrait ? 3 : 4,
                  childAspectRatio: 0.68,
                  crossAxisSpacing: 8,
                  mainAxisSpacing: 8,
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
                      margin: EdgeInsets.zero,
                      elevation: 2,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Expanded(
                            child: _buildProductImage(product),
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6.0,
                              vertical: 6.0,
                            ),
                            child: Text(
                              product.name,
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                letterSpacing: 0.2,
                              ),
                              textAlign: TextAlign.center,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
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

