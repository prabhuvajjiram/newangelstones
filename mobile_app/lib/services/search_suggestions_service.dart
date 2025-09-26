import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'dart:convert';

class SearchSuggestionsService {
  static const _storage = FlutterSecureStorage();
  static const String _recentSearchesKey = 'recent_searches';
  static const String _productCodesKey = 'product_codes_cache';
  static const int _maxRecentSearches = 10;

  // Common product codes and search terms
  static const List<String> _commonSearchTerms = [
    'AG-946', 'AG-948', 'AG-950', 'AG-952', 'AG-954',
    'monuments', 'granite', 'marble', 'headstone', 'memorial',
    'black', 'gray', 'red', 'blue', 'white',
    'base', 'tablet', 'upright', 'flat', 'slant',
    'MBNA 2025', 'benches', 'columbarium', 'designs'
  ];

  /// Get search suggestions based on input
  static Future<List<String>> getSuggestions(String query) async {
    if (query.isEmpty) {
      return await getRecentSearches();
    }

    final suggestions = <String>[];
    final queryLower = query.toLowerCase();

    // Add recent searches that match
    final recentSearches = await getRecentSearches();
    suggestions.addAll(
      recentSearches.where((search) => 
        search.toLowerCase().contains(queryLower)
      ).take(3)
    );

    // Add cached product codes that match
    final productCodes = await getCachedProductCodes();
    suggestions.addAll(
      productCodes.where((code) => 
        code.toLowerCase().contains(queryLower) && 
        !suggestions.contains(code)
      ).take(5)
    );

    // Add common terms that match
    suggestions.addAll(
      _commonSearchTerms.where((term) => 
        term.toLowerCase().contains(queryLower) && 
        !suggestions.contains(term)
      ).take(5)
    );

    return suggestions.take(8).toList();
  }

  /// Save a search term to recent searches
  static Future<void> saveRecentSearch(String searchTerm) async {
    if (searchTerm.trim().isEmpty) return;

    final recentSearches = await getRecentSearches();
    
    // Remove if already exists
    recentSearches.remove(searchTerm);
    
    // Add to beginning
    recentSearches.insert(0, searchTerm);
    
    // Keep only max items
    if (recentSearches.length > _maxRecentSearches) {
      recentSearches.removeRange(_maxRecentSearches, recentSearches.length);
    }

    await _storage.write(
      key: _recentSearchesKey, 
      value: jsonEncode(recentSearches)
    );
  }

  /// Get recent searches
  static Future<List<String>> getRecentSearches() async {
    try {
      final data = await _storage.read(key: _recentSearchesKey);
      if (data != null) {
        final List<dynamic> decoded = jsonDecode(data) as List<dynamic>;
        return decoded.cast<String>();
      }
    } catch (e) {
      // Handle error silently
    }
    return [];
  }

  /// Cache product codes for suggestions
  static Future<void> cacheProductCodes(List<String> productCodes) async {
    try {
      await _storage.write(
        key: _productCodesKey, 
        value: jsonEncode(productCodes.take(100).toList()) // Limit cache size
      );
    } catch (e) {
      // Handle error silently
    }
  }

  /// Get cached product codes
  static Future<List<String>> getCachedProductCodes() async {
    try {
      final data = await _storage.read(key: _productCodesKey);
      if (data != null) {
        final List<dynamic> decoded = jsonDecode(data) as List<dynamic>;
        return decoded.cast<String>();
      }
    } catch (e) {
      // Handle error silently
    }
    return [];
  }

  /// Clear recent searches
  static Future<void> clearRecentSearches() async {
    await _storage.delete(key: _recentSearchesKey);
  }

  /// Get popular search terms (static for now, could be dynamic from analytics)
  static List<String> getPopularSearchTerms() {
    return _commonSearchTerms.take(5).toList();
  }
}
