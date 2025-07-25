import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../services/saved_items_service.dart';
import '../models/inventory_item.dart';
import '../screens/inventory_item_details_screen.dart';

class SavedItemsScreen extends StatefulWidget {
  const SavedItemsScreen({super.key});

  @override
  State<SavedItemsScreen> createState() => _SavedItemsScreenState();
}

class _SavedItemsScreenState extends State<SavedItemsScreen> {
  List<Map<String, dynamic>> _savedItems = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadSavedItems();
  }

  Future<void> _loadSavedItems() async {
    setState(() => _isLoading = true);
    try {
      final items = await SavedItemsService.getSavedItems();
      if (mounted) {
        setState(() => _savedItems = items);
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }


  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Saved Items'),
        actions: [
          if (_savedItems.isNotEmpty)
            IconButton(
              icon: const Icon(Icons.delete_outline),
              onPressed: _showClearAllDialog,
              tooltip: 'Clear All',
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _savedItems.isEmpty
              ? _buildEmptyState()
              : _buildSavedItemsList(),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.bookmark_border,
            size: 64,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            'No saved items yet',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: Colors.grey[600],
                ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Tap the bookmark icon on any product to save it for later',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.grey),
          ),
        ],
      ),
    );
  }

  Widget _buildSavedItemsList() {
    final theme = Theme.of(context);
    final isSmallScreen = MediaQuery.of(context).size.width < 600;
    
    return RefreshIndicator(
      onRefresh: _loadSavedItems,
      child: ListView.builder(
        padding: const EdgeInsets.all(8),
        itemCount: _savedItems.length,
        itemBuilder: (context, index) {
          final item = _savedItems[index];
          
          // Helper function to safely get string values
          String getStringValue(String key) {
            try {
              final value = item[key];
              return value != null ? value.toString() : '';
            } catch (e) {
              return '';
            }
          }
          
          return Card(
            margin: const EdgeInsets.only(bottom: 8),
            child: InkWell(
              onTap: () {
                // Navigate to inventory item details
                try {
                  // Convert saved item to InventoryItem
                  final inventoryItem = InventoryItem.fromJson(item);
                  
                  // Use Navigator.push with MaterialPageRoute for better context preservation
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (context) => InventoryItemDetailsScreen(item: inventoryItem),
                    ),
                  );
                } catch (e) {
                  debugPrint('Error navigating to item details: $e');
                  // Fallback to GoRouter if Navigator fails
                  try {
                    final inventoryItemForRouter = InventoryItem.fromJson(item);
                    GoRouter.of(context).pushNamed('inventory-item-details', extra: inventoryItemForRouter);
                  } catch (routerError) {
                    debugPrint('Error with GoRouter navigation: $routerError');
                    // Try to show error message
                    try {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Could not open item details')),
                      );
                    } catch (scaffoldError) {
                      debugPrint('Error showing snackbar: $scaffoldError');
                    }
                  }
                }
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
                            getStringValue('description').isNotEmpty 
                                ? getStringValue('description') 
                                : 'No description',
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        // Remove button
                        IconButton(
                          icon: const Icon(
                            Icons.bookmark,
                            color: Colors.amber,
                          ),
                          onPressed: () => _removeItem(item),
                          tooltip: 'Remove from saved items',
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    // Item details
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildInfoRow('Code', getStringValue('code')),
                              _buildInfoRow('Type', getStringValue('type')),
                              _buildInfoRow('Color', getStringValue('color')),
                            ],
                          ),
                        ),
                        if (!isSmallScreen) const SizedBox(width: 16),
                        if (!isSmallScreen)
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildInfoRow('Size', getStringValue('size')),
                                _buildInfoRow('Design', getStringValue('design')),
                                _buildInfoRow('Finish', getStringValue('finish')),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _showClearAllDialog() async {
    if (_savedItems.isEmpty) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Clear All Saved Items'),
        content: const Text('Are you sure you want to remove all saved items?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('CANCEL'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('CLEAR ALL'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      await _clearAllSavedItems();
    }
  }

  Future<void> _clearAllSavedItems() async {
    try {
      // Use the new clearAllItems method from SavedItemsService
      final success = await SavedItemsService.clearAllItems();
      
      if (mounted) {
        if (success) {
          setState(() => _savedItems = []);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('All saved items have been removed')),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Failed to clear saved items')),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error clearing saved items: ${e.toString()}')),
        );
      }
    }
  }
  
  // Remove a single saved item
  Future<void> _removeItem(Map<String, dynamic> item) async {
    String? itemId;
    try {
      itemId = item['id']?.toString();
    } catch (e) {
      debugPrint('Error getting item ID: $e');
    }
    
    if (itemId == null || itemId.isEmpty) {
      debugPrint('Cannot remove item: ID is null or empty');
      return;
    }
    
    try {
      debugPrint('Removing item with ID: $itemId');
      final success = await SavedItemsService.removeItem(itemId);
      if (mounted) {
        if (success) {
          setState(() {
            _savedItems.removeWhere((savedItem) => 
                savedItem['id']?.toString() == itemId);
          });
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Item removed from saved items')),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Failed to remove item')),
          );
        }
      }
    } catch (e) {
      debugPrint('Error removing item: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error removing item: ${e.toString()}')),
        );
      }
    }
  }
  
  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '$label: ',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? 'N/A' : value,
              style: const TextStyle(fontSize: 12),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}