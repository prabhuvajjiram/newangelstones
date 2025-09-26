class InventoryItem {
  final String code;           // EndProductCode
  final String description;    // EndProductDescription
  final String color;          // PColor
  final String size;           // Size
  final String location;       // Locationname
  final int quantity;          // Qty
  final int productId;         // EndProductId
  final String type;           // Ptype
  final String design;         // PDesign
  final String finish;         // PFinish
  
  // Additional fields that might be in the API or useful for display
  final String length;
  final String width;
  final String height;
  final String weight;
  final String status;
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
    this.length = '',
    this.width = '',
    this.height = '',
    this.weight = '',
    this.status = '',
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

    return InventoryItem(
      code: getField(['EndProductCode', 'code']),
      description: getField(['EndProductDescription', 'description']),
      color: getField(['PColor', 'color']),
      size: getField(['Size', 'size']),
      location: getField(['Locationname', 'Location', 'LocName', 'location']),
      quantity: getQuantity(),
      productId: getProductId(),
      type: getField(['Ptype', 'Type', 'type']),
      design: getField(['PDesign', 'Design', 'design']),
      finish: getField(['PFinish', 'Finish', 'finish']),
      length: getField(['Length', 'length']),
      width: getField(['Width', 'width']),
      height: getField(['Height', 'height']),
      weight: getField(['Weight', 'weight']),
      status: getField(['Status', 'status']),
      lastUpdated: getField(['LastUpdated', 'last_updated', 'updated_at']),
      notes: getField(['Notes', 'notes', 'additional_info']),
    );
  }
}
