import 'package:flutter/material.dart';
import '../services/analytics_wrapper.dart';

/// A navigator observer that automatically tracks screen views for analytics
class AnalyticsNavigatorObserver extends NavigatorObserver {
  final AnalyticsWrapper _analytics = AnalyticsWrapper();
  
  @override
  void didPush(Route<dynamic> route, Route<dynamic>? previousRoute) {
    super.didPush(route, previousRoute);
    _trackScreenView(route);
  }
  
  @override
  void didReplace({Route<dynamic>? newRoute, Route<dynamic>? oldRoute}) {
    super.didReplace(newRoute: newRoute, oldRoute: oldRoute);
    if (newRoute != null) {
      _trackScreenView(newRoute);
    }
  }
  
  @override
  void didPop(Route<dynamic> route, Route<dynamic>? previousRoute) {
    super.didPop(route, previousRoute);
    if (previousRoute != null) {
      _trackScreenView(previousRoute);
    }
  }
  
  void _trackScreenView(Route<dynamic> route) {
    final String? screenName = _getScreenNameFromRoute(route);
    if (screenName != null) {
      _analytics.trackScreenView(screenName);
      
      // Track specific sections
      _trackSpecificSection(screenName);
    }
  }
  
  String? _getScreenNameFromRoute(Route<dynamic> route) {
    if (route.settings.name != null) {
      return route.settings.name;
    }
    
    // Try to get a meaningful name from the route
    final String routeStr = route.toString();
    if (routeStr.contains('ColorsScreen')) {
      return 'colors_screen';
    } else if (routeStr.contains('InventoryScreen')) {
      return 'inventory_screen';
    } else if (routeStr.contains('ContactScreen')) {
      return 'contact_screen';
    } else if (routeStr.contains('ProductDetailScreen')) {
      return 'product_detail_screen';
    } else if (routeStr.contains('FlyerViewerScreen')) {
      return 'flyer_viewer_screen';
    } else if (routeStr.contains('CartScreen')) {
      return 'cart_screen';
    } else if (routeStr.contains('MainNavigation')) {
      return 'main_screen';
    } else if (routeStr.contains('DesignGalleryScreen')) {
      return 'design_gallery_screen';
    } else if (routeStr.contains('InventoryItemDetailsScreen')) {
      return 'inventory_item_details_screen';
    }
    
    return null;
  }
  
  void _trackSpecificSection(String screenName) {
    switch (screenName) {
      case 'colors_screen':
        _analytics.trackColorsSection();
        break;
      case 'inventory_screen':
        _analytics.trackInventorySection();
        break;
      case 'contact_screen':
        _analytics.trackContactSection();
        break;
      case 'main_screen':
        _analytics.trackMainPage();
        break;
      case 'flyer_viewer_screen':
        _analytics.trackPdfView('flyer');
        break;
      case 'inventory_item_details_screen':
        _analytics.trackInStockItemsFolder();
        break;
    }
  }
}
