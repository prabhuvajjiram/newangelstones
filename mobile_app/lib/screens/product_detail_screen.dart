import 'package:flutter/material.dart';
import '../models/product.dart';
import '../utils/image_utils.dart';

class ProductDetailScreen extends StatelessWidget {
  final Product product;

  const ProductDetailScreen({super.key, required this.product});

  @override
  Widget build(BuildContext context) {
    final screenHeight = MediaQuery.of(context).size.height;
    final screenWidth = MediaQuery.of(context).size.width;
    
    // Responsive sizing based on screen dimensions
    final bool isSmallScreen = screenHeight < 700 || screenWidth < 400;
    final double toolbarHeight = isSmallScreen ? 48.0 : 56.0;
    final double titleFontSize = isSmallScreen ? 14.0 : 16.0;
    final int maxTitleLength = isSmallScreen ? 20 : 30;
    
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: toolbarHeight,
        title: Text(
          product.name.length > maxTitleLength ? 
            '${product.name.substring(0, maxTitleLength)}...' : 
            product.name,
          style: TextStyle(fontSize: titleFontSize),
        ),
        actions: const [],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Semantics(
              label: product.name,
              child: ImageUtils.buildImage(
                imageUrl: product.imageUrl,
                height: 200,
                fit: BoxFit.cover,
              ),
            ),
              const SizedBox(height: 16),
              Text(
                product.name,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(product.description),
            ],
          ),
        ),
      );
  }
}

