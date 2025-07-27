import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';

import '../state/cart_state.dart';
import '../state/saved_items_state.dart';
import '../widgets/app_button.dart';
import '../models/inventory_item.dart';

class EnhancedCartScreen extends StatefulWidget {
  const EnhancedCartScreen({super.key});

  @override
  State<EnhancedCartScreen> createState() => _EnhancedCartScreenState();
}

class _EnhancedCartScreenState extends State<EnhancedCartScreen> {
  final _formKey = GlobalKey<FormState>();
  final _notesController = TextEditingController();
  
  // Helper method to extract type and color from item
  String _getItemDisplayName(Map<String, dynamic> item) {
    final String type = item['type'] ?? '';
    final String color = item['color'] ?? '';
    
    if (type.isNotEmpty && color.isNotEmpty) {
      return '$type + $color';
    } else if (type.isNotEmpty) {
      return type;
    } else if (color.isNotEmpty) {
      return color;
    } else {
      return item['name'] ?? 'Product';
    }
  }

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _refreshCart() async {
    setState(() {});
    return Future.delayed(const Duration(milliseconds: 300));
  }

  @override
  Widget build(BuildContext context) {
    return Consumer2<CartState, SavedItemsState>(
      builder: (context, cart, savedItems, child) {
        final cartItemsList = cart.items;
        final savedItemsList = savedItems.items;
        final isCartEmpty = cartItemsList.isEmpty;
        final isSavedEmpty = savedItemsList.isEmpty;
        final totalQuantity = cart.totalQuantity;

        return Scaffold(
          appBar: AppBar(
            title: const Text('Your Cart'),
            actions: [
              if (!isCartEmpty)
                IconButton(
                  icon: const Icon(Icons.delete_outline),
                  tooltip: 'Clear Cart',
                  onPressed: () => _showClearCartDialog(context, cart),
                ),
              // No bookmark icon in the app bar
            ],
          ),
          body: RefreshIndicator(
            onRefresh: _refreshCart,
            child: _buildCartContent(
                context,
                cartItems: cartItemsList,
                savedItems: savedItemsList,
                totalQuantity: totalQuantity,
                cart: cart,
                savedItemsState: savedItems,
              ),
          ),
        );
      },
    );
  }

