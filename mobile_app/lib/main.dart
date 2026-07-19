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
import 'services/siri_inventory_service.dart';
import 'services/notification_service.dart';
import 'widgets/notification_permission_prompt.dart';
import 'firebase/firebase_messaging_handler.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';

void main() async {
  // Ensure Flutter is initialized
  WidgetsFlutterBinding.ensureInitialized();

  // Configure system UI for Android 15+ edge-to-edge compatibility
  SystemUIService.instance.configureNormalMode();

  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
  };

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

  unawaited(_initializeFirebaseInBackground());
}

Future<void> _initializeFirebaseInBackground() async {
  try {
    await FirebaseService.instance.initialize();
    if (!FirebaseService.isSupportedPlatform) return;

    FlutterError.onError = (FlutterErrorDetails details) {
      FlutterError.presentError(details);
      FirebaseCrashlytics.instance.recordFlutterFatalError(details);
    };

    WidgetsBinding.instance.platformDispatcher.onError = (error, stack) {
      FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
      return true;
    };
  } catch (e) {
    debugPrint('Firebase initialization failed: $e');
  }
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> with WidgetsBindingObserver {
  // Initialize services
  late final StorageService _storageService;
  late final ApiService _apiService;
  final InventoryService _inventoryService = InventoryService();
  final DirectoryService _directoryService = DirectoryService();
  late final ConnectivityService _connectivityService;
  late final OfflineCatalogService _offlineCatalogService;
  late final AppRouter _router;
  late final ImageSyncService _imageSyncService;

  @override
  void initState() {
    super.initState();

    WidgetsBinding.instance.addObserver(this);

    // Initialize storage service synchronously (required by router)
    _storageService = StorageService();
    _apiService = ApiService(storageService: _storageService);
    _connectivityService = ConnectivityService();
    _offlineCatalogService = OfflineCatalogService(
      apiService: _apiService,
      connectivityService: _connectivityService,
    );

    // Initialize router immediately for UI
    _router = AppRouter(
      storageService: _storageService,
      apiService: _apiService,
      inventoryService: _inventoryService,
      directoryService: _directoryService,
      offlineCatalogService: _offlineCatalogService,
      connectivityService: _connectivityService,
    );

    // Initialize heavy services in background to avoid UI jank
    unawaited(
      Future<void>(() async {
        try {
          await _initializeHeavyServices();
        } catch (e) {
          debugPrint('Heavy service initialization error: $e');
        }
      }),
    );

    // Kick off non-critical background work after the first frame
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _startBackgroundWork();
      // This is a no-op on platforms without the native Apple intent bridge.
      unawaited(_openPendingSiriInventorySearch());
      unawaited(_showNotificationPermissionPromptIfNeeded());
    });
  }

  Future<void> _initializeHeavyServices() async {
    // Initialize heavy services in background to avoid UI jank
    // Image sync service can be heavy - initialize it in background
    _imageSyncService = ImageSyncService(apiService: _apiService);
    debugPrint('Heavy services initialized in background');
  }

  void _startBackgroundWork() {
    // Firebase messaging setup - skip if already initialized
    // Firebase is already initialized in main(), just set up messaging
    unawaited(
      Future<void>.delayed(const Duration(seconds: 1), () async {
        try {
          await FirebaseMessagingHandler.setup();
        } catch (e) {
          debugPrint('Firebase messaging error: $e');
        }
      }),
    );

    // Image sync — guarded by 24-h throttle inside syncAllImages()
    unawaited(
      Future<void>.delayed(const Duration(seconds: 3), () async {
        try {
          await _imageSyncService.syncAllImages();
        } catch (e) {
          debugPrint('Image sync error: $e');
        }
      }),
    );

    // Catalog background sync (very low priority)
    unawaited(
      Future<void>.delayed(const Duration(seconds: 4), () {
        _offlineCatalogService.syncCatalog();
      }),
    );
  }

  Future<void> _openPendingSiriInventorySearch() async {
    final query = await SiriInventoryService.takePendingSearch();
    if (!mounted || query == null) return;

    _router.router.go(
      Uri(path: '/inventory', queryParameters: {'query': query}).toString(),
    );
  }

  Future<void> _showNotificationPermissionPromptIfNeeded() async {
    if (!NotificationService.isSupportedPlatform) return;
    await Future<void>.delayed(const Duration(seconds: 8));
    if (!mounted ||
        !await NotificationService.instance.shouldShowPermissionPrompt()) {
      return;
    }

    if (!mounted) return;
    final dialogContext = _router.navigatorKey.currentContext;
    if (dialogContext == null || !dialogContext.mounted) return;

    await showDialog<void>(
      context: dialogContext,
      useRootNavigator: true,
      barrierDismissible: false,
      builder: (_) => const NotificationPermissionPrompt(),
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _apiService.clearExpiredCache();
      _inventoryService.clearExpiredCache();
      _storageService.clearExpiredCache();
      unawaited(_openPendingSiriInventorySearch());
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
      theme: AppTheme.darkTheme,
      darkTheme: AppTheme.darkTheme,
      highContrastTheme: AppTheme.highContrastDarkTheme,
      highContrastDarkTheme: AppTheme.highContrastDarkTheme,
      themeMode: ThemeMode.dark,
      builder: (context, child) => FocusTraversalGroup(
        policy: ReadingOrderTraversalPolicy(),
        child: child ?? const SizedBox.shrink(),
      ),
      routerConfig: routerConfig,
    );
  }
}
