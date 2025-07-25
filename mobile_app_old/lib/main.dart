import 'package:flutter/material.dart';
import 'services/api_service.dart';
import 'services/storage_service.dart';
import 'services/inventory_service.dart';
import 'services/directory_service.dart';
import 'navigation/app_router.dart';
import 'theme/app_theme.dart';
import 'state/cart_state.dart';
import 'package:provider/provider.dart';
import 'services/firebase_service.dart';
import 'services/analytics_wrapper.dart';

void main() async {
  // Ensure Flutter is initialized
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Firebase
  await FirebaseService.instance.initialize();
  
  // Set up global error handling
  // Note: Firebase Crashlytics will handle error reporting in production
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
  
  // No need to preload assets for splash screen
  // Native splash screen will handle the initial display
  
  runApp(
    ChangeNotifierProvider(
      create: (_) => CartState(),
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  late final ApiService _apiService;
  late final StorageService _storageService;
  late final InventoryService _inventoryService;
  late final DirectoryService _directoryService;
  late final AppRouter _router;

  // Analytics observer is created but not currently used with GoRouter
  // Uncomment if needed for MaterialApp navigation
  // final _analyticsObserver = AnalyticsNavigatorObserver();
  
  @override
  void initState() {
    super.initState();
    _apiService = ApiService();
    _storageService = StorageService();
    _inventoryService = InventoryService();
    _directoryService = DirectoryService();
    _router = AppRouter(
      apiService: _apiService,
      storageService: _storageService,
      inventoryService: _inventoryService,
      directoryService: _directoryService,
    );
    
    // Log app start event
    FirebaseService.instance.logEvent(name: 'app_start');
    
    // Initialize analytics wrapper
    AnalyticsWrapper();
  }

  @override
  Widget build(BuildContext context) {
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
      routerConfig: _router.router,
    );
  }
}

