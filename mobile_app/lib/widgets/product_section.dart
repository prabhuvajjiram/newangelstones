import 'package:flutter/material.dart';
import '../models/product.dart';
import 'product_card.dart';
import 'package:go_router/go_router.dart';
import '../utils/error_utils.dart';

class ProductSection extends StatelessWidget {
  final String title;
  final Future<List<Product>> future;
  const ProductSection({super.key, required this.title, required this.future});

  @override
  Widget build(BuildContext context) {
    // Get screen width for responsive sizing
    final screenWidth = MediaQuery.of(context).size.width;
    final isSmallScreen = screenWidth < 375;
    
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: isSmallScreen ? 8.0 : 12.0,
        vertical: 8.0,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4.0),
            child: Text(
              title,
              style: TextStyle(
                fontSize: isSmallScreen ? 16 : 18,
                fontWeight: FontWeight.bold,
                letterSpacing: 0.3,
              ),
            ),
          ),
          const SizedBox(height: 12),
          FutureBuilder<List<Product>>(
            future: future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Center(child: CircularProgressIndicator());
              } else if (snapshot.hasError) {
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  showErrorSnackBar(context, 'Failed to load products');
                });
                return const Text('Failed to load products');
              } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
                return const Text('No items found');
              }
              final products = snapshot.data!;
              return GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  // 3 columns for portrait, more for landscape
                  crossAxisCount: MediaQuery.of(context).orientation == Orientation.portrait ? 3 : 4,
                  // Adjusted for better proportions
                  childAspectRatio: 0.68,
                  // Tighter spacing
                  crossAxisSpacing: 8,
                  mainAxisSpacing: 8,
                ),
                itemCount: products.length,
                itemBuilder: (context, index) {
                  final product = products[index];
                  return GestureDetector(
                    onTap: () {
                      context.push('/product', extra: product);
                    },
                    child: ProductCard(product: product),
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
