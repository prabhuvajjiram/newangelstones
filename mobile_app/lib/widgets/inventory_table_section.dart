import 'package:flutter/material.dart';
import '../models/inventory_item.dart';

class InventoryTableSection extends StatelessWidget {
  final String title;
  final Future<List<InventoryItem>> future;
  const InventoryTableSection({super.key, required this.title, required this.future});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          FutureBuilder<List<InventoryItem>>(
            future: future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Center(child: CircularProgressIndicator());
              } else if (snapshot.hasError) {
                return Text('Error: ${snapshot.error}');
              } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
                return const Text('No items found');
              }
              final items = snapshot.data!;
              return SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: DataTable(
                  columns: const [
                    DataColumn(label: Text('Code')),
                    DataColumn(label: Text('Description')),
                    DataColumn(label: Text('Color')),
                    DataColumn(label: Text('Size')),
                    DataColumn(label: Text('Status')),
                  ],
                  rows: items.map((item) {
                    return DataRow(cells: [
                      DataCell(Text(item.code)),
                      DataCell(Text(item.description)),
                      DataCell(Text(item.color)),
                      DataCell(Text(item.size)),
                      DataCell(Text(item.status)),
                    ]);
                  }).toList(),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

