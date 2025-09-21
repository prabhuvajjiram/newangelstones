import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher_string.dart';
import '../models/product.dart';
import '../widgets/pdf_viewer_widget.dart';

class FlyerViewerScreen extends StatefulWidget {
  final Product flyer;
  const FlyerViewerScreen({super.key, required this.flyer});

  @override
  State<FlyerViewerScreen> createState() => _FlyerViewerScreenState();
}

class _FlyerViewerScreenState extends State<FlyerViewerScreen> {
  bool _showAppBar = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: _showAppBar ? AppBar(
        title: Text(
          widget.flyer.name,
          style: const TextStyle(fontSize: 16),
          overflow: TextOverflow.ellipsis,
        ),
        toolbarHeight: 56, // Compact height
        elevation: 1,
        actions: [
          IconButton(
            icon: const Icon(Icons.fullscreen, size: 20),
            tooltip: 'Full Screen',
            onPressed: () {
              HapticFeedback.lightImpact();
              setState(() {
                _showAppBar = false;
              });
            },
          ),
          if (widget.flyer.pdfUrl != null)
            IconButton(
              icon: const Icon(Icons.open_in_new, size: 20),
              tooltip: 'Open in External App',
              onPressed: () {
                HapticFeedback.lightImpact();
                launchUrlString(widget.flyer.pdfUrl!);
              },
            ),
        ],
      ) : null,
      body: GestureDetector(
        onTap: () {
          if (!_showAppBar) {
            HapticFeedback.lightImpact();
            setState(() {
              _showAppBar = true;
            });
          }
        },
        child: widget.flyer.pdfUrl != null
            ? PdfViewerWidget(pdfUrl: widget.flyer.pdfUrl!)
            : InteractiveViewer(
                child: Image.network(
                  widget.flyer.imageUrl,
                  fit: BoxFit.contain,
                  errorBuilder: (context, error, stack) => const Center(
                    child: Icon(Icons.broken_image, size: 64),
                  ),
                ),
              ),
      ),
      floatingActionButton: widget.flyer.pdfUrl != null && _showAppBar
          ? FloatingActionButton.small(
              onPressed: () {
                HapticFeedback.lightImpact();
                launchUrlString(widget.flyer.pdfUrl!);
              },
              tooltip: 'Open in External App',
              child: const Icon(Icons.open_in_new, size: 20),
            )
          : null,
    );
  }
}

