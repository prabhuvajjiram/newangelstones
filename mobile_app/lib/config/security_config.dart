import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/cache_entry.dart';

/// Security configuration for the Angel Granites mobile app
class SecurityConfig {
  // Private constructor to prevent instantiation
  SecurityConfig._();
  
  /// API endpoints configuration
  static const String angelStonesBaseUrl = 'https://theangelstones.com';
  static const String monumentBusinessBaseUrl = 'https://monument.business';
  
  /// Request timeout configuration
  static const Duration defaultTimeout = Duration(seconds: 30);
  static const Duration longTimeout = Duration(seconds: 45);
  
  /// Configuration caching
  static const Duration _configCacheTTL = Duration(hours: 24);
  static CacheEntry<Map<String, dynamic>>? _configCache;
  static const _storage = FlutterSecureStorage();
  
  /// Fallback defaults
  static const Map<String, dynamic> _defaultConfig = {
    'api_endpoints': {
      'mautic_contact_form_id': 1,
      'mautic_quote_form_id': 2
    },
    'payment': {
      'url': 'https://www.convergepay.com/hosted-payments?ssl_txn_auth_token=E%2F8reYrhQjCCZuE850a9TQAAAZZqwm4V'
    }
  };
  
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
    // This is a public API token for monument.business - safe to hardcode
    return '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A';
  }
  
  /// Dynamic configuration methods
  static Future<String> getPaymentUrl() async {
    final config = await _getConfig();
    return (config['payment']?['url'] ?? _defaultConfig['payment']!['url']) as String;
  }
  
  static Future<String> getMonumentBusinessApiKey() async {
    final config = await _getConfig();
    return (config['api_endpoints']?['monument_business_api_key'] ?? 
           'e8l3DUB3i8gUT3ubYiEu73aOh80t6b5hW8mqhAOJOOvROxS5k3lASFHVxRY6Ky5U') as String;
  }
  
  static Future<int> getMonumentBusinessOrgId() async {
    final config = await _getConfig();
    return (config['api_endpoints']?['monument_business_org_id'] ?? 2) as int;
  }
  
  static Future<int> getMauticContactFormId() async {
    final config = await _getConfig();
    return (config['api_endpoints']?['mautic_contact_form_id'] ?? 
           _defaultConfig['api_endpoints']!['mautic_contact_form_id']) as int;
  }
  
  static Future<int> getMauticQuoteFormId() async {
    final config = await _getConfig();
    return (config['api_endpoints']?['mautic_quote_form_id'] ?? 
           _defaultConfig['api_endpoints']!['mautic_quote_form_id']) as int;
  }
  
  static Future<bool> isFeatureEnabled(String feature) async {
    final config = await _getConfig();
    return (config['features']?[feature] ?? false) as bool;
  }
  
  static Future<String> getContactPhone() async {
    final config = await _getConfig();
    return (config['contact']?['phone'] ?? '+1 866-682-5837') as String;
  }
  
  static Future<String> getContactEmail() async {
    final config = await _getConfig();
    return (config['contact']?['email'] ?? 'info@theangelstones.com') as String;
  }
  
  static Future<String> getMauticBaseUrl() async {
    final config = await _getConfig();
    return (config['api_endpoints']?['mautic_base'] ?? '$angelStonesBaseUrl/mautic/form/submit') as String;
  }
  
  /// Fetch configuration with three-layer fallback
  static Future<Map<String, dynamic>> _getConfig() async {
    // Use cache if available and not expired
    if (_configCache != null && !_configCache!.isExpired(_configCacheTTL)) {
      return _configCache!.data;
    }
    
    try {
      final response = await http.get(
        Uri.parse('$angelStonesBaseUrl/api/mobile-config.php'),
        headers: getSecurityHeaders(),
      ).timeout(defaultTimeout);
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body) as Map<String, dynamic>;
        _configCache = CacheEntry(data);
        
        // Save to secure storage
        await _storage.write(key: 'mobile_config', value: response.body);
        debugPrint('✅ Successfully fetched mobile configuration');
        return data;
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching config: $e');
    }
    
    // Fallback to secure storage
    try {
      final cached = await _storage.read(key: 'mobile_config');
      if (cached != null) {
        final data = json.decode(cached) as Map<String, dynamic>;
        debugPrint('📦 Using cached mobile configuration');
        return data;
      }
    } catch (e) {
      debugPrint('⚠️ Error reading cached config: $e');
    }
    
    // Ultimate fallback to defaults
    debugPrint('🔄 Using default mobile configuration');
    return _defaultConfig;
  }
  
  /// Clear configuration cache (useful for testing)
  static void clearConfigCache() {
    _configCache = null;
  }
}
