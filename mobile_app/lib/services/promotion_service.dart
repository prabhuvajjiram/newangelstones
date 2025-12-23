import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import '../models/promotion.dart';
import '../config/security_config.dart';

class PromotionService {
  static const String _cacheFileName = 'promotions_cache.json';
  static const Duration _cacheDuration = Duration(days: 1);
  
  List<Promotion>? _cachedPromotions;
  DateTime? _lastFetchTime;

  /// Fetch active promotions from server
  Future<List<Promotion>> fetchPromotions({bool forceRefresh = false}) async {
    try {
      // Check cache first (unless force refresh)
      if (!forceRefresh && _isCacheValid()) {
        debugPrint('📦 Using cached promotions');
        return _cachedPromotions!;
      }

      // Try to load from local cache file
      if (!forceRefresh) {
        final cachedPromotions = await _loadFromLocalCache();
        if (cachedPromotions != null && cachedPromotions.isNotEmpty) {
          _cachedPromotions = cachedPromotions;
          _lastFetchTime = DateTime.now();
          debugPrint('📂 Loaded ${cachedPromotions.length} promotions from local cache');
          return cachedPromotions;
        }
      }

      // Fetch from server
      final baseUrl = await SecurityConfig.getBaseUrl();
      final url = Uri.parse('$baseUrl/Api/promotions.json?platform=mobile');
      
      debugPrint('🌐 Fetching promotions from: $url');
      
      final response = await http.get(url).timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          throw TimeoutException('Request timed out');
        },
      );

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body) as Map<String, dynamic>;
        final promotionsList = jsonData['promotions'] as List<dynamic>;
        
        final promotions = promotionsList
            .map((item) => Promotion.fromJson(item as Map<String, dynamic>))
            .where((promo) => promo.isActive()) // Filter active promotions
            .toList();
        
        // Sort by priority
        promotions.sort((a, b) => a.priority.compareTo(b.priority));
        
        // Cache the results
        _cachedPromotions = promotions;
        _lastFetchTime = DateTime.now();
        
        // Save to local cache
        await _saveToLocalCache(promotions);
        
        debugPrint('✅ Fetched ${promotions.length} active promotions');
        return promotions;
      } else {
        debugPrint('⚠️ Failed to fetch promotions: ${response.statusCode}');
        
        // Try to return cached data as fallback
        if (_cachedPromotions != null) {
          return _cachedPromotions!;
        }
        
        return [];
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching promotions: $e');
      
      // Return cached data as fallback
      if (_cachedPromotions != null) {
        return _cachedPromotions!;
      }
      
      // Try to load from local cache as last resort
      final cachedPromotions = await _loadFromLocalCache();
      if (cachedPromotions != null) {
        return cachedPromotions;
      }
      
      return [];
    }
  }

  /// Check if in-memory cache is still valid
  bool _isCacheValid() {
    if (_cachedPromotions == null || _lastFetchTime == null) {
      return false;
    }
    
    final age = DateTime.now().difference(_lastFetchTime!);
    return age < _cacheDuration;
  }

  /// Save promotions to local cache file
  Future<void> _saveToLocalCache(List<Promotion> promotions) async {
    try {
      final directory = await getApplicationDocumentsDirectory();
      final file = File('${directory.path}/$_cacheFileName');
      
      final jsonData = {
        'timestamp': DateTime.now().toIso8601String(),
        'promotions': promotions.map((p) => p.toJson()).toList(),
      };
      
      await file.writeAsString(json.encode(jsonData));
      debugPrint('💾 Saved promotions to local cache');
    } catch (e) {
      debugPrint('⚠️ Error saving promotions to cache: $e');
    }
  }

  /// Load promotions from local cache file
  Future<List<Promotion>?> _loadFromLocalCache() async {
    try {
      final directory = await getApplicationDocumentsDirectory();
      final file = File('${directory.path}/$_cacheFileName');
      
      if (!await file.exists()) {
        return null;
      }
      
      final contents = await file.readAsString();
      final jsonData = json.decode(contents) as Map<String, dynamic>;
      
      // Check cache age
      final timestamp = DateTime.parse(jsonData['timestamp'] as String);
      final age = DateTime.now().difference(timestamp);
      
      if (age > _cacheDuration) {
        debugPrint('⚠️ Local cache expired (${age.inHours} hours old)');
        return null;
      }
      
      final promotionsList = jsonData['promotions'] as List<dynamic>;
      final promotions = promotionsList
          .map((item) => Promotion.fromJson(item as Map<String, dynamic>))
          .where((promo) => promo.isActive())
          .toList();
      
      return promotions;
    } catch (e) {
      debugPrint('⚠️ Error loading promotions from cache: $e');
      return null;
    }
  }

  /// Clear cache
  Future<void> clearCache() async {
    _cachedPromotions = null;
    _lastFetchTime = null;
    
    try {
      final directory = await getApplicationDocumentsDirectory();
      final file = File('${directory.path}/$_cacheFileName');
      
      if (await file.exists()) {
        await file.delete();
        debugPrint('🗑️ Cleared promotions cache');
      }
    } catch (e) {
      debugPrint('⚠️ Error clearing cache: $e');
    }
  }
}
