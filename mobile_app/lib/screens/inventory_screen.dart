import 'dart:async';

import 'package:flutter/material.dart';
import '../models/inventory_item.dart';
import '../services/inventory_service.dart';
import '../widgets/inventory_table_section.dart';

class InventoryScreen extends StatefulWidget {
  final InventoryService inventoryService;
  final String? initialColorFilter;
  
  const InventoryScreen({
    super.key, 
    required this.inventoryService,
    this.initialColorFilter,
  });

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
    
    // Apply initial color filter if provided
    if (widget.initialColorFilter != null) {
      _selectedColor = widget.initialColorFilter;
    }
    
    // Load initial filter options immediately
    _fetchFilterOptions();
    
    // Load inventory from cache/local first (offline-first strategy)
    _loadInventoryOfflineFirst();
    
    // Sync fresh data in background
    Future.delayed(const Duration(milliseconds: 500), () {
      _loadInventory(forceRefresh: true).catchError((e) {
        debugPrint('Background inventory sync error: $e');
      });
    });
  }
  
  Future<void> _loadInventoryOfflineFirst() async {
    setState(() {
      _futureInventory = widget.inventoryService.fetchInventory(
        pageSize: 100,
        searchQuery: _searchQuery.isNotEmpty ? _searchQuery : null,
        type: _selectedType,
        color: _selectedColor,
        forceRefresh: false, // Use cache first
      );
    });
  }
  
  void _fetchFilterOptions() async {
    // First load inventory to populate filter options
    await widget.inventoryService.fetchInventory();
    
    // Directly set the types from the inventory service's default types
    // This ensures we always have all the types regardless of API response
    setState(() {
      // Force use of the default types list
      _availableTypes = [
        'Base',
        'Bench Seat',
        'Bevel Marker',
        'Cap',
        'Ledger',
        'Legs',
        'Marker',
        'Panel',
        'Pedestal',
        'Piece',
        'Slab',
        'Slant',
        'Support',
        'Tablet',
        'Vase',
        'Design',
        'Monument'
      ];
      
      // Get colors from the service
      _availableColors = widget.inventoryService.availableColors;
    });
    
    // Debug log to verify types are loaded
    debugPrint('📋 Available types: ${_availableTypes.join(', ')}');
    debugPrint('🎨 Available colors: ${_availableColors.join(', ')}');
  }

  @override
  void dispose() {
    _searchController.dispose();
    _searchDebounce?.cancel();
    _searchFocusNode.dispose();
    super.dispose();
  }

  Future<void> _loadInventory({bool forceRefresh = false}) async {
    setState(() {
      _futureInventory = widget.inventoryService.fetchInventory(
        pageSize: 100,
        searchQuery: _searchQuery.isNotEmpty ? _searchQuery : null,
        type: _selectedType,
        color: _selectedColor,
        forceRefresh: forceRefresh,
      );
    });
  }
  
  Future<void> _refreshData() async {
    // Reset filters on pull-to-refresh for a clean reload
    _searchController.clear();
    _searchQuery = '';
    _selectedType = null;
    _selectedColor = null;
    
    // Reload inventory data
    await _loadInventory(forceRefresh: true);
    
    // Refresh filter options
    _fetchFilterOptions();
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
      child: RefreshIndicator(
        onRefresh: _refreshData,
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
                  hintText: 'Search inventory...(Pull to load more)',
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
    ),
    );
  }
  
  // Show filter dialog with options
  Future<void> _showFilterDialog({
    required String title,
    required List<String> options,
    required String? selectedValue,
    required void Function(String?) onSelected,
  }) async {
    await showDialog<void>(
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
