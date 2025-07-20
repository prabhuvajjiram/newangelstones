import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'services/api_service.dart';
import 'services/storage_service.dart';
import 'services/inventory_service.dart';
import 'services/directory_service.dart';
import 'package:provider/provider.dart';
import 'state/cart_state.dart';
import 'screens/home_screen.dart';
import 'screens/colors_screen.dart';
import 'screens/inventory_screen.dart';
import 'screens/contact_screen.dart';
import 'theme/app_theme.dart';
import 'widgets/cart_icon.dart';
import 'navigation/app_router.dart';

void main() async {
  // Ensure Flutter is initialized
  WidgetsFlutterBinding.ensureInitialized();
  
  // Set up global error handling
  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    debugPrint('Flutter error caught: ${details.exception}');
  };
  
  // Handle uncaught async errors
  // Use WidgetsBinding for platform error handling (compatible with older Flutter versions)
  WidgetsBinding.instance.platformDispatcher.onError = (error, stack) {
    debugPrint('Uncaught platform error: $error');
    return true;
  };
  
  // Preload critical assets with timeout safety
  try {
    await Future.wait([
      // Preload any critical assets here
      rootBundle.load('assets/logo.png'),
    ]).timeout(
      const Duration(seconds: 3),
      onTimeout: () {
        debugPrint('Asset preloading timed out, continuing with app startup');
        return [];
      },
    );
  } catch (e) {
    debugPrint('Error during asset preloading: $e');
    // Continue with app startup anyway
  }
  
  runApp(
    ChangeNotifierProvider(
      create: (_) => CartState(),
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Angel Granites',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        scaffoldBackgroundColor: AppTheme.primaryColor,
        colorScheme: const ColorScheme.dark(
          primary: AppTheme.accentColor,
          secondary: AppTheme.accentColor,
          surface: AppTheme.cardColor,
        ),
        textTheme: Theme.of(context).textTheme.apply(
              fontFamily: 'OpenSans',
              bodyColor: AppTheme.textPrimary,
              displayColor: AppTheme.textPrimary,
            ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.transparent,
          elevation: 0,
          centerTitle: true,
          titleTextStyle: TextStyle(
            color: AppTheme.textPrimary,
            fontSize: 24,
            fontWeight: FontWeight.bold,
            fontFamily: 'Poppins',
          ),
          iconTheme: IconThemeData(color: AppTheme.accentColor),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: AppTheme.accentColor,
            foregroundColor: AppTheme.primaryColor,
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(8),
            ),
            textStyle: const TextStyle(
              fontFamily: 'Poppins',
              fontWeight: FontWeight.w600,
              fontSize: 16,
            ),
          ),
        ),
        cardTheme: ThemeData.dark().cardTheme.copyWith(
          color: AppTheme.cardColor,
          elevation: 2,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
        ),
        bottomNavigationBarTheme: BottomNavigationBarThemeData(
          backgroundColor: AppTheme.cardColor,
          selectedItemColor: AppTheme.accentColor,
          unselectedItemColor: AppTheme.textSecondary,
          showSelectedLabels: true,
          showUnselectedLabels: true,
          type: BottomNavigationBarType.fixed,
        ),
      ),
      onGenerateRoute: AppRouter.generateRoute,
      home: const MainNavigation(),
    );
  }
}

class MainNavigation extends StatefulWidget {
  const MainNavigation({super.key});

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation> {
  final PageStorageBucket _bucket = PageStorageBucket();
  int _currentIndex = 0;
  final ApiService _apiService = ApiService();
  
  late final List<Widget> _pages;
  final StorageService _storageService = StorageService();
  final InventoryService _inventoryService = InventoryService();
  final DirectoryService _directoryService = DirectoryService();
  
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
        apiService: _apiService,
        storageService: _storageService,
        inventoryService: _inventoryService,
        directoryService: _directoryService,
        onViewFullInventory: () => setState(() => _currentIndex = 2),
      ),
      ColorsScreen(
        key: const PageStorageKey('colors'),
        apiService: _apiService,
      ),
      InventoryScreen(
        key: const PageStorageKey('inventory'),
        inventoryService: _inventoryService,
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
          _initError = 'Some services failed to initialize. The app may have limited functionality.';
        });
      }
    });
    
    try {
      // Initialize services with individual timeouts
      debugPrint('🔄 Initializing API service...');
      await _apiService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ API service initialization timed out');
          return;
        },
      );
      
      debugPrint('🔄 Initializing Storage service...');
      await _storageService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ Storage service initialization timed out');
          return;
        },
      );
      
      debugPrint('🔄 Initializing Inventory service...');
      await _inventoryService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () {
          debugPrint('⚠️ Inventory service initialization timed out');
          return;
        },
      );
      
      debugPrint('🔄 Initializing Directory service...');
      await _directoryService.initialize().timeout(
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
      await _apiService.loadLocalProducts('assets/featured_products.json')
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
              Navigator.pushNamed(context, AppRoutePaths.cart);
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
            child: Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Image.asset(
                    'assets/logo.png',
                    width: 120,
                    height: 120,
                    fit: BoxFit.contain,
                  ),
                  const SizedBox(height: 24),
                  const CircularProgressIndicator(color: AppTheme.accentColor),
                  const SizedBox(height: 16),
                  const Text(
                    'Loading...',
                    style: TextStyle(color: AppTheme.textPrimary, fontSize: 18),
                  ),
                ],
              ),
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
                style: TextStyle(color: AppTheme.textPrimary, fontSize: 20, fontWeight: FontWeight.bold),
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
    return Scaffold(
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
              Navigator.pushNamed(context, AppRoutePaths.cart);
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
  }
}
