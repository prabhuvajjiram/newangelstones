import 'dart:async';

import 'package:flutter/material.dart';
import '../models/inventory_item.dart';
import '../services/inventory_service.dart';
import '../widgets/inventory_table_section.dart';

class InventoryScreen extends StatefulWidget {
  final InventoryService inventoryService;
  const InventoryScreen({super.key, required this.inventoryService});

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  late Future<List<InventoryItem>> _futureInventory;
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String? _selectedType;
  String? _selectedColor;
  Timer? _searchDebounce;

  @override
  void initState() {
    super.initState();
    _loadInventory();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _searchDebounce?.cancel();
    super.dispose();
  }

  void _loadInventory() {
    setState(() {
      _futureInventory = widget.inventoryService.fetchInventory(
        pageSize: 100,
        searchQuery: _searchQuery.isNotEmpty ? _searchQuery : null,
        type: _selectedType,
        color: _selectedColor,
      );
    });
  }

  void _onSearchChanged(String query) {
    _searchQuery = query;
    // Cancel previous debounce timer
    _searchDebounce?.cancel();
    // Start a new debounce timer
    _searchDebounce = Timer(const Duration(milliseconds: 500), () {
      _loadInventory();
    });
  }

  void _clearFilters() {
    setState(() {
      _searchController.clear();
      _searchQuery = '';
      _selectedType = null;
      _selectedColor = null;
      _loadInventory();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Search and filter section
        Padding(
          padding: const EdgeInsets.all(8.0),
          child: Column(
            children: [
              // Search bar
              TextField(
                controller: _searchController,
                decoration: InputDecoration(
                  hintText: 'Search inventory...',
                  prefixIcon: const Icon(Icons.search),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  suffixIcon: _searchQuery.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear),
                          onPressed: () {
                            _searchController.clear();
                            _searchQuery = '';
                            _loadInventory();
                          },
                        )
                      : null,
                ),
                onChanged: _onSearchChanged,
              ),
              const SizedBox(height: 8),
              // Filter chips
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    FilterChip(
                      label: const Text('Type'),
                      selected: _selectedType != null,
                      onSelected: (selected) {
                        // TODO: Show type filter dialog
                      },
                    ),
                    const SizedBox(width: 8),
                    FilterChip(
                      label: const Text('Color'),
                      selected: _selectedColor != null,
                      onSelected: (selected) {
                        // TODO: Show color filter dialog
                      },
                    ),
                    const SizedBox(width: 8),
                    if (_searchQuery.isNotEmpty ||
                        _selectedType != null ||
                        _selectedColor != null)
                      TextButton(
                        onPressed: _clearFilters,
                        child: const Text('Clear Filters'),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
        // Inventory table
        Expanded(
          child: InventoryTableSection(
            title: 'Current Inventory',
            future: _futureInventory,
            onRetry: _loadInventory,
          ),
        ),
      ],
    );
  }
}
