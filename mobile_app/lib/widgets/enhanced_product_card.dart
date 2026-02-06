import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/saved_items_service.dart';
import '../services/image_share_service.dart';
import 'skeleton_loaders.dart';

class EnhancedProductCard extends StatefulWidget {
  final Map<String, dynamic> product;
  final VoidCallback? onTap;
  final bool showQuickView;
  final bool showSaveForLater;

  const EnhancedProductCard({
    super.key,
    required this.product,
    this.onTap,
    this.showQuickView = true,
    this.showSaveForLater = true,
  });

  @override
  State<EnhancedProductCard> createState() => _EnhancedProductCardState();
}

class _EnhancedProductCardState extends State<EnhancedProductCard> {
  bool _isSaved = false;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _checkIfSaved();
  }

  Future<void> _checkIfSaved() async {
    if (!widget.showSaveForLater) return;
    
    setState(() => _isLoading = true);
    try {
      final isSaved = await SavedItemsService.isItemSaved(widget.product['id']?.toString() ?? '');
      if (mounted) {
        setState(() => _isSaved = isSaved);
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _toggleSave() async {
    if (_isLoading) return;
    
    setState(() => _isLoading = true);
    try {
      if (_isSaved) {
        await SavedItemsService.removeItem(widget.product['id']?.toString() ?? '');
      } else {
        await SavedItemsService.saveItem(widget.product);
      }
      
      if (mounted) {
        setState(() => _isSaved = !_isSaved);
        
        // Show feedback
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_isSaved ? 'Saved for later' : 'Removed from saved'),
            duration: const Duration(seconds: 2),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to update saved items')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _shareProduct() async {
    try {
      final imageUrl = widget.product['imageUrl']?.toString() ?? '';
      final productName = widget.product['name']?.toString() ?? '';
      final productCode = widget.product['code']?.toString();
      
      if (imageUrl.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No image to share')),
        );
        return;
      }
      
      // Extract filename from URL
      final fileName = imageUrl.split('/').last.split('?').first;
      
      final success = await ImageShareService.shareImage(
        imageUrl: imageUrl,
        fileName: fileName,
        productName: productName,
        productCode: productCode,
      );
      
      if (!success && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to share image')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error sharing image')),
        );
      }
    }
  }

  void _showQuickView() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.8,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (_, controller) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              Expanded(
                child: SingleChildScrollView(
                  controller: controller,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Product Image
                      Container(
                        height: 200,
                        width: double.infinity,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(12),
                          image: DecorationImage(
                            image: NetworkImage(widget.product['imageUrl']?.toString() ?? ''),
                            fit: BoxFit.cover,
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      
                      // Product Name
                      Text(
                        widget.product['name']?.toString() ?? 'Product Name',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      
                      // Product Code
                      if (widget.product['code'] != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 4.0, bottom: 12),
                          child: Text(
                            'Code: ${widget.product['code']?.toString() ?? ''}',
                            style: TextStyle(
                              color: Colors.grey[600],
                              fontSize: 14,
                            ),
                          ),
                        ),
                      
                      // Description
                      if (widget.product['description'] != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 16.0),
                          child: Text(
                            widget.product['description']?.toString() ?? '',
                            style: const TextStyle(fontSize: 15, height: 1.5),
                          ),
                        ),
                      
                      // Specifications
                      if (widget.product['specs'] != null && 
                          widget.product['specs'] is Map)
                        ..._buildSpecs(widget.product['specs'] as Map<String, dynamic>? ?? {}),
                      
                      const SizedBox(height: 24),
                    ],
                  ),
                ),
              ),
              
              // Action Buttons
              Padding(
                padding: const EdgeInsets.only(top: 16.0),
                child: Row(
                  children: [
                    // Save for Later Button
                    if (widget.showSaveForLater)
                      IconButton(
                        icon: Icon(
                          _isSaved ? Icons.bookmark : Icons.bookmark_border,
                          color: _isSaved ? Theme.of(context).primaryColor : null,
                        ),
                        onPressed: _toggleSave,
                        tooltip: 'Save for later',
                      ),
                    
                    // Share Button
                    IconButton(
                      icon: const Icon(Icons.share_outlined),
                      onPressed: () => _shareProduct(),
                      tooltip: 'Share image',
                    ),
                    
                    // View Full Details Button
                    Expanded(
                      child: ElevatedButton(
                        onPressed: () {
                          Navigator.pop(context); // Close quick view
                          if (widget.onTap != null) {
                            widget.onTap!();
                          }
                        },
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 15),
                        ),
                        child: const Text('View Full Details'),
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

  List<Widget> _buildSpecs(Map<String, dynamic> specs) {
    return [
      const Text(
        'Specifications:',
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: Colors.black87,
        ),
      ),
      const SizedBox(height: 8),
      ...specs.entries.map((entry) => Padding(
            padding: const EdgeInsets.symmetric(vertical: 4.0),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '• ${entry.key}: ',
                  style: const TextStyle(fontWeight: FontWeight.w500),
                ),
                Expanded(
                  child: Text(
                    entry.value.toString(),
                    style: const TextStyle(color: Colors.black87),
                  ),
                ),
              ],
            ),
          )),
      const SizedBox(height: 16),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: widget.onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Product Image with Quick View Button
            Stack(
              children: [
                // Product Image
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(12),
                  ),
                  child: AspectRatio(
                    aspectRatio: 1,
                    child: widget.product['imageUrl']?.toString().isNotEmpty == true
                        ? CachedNetworkImage(
                            imageUrl: widget.product['imageUrl']?.toString() ?? '',
                            fit: BoxFit.cover,
                            memCacheWidth: 300,
                            memCacheHeight: 300,
                            placeholder: (context, url) => SkeletonLoaders.productCard(height: double.infinity),
                            errorWidget: (context, url, error) => 
                                const Center(child: Icon(Icons.image_not_supported)),
                            fadeInDuration: const Duration(milliseconds: 200),
                            fadeOutDuration: const Duration(milliseconds: 100),
                          )
                        : const Center(child: Icon(Icons.photo_library)),
                  ),
                ),
                
                // Quick View Button
                if (widget.showQuickView)
                  Positioned(
                    top: 8,
                    right: 8,
                    child: Material(
                      color: Colors.white.withValues(alpha: 0.9),
                      borderRadius: BorderRadius.circular(20),
                      child: IconButton(
                        icon: const Icon(Icons.visibility_outlined, size: 20),
                        onPressed: _showQuickView,
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                        tooltip: 'Quick View',
                      ),
                    ),
                  ),
                
                // Save for Later Button
                if (widget.showSaveForLater)
                  Positioned(
                    top: 8,
                    left: 8,
                    child: Material(
                      color: Colors.white.withValues(alpha: 0.9),
                      borderRadius: BorderRadius.circular(20),
                      child: IconButton(
                        icon: Icon(
                          _isSaved ? Icons.bookmark : Icons.bookmark_border,
                          size: 20,
                          color: _isSaved ? Theme.of(context).primaryColor : null,
                        ),
                        onPressed: _isLoading ? null : _toggleSave,
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                        tooltip: _isSaved ? 'Saved' : 'Save for later',
                      ),
                    ),
                  ),
              ],
            ),
            
            // Product Info
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Product Name
                  Text(
                    widget.product['name']?.toString() ?? 'Product Name',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 15,
                    ),
                  ),
                  
                  // Product Code
                  if (widget.product['code'] != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 4.0),
                      child: Text(
                        'Code: ${widget.product['code']?.toString() ?? ''}',
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 12,
                        ),
                      ),
                    ),
                  
                  // Price if available
                  if (widget.product['price'] != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 4.0),
                      child: Text(
                        '\$${widget.product['price']?.toString() ?? '0'}',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.green,
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
