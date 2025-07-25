import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';

import '../state/cart_state.dart';
import '../navigation/app_router.dart';
import '../widgets/app_button.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  final _formKey = GlobalKey<FormState>();
  final _notesController = TextEditingController();

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<CartState>(
      builder: (context, cart, child) {
        final items = cart.items;
        final isEmpty = items.isEmpty;
        final totalQuantity = cart.totalQuantity;
        final totalPrice = cart.totalPrice;

        return Scaffold(
          appBar: AppBar(
            title: const Text('Your Cart'),
            actions: [
              if (!isEmpty)
                IconButton(
                  icon: const Icon(Icons.delete_outline),
                  tooltip: 'Clear Cart',
                  onPressed: () => _showClearCartDialog(context, cart),
                ),
            ],
          ),
          body: isEmpty
              ? _buildEmptyState()
              : _buildCartContent(
                  context,
                  items: items,
                  totalQuantity: totalQuantity,
                  totalPrice: totalPrice,
                  cart: cart,
                ),
        );
      },
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.shopping_cart_outlined,
            size: 64,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            'Your cart is empty',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: Colors.grey[600],
                ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Browse our products and add items to your cart',
            style: TextStyle(color: Colors.grey),
          ),
          const SizedBox(height: 24),
          AppButton(
            onPressed: () {
              // Navigate to home or products screen
              if (GoRouter.of(context).canPop()) {
                GoRouter.of(context).pop();
              } else {
                GoRouter.of(context).go('/');
              }
            },
            child: const Text('Continue Shopping'),
          ),
        ],
      ),
    );
  }

  Widget _buildCartContent(
    BuildContext context, {
    required List<Map<String, dynamic>> items,
    required int totalQuantity,
    required double totalPrice,
    required CartState cart,
  }) {
    return Column(
      children: [
        // Cart Items List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index];
              return _buildCartItem(context, item, cart);
            },
          ),
        ),

        // Order Summary & Checkout
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 8,
                offset: const Offset(0, -2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Order Notes
              Form(
                key: _formKey,
                child: TextFormField(
                  controller: _notesController,
                  decoration: const InputDecoration(
                    labelText: 'Order Notes (Optional)',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 12,
                    ),
                  ),
                  maxLines: 2,
                ),
              ),
              const SizedBox(height: 16),

              // Order Summary
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Total Items:', style: TextStyle(fontSize: 16)),
                  Text('$totalQuantity', style: const TextStyle(fontSize: 16)),
                ],
              ),
              const SizedBox(height: 16),

              // Back to Inventory Button
              OutlinedButton(
                onPressed: () {
                  // Use Navigator.pop to go back to previous screen instead of GoRouter
                  // This preserves the navigation stack and back button
                  if (Navigator.of(context).canPop()) {
                    Navigator.of(context).pop();
                  } else {
                    // Fallback to GoRouter if Navigator stack is empty
                    GoRouter.of(context).go('/inventory');
                  }
                },
                child: const Text('Back to Inventory'),
              ),
              const SizedBox(height: 8),
              
              // Request Quote Button
              AppButton(
                onPressed: () => _navigateToQuoteRequest(context, items),
                child: const Text('Request a Quote'),
              ),
              const SizedBox(height: 8),

              // Or divider
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  children: [
                    Expanded(child: Divider()),
                    Padding(
                      padding: EdgeInsets.symmetric(horizontal: 8),
                      child: Text('OR', style: TextStyle(color: Colors.grey)),
                    ),
                    Expanded(child: Divider()),
                  ],
                ),
              ),

              // Proceed to Checkout Button (disabled for now)
              AppButton(
                onPressed: null, // Disabled for now
                child: const Text('Proceed to Checkout (coming soon)'),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildCartItem(
      BuildContext context, Map<String, dynamic> item, CartState cart) {
    final quantity = item['quantity'] as int;
    // Price calculation removed as it's not displayed

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Product Image
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(8),
                image: DecorationImage(
                  image: NetworkImage(item['imageUrl'] ?? ''),
                  fit: BoxFit.cover,
                ),
              ),
            ),
            const SizedBox(width: 12),
            
            // Product Details
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item['name'] ?? 'Product',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (item['code'] != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 4.0),
                      child: Text(
                        'Code: ${item['code']}',
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 12,
                        ),
                      ),
                    ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Price placeholder - hidden until API provides prices
                      const SizedBox(),
                      
                      // Quantity Selector
                      Container(
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey[300]!),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.remove, size: 18),
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                              onPressed: quantity > 1
                                  ? () => cart.updateQuantity(
                                      item, quantity - 1)
                                  : null,
                            ),
                            SizedBox(
                              width: 24,
                              child: Text(
                                '$quantity',
                                textAlign: TextAlign.center,
                                style: const TextStyle(fontSize: 14),
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.add, size: 18),
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                              onPressed: () =>
                                  cart.updateQuantity(item, quantity + 1),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            
            // Remove Button
            IconButton(
              icon: const Icon(Icons.close, size: 20),
              onPressed: () => cart.removeItem(item),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
              tooltip: 'Remove',
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showClearCartDialog(
      BuildContext context, CartState cart) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Clear Cart'),
        content: const Text('Are you sure you want to remove all items from your cart?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('CANCEL'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text(
              'CLEAR',
              style: TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      cart.clearCart();
    }
  }

  void _navigateToQuoteRequest(
      BuildContext context, List<Map<String, dynamic>> items) {
    // Pass the cart items to the quote request screen
    GoRouter.of(context).pushNamed(AppRouter.quoteRequest, extra: items);
  }
}
