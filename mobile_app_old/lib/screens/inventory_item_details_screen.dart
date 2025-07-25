import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../models/inventory_item.dart';
import '../services/saved_items_service.dart';
import '../state/cart_state.dart';
import '../screens/cart_screen.dart';

class InventoryItemDetailsScreen extends StatefulWidget {
  final InventoryItem item;

  const InventoryItemDetailsScreen({
    super.key,
    required this.item,
  });

  @override
  State<InventoryItemDetailsScreen> createState() => _InventoryItemDetailsScreenState();
}

class _InventoryItemDetailsScreenState extends State<InventoryItemDetailsScreen> {
  int quantity = 1;
  bool isSaved = false;
  
  @override
  void initState() {
    super.initState();
    _checkIfItemIsSaved();
  }
  
  Future<void> _checkIfItemIsSaved() async {
    final saved = await SavedItemsService.isItemSaved(widget.item.code);
    if (mounted) {
      setState(() {
        isSaved = saved;
      });
    }
  }
  
  Future<void> _toggleSaveItem() async {
    if (isSaved) {
      await SavedItemsService.removeItem(widget.item.code);
    } else {
      // Convert InventoryItem to Map<String, dynamic>
      final itemMap = {
        'id': widget.item.code,
        'code': widget.item.code,
        'description': widget.item.description,
        'color': widget.item.color,
        'type': widget.item.type,
        'size': widget.item.size,
        'quantity': widget.item.quantity,
        'location': widget.item.location,
        'design': widget.item.design,
        'finish': widget.item.finish,
        'weight': widget.item.weight,
        'productId': widget.item.productId,
      };
      await SavedItemsService.saveItem(itemMap);
    }
    
    if (mounted) {
      setState(() {
        isSaved = !isSaved;
      });
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isSaved ? 'Item saved!' : 'Item removed from saved items'),
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }
  
  void _addToCart() {
    // Create a cart item with the selected quantity
    final cartItem = {
      'id': widget.item.code,  // 'id' is used for identifying unique items in cart
      'code': widget.item.code,
      'description': widget.item.description,
      'quantity': quantity,
      'color': widget.item.color,
      'size': widget.item.size,
      'type': widget.item.type,
      'price': 0.0,  // Add default price (can be updated later)
      'location': widget.item.location,
      'design': widget.item.design,
      'finish': widget.item.finish,
    };
    
    // Add to cart using the CartState provider
    final cartState = Provider.of<CartState>(context, listen: false);
    
    // If item exists, update quantity, otherwise add new item
    final existingIndex = cartState.items.indexWhere((item) => item['id'] == cartItem['id']);
    
    if (existingIndex >= 0) {
      // Update existing item quantity
      final currentQuantity = cartState.items[existingIndex]['quantity'] as int;
      cartState.updateQuantity(cartState.items[existingIndex], currentQuantity + quantity);
    } else {
      // Add as new item with specified quantity
      cartState.addItemWithQuantity(cartItem, quantity);
    }
    
    // Show success message
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('${widget.item.description} added to cart'),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 2),
      ),
    );
    
    // Navigate to cart screen and ensure proper context
    try {
      // Use Navigator.of(context) to ensure proper context handling
      // This will maintain the widget tree and prevent Material widget errors
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (context) => const CartScreen(),
        ),
      );
    } catch (e) {
      debugPrint('Error navigating to cart: $e');
      // Fallback to GoRouter if Navigator fails
      try {
        GoRouter.of(context).go('/cart');
      } catch (routerError) {
        debugPrint('Error with GoRouter navigation: $routerError');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.item.description.isNotEmpty ? 
            (widget.item.description.length > 30 ? 
              '${widget.item.description.substring(0, 30)}...' : 
              widget.item.description) : 
            'Item Details',
        ),
        actions: [
          IconButton(
            icon: Icon(
              isSaved ? Icons.bookmark : Icons.bookmark_border,
              color: isSaved ? Colors.amber : null,
            ),
            onPressed: _toggleSaveItem,
            tooltip: isSaved ? 'Remove from saved items' : 'Save item',
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Item title and code
            Text(
              widget.item.description,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
          //  const SizedBox(height: 8),
           // Text(
             // 'Product Code: ${widget.item.code}',
             // style: Theme.of(context).textTheme.titleMedium,
           // ),
            const SizedBox(height: 24),
            
            // Basic information section
            _buildSectionHeader(context, 'Basic Information'),
            const SizedBox(height: 8),
            _buildInfoRow(context, 'Product Code', widget.item.code),
            _buildInfoRow(context, 'Product ID', widget.item.productId > 0 ? widget.item.productId.toString() : 'N/A'),
            _buildInfoRow(context, 'Type', widget.item.type),
            _buildInfoRow(context, 'Color', widget.item.color),
            _buildInfoRow(context, 'Design', widget.item.design),
            _buildInfoRow(context, 'Finish', widget.item.finish),
            
            const SizedBox(height: 24),
            // Dimensions section
            _buildSectionHeader(context, 'Dimensions'),
            const SizedBox(height: 8),
            _buildInfoRow(context, 'Size', widget.item.size),
            _buildInfoRow(context, 'Length', widget.item.lengthInInches.isNotEmpty ? widget.item.lengthInInches : 'Not specified'),
            _buildInfoRow(context, 'Height', widget.item.heightInInches.isNotEmpty ? widget.item.heightInInches : 'Not specified'),
            _buildInfoRow(context, 'Width', widget.item.widthInInches.isNotEmpty ? widget.item.widthInInches : 'Not specified'),
            _buildInfoRow(context, 'Weight', widget.item.weight.isNotEmpty ? '${widget.item.weight} lbs' : 'Not specified'),
            
            const SizedBox(height: 24),
            // Inventory information section
            _buildSectionHeader(context, 'Inventory Information'),
            const SizedBox(height: 8),
            _buildInfoRow(context, 'Quantity', widget.item.quantity.toString()),
            _buildInfoRow(context, 'Location', widget.item.location),
            _buildInfoRow(context, 'Status', 'In-Stock'),
            // Removed Last Updated field
            // Removed Additional Notes section
            
            const SizedBox(height: 32),
            // Quantity selector and Add to Cart button
            Row(
              children: [
                Text(
                  'Quantity:',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(width: 16),
                _buildQuantitySelector(context),
              ],
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _addToCart,
                icon: const Icon(Icons.shopping_cart),
                label: const Text('ADD TO CART'),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  backgroundColor: Theme.of(context).primaryColor,
                  foregroundColor: Colors.white,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  Widget _buildSectionHeader(BuildContext context, String title) {
    return Text(
      title,
      style: Theme.of(context).textTheme.titleLarge?.copyWith(
        fontWeight: FontWeight.bold,
      ),
    );
  }
  
  Widget _buildInfoRow(BuildContext context, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              '$label:',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildQuantitySelector(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey.shade300),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            icon: const Icon(Icons.remove),
            onPressed: quantity > 1 ? () {
              setState(() {
                quantity--;
              });
            } : null,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            iconSize: 20,
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Text(
              quantity.toString(),
              style: Theme.of(context).textTheme.titleMedium,
            ),
          ),
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () {
              setState(() {
                quantity++;
              });
            },
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            iconSize: 20,
          ),
        ],
      ),
    );
  }
}
