import 'package:flutter/material.dart';
import '../models/inventory_item.dart';
import 'enhanced_product_card.dart';

class InventoryTableSection extends StatelessWidget {
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
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isSmallScreen = MediaQuery.of(context).size.width < 600;

    return FutureBuilder<List<InventoryItem>>(
      future: future,
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
                  onPressed: onRetry,
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
        
        // For small screens, show a grid of EnhancedProductCards
        if (isSmallScreen) {
          return GridView.builder(
            padding: const EdgeInsets.all(8),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2, // 2 columns
              childAspectRatio: 0.75, // Adjust the aspect ratio as needed
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
            ),
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index];
              // Create a product map with available fields from InventoryItem
              final productData = {
                'id': item.code, // Using code as ID since InventoryItem doesn't have an id field
                'name': item.description.isNotEmpty ? item.description : 'No description',
                'code': item.code,
                'color': item.color,
                'size': item.size,
                'location': item.location,
                'type': item.description.toLowerCase().contains('design') && 
                         !item.description.toLowerCase().contains('vase') ? 'Vase' : 'Other',
                'imageUrl': 'https://via.placeholder.com/150?text=${Uri.encodeComponent(item.code)}',
              };
              
              return EnhancedProductCard(
                product: productData,
                onTap: () {
                  // Handle product tap
                  // You can navigate to a product detail screen here
                },
                showQuickView: true,
                showSaveForLater: true,
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
                  ],
                  rows: items.map((item) {
                    return DataRow(
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
          Text(
            value,
            style: const TextStyle(
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}

