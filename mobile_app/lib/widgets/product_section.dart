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
    return Padding(
      padding: const EdgeInsets.all(8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
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
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  childAspectRatio: 0.75,
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
