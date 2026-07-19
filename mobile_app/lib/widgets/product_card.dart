import 'package:flutter/material.dart';
import '../models/product.dart';
import '../utils/image_utils.dart';

class ProductCard extends StatelessWidget {
  final Product product;
  final VoidCallback? onTap;

  const ProductCard({
    super.key,
    required this.product,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: onTap != null,
      label: onTap == null ? product.name : 'View ${product.name}',
      excludeSemantics: true,
      child: Card(
        margin: EdgeInsets.zero,
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          canRequestFocus: onTap != null,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(
                child: ImageUtils.buildImage(
                  imageUrl: product.getImagePath(),
                  fit: BoxFit.cover,
                  errorWidget: const Icon(Icons.broken_image, size: 32),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 6.0,
                  vertical: 6.0,
                ),
                child: Text(
                  product.name,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    letterSpacing: 0.2,
                  ),
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
