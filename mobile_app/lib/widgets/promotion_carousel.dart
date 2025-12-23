import 'dart:async';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/promotion.dart';
import '../theme/app_theme.dart';

class PromotionCarousel extends StatefulWidget {
  final List<Promotion> promotions;
  final VoidCallback onClose;

  const PromotionCarousel({
    super.key,
    required this.promotions,
    required this.onClose,
  });

  @override
  State<PromotionCarousel> createState() => _PromotionCarouselState();
}

class _PromotionCarouselState extends State<PromotionCarousel> {
  late PageController _pageController;
  int _currentPage = 0;
  Timer? _autoRotateTimer;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
    
    // Start auto-rotation if multiple promotions
    if (widget.promotions.length > 1) {
      _startAutoRotate();
    }
  }

  @override
  void dispose() {
    _autoRotateTimer?.cancel();
    _pageController.dispose();
    super.dispose();
  }

  void _startAutoRotate() {
    _autoRotateTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      if (_pageController.hasClients) {
        final nextPage = (_currentPage + 1) % widget.promotions.length;
        _pageController.animateToPage(
          nextPage,
          duration: const Duration(milliseconds: 400),
          curve: Curves.easeInOut,
        );
      }
    });
  }

  void _handlePromotionTap(Promotion promotion) async {
    if (promotion.type == 'event' && promotion.linkUrl != null) {
      // Open external URL for events
      final url = Uri.parse(promotion.linkUrl!);
      try {
        await launchUrl(url, mode: LaunchMode.externalApplication);
      } catch (e) {
        debugPrint('⚠️ Error launching URL: $e');
      }
    } else if (promotion.type == 'product') {
      // Open email for product inquiries
      final emailUrl = Uri.parse(
        'mailto:sales@theangelstones.com?subject=Inquiry about ${promotion.title}',
      );
      try {
        await launchUrl(emailUrl);
      } catch (e) {
        debugPrint('⚠️ Error launching email: $e');
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Please email sales@theangelstones.com for inquiries'),
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.promotions.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Stack(
          children: [
            // Carousel
            SizedBox(
              height: 200,
              child: PageView.builder(
                controller: _pageController,
                onPageChanged: (index) {
                  setState(() {
                    _currentPage = index;
                  });
                },
                itemCount: widget.promotions.length,
                itemBuilder: (context, index) {
                  final promotion = widget.promotions[index];
                  return GestureDetector(
                    onTap: () => _handlePromotionTap(promotion),
                    child: CachedNetworkImage(
                      imageUrl: promotion.imageUrl,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(
                        color: AppTheme.cardColor,
                        child: const Center(
                          child: CircularProgressIndicator(
                            color: AppTheme.accentColor,
                          ),
                        ),
                      ),
                      errorWidget: (context, url, error) => Container(
                        color: AppTheme.cardColor,
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.image_not_supported,
                              color: AppTheme.textSecondary,
                              size: 48,
                            ),
                            const SizedBox(height: 8),
                            Text(
                              promotion.title,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                              textAlign: TextAlign.center,
                            ),
                            if (promotion.subtitle != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                promotion.subtitle!,
                                style: const TextStyle(
                                  color: AppTheme.textSecondary,
                                  fontSize: 14,
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),

            // Dots indicator (if multiple promotions)
            if (widget.promotions.length > 1)
              Positioned(
                bottom: 12,
                left: 0,
                right: 0,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(
                    widget.promotions.length,
                    (index) => Container(
                      margin: const EdgeInsets.symmetric(horizontal: 4),
                      width: 8,
                      height: 8,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: _currentPage == index
                            ? Colors.white
                            : Colors.white.withOpacity(0.4),
                      ),
                    ),
                  ),
                ),
              ),

            // Close button
            Positioned(
              top: 8,
              right: 8,
              child: Material(
                color: Colors.black.withOpacity(0.5),
                shape: const CircleBorder(),
                child: InkWell(
                  onTap: widget.onClose,
                  customBorder: const CircleBorder(),
                  child: const Padding(
                    padding: EdgeInsets.all(8),
                    child: Icon(
                      Icons.close,
                      color: Colors.white,
                      size: 20,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
