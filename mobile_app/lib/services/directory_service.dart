import 'dart:convert';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class DirectoryService {
  static const String _baseUrl = 'https://theangelstones.com';
  final Map<String, int> _countCache = {};
  bool _isInitialized = false;
  
  // Public getter for base URL
  static String get baseUrl => _baseUrl;
  
  /// Initialize the directory service with error handling and timeout
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      // Test API connectivity with a lightweight request
      final uri = Uri.parse('$_baseUrl/get_directory_files.php?dir=products');
      final response = await http.get(uri).timeout(
        const Duration(seconds: 3),
        onTimeout: () => throw TimeoutException('Directory API connection timed out'),
      );
      
      if (response.statusCode != 200) {
        throw Exception('Directory API returned status code: ${response.statusCode}');
      }
      
      // Parse response to verify format
      try {
        final Map<String, dynamic> data = json.decode(response.body);
        if (!data.containsKey('files')) {
          throw Exception('Invalid API response format');
        }
      } catch (e) {
        throw Exception('Failed to parse API response: $e');
      }
      
      _isInitialized = true;
      debugPrint('✅ DirectoryService initialized successfully');
    } catch (e) {
      debugPrint('⚠️ DirectoryService initialization error: $e');
      // Mark as initialized anyway to prevent repeated init attempts
      _isInitialized = true;
    }
  }

  Future<int> fetchDesignCount(String folder) async {
    if (_countCache.containsKey(folder)) {
      return _countCache[folder]!;
    }
    final uri = Uri.parse('$_baseUrl/get_directory_files.php?dir=$folder');
    try {
      final response = await http.get(uri).timeout(
        const Duration(seconds: 5),
        onTimeout: () {
          debugPrint('⚠️ Timeout fetching design count for $folder');
          throw TimeoutException('Directory API request timed out');
        },
      );
      if (response.statusCode == 200) {
        final Map<String, dynamic> data = json.decode(response.body);
        final List<dynamic> files = data['files'] ?? [];
        final count = files
            .whereType<Map<String, dynamic>>()
            .where((e) {
              final path = (e['path'] ?? '').toString().toLowerCase();
              return path.endsWith('.jpg') ||
                  path.endsWith('.jpeg') ||
                  path.endsWith('.png');
            })
            .length;
        _countCache[folder] = count;
        return count;
      } else {
        throw Exception('Failed to load directory files');
      }
    } catch (e) {
      debugPrint('Error fetching design count for \$folder: \$e');
      rethrow;
    }
  }

  void clearCache() => _countCache.clear();
}
