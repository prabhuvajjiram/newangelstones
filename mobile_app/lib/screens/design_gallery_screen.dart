import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../services/api_service.dart';
import '../widgets/full_screen_image.dart';
import '../utils/error_utils.dart';
import '../models/product_image.dart';

class DesignGalleryScreen extends StatelessWidget {
  final String categoryId;
  final String title;
  final ApiService apiService;

  const DesignGalleryScreen({super.key, required this.categoryId, required this.title, required this.apiService});

  @override
  Widget build(BuildContext context) {
    // Set preferred orientations and status bar style for better UX
    SystemChrome.setPreferredOrientations([
      DeviceOrientation.portraitUp,
      DeviceOrientation.portraitDown,
      DeviceOrientation.landscapeLeft,
      DeviceOrientation.landscapeRight,
    ]);
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
    );
    
    // Fetch product images with codes
    final futureProductImages = apiService.fetchProductImagesWithCodes(categoryId);
    
    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        elevation: 0, // Modern flat design
        systemOverlayStyle: SystemUiOverlayStyle.light,
      ),
      body: FutureBuilder<List<ProductImage>>(
        future: futureProductImages,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
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
          } else if (snapshot.hasError) {
            WidgetsBinding.instance.addPostFrameCallback((_) {
              showErrorSnackBar(context, 'Failed to load designs');
            });
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
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
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
          final productImages = snapshot.data!;
          return GridView.builder(
            padding: const EdgeInsets.all(12),
            physics: const BouncingScrollPhysics(), // Smoother scrolling
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: MediaQuery.of(context).orientation == Orientation.portrait ? 2 : 3,
              childAspectRatio: 0.75, // Taller cards for better product code visibility
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            itemCount: productImages.length,
            itemBuilder: (context, index) {
              final productImage = productImages[index];
              final imageUrls = productImages.map((img) => img.imageUrl).toList();
              
              // Implement staggered loading for better performance
              return AnimatedOpacity(
                duration: const Duration(milliseconds: 300),
                opacity: 1.0,
                curve: Curves.easeInOut,
                child: Card(
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
                              MaterialPageRoute(
                                builder: (_) => FullScreenImage(
                                  imageUrl: productImage.imageUrl,
                                  tag: 'image_$index',
                                  galleryImages: imageUrls,
                                  initialIndex: index,
                                ),
                              ),
                            );
                          },
                          child: Hero(
                            tag: 'image_$index',
                            child: Stack(
                              fit: StackFit.expand,
                              children: [
                                // Placeholder shimmer effect while loading
                                Container(color: Colors.grey.shade200),
                                // Actual image with fade-in effect
                                Image.network(
                                  productImage.imageUrl,
                                  fit: BoxFit.cover,
                                  frameBuilder: (context, child, frame, wasSynchronouslyLoaded) {
                                    if (wasSynchronouslyLoaded) return child;
                                    return AnimatedOpacity(
                                      opacity: frame == null ? 0 : 1,
                                      duration: const Duration(milliseconds: 300),
                                      curve: Curves.easeOut,
                                      child: child,
                                    );
                                  },
                                  errorBuilder: (context, error, stack) => const Center(
                                    child: Icon(Icons.broken_image, size: 40, color: Colors.grey),
                                  ),
                                  // Add caching for better performance
                                  cacheHeight: 300,
                                  cacheWidth: 300,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      // Display the product code with enhanced styling
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
                ),
              );
            },
          );
        },
      ),
    );
  }
}

