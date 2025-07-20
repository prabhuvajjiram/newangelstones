import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_pdfview/flutter_pdfview.dart';
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
  PDFViewController? pdfController;

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

      setState(() {
        localPath = file.path;
        isLoading = false;
      });
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
                color: Colors.black.withOpacity(0.1),
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
                        pdfController?.setPage(currentPage - 1);
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
                        pdfController?.setPage(currentPage + 1);
                      }
                    : null,
                icon: const Icon(Icons.navigate_next),
                tooltip: 'Next Page',
              ),
            ],
          ),
        ),
        // PDF Viewer
        Expanded(
          child: PDFView(
            filePath: localPath!,
            enableSwipe: true,
            swipeHorizontal: false,
            autoSpacing: false,
            pageFling: true,
            pageSnap: true,
            defaultPage: currentPage,
            fitPolicy: FitPolicy.BOTH,
            preventLinkNavigation: false,
            onRender: (pages) {
              setState(() {
                totalPages = pages ?? 0;
              });
            },
            onError: (error) {
              setState(() {
                errorMessage = 'Error rendering PDF: $error';
              });
            },
            onPageError: (page, error) {
              setState(() {
                errorMessage = 'Error on page $page: $error';
              });
            },
            onViewCreated: (PDFViewController controller) {
              pdfController = controller;
            },
            onLinkHandler: (String? uri) {
              // Handle PDF links if needed
            },
            onPageChanged: (int? page, int? total) {
              setState(() {
                currentPage = page ?? 0;
                totalPages = total ?? 0;
              });
            },
          ),
        ),
      ],
    );
  }
}
