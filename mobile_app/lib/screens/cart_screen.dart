import 'package:flutter/material.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Cart')),
      body: Center(
        child: Semantics(
          label: 'Cart is currently empty',
          child: const Text('Cart items will appear here'),
        ),
      ),
    );
  }
}
