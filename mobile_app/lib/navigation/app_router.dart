import 'package:flutter/material.dart';
import '../models/product.dart';
import '../screens/design_gallery_screen.dart';
import '../screens/product_detail_screen.dart';
import '../services/api_service.dart';
import '../screens/cart_screen.dart';

class DesignGalleryArgs {
  final String categoryId;
  final String title;
  final ApiService apiService;
  const DesignGalleryArgs({
    required this.categoryId,
    required this.title,
    required this.apiService,
  });
}

class AppRoutePaths {
  static const String productDetail = '/product';
  static const String designGallery = '/gallery';
  static const String cart = '/cart';
}

class AppRouter {
  static Route<dynamic>? generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case AppRoutePaths.productDetail:
        final product = settings.arguments as Product?;
        if (product != null) {
          return MaterialPageRoute(
            builder: (_) => ProductDetailScreen(product: product),
          );
        }
        break;
      case AppRoutePaths.designGallery:
        final args = settings.arguments as DesignGalleryArgs?;
        if (args != null) {
          return MaterialPageRoute(
            builder: (_) => DesignGalleryScreen(
              categoryId: args.categoryId,
              title: args.title,
              apiService: args.apiService,
            ),
          );
        }
        break;
      case AppRoutePaths.cart:
        return MaterialPageRoute(builder: (_) => const CartScreen());
    }
    return null;
  }
}
