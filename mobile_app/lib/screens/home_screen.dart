import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../widgets/flyer_section.dart';
import '../widgets/product_folder_section.dart';
import '../services/directory_service.dart';
import '../models/inventory_item.dart';
import '../services/inventory_service.dart';
import '../services/review_prompt_service.dart';
import '../theme/app_theme.dart';
import '../widgets/skeleton_loaders.dart';
import '../utils/app_store_utils.dart';
import '../utils/image_utils.dart';

class NavigationService {
  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
}

class HomeScreen extends StatefulWidget {
  final ApiService apiService;
  final StorageService storageService;
  final InventoryService inventoryService;
  final DirectoryService directoryService;
  final VoidCallback onViewFullInventory;

  const HomeScreen({
    super.key,
    required this.apiService,
    required this.storageService,
    required this.inventoryService,
    required this.directoryService,
    required this.onViewFullInventory,
  });

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<List<Product>> _futureFeatured;
  late Future<List<InventoryItem>> _futureInventorySummary;
  late Future<List<Product>> _futureSpecials;
  late DirectoryService _directoryService;
  bool _showConventionBanner = true;

  @override
  void initState() {
    super.initState();
    
    // Track app launch for review prompt
    ReviewPromptService.trackAppLaunch();
    
    // Initialize hybrid assets and sync new images in background
    _initializeHybridAssets();
    
    // Try to fetch featured products from server, fall back to local if needed
    _futureFeatured = widget.apiService.fetchFeaturedProducts();
    _futureInventorySummary =
        widget.inventoryService.fetchInventory(pageSize: 1000);
    _futureSpecials = widget.apiService.fetchSpecials();
    _directoryService = widget.directoryService;
    
    // Schedule review prompt to show after 5 seconds (regardless of screen)
    _scheduleReviewPrompt();
  }

