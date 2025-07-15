import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class DirectoryService {
  static const String _baseUrl = 'https://theangelstones.com';
  final Map<String, int> _countCache = {};

  Future<int> fetchDesignCount(String folder) async {
    if (_countCache.containsKey(folder)) {
      return _countCache[folder]!;
    }
    final uri = Uri.parse('\$_baseUrl/get_directory_files.php?dir=$folder');
    try {
      final response = await http.get(uri);
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
