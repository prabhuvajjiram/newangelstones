import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../models/inventory_item.dart';
import '../navigation/app_router.dart';
import '../services/navigation_service.dart';
import '../services/saved_items_service.dart';
import '../state/cart_state.dart';
import '../services/unified_saved_items_service.dart';

class InventoryTableSection extends StatefulWidget {
  final String title;
  final Future<List<InventoryItem>> future;
  final VoidCallback onRetry;
  
  const InventoryTableSection({
    super.key,
    required this.title,
    required this.future,
    required this.onRetry,
  });
  
  @override
  State<InventoryTableSection> createState() => _InventoryTableSectionState();
}

class _InventoryTableSectionState extends State<InventoryTableSection> {
  // Controller for the scroll position
  final ScrollController _scrollController = ScrollController();
  bool _showBackToTop = false;
  
  // Map to track saved status of items
  final Map<String, bool> _savedItems = {};
  
  @override
  void initState() {
    super.initState();
    
    // Load saved items
    _loadSavedItems();
    
    // Add listener to show/hide back to top button
    _scrollController.addListener(() {
      if (_scrollController.offset > 300 && !_showBackToTop) {
        setState(() => _showBackToTop = true);
      } else if (_scrollController.offset <= 300 && _showBackToTop) {
        setState(() => _showBackToTop = false);
      }
    });
  }
  
  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }
  
  // Load saved items status
  Future<void> _loadSavedItems() async {
    final items = await SavedItemsService.getSavedItems();
    if (mounted) {
      setState(() {
        for (final item in items) {
          _savedItems[item['id'] as String] = true;
        }
      });
    }
  }
  
  // Toggle save status for an item
  Future<void> _toggleSaveItem(InventoryItem item) async {
    final itemId = item.code;
    final isSaved = _savedItems[itemId] ?? false;
    
    // Convert InventoryItem to Map<String, dynamic>
    final itemMap = {
      'id': item.code,
      'code': item.code,
      'description': item.description,
      'color': item.color,
      'type': item.type,
      'size': item.size,
      'quantity': item.quantity,
      'location': item.location,
      'design': item.design,
      'finish': item.finish,
      'weight': item.weight,
      'productId': item.productId,
    };
    
    // Use the unified service for consistent behavior
    if (isSaved) {
      await UnifiedSavedItemsService.removeItem(context, itemId);
      if (mounted) {
        setState(() {
          _savedItems[itemId] = false;
        });
      }
    } else {
      await UnifiedSavedItemsService.saveItem(context, itemMap);
      if (mounted) {
        setState(() {
          _savedItems[itemId] = true;
        });
      }
    }
    
    // Show feedback
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isSaved ? 'Item removed from saved items' : 'Item saved!'),
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }
  
  // Add item to cart with quantity
  void _addToCart(BuildContext context, InventoryItem item, int quantity) {
    // Create a cart item with the selected quantity
    final cartItem = {
      'id': item.code,
      'code': item.code,
      'description': item.description,
      'quantity': quantity,
      'color': item.color,
      'size': item.size,
      'type': item.type,
      'price': 0.0,  // Add default price (can be updated later)
      'location': item.location,
      'design': item.design,
      'finish': item.finish,
    };
    
    // Add to cart using the CartState provider
    final cartState = Provider.of<CartState>(context, listen: false);
    
    // If item exists, update quantity, otherwise add new item
    final existingIndex = cartState.items.indexWhere((i) => i['id'] == cartItem['id']);
    
    if (existingIndex >= 0) {
      // Update existing item quantity
      final currentQuantity = cartState.items[existingIndex]['quantity'] as int;
      cartState.updateQuantity(cartState.items[existingIndex], currentQuantity + quantity);
    } else {
      // Add as new item with specified quantity
      cartState.addItemWithQuantity(cartItem, quantity);
    }
    
    // Show success message with modern map-style design
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.check_circle, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                '${item.description} added to cart',
                style: const TextStyle(color: Colors.white),
              ),
            ),
          ],
        ),
        backgroundColor: Theme.of(context).primaryColor,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        margin: const EdgeInsets.all(8),
        duration: const Duration(seconds: 2),
        action: SnackBarAction(
          label: 'VIEW CART',
          textColor: Colors.amber,
          onPressed: () {
            // Using NavigationService for context-independent navigation
            // This avoids widget lifecycle issues with the context
            NavigationService().navigateToNamed(AppRouter.cart);
          },
        ),
      ),
    );
  }
  
  // Show quantity selector dialog
  Future<void> _showQuantityDialog(BuildContext context, InventoryItem item) async {
    int quantity = 1;
    
    await showDialog<void>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) {
          return AlertDialog(
            title: const Text('Select Quantity'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(item.description),
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.remove),
                      onPressed: quantity > 1 ? () {
                        setState(() {
                          quantity--;
                        });
                      } : null,
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey.shade300),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        quantity.toString(),
                        style: const TextStyle(fontSize: 18),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.add),
                      onPressed: () {
                        setState(() {
                          quantity++;
                        });
                      },
                    ),
                  ],
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('CANCEL'),
              ),
              ElevatedButton(
                onPressed: () {
                  _addToCart(context, item, quantity);
                  Navigator.pop(context);
                },
                child: const Text('ADD TO CART'),
              ),
            ],
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isSmallScreen = MediaQuery.of(context).size.width < 600;

    return FutureBuilder<List<InventoryItem>>(
      future: widget.future,
      builder: (context, snapshot) {
        // Loading state
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        
        // Error state
        if (snapshot.hasError) {
          debugPrint('Inventory load error: ${snapshot.error}');
          return Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, color: Colors.red, size: 50),
                const SizedBox(height: 16),
                Text(
                  'Unable to load inventory',
                  style: theme.textTheme.titleMedium?.copyWith(color: Colors.red),
                ),
                const SizedBox(height: 8),
                const Text('Please check your connection and try again.'),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: widget.onRetry,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Retry'),
                ),
              ],
            ),
          );
        }
        
        // Empty state
        if (!snapshot.hasData || snapshot.data!.isEmpty) {
          return SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            child: SizedBox(
              height: MediaQuery.of(context).size.height * 0.8,
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.inventory_2_outlined,
                        size: 64,
                        color: theme.hintColor.withValues(alpha: 0.5)),
                    const SizedBox(height: 16),
                    Text(
                      'No items found',
                      style: theme.textTheme.titleMedium,
                    ),
                    const SizedBox(height: 8),
                    const Text('Try adjusting your search or filters'),
                  ],
                ),
              ),
            ),
          );
        }

        final items = snapshot.data!;
        
        // For small screens, show a list view with compact rows
        if (isSmallScreen) {
          return Stack(
          children: [
            ListView.builder(
              controller: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(8),
            itemCount: items.length,
              itemBuilder: (context, index) {
              final item = items[index];
              
              final isSaved = _savedItems[item.code] ?? false;
              
              return Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: InkWell(
                  onTap: () {
                    // Show detailed dialog when item is tapped
                    _showItemDetailsDialog(context, item);
                  },
                  child: Padding(
                    padding: const EdgeInsets.all(12.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                item.description.isNotEmpty ? item.description : 'No description',
                                style: theme.textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            // Save button (top right)
                            IconButton(
                              icon: Icon(
                                isSaved ? Icons.bookmark : Icons.bookmark_border,
                                color: isSaved ? Colors.amber : null,
                              ),
                              onPressed: () => _toggleSaveItem(item),
                              tooltip: isSaved ? 'Remove from saved items' : 'Save item',
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        _buildInfoRow('Code', item.code),
                        _buildInfoRow('Color', item.color),
                        
                        // Size and Location in parallel with Add to Cart button
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 2.0),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Size and Location column
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    // Size row
                                    Row(
                                      children: [
                                        const Text(
                                          'Size: ',
                                          style: TextStyle(
                                            fontWeight: FontWeight.w500,
                                            color: Colors.grey,
                                          ),
                                        ),
                                        Expanded(
                                          child: Text(
                                            item.size,
                                            style: const TextStyle(
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 2),
                                    // Location row
                                    Row(
                                      children: [
                                        const Text(
                                          'Location: ',
                                          style: TextStyle(
                                            fontWeight: FontWeight.w500,
                                            color: Colors.grey,
                                          ),
                                        ),
                                        Expanded(
                                          child: Text(
                                            item.location,
                                            style: const TextStyle(
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              // Add to cart button
                              IconButton(
                                icon: const Icon(Icons.add_shopping_cart),
                                onPressed: () => _showQuantityDialog(context, item),
                                tooltip: 'Add to cart',
                                padding: EdgeInsets.zero,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
            // Back to top button
            if (_showBackToTop)
              Positioned(
                bottom: 20,
                right: 20,
                child: FloatingActionButton(
                  onPressed: () {
                    _scrollController.animateTo(
                      0,
                      duration: const Duration(milliseconds: 500),
                      curve: Curves.easeInOut,
                    );
                  },
                  backgroundColor: Theme.of(context).primaryColor,
                  mini: true,
                  heroTag: 'backToTop',
                  child: const Icon(Icons.arrow_upward, color: Colors.white), // Unique hero tag
                ),
              ),
          ],
        );}
        
        // For larger screens, show a responsive data table
        return LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: ConstrainedBox(
                  constraints: BoxConstraints(minWidth: constraints.maxWidth),
                  child: DataTable(
                  columnSpacing: 16,
                  horizontalMargin: 12,
                  headingRowHeight: 56,
                  dataRowMinHeight: 48,
                  dataRowMaxHeight: 72,
                  columns: const [
                    DataColumn(
                      label: Text('Description', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: false,
                    ),
                    DataColumn(
                      label: Text('Color', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: false,
                    ),
                    DataColumn(
                      label: Text('Size', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: true,
                    ),
                    DataColumn(
                      label: Text('Location', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: false,
                    ),
                    DataColumn(
                      label: Text('Save', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: false,
                    ),
                    DataColumn(
                      label: Text('Cart', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: false,
                    ),
                  ],
                  rows: items.map((item) {
                    final isSaved = _savedItems[item.code] ?? false;
                    
                    return DataRow(
                      onSelectChanged: (_) {
                        // Show detailed dialog when row is tapped
                        _showItemDetailsDialog(context, item);
                      },
                      cells: [
                        DataCell(
                          SizedBox(
                            width: constraints.maxWidth * 0.4,
                            child: Text(
                              item.description.isNotEmpty ? item.description : 'No description',
                              overflow: TextOverflow.ellipsis,
                              maxLines: 2,
                            ),
                          ),
                        ),
                        DataCell(Text(item.color)),
                        DataCell(Text(item.size)),
                        DataCell(Text(item.location)),
                        // Save button cell
                        DataCell(
                          IconButton(
                            icon: Icon(
                              isSaved ? Icons.bookmark : Icons.bookmark_border,
                              color: isSaved ? Colors.amber : null,
                            ),
                            onPressed: () => _toggleSaveItem(item),
                            tooltip: isSaved ? 'Remove from saved items' : 'Save item',
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                        ),
                        // Add to cart button cell
                        DataCell(
                          IconButton(
                            icon: const Icon(Icons.add_shopping_cart),
                            onPressed: () => _showQuantityDialog(context, item),
                            tooltip: 'Add to cart',
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                        ),
                      ],
                    );
                  }).toList(),
                ),
              ),
            ));
          },
        );
      },
    );
  }
  
  // Helper method to build info row for mobile view
  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2.0),
      child: Row(
        children: [
          Text(
            '$label: ',
            style: const TextStyle(
              fontWeight: FontWeight.w500,
              color: Colors.grey,
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
  
  // Navigate to detailed item page instead of showing dialog
  void _showItemDetailsDialog(BuildContext context, InventoryItem item) {
    // Use GoRouter to navigate to the details page
    GoRouter.of(context).pushNamed(
      'inventory-item-details',
      extra: item,
    );
  }
}

