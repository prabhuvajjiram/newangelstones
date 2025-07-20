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
  final FocusNode _searchFocusNode = FocusNode();
  late Future<List<InventoryItem>> _futureInventory;
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String? _selectedType;
  String? _selectedColor;
  
  // Dynamic filter options from API
  List<String> _availableTypes = [];
  List<String> _availableColors = [];
  Timer? _searchDebounce;

  @override
  void initState() {
    super.initState();
    _loadInventory();
    
    // Load initial filter options
    _fetchFilterOptions();
  }
  
  void _fetchFilterOptions() async {
    // First load inventory to populate filter options
    await widget.inventoryService.fetchInventory();
    
    // Then get the available options
    setState(() {
      _availableTypes = widget.inventoryService.availableTypes;
      _availableColors = widget.inventoryService.availableColors;
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _searchDebounce?.cancel();
    _searchFocusNode.dispose();
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
    // Convert query to lowercase for case-insensitive search
    _searchQuery = query.trim().toLowerCase();
    
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
    return GestureDetector(
      onTap: () {
        // Dismiss keyboard when tapping outside of text fields
        FocusScope.of(context).unfocus();
      },
      child: Column(
      children: [
        // Search and filter section
        Padding(
          padding: const EdgeInsets.all(8.0),
          child: Column(
            children: [
              // Search bar
              Semantics(
                label: 'Search inventory',
                textField: true,
                child: TextField(
                controller: _searchController,
                focusNode: _searchFocusNode,
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
                            // Clear focus to dismiss keyboard
                            _searchFocusNode.unfocus();
                          },
                        )
                      : null,
                ),
                onChanged: _onSearchChanged,
                // Dismiss keyboard when done/submit button is pressed
                textInputAction: TextInputAction.search,
                onSubmitted: (_) {
                  _searchFocusNode.unfocus();
                },
              ),
              ),
              const SizedBox(height: 8),
              // Filter chips
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    FilterChip(
                      label: Text(_selectedType != null ? 'Type: $_selectedType' : 'Type'),
                      selected: _selectedType != null,
                      onSelected: (selected) {
                        _showFilterDialog(
                          title: 'Select Type',
                          options: _availableTypes,
                          selectedValue: _selectedType,
                          onSelected: (value) {
                            setState(() {
                              _selectedType = value;
                              _loadInventory();
                            });
                          },
                        );
                      },
                    ),
                    const SizedBox(width: 8),
                    FilterChip(
                      label: Text(_selectedColor != null ? 'Color: $_selectedColor' : 'Color'),
                      selected: _selectedColor != null,
                      onSelected: (selected) {
                        _showFilterDialog(
                          title: 'Select Color',
                          options: _availableColors,
                          selectedValue: _selectedColor,
                          onSelected: (value) {
                            setState(() {
                              _selectedColor = value;
                              _loadInventory();
                            });
                          },
                        );
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
    ),
    );
  }
  
  // Show filter dialog with options
  Future<void> _showFilterDialog({
    required String title,
    required List<String> options,
    required String? selectedValue,
    required Function(String?) onSelected,
  }) async {
    await showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: SizedBox(
          width: double.maxFinite,
          child: ListView(
            shrinkWrap: true,
            children: [
              // Add "All" option at the top
              ListTile(
                title: const Text('All'),
                selected: selectedValue == null,
                onTap: () {
                  onSelected(null);
                  Navigator.pop(context);
                },
              ),
              const Divider(),
              ...options.map((option) => ListTile(
                title: Text(option),
                selected: selectedValue == option,
                onTap: () {
                  onSelected(option);
                  Navigator.pop(context);
                },
              )),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
        ],
      ),
    );
  }
}
