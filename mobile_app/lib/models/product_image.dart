/// Model class to represent a product image with its product code
class ProductImage {
  /// The URL to the image
  final String imageUrl;
  
  /// The product code extracted from the filename (without extension)
  final String productCode;

  /// Optional bundled asset path (e.g. assets/products/monuments/AG-233.jpg)
  /// When set, the image is loaded from assets instantly without a network call.
  final String? assetPath;

  /// Constructor
  ProductImage({required this.imageUrl, required this.productCode, this.assetPath});

  /// Returns the best path to display: bundled asset first, then network URL
  String getDisplayPath() => (assetPath != null && assetPath!.isNotEmpty) ? assetPath! : imageUrl;

  /// True when a bundled asset is available for instant offline display
  bool get hasBundledAsset => assetPath != null && assetPath!.isNotEmpty;
}
