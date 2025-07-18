import 'package:flutter/material.dart';
import '../models/inventory_item.dart';

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
                    color: theme.hintColor.withOpacity(0.5)),
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
        
        // For small screens, show a card-based layout
        if (isSmallScreen) {
          return ListView.builder(
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index];
              return Card(
                margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                child: Padding(
                  padding: const EdgeInsets.all(12.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.description.isNotEmpty ? item.description : 'No description',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 8),
                      if (item.color.isNotEmpty) 
                        _buildInfoRow('Color', item.color),
                      if (item.size.isNotEmpty)
                        _buildInfoRow('Size', item.size),
                    ],
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

