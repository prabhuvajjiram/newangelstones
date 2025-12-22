import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../navigation/app_router.dart';
import '../theme/app_theme.dart';
import '../services/inventory_service.dart';
import '../utils/image_utils.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  String _statusMessage = 'Initializing...';
  double _progress = 0.0;

  @override
  void initState() {
    super.initState();
    _initializeApp();
  }

  Future<void> _initializeApp() async {
    try {
      // Step 1: Initialize image assets (20%)
      setState(() {
        _statusMessage = 'Loading images...';
        _progress = 0.2;
      });
      await ImageUtils.initialize();
      
      // Step 2: Sync new image assets from server (40%)
      setState(() {
        _statusMessage = 'Syncing new images...';
        _progress = 0.4;
      });
      
      try {
        final newCount = await ImageUtils.syncNewAssets();
        if (newCount > 0) {
          setState(() {
            _statusMessage = 'Downloaded $newCount new images';
          });
          await Future.delayed(const Duration(milliseconds: 300));
        }
      } catch (e) {
        debugPrint('⚠️ Image sync error: $e');
        // Continue even if image sync fails
      }
      
      // Step 3: Initialize and sync inventory data (70%)
      setState(() {
        _statusMessage = 'Syncing inventory data...';
        _progress = 0.7;
      });
      
      final inventoryService = InventoryService();
      await inventoryService.initialize();
      
      // Fetch and cache full inventory data
      try {
        final items = await inventoryService.fetchInventory(
          pageSize: 1000,
          forceRefresh: true,
        );
        
        if (items.isNotEmpty) {
          // Save to local cache for offline access
          await inventoryService.saveInventoryToLocal(items);
          setState(() {
            _statusMessage = 'Cached ${items.length} inventory items';
            _progress = 0.9;
          });
          await Future.delayed(const Duration(milliseconds: 300));
        }
      } catch (e) {
        debugPrint('⚠️ Inventory sync error: $e');
        // Continue even if inventory sync fails
      }

      // Step 4: Complete (100%)
      setState(() {
        _statusMessage = 'Ready!';
        _progress = 1.0;
      });
      
      await Future.delayed(const Duration(milliseconds: 300));

      // Navigate to home
      if (mounted) {
        context.goNamed(AppRouter.home);
      }
    } catch (e) {
      debugPrint('⚠️ Initialization error: $e');
      // Navigate anyway after a short delay
      await Future.delayed(const Duration(milliseconds: 500));
      if (mounted) {
        context.goNamed(AppRouter.home);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final shortestSide = size.shortestSide;
    final isSmallDevice = shortestSide < 550;
    final logoSize = isSmallDevice ? shortestSide * 0.6 : 300.0;

    return Scaffold(
      backgroundColor: AppTheme.primaryColor,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Hero(
              tag: 'splash-logo',
              child: Image.asset(
                'assets/logo.png',
                width: logoSize,
                height: logoSize,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) => Icon(
                  Icons.diamond_outlined,
                  size: logoSize * 0.5,
                  color: Theme.of(context).colorScheme.secondary,
                ),
              ),
            ),
            const SizedBox(height: 40),
            // Progress bar
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 60),
              child: Column(
                children: [
                  LinearProgressIndicator(
                    value: _progress,
                    backgroundColor: Colors.white.withOpacity(0.3),
                    valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                    minHeight: 4,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _statusMessage,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
