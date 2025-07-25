import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../state/cart_state.dart';

class CartIcon extends StatelessWidget {
  final VoidCallback onPressed;
  const CartIcon({super.key, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    final count = context.watch<CartState>().count;
    return Stack(
      children: [
        Semantics(
          label: 'Shopping cart, $count items',
          child: IconButton(
            icon: const Icon(Icons.shopping_cart_outlined),
            tooltip: 'Cart',
            onPressed: onPressed,
          ),
        ),
        if (count > 0)
          Positioned(
            right: 4,
            top: 4,
            child: Container(
              padding: const EdgeInsets.all(2),
              decoration: const BoxDecoration(
                color: Colors.red,
                shape: BoxShape.circle,
              ),
              constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
              child: Text(
                '$count',
                style: const TextStyle(color: Colors.white, fontSize: 10),
                textAlign: TextAlign.center,
              ),
            ),
          ),
      ],
    );
  }
}
