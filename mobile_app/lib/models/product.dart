class Product {
  final String id;
  final String name;
  final String description;
  final String imageUrl;
  final double price;
  final String? label;
  final String? pdfUrl;
  final String? localImagePath;
  final String? category;

  Product({
    required this.id,
    required this.name,
    required this.description,
    required this.imageUrl,
    required this.price,
    this.label,
    this.pdfUrl,
    this.localImagePath,
    this.category,
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

    String? label;
    if (data['label'] != null) {
      label = data['label'].toString();
    } else if (data['category'] is List && (data['category'] as List).isNotEmpty) {
      label = (data['category'] as List).first.toString();
    } else if (data['category'] is String) {
      label = data['category'] as String;
    }

    final localImagePath = data['localImagePath']?.toString();
    final category = data['category'] is String ? data['category'] as String : null;

    return Product(
      id: (data['sku'] ?? data['id'] ?? '').toString(),
      name: data['name'] ?? '',
      description: data['description'] ?? '',
      imageUrl: imageUrl,
      price: double.tryParse(priceField?.toString() ?? '') ?? 0.0,
      label: label,
      pdfUrl: data['pdf']?.toString(),
      localImagePath: localImagePath,
      category: category,
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'imageUrl': imageUrl,
      'price': price,
      'label': label,
      'pdfUrl': pdfUrl,
      'localImagePath': localImagePath,
      'category': category,
    };
  }
}
