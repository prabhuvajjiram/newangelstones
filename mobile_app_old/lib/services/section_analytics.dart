// Material import removed - not used in this file
// import 'package:flutter/material.dart';
import 'analytics_wrapper.dart';

/// Helper class to track analytics for specific app sections
class SectionAnalytics {
  static final AnalyticsWrapper _analytics = AnalyticsWrapper();
  
  /// Track when user views the main page
  static void trackMainPage() {
    _analytics.trackMainPage();
  }
  
  /// Track when user views the colors section
  static void trackColorsSection() {
    _analytics.trackColorsSection();
  }
  
  /// Track when user views the inventory section
  static void trackInventorySection() {
    _analytics.trackInventorySection();
  }
  
  /// Track when user views the contact section
  static void trackContactSection() {
    _analytics.trackContactSection();
  }
  
  /// Track when user views the cart
  static void trackCartView() {
    _analytics.logEvent(
      name: 'view_cart',
      parameters: {'timestamp': DateTime.now().toIso8601String()},
    );
  }
  
  /// Track when user views in-stock items
  static void trackInStockItems() {
    _analytics.trackInStockItemsFolder();
  }
  
  /// Track when user views any PDF
  static void trackPdfView(String pdfName) {
    _analytics.trackPdfView(pdfName);
  }
  
  /// Track when user views the catalog PDF
  static void trackCatalogPdf() {
    trackPdfView('catalog');
  }
  
  /// Track when user views the brochure PDF
  static void trackBrochurePdf() {
    trackPdfView('brochure');
  }
  
  /// Track when user views the price list PDF
  static void trackPriceListPdf() {
    trackPdfView('price_list');
  }
  
  /// Track when user views a featured product
  static void trackFeaturedProduct(String productId, String productName) {
    _analytics.trackFeaturedProductView(productId, productName);
  }
  
  /// Track when user adds a product to cart
  static void trackAddToCart({
    required String productId,
    required String productName,
    required double price,
    required int quantity,
  }) {
    _analytics.trackCartAction(
      action: 'add',
      productId: productId,
      productName: productName,
      price: price,
      quantity: quantity,
    );
  }
  
  /// Track when user removes a product from cart
  static void trackRemoveFromCart({
    required String productId,
    required String productName,
  }) {
    _analytics.trackCartAction(
      action: 'remove',
      productId: productId,
      productName: productName,
    );
  }
  
  /// Track when user proceeds to checkout
  static void trackCheckout() {
    _analytics.trackCartAction(action: 'checkout');
  }
  
  /// Track when user submits a contact form
  static void trackContactFormSubmit(bool successful) {
    _analytics.trackContactAction(
      method: 'form',
      successful: successful,
    );
  }
  
  /// Track when user clicks on email contact
  static void trackEmailContact() {
    _analytics.trackContactAction(
      method: 'email',
      successful: true,
    );
  }
  
  /// Track when user clicks on phone contact
  static void trackPhoneContact() {
    _analytics.trackContactAction(
      method: 'phone',
      successful: true,
    );
  }
  
  /// Track app performance metrics
  static void trackPerformance(String metricName, double valueMs) {
    _analytics.trackPerformanceMetric(
      metricName: metricName,
      valueMs: valueMs,
    );
  }
}
