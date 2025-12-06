class InventoryItem {
  final String code;           // EndProductCode
  final String description;    // EndProductDescription
  final String color;          // PColor
  final String size;           // Size
  final String location;       // Locationname
  final int quantity;          // Qty
  final int productId;         // EndProductId
  final String type;           // Ptype / ProductType
  final String design;         // PDesign / Design
  final String finish;         // PFinish / Finish
  
  // Detailed fields from GetAllStockDetailedSummary API
  final String container;      // Container (e.g., "AS-16")
  final String crateNo;        // CrateNo (e.g., "15")
  final String weight;         // Weight in lbs
  final String status;         // Status (e.g., "In-Stock")
  final String sublocation;    // SublocationName (e.g., "Warehouse")
  final String comments;       // Comments
  final int stockId;           // StockId
  final int locationId;        // Locid
  final bool hasComments;      // Hascomments
  
  // Additional fields that might be useful for display
  final String length;
  final String width;
  final String height;
  final String lastUpdated;
  final String notes;
  
  // Parse size string (e.g., "2-0 x 1-0 x 0-8") to get dimensions in inches
  String get lengthInInches {
    try {
      final dimensions = _parseDimensions(size);
      if (dimensions.isNotEmpty) {
        return '${dimensions[0]} inches';
      }
    } catch (e) {
      // Unable to parse length from size string
    }
    return length.isNotEmpty ? '$length inches' : '';
  }
  
  String get heightInInches {
    try {
      final dimensions = _parseDimensions(size);
      if (dimensions.length > 1) {
        return '${dimensions[1]} inches';
      }
    } catch (e) {
      // Unable to parse height from size string
    }
    return height.isNotEmpty ? '$height inches' : '';
  }
  
  String get widthInInches {
    try {
      final dimensions = _parseDimensions(size);
      if (dimensions.length > 2) {
        return '${dimensions[2]} inches';
      }
    } catch (e) {
      // Unable to parse width from size string
    }
    return width.isNotEmpty ? '$width inches' : '';
  }
  
  // Parse dimensions from format like "2-0 x 1-0 x 0-8" to inches [24, 12, 8]
  List<int> _parseDimensions(String sizeStr) {
    if (sizeStr.isEmpty) return [];
    
    // Split by 'x' to get each dimension
    final parts = sizeStr.toLowerCase().split('x').map((s) => s.trim()).toList();
    List<int> inches = [];
    
    for (var part in parts) {
      // Check if it contains feet-inches format (e.g., "2-0")
      if (part.contains('-')) {
        final feetInches = part.split('-');
        if (feetInches.length == 2) {
          try {
            final feet = int.tryParse(feetInches[0]) ?? 0;
            final inch = int.tryParse(feetInches[1]) ?? 0;
            inches.add((feet * 12) + inch); // Convert to total inches
          } catch (e) {
            inches.add(0);
          }
        } else {
          inches.add(0);
        }
      } else {
        // Just inches
        inches.add(int.tryParse(part) ?? 0);
      }
    }
    
    return inches;
  }

  InventoryItem({
    required this.code,
    required this.description,
    required this.color,
    required this.size,
    this.location = '',
    this.quantity = 0,
    this.productId = 0,
    this.type = '',
    this.design = '',
    this.finish = '',
    this.container = '',
    this.crateNo = '',
    this.weight = '',
    this.status = '',
    this.sublocation = '',
    this.comments = '',
    this.stockId = 0,
    this.locationId = 0,
    this.hasComments = false,
    this.length = '',
    this.width = '',
    this.height = '',
    this.lastUpdated = '',
    this.notes = '',
  });

  factory InventoryItem.fromJson(Map<String, dynamic> json) {
    String getField(List<String> keys) {
      for (final k in keys) {
        if (json.containsKey(k)) {
          final value = json[k];
          if (value != null) return value.toString();
        }
        final lower = k.toLowerCase();
        final match = json.keys.firstWhere(
          (e) => e.toLowerCase() == lower,
          orElse: () => '',
        );
        if (match.isNotEmpty) {
          final value = json[match];
          if (value != null) return value.toString();
        }
      }
      return '';
    }
    
    // Parse quantity as integer
    int getQuantity() {
      final qtyStr = getField(['Qty', 'Quantity', 'quantity']);
      return int.tryParse(qtyStr) ?? 0;
    }
    
    // Parse product ID as integer
    int getProductId() {
      final idStr = getField(['EndProductId', 'ProductId', 'product_id']);
      return int.tryParse(idStr) ?? 0;
    }
    
    // Parse stock ID as integer
    int getStockId() {
      final idStr = getField(['StockId', 'stock_id']);
      return int.tryParse(idStr) ?? 0;
    }
    
    // Parse location ID as integer
    int getLocationId() {
      final idStr = getField(['Locid', 'LocationId', 'location_id']);
      return int.tryParse(idStr) ?? 0;
    }
    
    // Parse boolean fields
    bool getHasComments() {
      final value = json['Hascomments'] ?? json['hasComments'] ?? json['has_comments'];
      if (value is bool) return value;
      if (value is String) return value.toLowerCase() == 'true';
      return false;
    }

    return InventoryItem(
      code: getField(['EndProductCode', 'code']),
      description: getField(['EndProductDescription', 'description']),
      color: getField(['PColor', 'Color', 'color']),
      size: getField(['Size', 'size']),
      location: getField(['Locationname', 'LocationName', 'Location', 'LocName', 'location']),
      quantity: getQuantity(),
      productId: getProductId(),
      type: getField(['Ptype', 'ProductType', 'Type', 'type']),
      design: getField(['PDesign', 'Design', 'design']),
      finish: getField(['PFinish', 'Finish', 'finish']),
      container: getField(['Container', 'container']),
      crateNo: getField(['CrateNo', 'CrateNumber', 'crate_no', 'crate_number']),
      weight: getField(['Weight', 'weight']),
      status: getField(['Status', 'status']),
      sublocation: getField(['SublocationName', 'Sublocation', 'sublocation']),
      comments: getField(['Comments', 'comments']),
      stockId: getStockId(),
      locationId: getLocationId(),
      hasComments: getHasComments(),
      length: getField(['Length', 'length']),
      width: getField(['Width', 'width']),
      height: getField(['Height', 'height']),
      lastUpdated: getField(['LastUpdated', 'last_updated', 'updated_at']),
      notes: getField(['Notes', 'notes', 'additional_info']),
    );
  }
}
