import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../models/inventory_item.dart';
import '../services/unified_saved_items_service.dart';
import '../services/inventory_service.dart';
import '../services/product_image_service.dart';
import '../widgets/product_image_viewer.dart';
import '../state/saved_items_state.dart';
import '../state/cart_state.dart';
import '../screens/enhanced_cart_screen.dart';

class InventoryItemDetailsScreen extends StatefulWidget {
  final InventoryItem item;

  const InventoryItemDetailsScreen({
    super.key,
    required this.item,
  });

  @override
  State<InventoryItemDetailsScreen> createState() => _InventoryItemDetailsScreenState();
}

class _InventoryItemDetailsScreenState extends State<InventoryItemDetailsScreen> {
  int quantity = 1;
  bool isSaved = false;
  List<InventoryItem> stoneRecords = [];
  bool isLoadingDetails = false;
  List<String> productImages = [];
  bool isLoadingImages = false;
  
  @override
  void initState() {
    super.initState();
    _checkIfItemIsSaved();
    _loadDetailedInfo();
    _loadProductImages();
  }
  
  Future<void> _loadDetailedInfo() async {
    setState(() {
      isLoadingDetails = true;
    });
    
    final inventoryService = InventoryService();
    final records = await inventoryService.getItemDetailedRecords(widget.item.code);
    
    if (mounted) {
      setState(() {
        stoneRecords = records;
        isLoadingDetails = false;
      });
    }
  }
  
  Future<void> _loadProductImages() async {
    final designCode = widget.item.designCode;
    debugPrint('📸 Item: ${widget.item.description}');
    debugPrint('📸 Design field: ${widget.item.design}');
    debugPrint('📸 Extracted design code: $designCode');
    
    if (designCode == null) {
      debugPrint('📸 No design code found for item');
      return;
    }
    
    setState(() {
      isLoadingImages = true;
    });
    
    final images = await ProductImageService.searchProductImages(designCode);
    debugPrint('📸 Found ${images.length} images for $designCode');
    
    if (mounted) {
      setState(() {
        productImages = images;
        isLoadingImages = false;
      });
    }
  }
  
  void _checkIfItemIsSaved() {
    final savedItemsState = Provider.of<SavedItemsState>(context, listen: false);
    setState(() {
      isSaved = savedItemsState.hasItem(widget.item.code);
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final savedItemsState = Provider.of<SavedItemsState>(context);
    final saved = savedItemsState.hasItem(widget.item.code);
    if (saved != isSaved) {
      setState(() {
        isSaved = saved;
      });
    }
  }
  
  Future<void> _toggleSaveItem() async {
    // Convert InventoryItem to Map<String, dynamic>
    final itemMap = {
      'id': widget.item.code,
      'code': widget.item.code,
      'description': widget.item.description,
      'color': widget.item.color,
      'type': widget.item.type,
      'size': widget.item.size,
      'quantity': widget.item.quantity,
      'location': widget.item.location,
      'design': widget.item.design,
      'finish': widget.item.finish,
      'weight': widget.item.weight,
      'productId': widget.item.productId,
    };
    
    if (isSaved) {
      await UnifiedSavedItemsService.removeItem(context, widget.item.code);
    } else {
      await UnifiedSavedItemsService.saveItem(context, itemMap);
    }
    
    if (mounted) {
      setState(() {
        isSaved = !isSaved;
      });
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isSaved ? 'Item saved!' : 'Item removed from saved items'),
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }
  
  void _addToCart() {
    // Create a cart item with the selected quantity
    final cartItem = {
      'id': widget.item.code,  // 'id' is used for identifying unique items in cart
      'code': widget.item.code,
      'description': widget.item.description,
      'quantity': quantity,
      'color': widget.item.color,
      'size': widget.item.size,
      'type': widget.item.type,
      'price': 0.0,  // Add default price (can be updated later)
      'location': widget.item.location,
      'design': widget.item.design,
      'finish': widget.item.finish,
    };
    
    // Add to cart using the CartState provider
    final cartState = Provider.of<CartState>(context, listen: false);
    
    // If item exists, update quantity, otherwise add new item
    final existingIndex = cartState.items.indexWhere((item) => item['id'] == cartItem['id']);
    
    if (existingIndex >= 0) {
      // Update existing item quantity
      final currentQuantity = cartState.items[existingIndex]['quantity'] as int;
      cartState.updateQuantity(cartState.items[existingIndex], currentQuantity + quantity);
    } else {
      // Add as new item with specified quantity
      cartState.addItemWithQuantity(cartItem, quantity);
    }
    
    // Show success message
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('${widget.item.description} added to cart'),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 2),
      ),
    );
    