  Widget _buildEmptyStateWithScrollView() {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: SizedBox(
        height: MediaQuery.of(context).size.height * 0.8,
        child: Center(
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
                onPressed: () => context.go('/'),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: const [
                    Icon(Icons.shopping_bag_outlined),
                    SizedBox(width: 8),
                    Text('Browse Products'),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCartContent(
    BuildContext context, {
    required List<Map<String, dynamic>> cartItems,
    required List<Map<String, dynamic>> savedItems,
    required int totalQuantity,
    required CartState cart,
    required SavedItemsState savedItemsState,
  }) {
    // Show empty state if both cart and saved items are empty
    if (cartItems.isEmpty && savedItems.isEmpty) {
      return _buildEmptyStateWithScrollView();
    }
    
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Cart items section
          if (cartItems.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.all(16),
              child: Text(
                'Cart (${cartItems.length})',
                style: Theme.of(context).textTheme.titleLarge,
              ),
            ),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: cartItems.length,
              itemBuilder: (context, index) {
                return _buildCartItemCard(
                  context,
                  cartItems[index],
                  cart,
                  savedItemsState,
                );
              },
            ),
            
            // Optional note section
            Padding(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: TextFormField(
                  controller: _notesController,
                  decoration: InputDecoration(
                    labelText: 'Add a note for your order (optional)',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    contentPadding: const EdgeInsets.all(16),
                  ),
                  maxLines: 2,
                ),
              ),
            ),
            
            // Action buttons section
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Request quote button
                  AppButton(
                    onPressed: () {
                      // Navigate to quote request screen with cart items
                      final cartItems = cart.items;
                      final totalQuantity = cart.totalQuantity;
                      context.pushNamed('quote-request', extra: cartItems);
                    },
                    color: const Color(0xFFFFD700), // Gold color
                    textColor: Colors.black,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: const [
                        Icon(Icons.request_quote),
                        SizedBox(width: 8),
                        Text(
                          'Request Quote',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  
                  // Back to inventory button
                  AppButton(
                    onPressed: () => context.go('/'),
                    color: const Color(0xFF1E1E1E), // Dark background
                    textColor: const Color(0xFFFFD700), // Gold text
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: const [
                        Icon(Icons.arrow_back),
                        SizedBox(width: 8),
                        Text(
                          'Back to Inventory',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  // Proceed to checkout button
                  ElevatedButton.icon(
                    icon: const Icon(Icons.shopping_cart_checkout),
                    label: const Text('Proceed to Checkout (Coming Soon)'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Theme.of(context).primaryColor,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                    onPressed: null, // Disabled for now
                  ),
                ],
              ),
            ),
          ],
          
          // Saved for later section
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text(
              'Saved for Later (${savedItems.length})',
              style: Theme.of(context).textTheme.titleLarge,
            ),
          ),
          if (savedItems.isEmpty)
            Padding(
              padding: const EdgeInsets.all(16),
              child: Center(
                child: Text(
                  'No items saved for later',
                  style: TextStyle(color: Colors.grey[600]),
                ),
              ),
            )
          else
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: savedItems.length,
              itemBuilder: (context, index) {
                return _buildSavedItemCard(
                  context,
                  savedItems[index],
                  cart,
                  savedItemsState,
                );
              },
            ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  // Build a cart item card with 3-dot menu
  Widget _buildCartItemCard(
    BuildContext context,
    Map<String, dynamic> item,
    CartState cart,
    SavedItemsState savedItemsState,
  ) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: InkWell(
        onTap: () => _openDetailView(context, item, cart, savedItemsState),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // No product icon or placeholder image
              
              // Product details
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _getItemDisplayName(item),
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    if (item['code'] != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          'Code: ${item['code']}',
                          style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                        ),
                      ),
                    const SizedBox(height: 12),
                    // Modern quantity selector
                    Row(
                      children: [
                        Container(
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey[300]!),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                icon: const Icon(Icons.remove, size: 16),
                                onPressed: () {
                                  final currentQty = item['quantity'] as int? ?? 1;
                                  if (currentQty > 1) {
                                    cart.updateQuantity(item, currentQty - 1);
                                  }
                                },
                                padding: const EdgeInsets.all(4),
                                constraints: const BoxConstraints(),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8),
                                child: Text(
                                  '${item['quantity'] ?? 1}',
                                  style: const TextStyle(fontSize: 14),
                                ),
                              ),
                              IconButton(
                                icon: const Icon(Icons.add, size: 16),
                                onPressed: () {
                                  final currentQty = item['quantity'] as int? ?? 1;
                                  cart.updateQuantity(item, currentQty + 1);
                                },
                                padding: const EdgeInsets.all(4),
                                constraints: const BoxConstraints(),
                              ),
                            ],
                          ),
                        ),
                        const Spacer(),
                        // 3-dot menu (Target style)
                        IconButton(
                          icon: const Icon(Icons.more_vert),
                          onPressed: () => _showCartItemOptions(context, item, cart, savedItemsState),
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // Build a saved item card with 3-dot menu
  Widget _buildSavedItemCard(
    BuildContext context,
    Map<String, dynamic> item,
    CartState cart,
    SavedItemsState savedItemsState,
  ) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: InkWell(
        onTap: () => _openDetailView(context, item, cart, savedItemsState),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // No product icon or placeholder image
              
              // Product details
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _getItemDisplayName(item),
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    if (item['code'] != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          'Code: ${item['code']}',
                          style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                        ),
                      ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Text('Available: ${item['quantity'] ?? 1}'),
                        const Spacer(),
                        // 3-dot menu (Target style)
                        IconButton(
                          icon: const Icon(Icons.more_vert),
                          onPressed: () => _showSavedItemOptions(context, item, cart, savedItemsState),
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // Show options menu for cart item
  void _showCartItemOptions(
    BuildContext context,
    Map<String, dynamic> item,
    CartState cart,
    SavedItemsState savedItemsState,
  ) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: const EdgeInsets.symmetric(vertical: 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.bookmark_border),
              title: const Text('Save item for later'),
              onTap: () {
                Navigator.pop(context);
                savedItemsState.addItem(item);
                cart.removeItem(item);
              },
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline, color: Colors.red),
              title: const Text('Remove item', style: TextStyle(color: Colors.red)),
              onTap: () {
                Navigator.pop(context);
                cart.removeItem(item);
              },
            ),
          ],
        ),
      ),
    );
  }

  // Show options menu for saved item
  void _showSavedItemOptions(
    BuildContext context,
    Map<String, dynamic> item,
    CartState cart,
    SavedItemsState savedItemsState,
  ) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: const EdgeInsets.symmetric(vertical: 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.shopping_cart_outlined),
              title: const Text('Move to cart'),
              onTap: () {
                Navigator.pop(context);
                cart.addItem(item);
                savedItemsState.removeItem(item);
              },
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline, color: Colors.red),
              title: const Text('Remove item', style: TextStyle(color: Colors.red)),
              onTap: () {
                Navigator.pop(context);
                savedItemsState.removeItem(item);
              },
            ),
          ],
        ),
      ),
    );
  }

  // Open detail view for an item
  void _openDetailView(
    BuildContext context,
    Map<String, dynamic> item,
    CartState cart,
    SavedItemsState savedItemsState,
  ) {
    // Convert the saved item map to an InventoryItem object
    final inventoryItem = InventoryItem(
      code: item['code'] ?? '',
      description: item['description'] ?? '',
      color: item['color'] ?? '',
      size: item['size'] ?? '',
      location: item['location'] ?? '',
      quantity: item['quantity'] is int ? item['quantity'] : int.tryParse(item['quantity']?.toString() ?? '0') ?? 0,
      type: item['type'] ?? '',
      design: item['design'] ?? '',
      finish: item['finish'] ?? '',
    );
    
    // Navigate to the inventory item details screen
    GoRouter.of(context).pushNamed(
      'inventory-item-details',
      extra: inventoryItem,
    );
  }

  // Build the detail view for an item
  Widget _buildDetailView(
    BuildContext context,
    Map<String, dynamic> item,
    CartState cart,
    SavedItemsState savedItemsState,
  ) {
    return DraggableScrollableSheet(
      initialChildSize: 0.9,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      builder: (_, controller) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: ListView(
            controller: controller,
            padding: const EdgeInsets.all(16),
            children: [
              // Close button
              Align(
                alignment: Alignment.topRight,
                child: IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ),
              
              // No product icon or placeholder image in detail view
              
              // Product name (Type + Color)
              Text(
                _getItemDisplayName(item),
                style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
              ),
              // Product code
              if (item['code'] != null)
                Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Text(
                    'Code: ${item['code']}',
                    style: TextStyle(fontSize: 14, color: Colors.grey[600]),
                  ),
                ),
              const SizedBox(height: 16),
              
              // Product description (if available)
              if (item['description'] != null)
                Text(
                  item['description'],
                  style: const TextStyle(fontSize: 16),
                ),
              const SizedBox(height: 24),
              
              // Quantity selector - improved UI
              Row(
                children: [
                  const Text('Quantity:', style: TextStyle(fontSize: 16)),
                  const SizedBox(width: 16),
                  Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey[300]!),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.remove),
                          onPressed: () {
                            final currentQty = item['quantity'] as int? ?? 1;
                            if (currentQty > 1) {
                              setState(() {
                                item['quantity'] = currentQty - 1;
                              });
                            }
                          },
                          padding: const EdgeInsets.all(4),
                          constraints: const BoxConstraints(),
                          iconSize: 20,
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          child: Text(
                            '${item['quantity'] ?? 1}',
                            style: const TextStyle(fontSize: 16),
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.add),
                          onPressed: () {
                            final currentQty = item['quantity'] as int? ?? 1;
                            setState(() {
                              item['quantity'] = currentQty + 1;
                            });
                          },
                          padding: const EdgeInsets.all(4),
                          constraints: const BoxConstraints(),
                          iconSize: 20,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 32),
              
              // Action buttons
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Theme.of(context).primaryColor,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                      onPressed: () {
                        cart.addItem(item);
                        if (savedItemsState.hasItem(item['id'])) {
                          savedItemsState.removeItem(item);
                        }
                        Navigator.pop(context);
                      },
                      child: const Text('Add to Cart'),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(color: Theme.of(context).primaryColor),
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                      onPressed: () {
                        savedItemsState.addItem(item);
                        if (cart.hasItem(item['id'])) {
                          cart.removeItem(item);
                        }
                        Navigator.pop(context);
                      },
                      child: const Text('Save for Later'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  // Show dialog to confirm clearing the cart
  void _showClearCartDialog(BuildContext context, CartState cart) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Clear Cart'),
        content: const Text('Are you sure you want to remove all items from your cart?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              cart.clearCart();
              Navigator.pop(context);
            },
            child: const Text('Clear', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }
}
