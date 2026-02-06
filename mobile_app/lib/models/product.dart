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
  
  /// Create a copy of this product with optional field overrides
  Product copyWith({
    String? id,
    String? name,
    String? description,
    String? imageUrl,
    double? price,
    String? label,
    String? pdfUrl,
    String? localImagePath,
    String? category,
  }) {
    return Product(
      id: id ?? this.id,
      name: name ?? this.name,
      description: description ?? this.description,
      imageUrl: imageUrl ?? this.imageUrl,
      price: price ?? this.price,
      label: label ?? this.label,
      pdfUrl: pdfUrl ?? this.pdfUrl,
      localImagePath: localImagePath ?? this.localImagePath,
      category: category ?? this.category,
    );
  }
  
  /// Get the best available image path (local first, fallback to URL)
  String getImagePath() {
    // Prefer local path if available
    if (localImagePath != null && localImagePath!.isNotEmpty) {
      return localImagePath!;
    }
    // Fallback to URL
    return imageUrl;
  }

  factory Product.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic> data =
        json.containsKey('item') ? json['item'] as Map<String, dynamic> : json;

    String imageUrl = '';
    final imageField = data['image'];
    if (imageField is List && imageField.isNotEmpty) {
      final first = imageField.first;
      if (first is Map<String, dynamic>) {
        imageUrl = (first['url'] ?? '') as String;
      } else if (first is String) {
        imageUrl = first;
      }
    } else if (imageField is String) {
      imageUrl = imageField;
    }

    // Extract price from either offers.price or price field
    dynamic priceField;
    if (data['offers'] is Map<String, dynamic>) {
      priceField = (data['offers'] as Map<String, dynamic>)['price'];
    } else {
      priceField = data['price'];
    }
    
    // Parse price safely, defaulting to 0.0 if missing or invalid
    double price = 0.0;
    if (priceField != null) {
      final parsed = double.tryParse(priceField.toString());
      if (parsed != null && parsed >= 0) {
        price = parsed;
      }
    }

    String? label;
    String? category;
    
    if (data['label'] != null) {
      label = data['label'].toString();
    } else if (data['category'] is List && (data['category'] as List).isNotEmpty) {
      label = (data['category'] as List).first.toString();
      // Extract category from list for category field
      category = label;
    } else if (data['category'] is String) {
      label = data['category'] as String;
      category = label;
    }

    final localImagePath = data['localImagePath']?.toString();

    return Product(
      id: (data['sku'] ?? data['id'] ?? '').toString(),
      name: (data['name'] ?? '') as String,
      description: (data['description'] ?? '') as String,
      imageUrl: imageUrl,
      price: price,
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
