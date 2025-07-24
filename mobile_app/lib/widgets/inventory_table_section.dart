import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../models/inventory_item.dart';
import '../services/saved_items_service.dart';

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
  // Map to track saved status of items
  final Map<String, bool> _savedItems = {};
  
  @override
  void initState() {
    super.initState();
    _loadSavedItems();
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
    
    if (isSaved) {
      await SavedItemsService.removeItem(itemId);
      if (mounted) {
        setState(() {
          _savedItems[itemId] = false;
        });
      }
    } else {
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
      
      await SavedItemsService.saveItem(itemMap);
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
          return Center(
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
          );
        }

        final items = snapshot.data!;
        
        // For small screens, show a list view with compact rows
        if (isSmallScreen) {
          return ListView.builder(
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
                            // Save button
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
                        _buildInfoRow('Size', item.size),
                        _buildInfoRow('Location', item.location),
                      ],
                    ),
                  ),
                ),
              );
            },
          );
        }
        
        // For larger screens, show a responsive data table
        return LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: ConstrainedBox(
                constraints: BoxConstraints(minWidth: constraints.maxWidth),
                child: DataTable(
                  columnSpacing: 16,
                  horizontalMargin: 12,
                  headingRowHeight: 56,
                  dataRowMinHeight: 48,
                  dataRowMaxHeight: 72,
                  columns: [
                    DataColumn(
                      label: const Text('Description', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: false,
                    ),
                    DataColumn(
                      label: const Text('Color', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: false,
                    ),
                    DataColumn(
                      label: const Text('Size', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: true,
                    ),
                    DataColumn(
                      label: const Text('Location', 
                        style: TextStyle(fontWeight: FontWeight.bold)),
                      numeric: false,
                    ),
                    DataColumn(
                      label: const Text('Save', 
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
                      ],
                    );
                  }).toList(),
                ),
              ),
            );
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

