class QuoteRequest {
  final String name;
  final String email;
  final String phone;
  final String projectDetails;
  final List<Map<String, dynamic>> cartItems;
  final int totalQuantity;
  final DateTime createdAt;

  QuoteRequest({
    required this.name,
    required this.email,
    required this.phone,
    required this.projectDetails,
    required this.cartItems,
    required this.totalQuantity,
    DateTime? createdAt,
  }) : createdAt = createdAt ?? DateTime.now();

  // Convert cart items to a formatted string for the form
  String get formattedCartItems {
    return cartItems.map((item) {
      final itemName = item['description'] ?? item['name'] ?? item['code'] ?? 'Unknown Item';
      final itemCode = item['code'] ?? '';
      final itemColor = item['color'] ?? '';
      final itemSize = item['size'] ?? '';
      
      return '${item['quantity']}x $itemName${itemCode.toString().isNotEmpty ? ' (Code: $itemCode)' : ''}${itemColor.toString().isNotEmpty ? ' (Color: $itemColor)' : ''}${itemSize.toString().isNotEmpty ? ' (Size: $itemSize)' : ''}';
    }).join('\n');
  }
  
  // Combine project details and cart items for Mautic form
  String get combinedProjectDetails {
    final itemsList = cartItems.map((item) {
      final itemName = item['description'] ?? item['name'] ?? item['code'] ?? 'Unknown Item';
      final itemCode = item['code'] ?? '';
      final itemQty = item['quantity'] ?? 1;
      
      return '$itemQty x $itemName${itemCode.toString().isNotEmpty ? ' (Code: $itemCode)' : ''}';
    }).join('\n');
    
    return 'PROJECT DETAILS:\n$projectDetails\n\nITEMS REQUESTED:\n$itemsList';
  }

  // Convert to map for form submission
  Map<String, dynamic> toFormData() {
    return {
      'name': name,
      'email': email,
      'phone': phone,
      'projectDetails': projectDetails,
      'cartItems': formattedCartItems,
      'totalQuantity': totalQuantity,
    };
  }
}
