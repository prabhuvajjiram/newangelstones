import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:shimmer/shimmer.dart';
import 'package:url_launcher/url_launcher.dart';

import 'app_router.dart';
import '../screens/home_screen.dart';
import '../screens/colors_screen.dart';
import '../screens/inventory_screen.dart';
import '../screens/contact_screen.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../services/inventory_service.dart';
import '../services/directory_service.dart';
import '../services/connectivity_service.dart';
import '../widgets/cart_icon.dart';
import '../theme/app_theme.dart';

class MainNavigation extends StatefulWidget {
  const MainNavigation({
    super.key,
    required this.apiService,
    required this.storageService,
    required this.inventoryService,
    required this.directoryService,
    this.connectivityService,
  });

  final ApiService apiService;
  final StorageService storageService;
  final InventoryService inventoryService;
  final DirectoryService directoryService;
  final ConnectivityService? connectivityService;

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation> with WidgetsBindingObserver {
  int _currentIndex = 0;
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
    _initializeServices();
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
    Future.delayed(const Duration(seconds: 10), () {
      if (mounted && !_isInitialized) {
        setState(() {
          _isInitialized = true;
          _initError = 'Some services failed to initialize. The app may have limited functionality.';
        });
      }
    });
    try {
      await widget.apiService.initialize().timeout(const Duration(seconds: 2), onTimeout: () => null);
      await widget.storageService.initialize().timeout(const Duration(seconds: 2), onTimeout: () => null);
      await widget.inventoryService.initialize().timeout(const Duration(seconds: 2), onTimeout: () => null);
      await widget.directoryService.initialize().timeout(const Duration(seconds: 2), onTimeout: () => null);
      await _preloadApiData().timeout(const Duration(seconds: 2), onTimeout: () => null);
      if (mounted) setState(() => _isInitialized = true);
    } catch (e, stackTrace) {
      debugPrint('⚠️ Error during service initialization: $e');
      debugPrint('Stack trace: $stackTrace');
      if (mounted) {
        setState(() {
          _initError = e.toString();
          _isInitialized = true;
        });
      }
    }
  }

  Future<void> _preloadApiData() async {
    try {
      await widget.apiService.loadLocalProducts('assets/featured_products.json')
          .timeout(const Duration(seconds: 3), onTimeout: () => []);
    } catch (e) {
      debugPrint('⚠️ Error preloading API data: $e');
    }
  }

  Future<void> _launchMonumentLink() async {
    final Uri uri = Uri.parse('https://monument.business/GV/');
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not launch URL')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final double dynamicFontSize = screenWidth / 20;

    final Widget mainContent = Scaffold(
      appBar: AppBar(
        centerTitle: false,
        backgroundColor: Colors.black,
        elevation: 0,
        title: Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            InkWell(
              onTap: _launchMonumentLink,
              splashColor: Colors.transparent,
              highlightColor: Colors.transparent,
              child: Container(
                width: 30,
                height: 30,
                color: kDebugMode
                    ? Colors.red.withOpacity(0.3)
                    : Colors.transparent,
              ),
            ),
            const SizedBox(width: 2),
            SizedBox(
              width: 36,
              height: 36,
              child: Stack(
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
                        color: const Color(0xFFD4AF37).withValues(alpha: 0.3),
                      ),
                    ),
                  ),
                  Image.asset(
                    'assets/logo.png',
                    fit: BoxFit.contain,
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Flexible(
              fit: FlexFit.tight,
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
                      //fontSize: 18, // this acts as max size
                      fontSize: dynamicFontSize.clamp(14.0, 22.0), // keeps it readable on all devices
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
        actions: [
          IconButton(
            icon: const Icon(Icons.search, color: Color(0xFFFFD700)),
            tooltip: 'Search',
            onPressed: () {
              GoRouter.of(context).pushNamed(AppRouter.search);
            },
          ),
          CartIcon(
            onPressed: () {
              GoRouter.of(context).pushNamed(AppRouter.cart);
            },
          ),
          IconButton(
            icon: const Icon(Icons.person_outline, color: Color(0xFFFFD700)),
            tooltip: 'Login',
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Login coming soon'),
                  behavior: SnackBarBehavior.floating,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.all(Radius.circular(8)),
                  ),
                ),
              );
            },
          ),
        ],
      ),
      body: AnimatedSwitcher(
        duration: const Duration(milliseconds: 300),
        transitionBuilder: (Widget child, Animation<double> animation) {
          return FadeTransition(
            opacity: animation,
            child: child,
          );
        },
        child: _pages[_currentIndex],
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
              blurRadius: 10,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: const BorderRadius.only(
            topLeft: Radius.circular(16),
            topRight: Radius.circular(16),
          ),
          child: BottomNavigationBar(
            currentIndex: _currentIndex,
            onTap: (index) {
              setState(() {
                _currentIndex = index;
              });
            },
            elevation: 8,
            type: BottomNavigationBarType.fixed,
            backgroundColor: AppTheme.cardColor,
            selectedItemColor: AppTheme.accentColor,
            unselectedItemColor: AppTheme.textSecondary,
            selectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12),
            unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.normal, fontSize: 11),
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
        ),
      ),
    );

    if (!_isInitialized) {
      return Stack(
        children: [
          mainContent,
          Container(
            color: AppTheme.primaryColor.withValues(alpha: 0.8),
            child: const Center(
              child: CircularProgressIndicator(color: AppTheme.accentColor),
            ),
          ),
        ],
      );
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

    return mainContent;
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && _connectivityService != null) {
      _checkConnectivityStatus();
    }
  }

  void _setupConnectivityMonitoring() {
    _checkConnectivityStatus();
    _connectivitySubscription = _connectivityService!.onConnectivityChanged.listen((isOnline) {
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
