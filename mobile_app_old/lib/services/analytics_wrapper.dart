import 'package:flutter/material.dart';
import 'firebase_service.dart';

/// A wrapper class that provides analytics tracking for navigation and interactions
class AnalyticsWrapper {
  static final AnalyticsWrapper _instance = AnalyticsWrapper._internal();
  
  factory AnalyticsWrapper() => _instance;
  
  AnalyticsWrapper._internal();
  
  /// Track navigation to a screen
  void trackScreenView(String screenName) {
    FirebaseService.instance.logScreenView(screenName: screenName);
    debugPrint('🔍 Analytics: Screen view - $screenName');
  }
  
  /// Track navigation to main page
  void trackMainPage() {
    FirebaseService.instance.trackMainPageView();
    debugPrint('🔍 Analytics: Main page view');
  }
  
  /// Track navigation to colors section
  void trackColorsSection() {
    FirebaseService.instance.logEvent(
      name: 'view_colors_section',
      parameters: {'timestamp': DateTime.now().toIso8601String()},
    );
    debugPrint('🔍 Analytics: Colors section view');
  }
  
  /// Track navigation to inventory section
  void trackInventorySection() {
    FirebaseService.instance.logEvent(
      name: 'view_inventory_section',
      parameters: {'timestamp': DateTime.now().toIso8601String()},
    );
    debugPrint('🔍 Analytics: Inventory section view');
  }
  
  /// Track navigation to in-stock items folder
  void trackInStockItemsFolder() {
    FirebaseService.instance.logEvent(
      name: 'view_in_stock_items',
      parameters: {'timestamp': DateTime.now().toIso8601String()},
    );
    debugPrint('🔍 Analytics: In-stock items view');
  }
  
  /// Track navigation to contact section
  void trackContactSection() {
    FirebaseService.instance.logEvent(
      name: 'view_contact_section',
      parameters: {'timestamp': DateTime.now().toIso8601String()},
    );
    debugPrint('🔍 Analytics: Contact section view');
  }
  
  /// Track PDF view for any of the PDFs
  void trackPdfView(String pdfName) {
    FirebaseService.instance.trackPdfView(
      pdfName: pdfName,
      pageCount: 0, // This will be updated when the PDF is loaded
    );
    debugPrint('🔍 Analytics: PDF view - $pdfName');
  }
  
  /// Update PDF page count once loaded
  void updatePdfPageCount(String pdfName, int pageCount) {
    FirebaseService.instance.logEvent(
      name: 'pdf_page_count',
      parameters: {
        'pdf_name': pdfName,
        'page_count': pageCount,
      },
    );
    debugPrint('🔍 Analytics: PDF page count - $pdfName: $pageCount pages');
  }
  
  /// Track featured product view
  void trackFeaturedProductView(String productId, String productName) {
    FirebaseService.instance.trackFeaturedProductView(
      productId: productId,
      productName: productName,
    );
    debugPrint('🔍 Analytics: Featured product view - $productName');
  }
  
  /// Track cart actions (add, remove, checkout)
  void trackCartAction({
    required String action,
    String? productId,
    String? productName,
    double? price,
    int? quantity,
  }) {
    FirebaseService.instance.trackCartAction(
      action: action,
      productId: productId,
      productName: productName,
      price: price,
      quantity: quantity,
    );
    debugPrint('🔍 Analytics: Cart action - $action ${productName ?? ''}');
  }
  
  /// Track contact actions (form, email, phone)
  void trackContactAction({
    required String method,
    required bool successful,
  }) {
    FirebaseService.instance.trackContactAction(
      method: method,
      successful: successful,
    );
    debugPrint('🔍 Analytics: Contact action - $method (success: $successful)');
  }
  
  /// Track performance metrics
  void trackPerformanceMetric({
    required String metricName,
    required double valueMs,
  }) {
    FirebaseService.instance.trackPerformanceMetric(
      metricName: metricName,
      valueMs: valueMs,
    );
    debugPrint('🔍 Analytics: Performance metric - $metricName: ${valueMs}ms');
  }
  
  /// Log a custom event
  void logEvent({
    required String name,
    Map<String, Object>? parameters,
  }) {
    FirebaseService.instance.logEvent(
      name: name,
      parameters: parameters,
    );
    debugPrint('🔍 Analytics: Custom event - $name');
  }
}
