import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../services/connectivity_service.dart';
import '../navigation/app_router.dart';

/// A widget that monitors connectivity and automatically navigates to offline mode when needed
class ConnectivityHandler extends StatefulWidget {
  final Widget child;
  final ConnectivityService connectivityService;

  const ConnectivityHandler({
    super.key, 
    required this.child,
    required this.connectivityService,
  });

  @override
  State<ConnectivityHandler> createState() => _ConnectivityHandlerState();
}

class _ConnectivityHandlerState extends State<ConnectivityHandler> with WidgetsBindingObserver {
  StreamSubscription<bool>? _connectivitySubscription;
  bool _wasOffline = false;
  bool _offlineHandled = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    
    // Check connectivity immediately after widget is built
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkConnectivityStatus();
      _setupConnectivityListener();
    });
  }
  
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Check connectivity when app resumes from background
    if (state == AppLifecycleState.resumed) {
      _checkConnectivityStatus();
    }
  }
  
  Future<void> _checkConnectivityStatus() async {
    final isOnline = await widget.connectivityService.isOnline;
    debugPrint('🔌 Connectivity check: ${isOnline ? "ONLINE" : "OFFLINE"}');
    
    if (!isOnline && !_offlineHandled) {
      _navigateToOfflineCatalog();
    }
  }

  void _setupConnectivityListener() {
    _connectivitySubscription = widget.connectivityService.onConnectivityChanged.listen((isOnline) {
      debugPrint('🔌 Connectivity changed: ${isOnline ? "ONLINE" : "OFFLINE"}');
      
      if (!isOnline && !_offlineHandled) {
        _navigateToOfflineCatalog();
      } else if (isOnline && _wasOffline) {
        // We're back online after being offline
        _wasOffline = false;
        _offlineHandled = false;
        debugPrint('🔌 Connectivity restored');
        
        // Show a snackbar that we're back online
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('You are back online'),
              duration: Duration(seconds: 3),
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      }
    });
  }
  
  void _navigateToOfflineCatalog() {
    // We're offline and haven't handled it yet
    _wasOffline = true;
    _offlineHandled = true;
    
    // Navigate to offline catalog if context is available
    if (mounted) {
      debugPrint('🔌 Navigating to offline catalog');
      try {
        GoRouter.of(context).pushNamed(AppRouter.offlineCatalog);
      } catch (e) {
        debugPrint('⚠️ Error navigating to offline catalog: $e');
      }
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _connectivitySubscription?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return widget.child;
  }
}
