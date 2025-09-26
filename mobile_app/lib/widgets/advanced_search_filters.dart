import 'package:flutter/material.dart';

class SearchFilters {
  String? selectedType;
  String? selectedColor;
  String? selectedSize;
  String? selectedLocation;
  String? selectedFinish;
  double? minPrice;
  double? maxPrice;
  bool inStockOnly;

  SearchFilters({
    this.selectedType,
    this.selectedColor,
    this.selectedSize,
    this.selectedLocation,
    this.selectedFinish,
    this.minPrice,
    this.maxPrice,
    this.inStockOnly = false,
  });

  SearchFilters copyWith({
    String? selectedType,
    String? selectedColor,
    String? selectedSize,
    String? selectedLocation,
    String? selectedFinish,
    double? minPrice,
    double? maxPrice,
    bool? inStockOnly,
  }) {
    return SearchFilters(
      selectedType: selectedType ?? this.selectedType,
      selectedColor: selectedColor ?? this.selectedColor,
      selectedSize: selectedSize ?? this.selectedSize,
      selectedLocation: selectedLocation ?? this.selectedLocation,
      selectedFinish: selectedFinish ?? this.selectedFinish,
      minPrice: minPrice ?? this.minPrice,
      maxPrice: maxPrice ?? this.maxPrice,
      inStockOnly: inStockOnly ?? this.inStockOnly,
    );
  }

  void clear() {
    selectedType = null;
    selectedColor = null;
    selectedSize = null;
    selectedLocation = null;
    selectedFinish = null;
    minPrice = null;
    maxPrice = null;
    inStockOnly = false;
  }

  bool get hasActiveFilters {
    return selectedType != null ||
        selectedColor != null ||
        selectedSize != null ||
        selectedLocation != null ||
        selectedFinish != null ||
        minPrice != null ||
        maxPrice != null ||
        inStockOnly;
  }

  int get activeFilterCount {
    int count = 0;
    if (selectedType != null) count++;
    if (selectedColor != null) count++;
    if (selectedSize != null) count++;
    if (selectedLocation != null) count++;
    if (selectedFinish != null) count++;
    if (minPrice != null || maxPrice != null) count++;
    if (inStockOnly) count++;
    return count;
  }
}

class AdvancedSearchFilters extends StatefulWidget {
  final SearchFilters filters;
  final void Function(SearchFilters) onFiltersChanged;
  final List<String> availableTypes;
  final List<String> availableColors;
  final List<String> availableSizes;
  final List<String> availableLocations;
  final List<String> availableFinishes;

  const AdvancedSearchFilters({
    super.key,
    required this.filters,
    required this.onFiltersChanged,
    this.availableTypes = const [],
    this.availableColors = const [],
    this.availableSizes = const [],
    this.availableLocations = const [],
    this.availableFinishes = const [],
  });

  @override
  State<AdvancedSearchFilters> createState() => _AdvancedSearchFiltersState();
}

class _AdvancedSearchFiltersState extends State<AdvancedSearchFilters> {
  late SearchFilters _currentFilters;

  @override
  void initState() {
    super.initState();
    _currentFilters = SearchFilters(
      selectedType: widget.filters.selectedType,
      selectedColor: widget.filters.selectedColor,
      selectedSize: widget.filters.selectedSize,
      selectedLocation: widget.filters.selectedLocation,
      selectedFinish: widget.filters.selectedFinish,
      minPrice: widget.filters.minPrice,
      maxPrice: widget.filters.maxPrice,
      inStockOnly: widget.filters.inStockOnly,
    );
  }

  void _updateFilters() {
    widget.onFiltersChanged(_currentFilters);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Advanced Filters',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              Row(
                children: [
                  if (_currentFilters.hasActiveFilters)
                    TextButton(
                      onPressed: () {
                        setState(() {
                          _currentFilters.clear();
                        });
                        _updateFilters();
                      },
                      child: const Text('Clear All'),
                    ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Scrollable filter content
          Flexible(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Product Type Filter
                  if (widget.availableTypes.isNotEmpty) ...[
                    _buildFilterSection(
                      'Product Type',
                      _buildDropdownFilter(
                        value: _currentFilters.selectedType,
                        items: widget.availableTypes,
                        hint: 'Select type',
                        onChanged: (value) {
                          setState(() {
                            _currentFilters.selectedType = value;
                          });
                          _updateFilters();
                        },
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Color Filter
                  if (widget.availableColors.isNotEmpty) ...[
                    _buildFilterSection(
                      'Color',
                      _buildDropdownFilter(
                        value: _currentFilters.selectedColor,
                        items: widget.availableColors,
                        hint: 'Select color',
                        onChanged: (value) {
                          setState(() {
                            _currentFilters.selectedColor = value;
                          });
                          _updateFilters();
                        },
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Size Filter
                  if (widget.availableSizes.isNotEmpty) ...[
                    _buildFilterSection(
                      'Size',
                      _buildDropdownFilter(
                        value: _currentFilters.selectedSize,
                        items: widget.availableSizes,
                        hint: 'Select size',
                        onChanged: (value) {
                          setState(() {
                            _currentFilters.selectedSize = value;
                          });
                          _updateFilters();
                        },
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Location Filter
                  if (widget.availableLocations.isNotEmpty) ...[
                    _buildFilterSection(
                      'Location',
                      _buildDropdownFilter(
                        value: _currentFilters.selectedLocation,
                        items: widget.availableLocations,
                        hint: 'Select location',
                        onChanged: (value) {
                          setState(() {
                            _currentFilters.selectedLocation = value;
                          });
                          _updateFilters();
                        },
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Finish Filter
                  if (widget.availableFinishes.isNotEmpty) ...[
                    _buildFilterSection(
                      'Finish',
                      _buildDropdownFilter(
                        value: _currentFilters.selectedFinish,
                        items: widget.availableFinishes,
                        hint: 'Select finish',
                        onChanged: (value) {
                          setState(() {
                            _currentFilters.selectedFinish = value;
                          });
                          _updateFilters();
                        },
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // In Stock Only Toggle
                  _buildFilterSection(
                    'Availability',
                    SwitchListTile(
                      title: const Text('In Stock Only'),
                      subtitle: const Text('Show only items currently available'),
                      value: _currentFilters.inStockOnly,
                      onChanged: (value) {
                        setState(() {
                          _currentFilters.inStockOnly = value;
                        });
                        _updateFilters();
                      },
                      contentPadding: EdgeInsets.zero,
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Apply button
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () {
                Navigator.pop(context);
              },
              child: Text(
                _currentFilters.hasActiveFilters
                    ? 'Apply ${_currentFilters.activeFilterCount} Filter${_currentFilters.activeFilterCount == 1 ? '' : 's'}'
                    : 'Close',
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterSection(String title, Widget child) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 8),
        child,
      ],
    );
  }

  Widget _buildDropdownFilter({
    required String? value,
    required List<String> items,
    required String hint,
    required void Function(String?) onChanged,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey.withValues(alpha: 0.3)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          hint: Text(hint),
          isExpanded: true,
          items: [
            DropdownMenuItem<String>(
              value: null,
              child: Text('All ${hint.toLowerCase()}'),
            ),
            ...items.map((item) => DropdownMenuItem<String>(
              value: item,
              child: Text(item),
            )),
          ],
          onChanged: onChanged,
        ),
      ),
    );
  }
}
