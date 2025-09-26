import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'dart:convert';
import 'dart:developer' as developer;

class SavedItemsService {
  static const String savedItemsKey = 'saved_items';
  static const _storage = FlutterSecureStorage();
  
  // Save an item
  static Future<bool> saveItem(Map<String, dynamic> item) async {
    try {
      // Ensure the item has an ID
      if (!item.containsKey('id') || item['id'] == null || item['id'].toString().isEmpty) {
        // Generate an ID using code or timestamp if not present
        item['id'] = item['code'] ?? DateTime.now().millisecondsSinceEpoch.toString();
        developer.log('Generated ID for saved item: ${item['id']}', name: 'SavedItemsService');
      }
      
      final savedItems = await getSavedItems();
      
      // Debug print
      developer.log('Current saved items count: ${savedItems.length}', name: 'SavedItemsService');
      
      // Check if item already exists
      if (!savedItems.any((i) => i['id'] == item['id'])) {
        savedItems.add(item);
        await _storage.write(key: savedItemsKey, value: jsonEncode(savedItems));
        developer.log('Item saved successfully with ID: ${item['id']}', name: 'SavedItemsService');
        return true;
      }
      developer.log('Item already exists in saved items: ${item['id']}', name: 'SavedItemsService');
      return false;
    } catch (e) {
      developer.log('Error saving item: $e', name: 'SavedItemsService', error: e);
      return false;
    }
  }
  
  // Get all saved items
  static Future<List<Map<String, dynamic>>> getSavedItems() async {
    try {
      final savedItemsJson = await _storage.read(key: savedItemsKey);
      developer.log('Reading saved items from storage', name: 'SavedItemsService');
      
      if (savedItemsJson != null && savedItemsJson.isNotEmpty) {
        try {
          final List<dynamic> decoded = jsonDecode(savedItemsJson) as List<dynamic>;
          final items = decoded.cast<Map<String, dynamic>>();
          developer.log('Retrieved ${items.length} saved items', name: 'SavedItemsService');
          return items;
        } catch (parseError) {
          developer.log('Error parsing saved items JSON: $parseError', name: 'SavedItemsService', error: parseError);
          // If JSON is corrupted, reset storage
          await _storage.delete(key: savedItemsKey);
          developer.log('Deleted corrupted saved items data', name: 'SavedItemsService');
          return [];
        }
      }
      developer.log('No saved items found in storage', name: 'SavedItemsService');
      return [];
    } catch (e) {
      developer.log('Error getting saved items: $e', name: 'SavedItemsService', error: e);
      return [];
    }
  }
  
  // Remove an item
  static Future<bool> removeItem(String itemId) async {
    try {
      final savedItems = await getSavedItems();
      final initialLength = savedItems.length;
      
      savedItems.removeWhere((item) => item['id'] == itemId);
      
      if (savedItems.length < initialLength) {
        await _storage.write(key: savedItemsKey, value: jsonEncode(savedItems));
        developer.log('Item removed successfully: $itemId', name: 'SavedItemsService');
        return true;
      }
      
      developer.log('Item not found for removal: $itemId', name: 'SavedItemsService');
      return false;
    } catch (e) {
      developer.log('Error removing item: $e', name: 'SavedItemsService', error: e);
      return false;
    }
  }
  
  // Check if an item is saved
  static Future<bool> isItemSaved(String itemId) async {
    final savedItems = await getSavedItems();
    return savedItems.any((item) => item['id'] == itemId);
  }
  
  // Clear all saved items
  static Future<bool> clearAllItems() async {
    try {
      await _storage.delete(key: savedItemsKey);
      return true;
    } catch (e) {
      return false;
    }
  }
}
