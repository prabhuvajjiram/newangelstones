import 'package:flutter/material.dart';
import '../models/inventory_item.dart';

/// Fullscreen image viewer with stock details overlay
/// Mobile-first design with touch gestures
class ProductImageViewer extends StatefulWidget {
  final List<String> images;
  final InventoryItem item;
  final int initialIndex;

  const ProductImageViewer({
    super.key,
    required this.images,
    required this.item,
    this.initialIndex = 0,
  });

  @override
  State<ProductImageViewer> createState() => _ProductImageViewerState();
}

class _ProductImageViewerState extends State<ProductImageViewer> {
  late PageController _pageController;
  late int _currentIndex;
  bool _showDetails = true;

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
    _pageController = PageController(initialPage: widget.initialIndex);
    debugPrint('🖼️ ProductImageViewer initialized with ${widget.images.length} images');
    debugPrint('🖼️ _showDetails: $_showDetails');
    debugPrint('🖼️ Item: ${widget.item.description}');
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _toggleDetails() {
    setState(() {
      _showDetails = !_showDetails;
      debugPrint('🖼️ Details toggled: $_showDetails');
    });
  }

  @override
  Widget build(BuildContext context) {
    debugPrint('🖼️ Building ProductImageViewer, _showDetails: $_showDetails');
    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onVerticalDragEnd: (details) {
          // Swipe up = show details
          if (details.primaryVelocity! < -500) {
            if (!_showDetails) {
              _toggleDetails();
            }
          }
          // Swipe down = hide details or close viewer
          else if (details.primaryVelocity! > 500) {
            if (_showDetails) {
              _toggleDetails();
            } else {
              // Details already hidden, close viewer
              Navigator.of(context).pop();
            }
          }
        },
        child: Stack(
          children: [
            // Image gallery with horizontal swipe
            GestureDetector(
              onTap: _toggleDetails,
              child: PageView.builder(
              controller: _pageController,
              onPageChanged: (index) {
                setState(() {
                  _currentIndex = index;
                });
              },
              itemCount: widget.images.length,
              itemBuilder: (context, index) {
                return Center(
                  child: InteractiveViewer(
                    minScale: 0.5,
                    maxScale: 4.0,
                    child: Image.network(
                      widget.images[index],
                      fit: BoxFit.contain,
                      loadingBuilder: (context, child, loadingProgress) {
                        if (loadingProgress == null) return child;
                        return Center(
                          child: CircularProgressIndicator(
                            value: loadingProgress.expectedTotalBytes != null
                                ? loadingProgress.cumulativeBytesLoaded /
                                    loadingProgress.expectedTotalBytes!
                                : null,
                            color: Colors.white,
                          ),
                        );
                      },
                      errorBuilder: (context, error, stackTrace) {
                        return const Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.broken_image,
                                size: 64,
                                color: Colors.white54,
                              ),
                              SizedBox(height: 16),
                              Text(
                                'Failed to load image',
                                style: TextStyle(color: Colors.white54),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                );
              },
            ),
          ),

          // Top bar with close button and counter
          AnimatedOpacity(
            opacity: _showDetails ? 1.0 : 0.0,
            duration: const Duration(milliseconds: 200),
            child: SafeArea(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withOpacity(0.7),
                      Colors.transparent,
                    ],
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.close, color: Colors.white, size: 28),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                    if (widget.images.length > 1)
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.black.withOpacity(0.6),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Text(
                          '${_currentIndex + 1} / ${widget.images.length}',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    const SizedBox(width: 48), // Balance the close button
                  ],
                ),
              ),
            ),
          ),

          // Bottom details panel (collapsible)
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Builder(
              builder: (context) {
                debugPrint('🖼️ Building details panel, _showDetails: $_showDetails');
                debugPrint('🖼️ Transform Y: ${_showDetails ? 0 : 400}');
                return AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  curve: Curves.easeInOut,
                  transform: Matrix4.translationValues(
                    0,
                    _showDetails ? 0 : 400,
                    0,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1a1a1a),
                    border: Border(
                      top: BorderSide(
                        color: const Color(0xFFd4af37).withOpacity(0.3),
                        width: 1,
                      ),
                    ),
                  ),
                  child: _buildDetailsContent(),
                );
              },
            ),
          ),
        ],
        ),
      ),
    );
  }
  
  Widget _buildDetailsContent() {
    debugPrint('🖼️ Building details content');
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Drag handle
            Center(
              child: Container(
                width: 50,
                height: 5,
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                  color: const Color(0xFFd4af37),
                  borderRadius: BorderRadius.circular(3),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFFd4af37).withOpacity(0.3),
                      blurRadius: 4,
                      spreadRadius: 1,
                    ),
                  ],
                ),
              ),
            ),
            
            // Stock details header
            Row(
              children: [
                const Icon(
                  Icons.info_outline,
                  color: Color(0xFFd4af37),
                  size: 20,
                ),
                const SizedBox(width: 8),
                const Text(
                  'Stock Details',
                  style: TextStyle(
                    color: Color(0xFFd4af37),
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.5,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            
            // Product description
            Text(
              widget.item.description,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 15,
                fontWeight: FontWeight.w600,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 16),
            
            // Details grid
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.black.withOpacity(0.3),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Column(
                children: [
            
                  _buildDetailRow('Code', widget.item.code),
                  if (widget.item.designCode != null)
                    _buildDetailRow('Design', widget.item.designCode!),
                  _buildDetailRow('Quantity', '${widget.item.quantity} available'),
                  if (widget.item.location.isNotEmpty)
                    _buildDetailRow('Location', widget.item.location),
                  if (widget.item.color.isNotEmpty)
                    _buildDetailRow('Color', widget.item.color),
                  if (widget.item.size.isNotEmpty)
                    _buildDetailRow('Size', widget.item.size),
                ],
              ),
            ),
            
            const SizedBox(height: 12),
            
            // Hint text
            Center(
              child: Text(
                'Tap image to ${_showDetails ? 'hide' : 'show'} details',
                style: TextStyle(
                  color: const Color(0xFFd4af37).withOpacity(0.7),
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  Widget _buildOldContent() {
    return Container(
      // OLD CODE REMOVED
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 90,
            child: Text(
              '$label:',
              style: const TextStyle(
                color: Color(0xFFd4af37),
                fontSize: 13,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 13,
                fontWeight: FontWeight.w400,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
