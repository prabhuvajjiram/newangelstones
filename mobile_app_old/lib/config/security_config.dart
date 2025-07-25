import 'package:flutter/foundation.dart';

/// Security configuration for the Angel Granites mobile app
class SecurityConfig {
  // Private constructor to prevent instantiation
  SecurityConfig._();
  
  /// API endpoints configuration
  static const String angelStonesBaseUrl = 'https://theangelstones.com';
  static const String monumentBusinessBaseUrl = 'https://monument.business';
  
  /// Request timeout configuration
  static const Duration defaultTimeout = Duration(seconds: 10);
  static const Duration longTimeout = Duration(seconds: 30);
  
  /// Security headers for HTTP requests
  static Map<String, String> getSecurityHeaders() {
    return {
      'User-Agent': 'AngelGranites-Mobile-App/1.0',
      'Accept': 'application/json, text/plain, */*',
      'Cache-Control': 'no-cache',
      'Pragma': 'no-cache',
      'X-Requested-With': 'XMLHttpRequest',
      if (!kDebugMode) 'X-App-Version': '1.0.0',
    };
  }
  
  /// Validate URL before making requests
  static bool isValidUrl(String url) {
    try {
      final uri = Uri.parse(url);
      // Only allow HTTPS in production
      if (!kDebugMode && uri.scheme != 'https') {
        return false;
      }
      // Whitelist allowed domains
      final allowedDomains = [
        'theangelstones.com',
        'monument.business',
        'www.google.com', // For maps
        'www.convergepay.com', // For payments
      ];
      return allowedDomains.any((domain) => uri.host.endsWith(domain));
    } catch (e) {
      return false;
    }
  }
  
  /// Sanitize input parameters
  static String sanitizeInput(String input) {
    return Uri.encodeComponent(input.trim());
  }
  
  /// Get environment-specific configuration
  static String getApiToken() {
    // In production, this should come from secure storage or environment variables
    // For now, we'll keep it here but add security measures
    if (kDebugMode) {
      return '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A';
    }
    // In production, retrieve from secure storage
    return _getSecureApiToken();
  }
  
  static String _getSecureApiToken() {
    // TODO: Implement secure token retrieval from:
    // - Flutter Secure Storage
    // - Environment variables
    // - Remote configuration service
    return '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A';
  }
}
