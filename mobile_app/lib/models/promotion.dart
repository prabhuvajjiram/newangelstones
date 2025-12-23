class PromotionPricing {
  final double? specialPrice;
  final double? listPrice;
  final String? currency;
  final String? displayFormat;

  PromotionPricing({
    this.specialPrice,
    this.listPrice,
    this.currency,
    this.displayFormat,
  });

  factory PromotionPricing.fromJson(Map<String, dynamic> json) {
    return PromotionPricing(
      specialPrice: (json['specialPrice'] as num?)?.toDouble(),
      listPrice: (json['listPrice'] as num?)?.toDouble(),
      currency: json['currency'] as String?,
      displayFormat: json['displayFormat'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'specialPrice': specialPrice,
      'listPrice': listPrice,
      'currency': currency,
      'displayFormat': displayFormat,
    };
  }

  double? get discount => (listPrice != null && specialPrice != null) 
      ? listPrice! - specialPrice! 
      : null;

  double? get discountPercent => (listPrice != null && specialPrice != null && listPrice! > 0)
      ? ((listPrice! - specialPrice!) / listPrice!) * 100
      : null;
}

class ProductDetails {
  final String? productCode;
  final String? color;
  final String? tablet;
  final String? base;
  final String? features;

  ProductDetails({
    this.productCode,
    this.color,
    this.tablet,
    this.base,
    this.features,
  });

  factory ProductDetails.fromJson(Map<String, dynamic> json) {
    return ProductDetails(
      productCode: json['productCode'] as String?,
      color: json['color'] as String?,
      tablet: json['tablet'] as String?,
      base: json['base'] as String?,
      features: json['features'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'productCode': productCode,
      'color': color,
      'tablet': tablet,
      'base': base,
      'features': features,
    };
  }
}

class Promotion {
  final String id;
  final String type; // 'event' or 'product'
  final String title;
  final String? subtitle;
  final String? description;
  final String imageUrl;
  final String? linkUrl;
  final DateTime startDate;
  final DateTime endDate;
  final int priority;
  final bool enabled;
  final PromotionPricing? pricing;
  final ProductDetails? productDetails;

  Promotion({
    required this.id,
    required this.type,
    required this.title,
    this.subtitle,
    this.description,
    required this.imageUrl,
    this.linkUrl,
    required this.startDate,
    required this.endDate,
    required this.priority,
    required this.enabled,
    this.pricing,
    this.productDetails,
  });

  factory Promotion.fromJson(Map<String, dynamic> json) {
    return Promotion(
      id: json['id'] as String,
      type: json['type'] as String,
      title: json['title'] as String,
      subtitle: json['subtitle'] as String?,
      description: json['description'] as String?,
      imageUrl: json['imageUrl'] as String,
      linkUrl: json['linkUrl'] as String?,
      startDate: DateTime.parse(json['startDate'] as String),
      endDate: DateTime.parse(json['endDate'] as String),
      priority: json['priority'] as int? ?? 0,
      enabled: json['enabled'] as bool? ?? true,
      pricing: json['pricing'] != null 
          ? PromotionPricing.fromJson(json['pricing'] as Map<String, dynamic>)
          : null,
      productDetails: json['productDetails'] != null
          ? ProductDetails.fromJson(json['productDetails'] as Map<String, dynamic>)
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type,
      'title': title,
      'subtitle': subtitle,
      'description': description,
      'imageUrl': imageUrl,
      'linkUrl': linkUrl,
      'startDate': startDate.toIso8601String(),
      'endDate': endDate.toIso8601String(),
      'priority': priority,
      'enabled': enabled,
      'pricing': pricing?.toJson(),
      'productDetails': productDetails?.toJson(),
    };
  }

  bool isActive() {
    final now = DateTime.now();
    return enabled && 
           now.isAfter(startDate) && 
           now.isBefore(endDate);
  }
}
