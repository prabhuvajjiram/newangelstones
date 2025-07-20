import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../screens/home_screen.dart';
import '../screens/colors_screen.dart';
import '../screens/inventory_screen.dart';
import '../screens/contact_screen.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../services/inventory_service.dart';
import '../services/directory_service.dart';
import '../widgets/cart_icon.dart';
import '../theme/app_theme.dart';

class MainNavigation extends StatefulWidget {
  const MainNavigation({
    super.key,
    required this.apiService,
    required this.storageService,
    required this.inventoryService,
    required this.directoryService,
  });

  final ApiService apiService;
  final StorageService storageService;
  final InventoryService inventoryService;
  final DirectoryService directoryService;

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation> {
  final PageStorageBucket _bucket = PageStorageBucket();
  int _currentIndex = 0;

  late final List<Widget> _pages;

  bool _isInitialized = false;
  String? _initError;

  @override
  void initState() {
    super.initState();
    _initializeServices();

    // Create pages immediately so UI can render even if services are still initializing
    _pages = [
      HomeScreen(
        key: const PageStorageKey('home'),
        apiService: widget.apiService,
        storageService: widget.storageService,
        inventoryService: widget.inventoryService,
        directoryService: widget.directoryService,
        onViewFullInventory: () => setState(() => _currentIndex = 2),
      ),
      ColorsScreen(
        key: const PageStorageKey('colors'),
        apiService: widget.apiService,
      ),
      InventoryScreen(
        key: const PageStorageKey('inventory'),
        inventoryService: widget.inventoryService,
      ),
      const ContactScreen(key: PageStorageKey('contact')),
    ];
  }

  Future<void> _initializeServices() async {
    debugPrint('🔄 Starting service initialization...');

    // Add a global failsafe timeout to ensure UI is updated even if something gets stuck
    Future.delayed(const Duration(seconds: 10), () {
      if (mounted && !_isInitialized) {
        debugPrint('⚠️ Global failsafe timeout triggered - forcing UI update');
        setState(() {
          _isInitialized = true;
          _initError =
              'Some services failed to initialize. The app may have limited functionality.';
        });
      }
    });

    try {
      // Initialize services with individual timeouts
      debugPrint('🔄 Initializing API service...');
      await widget.apiService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ API service initialization timed out');
          return;
        },
      );

      debugPrint('🔄 Initializing Storage service...');
      await widget.storageService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ Storage service initialization timed out');
          return;
        },
      );

      debugPrint('🔄 Initializing Inventory service...');
      await widget.inventoryService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ Inventory service initialization timed out');
          return;
        },
      );

      debugPrint('🔄 Initializing Directory service...');
      await widget.directoryService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ Directory service initialization timed out');
          return;
        },
      );

      debugPrint('🔄 Preloading API data...');
      await _preloadApiData().timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ API data preloading timed out');
          return;
        },
      );

      debugPrint('✅ All services initialized successfully!');

      if (mounted) {
        setState(() {
          _isInitialized = true;
          debugPrint('✅ UI updated: _isInitialized = true');
        });
      } else {
        debugPrint('⚠️ Widget not mounted, cannot update state');
      }
    } catch (e, stackTrace) {
      debugPrint('⚠️ Error during service initialization: $e');
      debugPrint('Stack trace: $stackTrace');
      if (mounted) {
        setState(() {
          _initError = e.toString();
          _isInitialized = true; // Still mark as initialized to prevent infinite loading
          debugPrint('✅ UI updated with error: $_initError');
        });
      } else {
        debugPrint('⚠️ Widget not mounted, cannot update state with error');
      }
    }
  }

  Future<void> _preloadApiData() async {
    try {
      // Preload essential data with individual timeouts
      await widget.apiService
          .loadLocalProducts('assets/featured_products.json')
          .timeout(const Duration(seconds: 3), onTimeout: () => []);
    } catch (e) {
      debugPrint('⚠️ Error preloading API data: $e');
      // Continue anyway
    }
  }

  @override
  Widget build(BuildContext context) {
    // Always show the main UI, but add a loading overlay if still initializing
    // This ensures we don't get stuck at a blank screen
    final Widget mainContent = Scaffold(
      appBar: AppBar(
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(4),
              margin: const EdgeInsets.only(right: 12),
              decoration: BoxDecoration(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Image.asset(
                'assets/logo.png',
                width: 28,
                height: 28,
                fit: BoxFit.contain,
              ),
            ),
            const Text('Angel Granites'),
          ],
        ),
        actions: [
          CartIcon(
            onPressed: () {
              context.push('/cart');
            },
          ),
          IconButton(
            icon: const Icon(Icons.person_outline),
            tooltip: 'Login',
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: const Text('Login coming soon'),
                  behavior: SnackBarBehavior.floating,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              );
            },
          ),
        ],
      ),
      body: _pages[_currentIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        type: BottomNavigationBarType.fixed,
        backgroundColor: AppTheme.cardColor,
        selectedItemColor: AppTheme.accentColor,
        unselectedItemColor: AppTheme.textSecondary,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.color_lens),
            label: 'Colors',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.inventory),
            label: 'Inventory',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.contact_page),
            label: 'Contact',
          ),
        ],
      ),
    );

    // If still initializing, show a loading overlay
    if (!_isInitialized) {
      return Stack(
        children: [
          mainContent, // Show the main UI in the background
          // Overlay with semi-transparent background
          Container(
            color: AppTheme.primaryColor.withOpacity(0.8),
            child: const Center(
              child: CircularProgressIndicator(color: AppTheme.accentColor),
            ),
          ),
        ],
      );
    }

    // Show error screen if initialization failed
    if (_initError != null) {
      return Scaffold(
        backgroundColor: AppTheme.primaryColor,
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, color: Colors.red, size: 64),
              const SizedBox(height: 16),
              const Text(
                'Initialization Error',
                style: TextStyle(
                    color: AppTheme.textPrimary,
                    fontSize: 20,
                    fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 32),
                child: Text(
                  _initError!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppTheme.textSecondary),
                ),
              ),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: () {
                  setState(() {
                    _initError = null;
                  });
                },
                child: const Text('Continue Anyway'),
              ),
            ],
          ),
        ),
      );
    }

    // Main app UI once initialized
    return mainContent;
  }
}
