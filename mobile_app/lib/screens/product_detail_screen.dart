import 'package:flutter/material.dart';
import '../models/product.dart';
import 'cart_screen.dart';

class ProductDetailScreen extends StatelessWidget {
  final Product product;

  const ProductDetailScreen({super.key, required this.product});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(product.name),
        actions: [
          IconButton(
            icon: const Icon(Icons.shopping_cart),
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const CartScreen()));
            },
          )
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Image.network(product.imageUrl, height: 200, fit: BoxFit.cover),
            const SizedBox(height: 16),
            Text(product.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text('''\$${product.price.toStringAsFixed(2)}''', style: const TextStyle(fontSize: 18)),
            const SizedBox(height: 8),
            Text(product.description),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                // Add to cart logic would go here
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Added to cart')));
              },
              child: const Text('Add to Cart'),
            ),
          ],
        ),
      ),
    );
  }
}
