/// Model class to represent a product image with its product code
class ProductImage {
  /// The URL to the image
  final String imageUrl;
  
  /// The product code extracted from the filename (without extension)
  final String productCode;
  
  /// Constructor
  ProductImage({required this.imageUrl, required this.productCode});
}
