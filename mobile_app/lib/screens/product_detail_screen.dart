cat > mobile_app/lib/screens/product_detail_screen.dart <<'EOF'
cat > mobile_app/lib/screens/product_detail_screen.dart <<'EOF'
cat <<'EOF' > /tmp/pds.b64
aW1wb3J0ICdwYWNrYWdlOmZsdXR0ZXIvbWF0ZXJpYWwuZGFydCc7CmltcG9ydCcuLi9tb2RlbHMvcHJvZHVjdC5kYXJ0JzsKaW1wb3J0ICdjYXJ0X3NjcmVlbi5kYXJ0JzsKCmNsYXNzIFByb2R1Y3REZXRhaWxTY3JlZW4gZXh0ZW5kcyBTdGF0ZWxlc3NXaWRnZXQgewogIGZpbmFsIFByb2R1Y3QgcHJvZHVjdDsKCiAgY29uc3QgUHJvZHVjdERldGFpbFNjcmVlbih7c3VwZXIua2V5LCByZXF1aXJlZCB0aGlzLnByb2R1Y3R9KTsKCiAgQG92ZXJyaWRlCiAgV2lkZ2V0IGJ1aWxkKEJ1aWxkQ29udGV4dCBjb250ZXh0KSB7CiAgICByZXR1cm4gU2NhZmZvbGQ(-CiAgICAgIGFwcEJhckFwcChcbiAgICAgICAgdGl0bGU6IFRleHQocHJvZHVjdC5uYW1lKSxcbiAgICAgICAgYWN0aW9uczogW1xuICAgICAgICAgIEljb25CdXR0b24oXG4gICAgICAgICAgICBjb24qXCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIA==
cat <<'EOF' > mobile_app/lib/screens/product_detail_screen.dart
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
