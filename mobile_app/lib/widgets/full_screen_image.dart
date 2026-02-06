import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import '../models/product_image.dart';
import '../services/firebase_service.dart';
import '../services/inventory_service.dart';
import '../services/image_share_service.dart';

class FullScreenImage extends StatefulWidget {
  final String imageUrl;
  final String tag;
  final List<ProductImage> galleryImages;
  final int initialIndex;

  const FullScreenImage({
    super.key,
    required this.imageUrl,
    required this.tag,
    required this.galleryImages,
    required this.initialIndex,
  });

  @override
  State<FullScreenImage> createState() => _FullScreenImageState();
}

class _FullScreenImageState extends State<FullScreenImage> {
  late PageController _pageController;
  late int _currentIndex;
  bool _showControls = true;

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
    _pageController = PageController(initialPage: _currentIndex);
    
    // Track image gallery view
    _trackAnalyticsEvent('image_gallery_opened', {
      'product_code': widget.galleryImages[_currentIndex].productCode,
      'total_images': widget.galleryImages.length,
      'initial_index': _currentIndex,
    });
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _toggleControls() {
    setState(() {
      _showControls = !_showControls;
    });
  }

  Future<void> _shareImage() async {
    final currentProduct = widget.galleryImages[_currentIndex];
    HapticFeedback.lightImpact();
    
    // Track share event
    _trackAnalyticsEvent('image_shared', {
      'product_code': currentProduct.productCode,
      'image_url': currentProduct.imageUrl,
      'gallery_position': _currentIndex,
    });

    final fileName = currentProduct.imageUrl.split('/').last.split('?').first;
    
    final success = await ImageShareService.shareImage(
      imageUrl: currentProduct.imageUrl,
      fileName: fileName,
      productName: currentProduct.productCode.isNotEmpty ? currentProduct.productCode : 'Design',
      productCode: currentProduct.productCode,
    );
    
    if (!success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to share image')),
      );
    }
  }

  void _trackAnalyticsEvent(String eventName, Map<String, Object> parameters) {
    try {
      FirebaseService.instance.logEvent(
        name: eventName,
        parameters: parameters,
      );
    } catch (e) {
      // Silently handle analytics errors
    }
  }

  Future<void> _navigateToInventory() async {
    final currentProduct = widget.galleryImages[_currentIndex];
    String productCode = currentProduct.productCode;
    // Normalize: uppercase and ensure dash is present (e.g., AG-356, AS-513)
    productCode = productCode.trim().toUpperCase();
    if (productCode.isNotEmpty && !productCode.contains('-') && productCode.length > 2) {
      // Insert dash after prefix if missing (e.g., AG356 -> AG-356)
      final prefix = productCode.substring(0, 2);
      final rest = productCode.substring(2);
      if ((prefix == 'AG' || prefix == 'AS') && int.tryParse(rest) != null) {
        productCode = '$prefix-$rest';
      }
    }
    if (productCode.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No product code available'),
          duration: Duration(seconds: 2),
        ),
      );
      return;
    }
    HapticFeedback.lightImpact();
    // Track navigation event
    _trackAnalyticsEvent('navigate_to_inventory_from_gallery', {
      'product_code': productCode,
      'gallery_position': _currentIndex,
    });
    // Show loading indicator
    if (!mounted) return;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(
        child: CircularProgressIndicator(),
      ),
    );
    try {
      // Search for the product in inventory
      debugPrint('🔍 Searching inventory for: $productCode');
      final inventoryService = InventoryService();
      final items = await inventoryService.fetchInventory(
        searchQuery: productCode,
      );
      debugPrint('📊 Search results: ${items.length} items found');
      if (items.isNotEmpty) {
        debugPrint('📦 First item: ${items.first.description}');
      }
      if (!mounted) return;
      Navigator.of(context).pop(); // Close loading dialog
      if (items.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('$productCode not found in inventory'),
            duration: const Duration(seconds: 2),
          ),
        );
        return;
      }
      // Close the image viewer first
      Navigator.of(context).pop();
      // Navigate directly to the item details screen
      context.push('/inventory-item-details', extra: items.first);
    } catch (e) {
      if (!mounted) return;
      Navigator.of(context).pop(); // Close loading dialog
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error searching inventory: ${e.toString()}'),
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentProduct = widget.galleryImages[_currentIndex];
    
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: _showControls
          ? AppBar(
              backgroundColor: Colors.black.withValues(alpha: 0.7),
              title: Text('${currentProduct.productCode.isNotEmpty ? currentProduct.productCode : 'No Code'} (${_currentIndex + 1}/${widget.galleryImages.length})'),
              leading: IconButton(
                icon: const Icon(Icons.arrow_back),
                onPressed: () => Navigator.pop(context),
              ),
              actions: [
                IconButton(
                  icon: const Icon(Icons.info_outline),
                  onPressed: _navigateToInventory,
                  tooltip: 'View Details',
                ),
                IconButton(
                  icon: const Icon(Icons.share),
                  onPressed: _shareImage,
                  tooltip: 'Share Image',
                ),
              ],
            )
          : null,
      body: GestureDetector(
        onTap: _toggleControls,
        onVerticalDragEnd: (details) {
          // Close image on swipe down
          if (details.primaryVelocity != null && details.primaryVelocity! > 300) {
            HapticFeedback.lightImpact();
            Navigator.pop(context);
          }
        },
        child: Stack(
          children: [
            // PageView for horizontal swiping between images
            PageView.builder(
              controller: _pageController,
              itemCount: widget.galleryImages.length,
              onPageChanged: (index) {
                HapticFeedback.selectionClick();
                
                // Track page change
                _trackAnalyticsEvent('image_gallery_swipe', {
                  'from_index': _currentIndex,
                  'to_index': index,
                  'product_code': widget.galleryImages[index].productCode,
                });
                
                setState(() {
                  _currentIndex = index;
                });
              },
              itemBuilder: (context, index) {
                final product = widget.galleryImages[index];
                return Hero(
                  tag: index == widget.initialIndex ? widget.tag : 'image_gallery_$index',
                  child: InteractiveViewer(
                    minScale: 0.5,
                    maxScale: 4.0,
                    child: Center(
                      child: Image.network(
                        product.imageUrl,
                        fit: BoxFit.contain,
                        loadingBuilder: (context, child, loadingProgress) {
                          if (loadingProgress == null) return child;
                          return Center(
                            child: CircularProgressIndicator(
                              value: loadingProgress.expectedTotalBytes != null
                                  ? loadingProgress.cumulativeBytesLoaded /
                                      (loadingProgress.expectedTotalBytes ?? 1)
                                  : null,
                            ),
                          );
                        },
                        errorBuilder: (context, error, stack) => const Center(
                          child: Icon(
                            Icons.broken_image,
                            size: 64,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
            // Product code overlay at bottom
            if (_showControls)
              Positioned(
                left: 0,
                right: 0,
                bottom: 16,
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 16),
                  padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.7),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    '${currentProduct.productCode.isNotEmpty ? currentProduct.productCode : 'No Code'} (${_currentIndex + 1}/${widget.galleryImages.length})',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 0.5,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
            // Navigation arrows (optional visual indicators)
            if (_showControls && widget.galleryImages.length > 1)
              Positioned(
                left: 0,
                right: 0,
                bottom: 80,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    // Left arrow
                    if (_currentIndex > 0)
                      Padding(
                        padding: const EdgeInsets.only(left: 16),
                        child: Container(
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.5),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: IconButton(
                            icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                            onPressed: () {
                              HapticFeedback.lightImpact();
                              _pageController.previousPage(
                                duration: const Duration(milliseconds: 300),
                                curve: Curves.easeInOut,
                              );
                            },
                          ),
                        ),
                      )
                    else
                      const SizedBox(width: 56),
                    // Right arrow
                    if (_currentIndex < widget.galleryImages.length - 1)
                      Padding(
                        padding: const EdgeInsets.only(right: 16),
                        child: Container(
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.5),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: IconButton(
                            icon: const Icon(Icons.arrow_forward_ios, color: Colors.white),
                            onPressed: () {
                              HapticFeedback.lightImpact();
                              _pageController.nextPage(
                                duration: const Duration(milliseconds: 300),
                                curve: Curves.easeInOut,
                              );
                            },
                          ),
                        ),
                      )
                    else
                      const SizedBox(width: 56),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}
