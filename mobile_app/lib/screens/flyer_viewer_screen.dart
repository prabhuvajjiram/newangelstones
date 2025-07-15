import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher_string.dart';
import '../models/product.dart';

class FlyerViewerScreen extends StatelessWidget {
  final Product flyer;
  const FlyerViewerScreen({super.key, required this.flyer});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(flyer.name)),
      body: Column(
        children: [
          Expanded(
            child: InteractiveViewer(
              child: Image.network(
                flyer.imageUrl,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stack) => const Center(child: Icon(Icons.broken_image)),
              ),
            ),
          ),
          if (flyer.pdfUrl != null)
            Padding(
              padding: const EdgeInsets.all(8.0),
              child: ElevatedButton(
                onPressed: () => launchUrlString(flyer.pdfUrl!),
                child: const Text('Open PDF'),
              ),
            ),
        ],
      ),
    );
  }
}

