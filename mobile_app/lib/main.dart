import 'package:flutter/material.dart';
//import 'package:go_router/go_router.dart';
import 'services/api_service.dart';
import 'services/storage_service.dart';
import 'services/inventory_service.dart';
import 'services/directory_service.dart';
// Unified service not directly used in main.dart anymore
import 'services/connectivity_service.dart';
import 'services/offline_catalog_service.dart';
import 'services/system_ui_service.dart';
import 'services/image_sync_service.dart';
import 'navigation/app_router.dart';
import 'theme/app_theme.dart';
import 'state/cart_state.dart';
import 'state/saved_items_state.dart';
import 'package:provider/provider.dart';
import 'dart:async';
import 'services/firebase_service.dart';
import 'firebase/firebase_messaging_handler.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';

void main() async {
  // Ensure Flutter is initialized
  WidgetsFlutterBinding.ensureInitialized();
  
  // Configure system UI for Android 15+ edge-to-edge compatibility
  SystemUIService.instance.configureNormalMode();
  
  // Initialize Firebase first (required for Crashlytics)
  try {
    await FirebaseService.instance.initialize();
    debugPrint('Firebase initialized successfully');
    
    // Now set up Crashlytics error handling
    FlutterError.onError = (FlutterErrorDetails details) {
      FlutterError.presentError(details);
      FirebaseCrashlytics.instance.recordFlutterFatalError(details);
    };

    // Handle uncaught async errors
    WidgetsBinding.instance.platformDispatcher.onError = (error, stack) {
      FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
      return true;
    };
  } catch (e) {
    debugPrint('Firebase initialization failed: $e');
    // Set up basic error handling without Crashlytics
    FlutterError.onError = (FlutterErrorDetails details) {
      FlutterError.presentError(details);
    };
  }
  
  // Start app immediately - Firebase will initialize in background
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
  late final ImageSyncService _imageSyncService;

  // Analytics observer is created but not currently used with GoRouter
  // Uncomment if needed for MaterialApp navigation
  // final _analyticsObserver = AnalyticsNavigatorObserver();
  
  @override
  void initState() {
    super.initState();

    WidgetsBinding.instance.addObserver(this);

    // Initialize only essential services synchronously
    _apiService = ApiService(storageService: _storageService);
    _connectivityService = ConnectivityService();
    _offlineCatalogService = OfflineCatalogService(
      apiService: _apiService,
      connectivityService: _connectivityService,
    );
    
    // Initialize image sync service
    _imageSyncService = ImageSyncService(apiService: _apiService);
    
    // Initialize router immediately for UI
    _router = AppRouter(
      storageService: _storageService,
      apiService: _apiService,
      inventoryService: _inventoryService,
      directoryService: _directoryService,
      offlineCatalogService: _offlineCatalogService,
      connectivityService: _connectivityService,
    );
    
    // Move heavy operations to background
    _initializeInBackground();
  }
  
  void _initializeInBackground() {
    // Initialize services in background
    _initializeServices();
  }
  
  Future<void> _initializeServices() async {
    try {
      // Initialize storage first (fastest)
      await _storageService.initialize().timeout(
        const Duration(seconds: 10),
        onTimeout: () => debugPrint('⚠️ Storage timeout'),
      );
      
      // Initialize API service (needed for app functionality)
      await _apiService.initialize().timeout(
        const Duration(seconds: 10),
        onTimeout: () => debugPrint('⚠️ API timeout'),
      );
      
      // All other services in background (non-blocking)
      unawaited(_initializeBackgroundServices());
      
      debugPrint('✅ Core services initialized');
    } catch (e) {
      debugPrint('❌ Error: $e');
    }
  }
  
  Future<void> _initializeBackgroundServices() async {
    // Stagger initialization to prevent resource contention
    
    // Initialize inventory service
    unawaited(
      _inventoryService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () => null,
      )
    );
    
    // Small delay before next service
    await Future<void>.delayed(const Duration(milliseconds: 100));
    
    // Initialize directory service
    unawaited(
      _directoryService.initialize().timeout(
        const Duration(seconds: 2),
        onTimeout: () => null,
      )
    );
    
    // Initialize Firebase messaging (Firebase already initialized)
    unawaited(
      Future<void>.delayed(const Duration(milliseconds: 500), () async {
        try {
          await FirebaseMessagingHandler.setup();
        } catch (e) {
          debugPrint('Firebase messaging error: $e');
        }
      })
    );
    
    // Sync images on app launch (medium priority)
    unawaited(
      Future<void>.delayed(const Duration(milliseconds: 800), () async {
        try {
          await _imageSyncService.syncAllImages();
        } catch (e) {
          debugPrint('Image sync error: $e');
        }
      })
    );
    
    // Background sync (very low priority)
    unawaited(
      Future<void>.delayed(const Duration(seconds: 1), () {
        _offlineCatalogService.syncCatalog();
      })
    );
    
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
          iconTheme: const IconThemeData(color: AppTheme.accentColor),
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
        bottomNavigationBarTheme: const BottomNavigationBarThemeData(
          backgroundColor: AppTheme.cardColor,
          selectedItemColor: AppTheme.accentColor,
          unselectedItemColor: AppTheme.textSecondary,
          showSelectedLabels: true,
          showUnselectedLabels: true,
          type: BottomNavigationBarType.fixed,
          elevation: 0,
          // Compact sizing
          selectedLabelStyle: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
          ),
          unselectedLabelStyle: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
      routerConfig: routerConfig,
    );
  }
}

