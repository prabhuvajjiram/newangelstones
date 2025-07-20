class InventoryItem {
  final String code;
  final String description;
  final String color;
  final String size;
  final String location;

  InventoryItem({
    required this.code,
    required this.description,
    required this.color,
    required this.size,
    this.location = '',
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

    return InventoryItem(
      code: getField(['EndProductCode', 'code']),
      description: getField(['EndProductDescription', 'description']),
      color: getField(['PColor', 'color']),
      size: getField(['Size', 'size']),
      location: getField(['Location', 'LocName', 'location']),
    );
  }
}
