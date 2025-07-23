import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'dart:convert';

class SavedItemsService {
  static const String savedItemsKey = 'saved_items';
  static const _storage = FlutterSecureStorage();
  
  // Save an item
  static Future<bool> saveItem(Map<String, dynamic> item) async {
    try {
      final savedItems = await getSavedItems();
      
      // Check if item already exists
      if (!savedItems.any((i) => i['id'] == item['id'])) {
        savedItems.add(item);
        await _storage.write(key: savedItemsKey, value: jsonEncode(savedItems));
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }
  
  // Get all saved items
  static Future<List<Map<String, dynamic>>> getSavedItems() async {
    try {
      final savedItemsJson = await _storage.read(key: savedItemsKey);
      
      if (savedItemsJson != null) {
        final List<dynamic> decoded = jsonDecode(savedItemsJson);
        return decoded.cast<Map<String, dynamic>>();
      }
      return [];
    } catch (e) {
      return [];
    }
  }
  
  // Remove an item
  static Future<bool> removeItem(String itemId) async {
    try {
      final savedItems = await getSavedItems();
      savedItems.removeWhere((item) => item['id'] == itemId);
      
      await _storage.write(key: savedItemsKey, value: jsonEncode(savedItems));
      return true;
    } catch (e) {
      return false;
    }
  }
  
  // Check if an item is saved
  static Future<bool> isItemSaved(String itemId) async {
    final savedItems = await getSavedItems();
    return savedItems.any((item) => item['id'] == itemId);
  }
}
