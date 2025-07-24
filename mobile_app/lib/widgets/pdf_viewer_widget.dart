import 'dart:io';
import 'package:flutter/material.dart';
import 'package:pdfx/pdfx.dart';
import 'package:path_provider/path_provider.dart';
import 'package:http/http.dart' as http;

class PdfViewerWidget extends StatefulWidget {
  final String pdfUrl;
  
  const PdfViewerWidget({super.key, required this.pdfUrl});

  @override
  State<PdfViewerWidget> createState() => _PdfViewerWidgetState();
}

class _PdfViewerWidgetState extends State<PdfViewerWidget> {
  String? localPath;
  bool isLoading = true;
  String? errorMessage;
  int currentPage = 0;
  int totalPages = 0;
  late PdfControllerPinch pdfController;
  bool _controllerInitialized = false;

  @override
  void initState() {
    super.initState();
    _downloadAndLoadPdf();
  }

  Future<void> _downloadAndLoadPdf() async {
    try {
      setState(() {
        isLoading = true;
        errorMessage = null;
      });

      // Get the application documents directory
      final dir = await getApplicationDocumentsDirectory();
      final fileName = widget.pdfUrl.split('/').last;
      final file = File('${dir.path}/$fileName');

      // Check if file already exists
      if (!await file.exists()) {
        // Download the PDF
        final response = await http.get(Uri.parse(widget.pdfUrl));
        
        if (response.statusCode == 200) {
          await file.writeAsBytes(response.bodyBytes);
        } else {
          throw Exception('Failed to download PDF: ${response.statusCode}');
        }
      }

      // Initialize the controller here
      try {
        pdfController = PdfControllerPinch(
          document: PdfDocument.openFile(file.path),
        );
        _controllerInitialized = true;
        
        setState(() {
          localPath = file.path;
          isLoading = false;
        });
      } catch (e) {
        setState(() {
          isLoading = false;
          errorMessage = 'Error initializing PDF controller: $e';
        });
      }
    } catch (e) {
      setState(() {
        isLoading = false;
        errorMessage = 'Error loading PDF: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text('Loading PDF...'),
          ],
        ),
      );
    }

    if (errorMessage != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 64, color: Colors.red),
            const SizedBox(height: 16),
            Text(
              errorMessage!,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.red),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _downloadAndLoadPdf,
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    if (localPath == null) {
      return const Center(child: Text('PDF not available'));
    }

    return Column(
      children: [
        // PDF Page Counter
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 2,
                offset: const Offset(0, 1),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              IconButton(
                onPressed: currentPage > 0
                    ? () {
                        pdfController.jumpToPage(currentPage - 1);
                      }
                    : null,
                icon: const Icon(Icons.navigate_before),
                tooltip: 'Previous Page',
              ),
              Text(
                'Page ${currentPage + 1} of $totalPages',
                style: Theme.of(context).textTheme.titleSmall,
              ),
              IconButton(
                onPressed: currentPage < totalPages - 1
                    ? () {
                        pdfController.jumpToPage(currentPage + 1);
                      }
                    : null,
                icon: const Icon(Icons.navigate_next),
                tooltip: 'Next Page',
              ),
            ],
          ),
        ),
        if (localPath != null && _controllerInitialized) 
          Expanded(
            child: Stack(
              children: [
                PdfViewPinch(
                  controller: pdfController,
                  onDocumentLoaded: (document) {
                    setState(() {
                      totalPages = document.pagesCount;
                    });
                  },
                  onPageChanged: (page) {
                    setState(() {
                      currentPage = page;
                    });
                  },
                  builders: PdfViewPinchBuilders<DefaultBuilderOptions>(
                    options: const DefaultBuilderOptions(),
                    documentLoaderBuilder: (_) => const Center(
                      child: CircularProgressIndicator(),
                    ),
                    pageLoaderBuilder: (_) => const Center(
                      child: CircularProgressIndicator(),
                    ),
                    errorBuilder: (_, error) => Center(
                      child: Text('Error loading PDF: $error'),
                    ),
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}
