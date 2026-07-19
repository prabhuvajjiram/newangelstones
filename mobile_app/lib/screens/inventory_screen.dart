import 'dart:async';

import 'package:flutter/material.dart';
import '../models/inventory_item.dart';
import '../services/inventory_service.dart';
import '../services/accessibility_service.dart';
import '../widgets/inventory_table_section.dart';

class InventoryScreen extends StatefulWidget {
  final InventoryService inventoryService;
  final String? initialColorFilter;
  final String? initialSearchQuery;

  const InventoryScreen({
    super.key,
    required this.inventoryService,
    this.initialColorFilter,
    this.initialSearchQuery,
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
  String? _selectedLocation;

  // Dynamic filter options from API
  List<String> _availableTypes = [];
  List<String> _availableColors = [];
  List<String> _availableLocations = [];
  Timer? _searchDebounce;

  @override
  void initState() {
    super.initState();

    final initialSearch = widget.initialSearchQuery?.trim() ?? '';
    if (initialSearch.isNotEmpty) {
      _searchController.text = initialSearch;
      _searchQuery = initialSearch;
    }

    // Apply initial color filter if provided
    if (widget.initialColorFilter != null) {
      _selectedColor = widget.initialColorFilter;
    }

    // Load initial filter options immediately
    _fetchFilterOptions();

    // Load inventory from cache/local first (offline-first strategy)
    _loadInventoryOfflineFirst();

    // Refresh the hydrated inventory once in the background. Every startup
    // caller joins the same InventoryService initialization request.
    unawaited(_refreshInventoryInBackground());
  }

  @override
  void didUpdateWidget(covariant InventoryScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    final search = widget.initialSearchQuery?.trim() ?? '';
    if (search.isNotEmpty && search != oldWidget.initialSearchQuery?.trim()) {
      _searchController.text = search;
      _searchQuery = search;
      _loadInventory();
    }
  }

  Future<void> _loadInventoryOfflineFirst() async {
    // Try in-memory cache first (fastest), then local file, then network
    setState(() {
      _futureInventory = _loadFromCacheOrLocal();
    });
  }

  Future<List<InventoryItem>> _loadFromCacheOrLocal() async {
    // fetchInventory hydrates the saved full inventory into memory before it
    // considers any network request.
    final cached = await widget.inventoryService.fetchInventory(
      searchQuery: _searchQuery.isNotEmpty ? _searchQuery : null,
      type: _selectedType,
      color: _selectedColor,
      location: _selectedLocation,
      forceRefresh: false,
    );
    final hasSearchOrFilter = _searchQuery.isNotEmpty ||
        _selectedType != null ||
        _selectedColor != null ||
        _selectedLocation != null;
    if (cached.isNotEmpty || hasSearchOrFilter) return cached;

    // Fallback: read from local file (populated by startup sync)
    return widget.inventoryService.loadLocalInventory();
  }

  Future<void> _fetchFilterOptions() async {
    // Use values that exist in the current API response or offline cache.
    await widget.inventoryService.fetchInventory();
    if (!mounted) return;

    setState(() {
      _availableTypes = widget.inventoryService.availableTypes;
      _availableColors = widget.inventoryService.availableColors;
      _availableLocations = widget.inventoryService.availableLocations;
    });

    debugPrint('📋 Available types: ${_availableTypes.join(', ')}');
    debugPrint('🎨 Available colors: ${_availableColors.join(', ')}');
    debugPrint('📍 Available locations: ${_availableLocations.join(', ')}');
  }

  Future<void> _refreshInventoryInBackground() async {
    await widget.inventoryService.initialize();
    if (!mounted) return;

    await _loadInventory();
    await _fetchFilterOptions();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _searchDebounce?.cancel();
    _searchFocusNode.dispose();
    super.dispose();
  }

  Future<void> _loadInventory() async {
    final request = widget.inventoryService.fetchInventory(
      pageSize: 100,
      searchQuery: _searchQuery.isNotEmpty ? _searchQuery : null,
      type: _selectedType,
      color: _selectedColor,
      location: _selectedLocation,
    );
    setState(() {
      _futureInventory = request;
    });
    final items = await request;
    if (!mounted) return;
    final hasActiveSearch = _searchQuery.isNotEmpty ||
        _selectedType != null ||
        _selectedColor != null ||
        _selectedLocation != null;
    if (hasActiveSearch) {
      AccessibilityService.announce(
        context,
        '${items.length} inventory ${items.length == 1 ? 'item' : 'items'} found',
      );
    }
  }

  Future<void> _refreshData() async {
    // Preserve the visible search/filter scope while refreshing source data.
    await widget.inventoryService.refreshInventory(force: true);
    if (!mounted) return;
    await _loadInventory();

    // Refresh filter options
    _fetchFilterOptions();
  }

  void _onSearchChanged(String query) {
    // Convert query to lowercase for case-insensitive search
    _searchQuery = query.trim().toLowerCase();

    // Cancel previous debounce timer
    _searchDebounce?.cancel();

    // Start a new debounce timer
    _searchDebounce = Timer(const Duration(milliseconds: 200), () {
      _loadInventory();
    });
  }

  void _clearFilters() {
    setState(() {
      _selectedType = null;
      _selectedColor = null;
      _selectedLocation = null;
    });
    _loadInventory();
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
                  FocusTraversalGroup(
                    policy: OrderedTraversalPolicy(),
                    child: Semantics(
                      label: 'Search inventory',
                      textField: true,
                      child: TextField(
                        controller: _searchController,
                        focusNode: _searchFocusNode,
                        decoration: InputDecoration(
                          hintText:
                              'Search code, size, color, type, or location',
                          prefixIcon: const Icon(Icons.search),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          suffixIcon: _searchQuery.isNotEmpty
                              ? IconButton(
                                  icon: const Icon(Icons.clear),
                                  tooltip: 'Clear inventory search',
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
                  ),
                  const SizedBox(height: 8),
                  // Filter chips
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        FilterChip(
                          label: Text(_selectedType != null
                              ? 'Type: $_selectedType'
                              : 'Type'),
                          selected: _selectedType != null,
                          onSelected: (selected) {
                            _showFilterDialog(
                              title: 'Select Type',
                              options: _availableTypes,
                              selectedValue: _selectedType,
                              onSelected: (value) {
                                setState(() {
                                  _selectedType = value;
                                });
                                _loadInventory();
                              },
                            );
                          },
                        ),
                        const SizedBox(width: 8),
                        FilterChip(
                          label: Text(_selectedColor != null
                              ? 'Color: $_selectedColor'
                              : 'Color'),
                          selected: _selectedColor != null,
                          onSelected: (selected) {
                            _showFilterDialog(
                              title: 'Select Color',
                              options: _availableColors,
                              selectedValue: _selectedColor,
                              onSelected: (value) {
                                setState(() {
                                  _selectedColor = value;
                                });
                                _loadInventory();
                              },
                            );
                          },
                        ),
                        const SizedBox(width: 8),
                        FilterChip(
                          label: Text(_selectedLocation != null
                              ? 'Location: $_selectedLocation'
                              : 'Location'),
                          selected: _selectedLocation != null,
                          onSelected: (selected) {
                            _showFilterDialog(
                              title: 'Select Location',
                              options: _availableLocations,
                              selectedValue: _selectedLocation,
                              onSelected: (value) {
                                setState(() {
                                  _selectedLocation = value;
                                });
                                _loadInventory();
                              },
                            );
                          },
                        ),
                        const SizedBox(width: 8),
                        if (_selectedType != null ||
                            _selectedColor != null ||
                            _selectedLocation != null)
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