  /// Initialize hybrid asset system and sync new assets
  Future<void> _initializeHybridAssets() async {
    try {
      await ImageUtils.initialize();
      
      // Sync new assets in background (non-blocking)
      ImageUtils.syncNewAssets().then((newCount) {
        if (newCount > 0) {
          debugPrint('✅ Downloaded $newCount new images from server');
          // Optional: Show a snackbar to user
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('Downloaded $newCount new images'),
                duration: const Duration(seconds: 2),
                behavior: SnackBarBehavior.floating,
              ),
            );
          }
        }
      }).catchError((e) {
        debugPrint('⚠️ Error syncing new assets: $e');
      });
    } catch (e) {
      debugPrint('⚠️ Error initializing hybrid assets: $e');
    }
  }

  /// Schedule review prompt to show after user has been active
  void _scheduleReviewPrompt() {
    // Wait for app to fully initialize and user to interact
    Future.delayed(const Duration(seconds: 10), () async {
      // Use global navigator context to avoid async gap issues
      final context = NavigationService.navigatorKey.currentContext;
      if (context != null && context.mounted) {
        // Check if we should show the prompt
        final shouldShow = await ReviewPromptService.shouldShowReviewPrompt();
        if (shouldShow) {
          // Wait a bit more to ensure user is engaged
          await Future.delayed(const Duration(seconds: 2));
          if (context.mounted) {
            await ReviewPromptService.showReviewPromptIfAppropriate(context);
          }
        }
      }
    });
  }

  Future<void> _refreshData() async {
    setState(() {
      // Fetch featured products dynamically from server with force refresh
      _futureFeatured = widget.apiService.fetchFeaturedProducts(forceRefresh: true);
      // Fetch inventory summary from API
      _futureInventorySummary =
          widget.inventoryService.fetchInventory(pageSize: 1000, forceRefresh: true);
      // Refresh specials
      _futureSpecials = widget.apiService.fetchSpecials(forceRefresh: true);
    });
  }

  String _getErrorMessage(Object? error) {
    if (error == null) return 'Something went wrong';
    
    final errorString = error.toString().toLowerCase();
    if (errorString.contains('timeout')) {
      return 'Connection is slow';
    } else if (errorString.contains('socket') || errorString.contains('network')) {
      return 'Check your internet connection';
    } else if (errorString.contains('404') || errorString.contains('not found')) {
      return 'Service temporarily unavailable';
    } else {
      return 'Unable to load data';
    }
  }

  String _getErrorSubtitle(Object? error) {
    if (error == null) return 'Please try again';
    
    final errorString = error.toString().toLowerCase();
    if (errorString.contains('timeout')) {
      return 'The server is taking too long to respond';
    } else if (errorString.contains('socket') || errorString.contains('network')) {
      return 'Make sure you\'re connected to the internet';
    } else if (errorString.contains('404') || errorString.contains('not found')) {
      return 'We\'ll be back shortly';
    } else {
      return 'Pull down to refresh or tap try again';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: AppTheme.gradientBackground,
      child: RefreshIndicator(
        onRefresh: _refreshData,
        child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          children: [
            // Header with Logo and Welcome - Compact for portrait
            Container(
              padding: EdgeInsets.symmetric(
                horizontal: MediaQuery.of(context).size.width < 375 ? 16 : 20,
                vertical: 16,
              ),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    AppTheme.primaryColor,
                    AppTheme.primaryColor.withValues(alpha: 0.9),
                  ],
                ),
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(24),
                  bottomRight: Radius.circular(24),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Centered branding content - more compact
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 8.0),
                    child: Center(
                      child: Column(
                        children: [
                          // First line in gold with italic style
                          const Text(
                            'Crafted by Angel Stones',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: Color(0xFFFFD700),
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              fontStyle: FontStyle.italic,
                              letterSpacing: 0.3,
                            ),
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Elevating Granite, Preserving Memories',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: AppTheme.textSecondary,
                              fontSize: 13,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Discover our handcrafted monuments and timeless memorial stones.',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: AppTheme.textSecondary.withValues(alpha: 0.9),
                              fontSize: 12,
                              fontWeight: FontWeight.w400,
                              height: 1.3,
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
                  // Welcome section removed as requested
                ],
              ),
            ),
            
            // Convention Announcement Banner
            if (_showConventionBanner)
              Container(
                margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      const Color(0xFFFFD700), // Gold
                      const Color(0xFFFFA500), // Orange
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFFFFD700).withOpacity(0.3),
                      blurRadius: 8,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: () {
                      // Open convention website
                      final url = Uri.parse('https://mid-atlanticconvention.com/');
                      launchUrl(url, mode: LaunchMode.externalApplication);
                    },
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          // Icon
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.3),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.event,
                              color: Colors.white,
                              size: 28,
                            ),
                          ),
                          const SizedBox(width: 16),
                          // Text content
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Meet Us at Mid-Atlantic Convention!',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    letterSpacing: 0.3,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                const Text(
                                  'Visit Booth 46 & 54',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  'Tap to learn more',
                                  style: TextStyle(
                                    color: Colors.white.withOpacity(0.9),
                                    fontSize: 12,
                                    fontStyle: FontStyle.italic,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          // Close button
                          IconButton(
                            icon: const Icon(Icons.close, color: Colors.white, size: 20),
                            onPressed: () {
                              setState(() {
                                _showConventionBanner = false;
                              });
                            },
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            
            // Main Content - Ultra-tight spacing
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 10.0, vertical: 8.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Flyers Section - Ultra compact
                  Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Container(
                      decoration: AppTheme.cardGradient,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Padding(
                            padding: EdgeInsets.fromLTRB(12, 12, 12, 4),
                            child: Text(
                              'Current Flyers',
                              style: TextStyle(
                                fontSize: 17,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.textPrimary,
                                letterSpacing: 0.3,
                              ),
                            ),
                          ),
                          FlyerSection(
                            title: '',
                            future: _futureSpecials,
                          ),
                        ],
                      ),
                    ),
                  ),
                  
                  // Featured Products Section - Ultra compact
                  Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Container(
                      decoration: AppTheme.cardGradient,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Padding(
                            padding: EdgeInsets.fromLTRB(12, 12, 12, 4),
                            child: Text(
                              'Featured Products',
                              style: TextStyle(
                                fontSize: 17,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.textPrimary,
                              ),
                            ),
                          ),
                          ProductFolderSection(
                            title: '',
                            future: _futureFeatured,
                            apiService: widget.apiService,
                            directoryService: _directoryService,
                          ),
                        ],
                      ),
                    ),
                  ),
                  
                  // Latest Inventory Section
                  Card(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Container(
                      decoration: AppTheme.cardGradient,
                      padding: const EdgeInsets.all(16),
                      child: FutureBuilder<List<InventoryItem>>(
                        future: _futureInventorySummary,
                        builder: (context, snapshot) {
                          if (snapshot.connectionState == ConnectionState.waiting) {
                            return Padding(
                              padding: const EdgeInsets.symmetric(vertical: 24.0),
                              child: Column(
                                children: [
                                  const Text(
                                    'Latest Inventory',
                                    style: TextStyle(
                                      fontSize: 20,
                                      fontWeight: FontWeight.bold,
                                      color: AppTheme.textPrimary,
                                    ),
                                  ),
                                  const SizedBox(height: 16),
                                  // Skeleton loading instead of spinner
                                  SkeletonLoaders.inventoryItem(),
                                  SkeletonLoaders.inventoryItem(),
                                  SkeletonLoaders.inventoryItem(),
                                ],
                              ),
                            );
                          } else if (snapshot.hasError) {
                            return Padding(
                              padding: const EdgeInsets.symmetric(vertical: 24.0),
                              child: Column(
                                children: [
                                  const Text(
                                    'Latest Inventory',
                                    style: TextStyle(
                                      fontSize: 20,
                                      fontWeight: FontWeight.bold,
                                      color: AppTheme.textPrimary,
                                    ),
                                  ),
                                  const SizedBox(height: 24),
                                  Icon(Icons.error_outline, size: 40, color: Colors.red[300]),
                                  const SizedBox(height: 16),
                                  Text(
                                    _getErrorMessage(snapshot.error),
                                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    _getErrorSubtitle(snapshot.error),
                                    style: TextStyle(color: Colors.grey[400], fontSize: 14),
                                    textAlign: TextAlign.center,
                                  ),
                                  const SizedBox(height: 16),
                                  ElevatedButton.icon(
                                    onPressed: () {
                                      HapticFeedback.lightImpact();
                                      setState(() {
                                        _futureInventorySummary = widget.inventoryService.fetchInventory(pageSize: 1000);
                                      });
                                    },
                                    icon: const Icon(Icons.refresh),
                                    label: const Text('Try Again'),
                                  ),
                                ],
                              ),
                            );
                          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
                            return const Padding(
                              padding: EdgeInsets.symmetric(vertical: 24.0),
                              child: Column(
                                children: [
                                  Text(
                                    'Latest Inventory',
                                    style: TextStyle(
                                      fontSize: 20,
                                      fontWeight: FontWeight.bold,
                                      color: AppTheme.textPrimary,
                                    ),
                                  ),
                                  SizedBox(height: 24),
                                  Icon(Icons.inventory_2_outlined, size: 40, color: Colors.grey),
                                  SizedBox(height: 16),
                                  Text(
                                    'No inventory items available',
                                    style: TextStyle(fontSize: 16),
                                  ),
                                ],
                              ),
                            );
                          }
                          final randomItems =
                              List<InventoryItem>.from(snapshot.data!);
                          randomItems.shuffle();
                          final items =
                              randomItems.take(5).toList();
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Latest Inventory',
                                style: TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.bold,
                                  color: AppTheme.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 12),
                              ...List.generate(items.length, (index) {
                                final item = items[index];
                                return AnimatedOpacity(
                                  opacity: 1.0,
                                  duration: Duration(milliseconds: 300 + (index * 100)),
                                  curve: Curves.easeInOut,
                                  child: Container(
                                    margin: const EdgeInsets.symmetric(vertical: 6),
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(8),
                                      border: Border.all(color: Colors.grey.shade200),
                                      color: Colors.white.withValues(alpha: 0.6),
                                    ),
                                    child: Material(
                                      color: Colors.transparent,
                                      child: InkWell(
                                        borderRadius: BorderRadius.circular(8),
                                        onTap: () {
                                          // Navigate to inventory detail or show more info
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(content: Text('Selected: ${item.description}'))
                                          );
                                        },
                                        child: Padding(
                                          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 12),
                                          child: Row(
                                            children: [
                                              Container(
                                                width: 10,
                                                height: 10,
                                                margin: const EdgeInsets.only(right: 12),
                                                decoration: BoxDecoration(
                                                  color: AppTheme.accentColor,
                                                  shape: BoxShape.circle,
                                                  boxShadow: [
                                                    BoxShadow(
                                                      color: AppTheme.accentColor.withValues(alpha: 0.3),
                                                      blurRadius: 4,
                                                      spreadRadius: 1,
                                                    ),
                                                  ],
                                                ),
                                              ),
                                              Expanded(
                                                child: Text(
                                                  item.description.isNotEmpty 
                                                      ? item.description 
                                                      : 'Untitled Item',
                                                  style: const TextStyle(
                                                    color: AppTheme.textPrimary,
                                                    fontSize: 15,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                  maxLines: 1,
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                              Container(
                                                padding: const EdgeInsets.symmetric(
                                                  horizontal: 10,
                                                  vertical: 5,
                                                ),
                                                decoration: BoxDecoration(
                                                  gradient: LinearGradient(
                                                    colors: [
                                                      AppTheme.accentColor.withValues(alpha: 0.7),
                                                      AppTheme.accentColor,
                                                    ],
                                                    begin: Alignment.topLeft,
                                                    end: Alignment.bottomRight,
                                                  ),
                                                  borderRadius: BorderRadius.circular(12),
                                                  boxShadow: [
                                                    BoxShadow(
                                                      color: AppTheme.accentColor.withValues(alpha: 0.2),
                                                      blurRadius: 4,
                                                      offset: const Offset(0, 2),
                                                    ),
                                                  ],
                                                ),
                                                child: Text(
                                                  item.size,
                                                  style: const TextStyle(
                                                    color: Colors.white,
                                                    fontSize: 12,
                                                    fontWeight: FontWeight.w600,
                                                    letterSpacing: 0.3,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                );
                              }),
                              const SizedBox(height: 16),
                              // View Inventory Button
                              Container(
                                width: double.infinity,
                                margin: const EdgeInsets.only(top: 8),
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(12),
                                  boxShadow: [
                                    BoxShadow(
                                      color: AppTheme.accentColor.withValues(alpha: 0.3),
                                      blurRadius: 8,
                                      offset: const Offset(0, 3),
                                    ),
                                  ],
                                  gradient: LinearGradient(
                                    colors: [
                                      const Color(0xFFD4AF37), // Subtle gold color
                                      const Color(0xFFFFD700).withValues(alpha: 0.9), // Softer gold
                                    ],
                                    begin: Alignment.topLeft,
                                    end: Alignment.bottomRight,
                                  ),
                                ),
                                child: ElevatedButton(
                                  onPressed: widget.onViewFullInventory,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.transparent,
                                    foregroundColor: Colors.white,
                                    shadowColor: Colors.transparent,
                                    padding: const EdgeInsets.symmetric(vertical: 16),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                  ),
                                  child: const Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        'View Inventory',
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 16,
                                          letterSpacing: 0.5,
                                        ),
                                      ),
                                      SizedBox(width: 8),
                                      Icon(Icons.arrow_forward, size: 18),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          );
                        }
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    ),
  );
  }
}