    // Navigate to cart screen and ensure proper context
    try {
      // Use Navigator.of(context) to ensure proper context handling
      // This will maintain the widget tree and prevent Material widget errors
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (context) => const EnhancedCartScreen(),
        ),
      );
    } catch (e) {
      debugPrint('Error navigating to cart: $e');
      // Fallback to GoRouter if Navigator fails
      try {
        GoRouter.of(context).go('/cart');
      } catch (routerError) {
        debugPrint('Error with GoRouter navigation: $routerError');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final screenHeight = MediaQuery.of(context).size.height;
    final screenWidth = MediaQuery.of(context).size.width;
    
    // Responsive sizing based on screen dimensions
    final bool isSmallScreen = screenHeight < 700 || screenWidth < 400;
    final double toolbarHeight = isSmallScreen ? 48.0 : 56.0;
    final double titleFontSize = isSmallScreen ? 14.0 : 16.0;
    final int maxTitleLength = isSmallScreen ? 20 : 30;
    
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: toolbarHeight,
        title: Text(
          widget.item.description.isNotEmpty ? 
            (widget.item.description.length > maxTitleLength ? 
              '${widget.item.description.substring(0, maxTitleLength)}...' : 
              widget.item.description) : 
            'Item Details',
          style: TextStyle(fontSize: titleFontSize),
        ),
        actions: [
          IconButton(
            icon: Icon(
              isSaved ? Icons.bookmark : Icons.bookmark_border,
              color: isSaved ? Colors.amber : null,
            ),
            onPressed: _toggleSaveItem,
            tooltip: isSaved ? 'Remove from saved items' : 'Save item',
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Item title and code
            Text(
              widget.item.description,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
          //  const SizedBox(height: 8),
           // Text(
             // 'Product Code: ${widget.item.code}',
             // style: Theme.of(context).textTheme.titleMedium,
           // ),
            const SizedBox(height: 24),
            
            // Basic information section
            _buildSectionHeader(context, 'Basic Information'),
            const SizedBox(height: 8),
            _buildInfoRow(context, 'Product Code', widget.item.code),
            _buildInfoRow(context, 'Product ID', widget.item.productId > 0 ? widget.item.productId.toString() : 'N/A'),
            _buildInfoRow(context, 'Type', widget.item.type),
            _buildInfoRow(context, 'Color', widget.item.color),
            _buildInfoRow(context, 'Design', widget.item.design),
            _buildInfoRow(context, 'Finish', widget.item.finish),
            
            const SizedBox(height: 24),
            // Dimensions section
            _buildSectionHeader(context, 'Dimensions'),
            const SizedBox(height: 8),
            _buildInfoRow(context, 'Size', widget.item.size),
            _buildInfoRow(context, 'Length', widget.item.lengthInInches.isNotEmpty ? widget.item.lengthInInches : 'Not specified'),
            _buildInfoRow(context, 'Height', widget.item.heightInInches.isNotEmpty ? widget.item.heightInInches : 'Not specified'),
            _buildInfoRow(context, 'Width', widget.item.widthInInches.isNotEmpty ? widget.item.widthInInches : 'Not specified'),
            _buildInfoRow(context, 'Weight', widget.item.weight.isNotEmpty ? '${widget.item.weight} lbs' : stoneRecords.isNotEmpty && stoneRecords[0].weight.isNotEmpty ? '${stoneRecords[0].weight} lbs' : 'Not specified'),
            
            const SizedBox(height: 24),
            // Inventory information section
            _buildSectionHeader(context, 'Inventory Information'),
            const SizedBox(height: 8),
            _buildInfoRow(context, 'Total Available', widget.item.quantity.toString()),
            _buildInfoRow(context, 'Location', widget.item.location),
            _buildInfoRow(context, 'Status', stoneRecords.isNotEmpty && stoneRecords[0].status.isNotEmpty ? stoneRecords[0].status : 'In-Stock'),
            if (stoneRecords.isNotEmpty && stoneRecords[0].sublocation.isNotEmpty)
              _buildInfoRow(context, 'Sublocation', stoneRecords[0].sublocation),
            
            // Show individual stone records with Container and Crate info
            if (stoneRecords.isNotEmpty) ...[
              const SizedBox(height: 24),
              _buildSectionHeader(context, 'Individual Stones (${stoneRecords.length})'),
              const SizedBox(height: 8),
              ...stoneRecords.asMap().entries.map((entry) {
                final index = entry.key;
                final stone = entry.value;
                return Padding(
                  padding: const EdgeInsets.only(bottom: 16.0),
                  child: Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Stone #${index + 1}',
                          style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.bold,
                            color: Theme.of(context).primaryColor,
                          ),
                        ),
                        const SizedBox(height: 8),
                        if (stone.container.isNotEmpty)
                          _buildInfoRow(context, 'Container', stone.container),
                        if (stone.crateNo.isNotEmpty)
                          _buildInfoRow(context, 'Crate Number', stone.crateNo),
                        if (stone.stockId > 0)
                          _buildInfoRow(context, 'Stock ID', stone.stockId.toString()),
                        if (stone.hasComments && stone.comments.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(top: 8.0),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Notes:',
                                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  stone.comments,
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),
                );
              }),
            ],
            
            // Product Images Section
            if (widget.item.designCode != null) ...[
              const SizedBox(height: 24),
              _buildSectionHeader(context, 'Product Images'),
              const SizedBox(height: 8),
              if (isLoadingImages)
                const Center(
                  child: Padding(
                    padding: EdgeInsets.all(16.0),
                    child: CircularProgressIndicator(),
                  ),
                )
              else if (productImages.isEmpty)
                Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Text(
                    'No images available for ${widget.item.designCode}',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Colors.grey,
                    ),
                  ),
                )
              else
                SizedBox(
                  height: 200,
                  child: ListView.builder(
                    scrollDirection: Axis.horizontal,
                    itemCount: productImages.length,
                    itemBuilder: (context, index) {
                      final image = productImages[index];
                      return Padding(
                        padding: const EdgeInsets.only(right: 12.0),
                        child: GestureDetector(
                          onTap: () {
                            // Open fullscreen viewer with stock details
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => ProductImageViewer(
                                  images: productImages,
                                  item: widget.item,
                                  initialIndex: index,
                                ),
                              ),
                            );
                          },
                          child: Container(
                            width: 200,
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.grey.shade300),
                            ),
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(8),
                              child: Image.network(
                                image,
                                fit: BoxFit.cover,
                                errorBuilder: (context, error, stackTrace) {
                                  return Container(
                                    color: Colors.grey.shade200,
                                    child: const Center(
                                      child: Icon(
                                        Icons.broken_image,
                                        size: 48,
                                        color: Colors.grey,
                                      ),
                                    ),
                                  );
                                },
                                loadingBuilder: (context, child, loadingProgress) {
                                  if (loadingProgress == null) return child;
                                  return Center(
                                    child: CircularProgressIndicator(
                                      value: loadingProgress.expectedTotalBytes != null
                                          ? loadingProgress.cumulativeBytesLoaded /
                                              loadingProgress.expectedTotalBytes!
                                          : null,
                                    ),
                                  );
                                },
                              ),
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
            ],
            
            // Show loading indicator while fetching details
            if (isLoadingDetails) ...[
              const SizedBox(height: 24),
              const Center(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                    SizedBox(width: 12),
                    Text('Loading detailed information...'),
                  ],
                ),
              ),
            ],
            
            const SizedBox(height: 32),
            // Quantity selector and Add to Cart button
            Row(
              children: [
                Text(
                  'Quantity:',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(width: 16),
                _buildQuantitySelector(context),
              ],
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _addToCart,
                icon: const Icon(Icons.shopping_cart),
                label: const Text('ADD TO CART'),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  backgroundColor: Theme.of(context).primaryColor,
                  foregroundColor: Colors.white,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  Widget _buildSectionHeader(BuildContext context, String title) {
    return Text(
      title,
      style: Theme.of(context).textTheme.titleLarge?.copyWith(
        fontWeight: FontWeight.bold,
      ),
    );
  }
  
  Widget _buildInfoRow(BuildContext context, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              '$label:',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildQuantitySelector(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey.shade300),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            icon: const Icon(Icons.remove),
            onPressed: quantity > 1 ? () {
              setState(() {
                quantity--;
              });
            } : null,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            iconSize: 20,
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Text(
              quantity.toString(),
              style: Theme.of(context).textTheme.titleMedium,
            ),
          ),
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () {
              setState(() {
                quantity++;
              });
            },
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            iconSize: 20,
          ),
        ],
      ),
    );
  }
}
