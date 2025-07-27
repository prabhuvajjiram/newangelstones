import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'dart:developer' as developer;

import '../state/saved_items_state.dart';
import 'saved_items_service.dart';

/// A unified service that bridges the gap between the local SavedItemsService
/// and the global SavedItemsState provider.
/// 
/// This ensures that all bookmark/save-for-later operations are synchronized
/// across the entire app.
class UnifiedSavedItemsService {
  /// Initializes the SavedItemsState provider with data from local storage.
  /// Should be called once at app startup.
  static Future<void> initializeFromStorage(BuildContext context) async {
    try {
      // Safety check to ensure context is valid
      if (!context.mounted) {
        developer.log('Context is not mounted, skipping initialization', 
          name: 'UnifiedSavedItemsService');
        return;
      }
      
      final savedItemsState = Provider.of<SavedItemsState>(context, listen: false);
      final storedItems = await SavedItemsService.getSavedItems();
      
      // Clear current items and add all stored items
      savedItemsState.clearSavedItems();
      for (var item in storedItems) {
        savedItemsState.addItem(item);
      }
      
      developer.log('Initialized SavedItemsState with ${storedItems.length} items from storage', 
        name: 'UnifiedSavedItemsService');
    } catch (e) {
      developer.log('Error initializing from storage: $e', 
        name: 'UnifiedSavedItemsService', error: e);
    }
  }

  /// Saves an item both to the provider and local storage.
  static Future<bool> saveItem(BuildContext context, Map<String, dynamic> item) async {
    try {
      // Ensure the item has an ID
      if (!item.containsKey('id') || item['id'] == null || item['id'].toString().isEmpty) {
        item['id'] = item['code'] ?? DateTime.now().millisecondsSinceEpoch.toString();
      }
      
      // Add to provider
      final savedItemsState = Provider.of<SavedItemsState>(context, listen: false);
      savedItemsState.addItem(item);
      
      // Save to storage
      await SavedItemsService.saveItem(item);
      
      developer.log('Item saved successfully with ID: ${item['id']}', 
        name: 'UnifiedSavedItemsService');
      return true;
    } catch (e) {
      developer.log('Error saving item: $e', 
        name: 'UnifiedSavedItemsService', error: e);
      return false;
    }
  }

  /// Removes an item both from the provider and local storage.
  static Future<bool> removeItem(BuildContext context, String itemId) async {
    try {
      // Remove from provider
      final savedItemsState = Provider.of<SavedItemsState>(context, listen: false);
      final item = savedItemsState.getItem(itemId);
      
      if (item != null) {
        savedItemsState.removeItem(item);
      }
      
      // Remove from storage
      await SavedItemsService.removeItem(itemId);
      
      developer.log('Item removed successfully with ID: $itemId', 
        name: 'UnifiedSavedItemsService');
      return true;
    } catch (e) {
      developer.log('Error removing item: $e', 
        name: 'UnifiedSavedItemsService', error: e);
      return false;
    }
  }

  /// Checks if an item is saved.
  static bool isItemSaved(BuildContext context, String itemId) {
    try {
      final savedItemsState = Provider.of<SavedItemsState>(context, listen: false);
      return savedItemsState.hasItem(itemId);
    } catch (e) {
      developer.log('Error checking if item is saved: $e', 
        name: 'UnifiedSavedItemsService', error: e);
      return false;
    }
  }

  /// Synchronizes the provider state with local storage.
  /// This should be called after making changes to the provider.
  static Future<bool> syncToStorage(BuildContext context) async {
    try {
      // Safety check to ensure context is valid
      if (!context.mounted) {
        developer.log('Context is not mounted, skipping sync', 
          name: 'UnifiedSavedItemsService');
        return false;
      }
      
      final savedItemsState = Provider.of<SavedItemsState>(context, listen: false);
      final items = savedItemsState.items;
      
      // Clear storage and save all items
      await SavedItemsService.clearAllItems();
      for (var item in items) {
        await SavedItemsService.saveItem(item);
      }
      
      developer.log('Synced ${items.length} items to storage', 
        name: 'UnifiedSavedItemsService');
      return true;
    } catch (e) {
      developer.log('Error syncing to storage: $e', 
        name: 'UnifiedSavedItemsService', error: e);
      return false;
    }
  }
}
