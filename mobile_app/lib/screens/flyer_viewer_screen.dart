import 'package:flutter/material.dart';
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
  bool _showPdfViewer = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.flyer.name),
        actions: [
          if (widget.flyer.pdfUrl != null && _showPdfViewer)
            IconButton(
              icon: const Icon(Icons.open_in_new),
              tooltip: 'Open in External App',
              onPressed: () => launchUrlString(widget.flyer.pdfUrl!),
            ),
        ],
      ),
      body: _showPdfViewer && widget.flyer.pdfUrl != null
          ? PdfViewerWidget(pdfUrl: widget.flyer.pdfUrl!)
          : Column(
              children: [
                Expanded(
                  child: InteractiveViewer(
                    child: Image.network(
                      widget.flyer.imageUrl,
                      fit: BoxFit.contain,
                      errorBuilder: (context, error, stack) => const Center(
                        child: Icon(Icons.broken_image, size: 64),
                      ),
                    ),
                  ),
                ),
                if (widget.flyer.pdfUrl != null)
                  Container(
                    padding: const EdgeInsets.all(16.0),
                    decoration: BoxDecoration(
                      color: Theme.of(context).cardColor,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),  // Using double value for opacity
                          blurRadius: 4,
                          offset: const Offset(0, -2),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: () {
                              setState(() {
                                _showPdfViewer = true;
                              });
                            },
                            icon: const Icon(Icons.picture_as_pdf),
                            label: const Text('View PDF in App'),
                            style: ElevatedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () => launchUrlString(widget.flyer.pdfUrl!),
                            icon: const Icon(Icons.open_in_new),
                            label: const Text('Open Externally'),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
      floatingActionButton: _showPdfViewer
          ? FloatingActionButton(
              onPressed: () {
                setState(() {
                  _showPdfViewer = false;
                });
              },
              tooltip: 'Back to Image View',
              child: const Icon(Icons.image),
            )
          : null,
    );
  }
}

