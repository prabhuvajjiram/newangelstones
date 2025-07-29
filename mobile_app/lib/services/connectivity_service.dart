import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:connectivity_plus/connectivity_plus.dart';

class ConnectivityService {
  final Connectivity _connectivity = Connectivity();
  final StreamController<bool> _connectivityController = StreamController<bool>.broadcast();
  bool _lastKnownState = true; // Assume online initially
  
  ConnectivityService() {
    // Initialize the service
    _initialize();
  }
  
  Future<void> _initialize() async {
    // Check initial connection state
    _lastKnownState = await _checkConnection();
    _connectivityController.add(_lastKnownState);
    
    // Listen to connectivity changes
    _connectivity.onConnectivityChanged.listen((List<ConnectivityResult> results) async {
      final isOnline = results.isNotEmpty && results.any((result) => result != ConnectivityResult.none);
      
      // Only emit if state changed
      if (isOnline != _lastKnownState) {
        _lastKnownState = isOnline;
        debugPrint('🔌 Connectivity changed: ${isOnline ? 'ONLINE' : 'OFFLINE'}');
        _connectivityController.add(isOnline);
      }
    });
  }
  
  Future<bool> _checkConnection() async {
    try {
      final results = await _connectivity.checkConnectivity();
      return results.isNotEmpty && results.any((result) => result != ConnectivityResult.none);
    } catch (e) {
      debugPrint('⚠️ Error checking connectivity: $e');
      return false; // Assume offline on error
    }
  }

  Stream<bool> get onConnectivityChanged => _connectivityController.stream;

  Future<bool> get isOnline async => await _checkConnection();
}
