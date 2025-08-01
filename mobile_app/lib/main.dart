import 'package:flutter/material.dart';
//import 'package:go_router/go_router.dart';
import 'services/api_service.dart';
import 'services/storage_service.dart';
import 'services/inventory_service.dart';
import 'services/directory_service.dart';
// Unified service not directly used in main.dart anymore
import 'services/saved_items_service.dart';
import 'services/connectivity_service.dart';
import 'services/offline_catalog_service.dart';
import 'navigation/app_router.dart';
import 'theme/app_theme.dart';
import 'state/cart_state.dart';
import 'state/saved_items_state.dart';
import 'package:provider/provider.dart';
import 'services/firebase_service.dart';
import 'services/analytics_wrapper.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';

void main() async {
  // Ensure Flutter is initialized
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Firebase
  await FirebaseService.instance.initialize();
  
  // Set up global error handling and forward to Crashlytics
  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    FirebaseCrashlytics.instance.recordFlutterFatalError(details);
  };

  // Handle uncaught async errors
  WidgetsBinding.instance.platformDispatcher.onError = (error, stack) {
    FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
    return true;
  };
  
  // No need to preload assets for splash screen
  // Native splash screen will handle the initial display
  
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => CartState()),
        ChangeNotifierProvider(create: (_) => SavedItemsState()),
        Provider(create: (_) => InventoryService()),
        Provider(create: (_) => ConnectivityService()),
      ],
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> with WidgetsBindingObserver {
  // Initialize services
  late final StorageService _storageService = StorageService();
  late final ApiService _apiService;
  final InventoryService _inventoryService = InventoryService();
  final DirectoryService _directoryService = DirectoryService();
  late final ConnectivityService _connectivityService;
  late final OfflineCatalogService _offlineCatalogService;
  late final AppRouter _router;

  // Analytics observer is created but not currently used with GoRouter
  // Uncomment if needed for MaterialApp navigation
  // final _analyticsObserver = AnalyticsNavigatorObserver();
  
  @override
  void initState() {
    super.initState();

    WidgetsBinding.instance.addObserver(this);

    // Initialize services in the correct order
    _apiService = ApiService(storageService: _storageService);
    _connectivityService = ConnectivityService();
    _offlineCatalogService = OfflineCatalogService(
      apiService: _apiService,
      connectivityService: _connectivityService,
    );
    
    // Initialize router
    _router = AppRouter(
      apiService: _apiService,
      storageService: _storageService,
      inventoryService: _inventoryService,
      directoryService: _directoryService,
      offlineCatalogService: _offlineCatalogService,
      connectivityService: _connectivityService,
    );
    
    // Initialize services
    _initializeServices();
  }
  
  Future<void> _initializeServices() async {
    try {
      // Initialize API service
      await _apiService.initialize();
      
      // Initialize storage service
      await _storageService.initialize();
      
      // Initialize inventory service
      await _inventoryService.initialize();
      
      // Initialize directory service
      await _directoryService.initialize();

      // Kick off offline catalog sync in background
      _offlineCatalogService.syncCatalog();
      
      // Initialize saved items from storage (after widget is built)
      // We need to wait for the first frame to be built before accessing context
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          // This ensures context is available for Provider access
          _initializeSavedItems();
        }
      });
      
      debugPrint('All services initialized successfully');
    } catch (e) {
      debugPrint('Error initializing services: $e');
    }
  }
  
  Future<void> _initializeSavedItems() async {
    try {
      // Safety check to ensure we have a valid context
      if (!mounted) {
        debugPrint('Context not mounted, skipping saved items initialization');
        return;
      }
      
      // Get the saved items from storage directly
      final savedItems = await SavedItemsService.getSavedItems();
      
      // Update the provider with the saved items
      if (mounted) {
        final savedItemsState = Provider.of<SavedItemsState>(context, listen: false);
        savedItemsState.clearSavedItems();
        for (var item in savedItems) {
          savedItemsState.addItem(item);
        }
        debugPrint('Saved items initialized from storage: ${savedItems.length} items');
      }
      
      // Log app start event
      FirebaseService.instance.logEvent(name: 'app_start');
      
      // Initialize analytics wrapper
      AnalyticsWrapper();
    } catch (e) {
      debugPrint('Error initializing saved items: $e');
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _apiService.clearExpiredCache();
      _inventoryService.clearExpiredCache();
      _storageService.clearExpiredCache();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // Check if router is initialized, use a placeholder if not
    final routerConfig = _router.router;
    
    return MaterialApp.router(
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
        appBarTheme: AppBarTheme(
          backgroundColor: Colors.transparent,
          elevation: 0,
          centerTitle: true,
          titleTextStyle: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            fontFamily: 'Poppins',
            letterSpacing: 1.2,
            foreground: Paint()
              ..shader = const LinearGradient(
                colors: [
                  Color(0xFFD4AF37),  // Rich gold
                  Color(0xFFFFD700),  // Bright gold
                  Color(0xFFD4AF37),  // Back to rich gold
                ],
              ).createShader(const Rect.fromLTWH(0.0, 0.0, 200.0, 70.0)),
            shadows: [
              Shadow(
                color: AppTheme.accentColor.withValues(alpha: 0.7),
                blurRadius: 10.0,
                offset: const Offset(0, 0),
              ),
              Shadow(
                color: AppTheme.accentColor.withValues(alpha: 0.3),
                blurRadius: 5.0,
                offset: const Offset(0, 0),
              ),
            ],
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
      routerConfig: routerConfig,
    );
  }
}

