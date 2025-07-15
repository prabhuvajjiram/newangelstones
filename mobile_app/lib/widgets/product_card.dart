import 'package:flutter/material.dart';
import '../models/product.dart';

class ProductCard extends StatelessWidget {
  final Product product;
  const ProductCard({super.key, required this.product});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Expanded(
            child: Image.network(
              product.imageUrl,
              fit: BoxFit.cover,
            ),
          ),
          if (product.label != null && product.label!.isNotEmpty)
            Container(
              color: Colors.blueAccent,
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
              child: Text(
                product.label!,
                style: const TextStyle(color: Colors.white, fontSize: 12),
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: Text(product.name, style: const TextStyle(fontSize: 16)),
          ),
        ],
      ),
    );
  }
}
