import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../models/product.dart';
import '../screens/flyer_viewer_screen.dart';
import 'skeleton_loaders.dart';

class FlyerSection extends StatelessWidget {
  final String title;
  final Future<List<Product>> future;
  const FlyerSection({super.key, required this.title, required this.future});

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isSmallScreen = screenWidth < 375;
    final textScale = MediaQuery.textScalerOf(context).scale(1);
    final crossAxisCount = screenWidth >= 900
        ? 4
        : textScale > 1.4
            ? 2
            : MediaQuery.of(context).orientation == Orientation.portrait
                ? 3
                : 4;

    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: isSmallScreen ? 6.0 : 8.0,
        vertical: 6.0,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (title.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4.0),
              child: Semantics(
                header: true,
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: isSmallScreen ? 16 : 17,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.3,
                  ),
                ),
              ),
            ),
          if (title.isNotEmpty) const SizedBox(height: 10),
          FutureBuilder<List<Product>>(
            future: future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return SkeletonLoaders.productGrid(itemCount: 6);
              } else if (snapshot.hasError) {
                return Text('Error: ${snapshot.error}');
              } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
                return const Text('No flyers found');
              }
              final flyers = snapshot.data!;
              return GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  // 3 columns portrait, 4 landscape for flyers too
                  crossAxisCount: crossAxisCount,
                  childAspectRatio: 0.68 / textScale.clamp(1.0, 1.4),
                  crossAxisSpacing: 8,
                  mainAxisSpacing: 8,
                ),
                itemCount: flyers.length,
                itemBuilder: (context, index) {
                  final flyer = flyers[index];
                  return Semantics(
                    button: true,
                    label: 'Open flyer ${flyer.name}',
                    excludeSemantics: true,
                    child: Card(
                      margin: EdgeInsets.zero,
                      elevation: 2,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: InkWell(
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute<void>(
                              builder: (_) => FlyerViewerScreen(flyer: flyer),
                            ),
                          );
                        },
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Expanded(
                              child: CachedNetworkImage(
                                imageUrl: flyer.imageUrl,
                                fit: BoxFit.cover,
                                memCacheWidth: 300,
                                memCacheHeight: 300,
                                placeholder: (context, url) =>
                                    SkeletonLoaders.productCard(
                                        height: double.infinity),
                                errorWidget: (context, url, error) => Container(
                                  color: Colors.grey.shade200,
                                  child: const Icon(Icons.broken_image,
                                      size: 32, color: Colors.grey),
                                ),
                                fadeInDuration:
                                    const Duration(milliseconds: 200),
                                fadeOutDuration:
                                    const Duration(milliseconds: 100),
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 6.0,
                                vertical: 6.0,
                              ),
                              child: Text(
                                flyer.name,
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
                },
              );
            },
          ),
        ],
      ),
    );
  }
}
