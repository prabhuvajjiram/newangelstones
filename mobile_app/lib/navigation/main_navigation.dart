import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:shimmer/shimmer.dart';
import 'app_router.dart';
import '../screens/home_screen.dart';
import '../screens/colors_screen.dart';
import '../screens/inventory_screen.dart';
import '../screens/contact_screen.dart';
import '../screens/webview_screen.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../services/inventory_service.dart';
import '../services/directory_service.dart';
import '../services/connectivity_service.dart';
import '../services/system_ui_service.dart';
import '../services/accessibility_service.dart';
import '../widgets/cart_icon.dart';
import '../widgets/edge_to_edge_wrapper.dart';
import '../theme/app_theme.dart';
import '../config/security_config.dart';

class MainNavigation extends StatefulWidget {
  const MainNavigation({
    super.key,
    required this.apiService,
    required this.storageService,
    required this.inventoryService,
    required this.directoryService,
    this.connectivityService,
    this.initialTabIndex = 0,
  });

  final ApiService apiService;
  final StorageService storageService;
  final InventoryService inventoryService;
  final DirectoryService directoryService;
  final ConnectivityService? connectivityService;
  final int initialTabIndex;

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation>
    with WidgetsBindingObserver {
  static const _pageNames = ['Home', 'Colors', 'Stock', 'Contact'];

  late int _currentIndex;
  late final List<Widget> _pages;
  bool _isInitialized = false;
  String? _initError;
  StreamSubscription<bool>? _connectivitySubscription;
  bool _wasOffline = false;
  bool _offlineHandled = false;
  ConnectivityService? _connectivityService;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _currentIndex = widget.initialTabIndex.clamp(0, 3);

    // Configure system UI for main navigation
    SystemUIService.instance.configureForScreen('home');

    // All assets are bundled — show the home screen immediately.
    // Storage/API init runs in the background after the first frame.
    _isInitialized = true;
    WidgetsBinding.instance.addPostFrameCallback((_) => _initializeServices());

    _connectivityService = widget.connectivityService;
    if (_connectivityService != null) {
      _setupConnectivityMonitoring();
    }
    _pages = [
      HomeScreen(
        key: const PageStorageKey('home'),
        apiService: widget.apiService,
        storageService: widget.storageService,
        inventoryService: widget.inventoryService,
        directoryService: widget.directoryService,
        onViewFullInventory: () => _selectTab(2),
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

  void _selectTab(int index) {
    final nextIndex = index.clamp(0, _pageNames.length - 1);
    if (_currentIndex == nextIndex) return;
    setState(() => _currentIndex = nextIndex);
    AccessibilityService.announce(
      context,
      '${_pageNames[nextIndex]} tab selected',
    );
  }

  void _openSearch() {
    GoRouter.of(context).pushNamed(AppRouter.search);
  }

  Future<void> _initializeServices() async {
    debugPrint('🚀 Initializing services in background...');
    try {
      await Future.wait([
        widget.storageService.initialize().timeout(
          const Duration(seconds: 8),
          onTimeout: () {
            debugPrint('⚠️ Storage service timeout');
            return null;
          },
        ),
        widget.apiService.initialize().timeout(
          const Duration(seconds: 8),
          onTimeout: () {
            debugPrint('⚠️ API service timeout');
            return null;
          },
        ),
      ]);
      debugPrint('✅ Services initialized');
      _initializeBackgroundServices();
    } catch (e) {
      debugPrint('❌ Service initialization error: $e');
    }
  }

  void _initializeBackgroundServices() {
    // Initialize remaining services in background
    unawaited(widget.inventoryService.initialize());
    widget.directoryService.initialize();
    _preloadApiData();
  }

  Future<void> _preloadApiData() async {
    try {
      await widget.apiService
          .loadLocalProducts('assets/featured_products.json')
          .timeout(const Duration(seconds: 2), onTimeout: () => []);
    } catch (e) {
      // Continue without preloaded data
    }
  }

  @override
  void didUpdateWidget(MainNavigation oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.initialTabIndex != oldWidget.initialTabIndex) {
      setState(() {
        _currentIndex = widget.initialTabIndex.clamp(0, 3);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final double dynamicFontSize = screenWidth / 20;
    final reduceMotion =
        MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    final Widget mainContent = EdgeToEdgeWrapper.withBottomNav(
      child: Scaffold(
        appBar: AppBar(
          centerTitle: false,
          backgroundColor: Colors.black,
          elevation: 0,
          title: Semantics(
            container: true,
            header: true,
            label: 'Angel Granites',
            excludeSemantics: true,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                const SizedBox(width: 32),
                SizedBox(
                  width: 36,
                  height: 36,
                  child: reduceMotion
                      ? Image.asset(
                          'assets/logo.png',
                          fit: BoxFit.contain,
                          excludeFromSemantics: true,
                        )
                      : Stack(
                          alignment: Alignment.center,
                          children: [
                            Shimmer.fromColors(
                              baseColor: const Color(0xFFD4AF37),
                              highlightColor: const Color(0xFFFFF8DC),
                              period: const Duration(seconds: 3),
                              child: Container(
                                width: 36,
                                height: 36,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: const Color(0xFFD4AF37)
                                      .withValues(alpha: 0.3),
                                ),
                              ),
                            ),
                            Image.asset(
                              'assets/logo.png',
                              fit: BoxFit.contain,
                              excludeFromSemantics: true,
                            ),
                          ],
                        ),
                ),
                const SizedBox(width: 12),
                // Constrained width to leave room for actions (3 icons ~144px needed)
                Container(
                  constraints: BoxConstraints(
                    maxWidth: screenWidth * 0.30, // 30% of screen width
                  ),
                  child: ShaderMask(
                    blendMode: BlendMode.srcIn,
                    shaderCallback: (Rect bounds) {
                      return const LinearGradient(
                        colors: [
                          Color(0xFFD4AF37),
                          Color(0xFFFFD700),
                          Color(0xFFE6BE8A),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ).createShader(bounds);
                    },
                    child: FittedBox(
                      fit: BoxFit.scaleDown,
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'ANGEL GRANITES',
                        style: TextStyle(
                          fontSize: dynamicFontSize.clamp(14.0, 22.0),
                          fontWeight: FontWeight.w700,
                          fontFamily: 'OpenSans',
                          letterSpacing: 0.5,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.search, color: Color(0xFFFFD700)),
              tooltip: 'Search inventory (Ctrl+F)',
              onPressed: _openSearch,
            ),
            CartIcon(
              onPressed: () {
                GoRouter.of(context).pushNamed(AppRouter.cart);
              },
            ),
            IconButton(
              icon: const Icon(Icons.person_outline, color: Color(0xFFFFD700)),
              tooltip: 'Customer Portal',
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute<void>(
                    builder: (context) => const WebViewScreen(
                      url:
                          '${SecurityConfig.monumentBusinessBaseUrl}/GV/Account/Login',
                      title: 'Customer Portal',
                    ),
                  ),
                );
              },
            ),
          ],
        ),
        body: AnimatedSwitcher(
          duration:
              reduceMotion ? Duration.zero : const Duration(milliseconds: 300),
          transitionBuilder: (Widget child, Animation<double> animation) {
            return FadeTransition(
              opacity: animation,
              child: child,
            );
          },
          child: _pages[_currentIndex],
        ),
        bottomNavigationBar: SafeArea(
          child: Container(
            decoration: BoxDecoration(
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.08),
                  blurRadius: 8,
                  offset: const Offset(0, -1),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(20),
                topRight: Radius.circular(20),
              ),
              child: BottomNavigationBar(
                currentIndex: _currentIndex,
                onTap: _selectTab,
                elevation: 0,
                type: BottomNavigationBarType.fixed,
                backgroundColor: AppTheme.cardColor,
                selectedItemColor: AppTheme.accentColor,
                unselectedItemColor: AppTheme.textSecondary,
                // Compact font sizes for portrait mode
                selectedLabelStyle: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 11,
                  letterSpacing: 0.3,
                ),
                unselectedLabelStyle: const TextStyle(
                  fontWeight: FontWeight.w500,
                  fontSize: 10,
                  letterSpacing: 0.2,
                ),
                // Reduced icon size for better proportion
                iconSize: 22,
                // Compact spacing
                selectedFontSize: 11,
                unselectedFontSize: 10,
                items: const [
                  BottomNavigationBarItem(
                    icon: Icon(Icons.home_rounded),
                    label: 'Home',
                  ),
                  BottomNavigationBarItem(
                    icon: Icon(Icons.palette_rounded),
                    label: 'Colors',
                  ),
                  BottomNavigationBarItem(
                    icon: Icon(Icons.inventory_2_rounded),
                    label: 'Stock',
                  ),
                  BottomNavigationBarItem(
                    icon: Icon(Icons.contact_page_rounded),
                    label: 'Contact',
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
    // Skip splash screen - show main content immediately
    if (!_isInitialized) {
      // Return main content directly instead of splash
      // Splash animation was causing hang issues
      setState(() {
        _isInitialized = true;
      });
    }

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
                  fontWeight: FontWeight.bold,
                ),
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

    return CallbackShortcuts(
      bindings: {
        const SingleActivator(LogicalKeyboardKey.keyF, control: true):
            _openSearch,
        const SingleActivator(LogicalKeyboardKey.digit1, alt: true): () =>
            _selectTab(0),
        const SingleActivator(LogicalKeyboardKey.digit2, alt: true): () =>
            _selectTab(1),
        const SingleActivator(LogicalKeyboardKey.digit3, alt: true): () =>
            _selectTab(2),
        const SingleActivator(LogicalKeyboardKey.digit4, alt: true): () =>
            _selectTab(3),
      },
      child: FocusTraversalGroup(
        policy: ReadingOrderTraversalPolicy(),
        child: mainContent,
      ),
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && _connectivityService != null) {
      _checkConnectivityStatus();
    }
  }

  void _setupConnectivityMonitoring() {
    _checkConnectivityStatus();
    _connectivitySubscription =
        _connectivityService!.onConnectivityChanged.listen((isOnline) {
      if (!isOnline && !_offlineHandled) {
        _navigateToOfflineCatalog();
      } else if (isOnline && _wasOffline) {
        _wasOffline = false;
        _offlineHandled = false;
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

  Future<void> _checkConnectivityStatus() async {
    if (_connectivityService == null) return;
    final isOnline = await _connectivityService!.isOnline;
    if (!isOnline && !_offlineHandled) {
      _navigateToOfflineCatalog();
    }
  }

  void _navigateToOfflineCatalog() {
    _wasOffline = true;
    _offlineHandled = true;
    try {
      GoRouter.of(context).pushNamed(AppRouter.offlineCatalog);
    } catch (e) {
      debugPrint('⚠️ Error navigating to offline catalog: $e');
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _connectivitySubscription?.cancel();
    super.dispose();
  }
}
