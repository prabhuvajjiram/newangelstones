import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:go_router/go_router.dart';
import '../services/api_service.dart';
import '../services/system_ui_service.dart';
import '../widgets/full_screen_image.dart';
import '../models/product_image.dart';
import '../widgets/skeleton_loaders.dart';
import '../theme/app_theme.dart';

class DesignGalleryScreen extends StatefulWidget {
  final String categoryId;
  final String title;
  final ApiService apiService;

  const DesignGalleryScreen({super.key, required this.categoryId, required this.title, required this.apiService});

  @override
  State<DesignGalleryScreen> createState() => _DesignGalleryScreenState();
}

class _DesignGalleryScreenState extends State<DesignGalleryScreen> {
  List<ProductImage> _images = [];
  bool _isLoading = true;
  bool _hasError = false;

  @override
  void initState() {
    super.initState();
    SystemChrome.setPreferredOrientations([
      DeviceOrientation.portraitUp,
      DeviceOrientation.portraitDown,
      DeviceOrientation.landscapeLeft,
      DeviceOrientation.landscapeRight,
    ]);
    SystemUIService.instance.configureForScreen('gallery');
    _loadImages();
  }

  Future<void> _loadImages() async {
    // Step 1: show bundled assets instantly (no network needed)
    final bundled = widget.apiService.getBundledProductImages(widget.categoryId);
    if (bundled != null && bundled.isNotEmpty) {
      if (mounted) setState(() { _images = bundled; _isLoading = false; });
    }

    // Step 2: fetch from network / memory cache in background
    try {
      final networkImages = await widget.apiService.fetchProductImagesWithCodes(widget.categoryId);
      if (mounted && networkImages.isNotEmpty) {
        setState(() { _images = networkImages; _isLoading = false; _hasError = false; });
      }
    } catch (_) {
      if (mounted && _images.isEmpty) {
        setState(() { _isLoading = false; _hasError = true; });
      }
    }
  }

  /// Smart image widget: asset (instant) → network (with skeleton)
  Widget _buildImage(ProductImage productImage) {
    if (productImage.hasBundledAsset) {
      return Image.asset(
        productImage.assetPath!,
        fit: BoxFit.cover,
        errorBuilder: (_, __, ___) => _buildNetworkImage(productImage.imageUrl),
      );
    }
    return _buildNetworkImage(productImage.imageUrl);
  }

  Widget _buildNetworkImage(String url) {
    if (url.isEmpty) return const Center(child: Icon(Icons.broken_image, size: 40, color: Colors.grey));
    return CachedNetworkImage(
      imageUrl: url,
      fit: BoxFit.cover,
      memCacheHeight: 300,
      memCacheWidth: 300,
      placeholder: (context, url) => SkeletonLoaders.productCard(height: double.infinity),
      errorWidget: (context, error, stack) => const Center(
        child: Icon(Icons.broken_image, size: 40, color: Colors.grey),
      ),
      fadeInDuration: const Duration(milliseconds: 300),
      fadeOutDuration: const Duration(milliseconds: 100),
    );
  }

  @override
  Widget build(BuildContext context) {
    
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        elevation: 0,
        systemOverlayStyle: SystemUiOverlayStyle.light,
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
              currentIndex: 0,
              onTap: (index) => context.go('/?tab=$index'),
              elevation: 0,
              type: BottomNavigationBarType.fixed,
              backgroundColor: AppTheme.cardColor,
              selectedItemColor: AppTheme.accentColor,
              unselectedItemColor: AppTheme.textSecondary,
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
              iconSize: 22,
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
      body: _buildBody(context),
    );
  }

  Widget _buildBody(BuildContext context) {
    if (_isLoading && _images.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text('Loading designs...', style: TextStyle(fontWeight: FontWeight.w500)),
          ],
        ),
      );
    }
    if (_hasError && _images.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 48, color: Colors.red[300]),
            const SizedBox(height: 16),
            const Text('Unable to load designs', style: TextStyle(fontSize: 16)),
            const SizedBox(height: 8),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Go Back'),
            ),
          ],
        ),
      );
    }
    if (_images.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.image_not_supported, size: 48, color: Colors.grey[400]),
            const SizedBox(height: 16),
            const Text('No designs found in this category', style: TextStyle(fontSize: 16)),
            const SizedBox(height: 8),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Go Back'),
            ),
          ],
        ),
      );
    }
    return GridView.builder(
      padding: const EdgeInsets.all(12),
      physics: const BouncingScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: MediaQuery.of(context).orientation == Orientation.portrait ? 2 : 3,
        childAspectRatio: 0.75,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
      ),
      itemCount: _images.length,
      itemBuilder: (context, index) {
        final productImage = _images[index];
        return Card(
          clipBehavior: Clip.antiAlias,
          elevation: 2.0,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8.0)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute<void>(
                        builder: (_) => FullScreenImage(
                          imageUrl: productImage.getDisplayPath(),
                          tag: 'image_$index',
                          galleryImages: _images,
                          initialIndex: index,
                        ),
                      ),
                    );
                  },
                  child: Hero(
                    tag: 'image_$index',
                    child: _buildImage(productImage),
                  ),
                ),
              ),
              Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  border: Border(
                    top: BorderSide(color: Colors.grey.shade200, width: 1),
                  ),
                ),
                padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                child: Text(
                  productImage.productCode.isEmpty ? 'No Code' : productImage.productCode,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                    letterSpacing: 0.2,
                  ),
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

