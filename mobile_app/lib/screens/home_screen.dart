import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../widgets/flyer_section.dart';
import '../widgets/product_folder_section.dart';
import '../services/directory_service.dart';
import '../models/inventory_item.dart';
import '../services/inventory_service.dart';
import '../theme/app_theme.dart';

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

  @override
  void initState() {
    super.initState();
    _futureFeatured =
        widget.apiService.loadLocalProducts('assets/featured_products.json');
    _futureInventorySummary =
        widget.inventoryService.fetchInventory(pageSize: 3);
    _futureSpecials =
        widget.apiService.loadLocalProducts('assets/specials.json');
    _directoryService = widget.directoryService;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: AppTheme.gradientBackground,
      child: SingleChildScrollView(
        child: Column(
          children: [
            // Header with Logo and Welcome
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    AppTheme.primaryColor,
                    AppTheme.primaryColor.withOpacity(0.9),
                  ],
                ),
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(30),
                  bottomRight: Radius.circular(30),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Logo and App Name
                  Row(
                    children: [
                      Container(
                        width: 60,
                        height: 60,
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(30),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.2),
                              blurRadius: 8,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(26),
                          child: Image.asset(
                            'assets/logo.png',
                            fit: BoxFit.contain,
                            errorBuilder: (context, error, stackTrace) => const Icon(
                              Icons.star,
                              color: AppTheme.accentColor,
                              size: 32,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Angel Granites',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              fontFamily: 'Montserrat',
                            ),
                          ),
                          Text(
                            'Eternal Memories in Stone',
                            style: TextStyle(
                              color: AppTheme.textSecondary,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  // Welcome Message
                  const Text(
                    'Welcome to Angel Granites',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      fontFamily: 'Montserrat',
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Discover our beautiful collection of memorial stones and monuments',
                    style: TextStyle(
                      color: AppTheme.textSecondary,
                      fontSize: 16,
                      height: 1.4,
                    ),
                  ),
                ],
              ),
            ),
            
            // Main Content
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Flyers Section
                  Card(
                    margin: const EdgeInsets.only(bottom: 24),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Container(
                      decoration: AppTheme.cardGradient,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Padding(
                            padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                            child: Text(
                              'Current Flyers',
                              style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.textPrimary,
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
                  
                  // Featured Products Section
                  Card(
                    margin: const EdgeInsets.only(bottom: 24),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Container(
                      decoration: AppTheme.cardGradient,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Padding(
                            padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                            child: Text(
                              'Featured Products',
                              style: TextStyle(
                                fontSize: 20,
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
                                  CircularProgressIndicator(),
                                  SizedBox(height: 16),
                                  Text(
                                    'Loading inventory items...',
                                    style: TextStyle(color: AppTheme.textSecondary),
                                  ),
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
                                    'Failed to load inventory',
                                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    snapshot.error.toString(),
                                    style: TextStyle(color: Colors.red[300], fontSize: 14),
                                    textAlign: TextAlign.center,
                                  ),
                                  const SizedBox(height: 16),
                                  ElevatedButton(
                                    onPressed: () {
                                      setState(() {
                                        _futureInventorySummary = widget.inventoryService.fetchInventory(pageSize: 3);
                                      });
                                    },
                                    child: const Text('Retry'),
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
                          final items = snapshot.data!;
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
                                      color: Colors.white.withOpacity(0.6),
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
                                                      color: AppTheme.accentColor.withOpacity(0.3),
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
                                                      AppTheme.accentColor.withOpacity(0.7),
                                                      AppTheme.accentColor,
                                                    ],
                                                    begin: Alignment.topLeft,
                                                    end: Alignment.bottomRight,
                                                  ),
                                                  borderRadius: BorderRadius.circular(12),
                                                  boxShadow: [
                                                    BoxShadow(
                                                      color: AppTheme.accentColor.withOpacity(0.2),
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
                              // View Full Inventory Button
                              Container(
                                width: double.infinity,
                                margin: const EdgeInsets.only(top: 8),
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(12),
                                  boxShadow: [
                                    BoxShadow(
                                      color: AppTheme.accentColor.withOpacity(0.3),
                                      blurRadius: 8,
                                      offset: const Offset(0, 3),
                                    ),
                                  ],
                                  gradient: LinearGradient(
                                    colors: [
                                      AppTheme.accentColor,
                                      AppTheme.accentColor.withBlue(AppTheme.accentColor.blue + 20),
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
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: const [
                                      Text(
                                        'View Full Inventory',
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
    );
  }
}
