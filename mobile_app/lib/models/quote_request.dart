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
      return '${item['quantity']}x ${item['name']} (${item['code']})';
    }).join('\n');
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
