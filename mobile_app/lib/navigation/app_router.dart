import 'package:go_router/go_router.dart';

import '../models/product.dart';
import '../models/inventory_item.dart';
import '../screens/cart_screen.dart';
import '../screens/colors_screen.dart';
import '../screens/contact_screen.dart';
import '../screens/design_gallery_screen.dart';
import '../screens/flyer_viewer_screen.dart';
import '../screens/inventory_screen.dart';
import '../screens/inventory_item_details_screen.dart';
import '../screens/product_detail_screen.dart';
import '../screens/saved_items_screen.dart';
import '../screens/quote_request_screen.dart';
import '../services/api_service.dart';
import '../services/directory_service.dart';
import '../services/inventory_service.dart';
import '../services/storage_service.dart';
import 'main_navigation.dart';

/// Holds all application routes using [GoRouter].
class AppRouter {
  /// Route names for navigation
  static const String home = 'home';
  static const String colors = 'colors';
  static const String inventory = 'inventory';
  static const String contact = 'contact';
  static const String productDetail = 'product-detail';
  static const String designGallery = 'design-gallery';
  static const String flyerViewer = 'flyer-viewer';
  static const String cart = 'cart';
  static const String savedItems = 'saved-items';
  static const String quoteRequest = 'quote-request';
  static const String inventoryItemDetails = 'inventory-item-details';

  AppRouter({
    required this.apiService,
    required this.storageService,
    required this.inventoryService,
    required this.directoryService,
  });

  /// API helper for loading data.
  final ApiService apiService;

  /// Local storage handler.
  final StorageService storageService;

  /// Inventory backend helper.
  final InventoryService inventoryService;

  /// Directory helper for fetching design counts.
  final DirectoryService directoryService;

  /// Returns the configured router.
  late final GoRouter router = GoRouter(
    initialLocation: '/',
    routes: <GoRoute>[
      GoRoute(
        path: '/',
        name: home,
        builder: (context, state) => MainNavigation(
          apiService: apiService,
          storageService: storageService,
          inventoryService: inventoryService,
          directoryService: directoryService,
        ),
      ),
      GoRoute(
        path: '/colors',
        builder: (context, state) => ColorsScreen(apiService: apiService),
      ),
      GoRoute(
        path: '/inventory',
        builder: (context, state) =>
            InventoryScreen(inventoryService: inventoryService),
      ),
      GoRoute(
        path: '/contact',
        builder: (context, state) => const ContactScreen(),
      ),
      GoRoute(
        path: '/gallery/:categoryId',
        builder: (context, state) {
          final categoryId = state.pathParameters['categoryId']!;
          final title = state.uri.queryParameters['title'] ?? 'Gallery';
          return DesignGalleryScreen(
            categoryId: categoryId,
            title: title,
            apiService: apiService,
          );
        },
      ),
      GoRoute(
        path: '/product',
        builder: (context, state) {
          final product = state.extra as Product;
          return ProductDetailScreen(product: product);
        },
      ),
      GoRoute(
        path: '/flyer',
        builder: (context, state) {
          final product = state.extra as Product;
          return FlyerViewerScreen(flyer: product);
        },
      ),
      GoRoute(
        path: '/cart',
        name: cart,
        builder: (context, state) => const CartScreen(),
      ),
      GoRoute(
        path: '/saved-items',
        name: savedItems,
        builder: (context, state) => const SavedItemsScreen(),
      ),
      GoRoute(
        path: '/quote-request',
        name: quoteRequest,
        builder: (context, state) {
          final cartItems = state.extra as List<Map<String, dynamic>>? ?? [];
          final totalQuantity = cartItems.fold<int>(
              0, (sum, item) => sum + (item['quantity'] as int? ?? 1));
          return QuoteRequestScreen(
            cartItems: cartItems,
            totalQuantity: totalQuantity,
          );
        },
      ),
      GoRoute(
        path: '/inventory-item-details',
        name: inventoryItemDetails,
        builder: (context, state) {
          final item = state.extra as InventoryItem;
          return InventoryItemDetailsScreen(item: item);
        },
      ),
    ],
  );
}
