class Product {
  final String id;
  final String name;
  final String description;
  final String imageUrl;
  final double price;

  Product({
    required this.id,
    required this.name,
    required this.description,
    required this.imageUrl,
    required this.price,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic> data =
        json.containsKey('item') ? json['item'] as Map<String, dynamic> : json;

    String imageUrl = '';
    final imageField = data['image'];
    if (imageField is List && imageField.isNotEmpty) {
      final first = imageField.first;
      if (first is Map<String, dynamic>) {
        imageUrl = first['url'] ?? '';
      } else if (first is String) {
        imageUrl = first;
      }
    } else if (imageField is String) {
      imageUrl = imageField;
    }

    final priceField =
        (data['offers'] is Map) ? (data['offers']['price']) : data['price'];

    return Product(
      id: (data['sku'] ?? data['id'] ?? '').toString(),
      name: data['name'] ?? '',
      description: data['description'] ?? '',
      imageUrl: imageUrl,
      price: double.tryParse(priceField?.toString() ?? '') ?? 0.0,
    );
  }
}
