import 'dart:async';

import 'package:flutter/material.dart';
import 'package:pdfrx/pdfrx.dart';

import '../utils/pdf_utils.dart';

class PdfViewerWidget extends StatefulWidget {
  final String pdfUrl;

  const PdfViewerWidget({super.key, required this.pdfUrl});

  @override
  State<PdfViewerWidget> createState() => _PdfViewerWidgetState();
}

class _PdfViewerWidgetState extends State<PdfViewerWidget> {
  final PdfViewerController pdfController = PdfViewerController();

  String? localPath;
  bool isLoading = true;
  String? errorMessage;
  int currentPage = 1;
  int totalPages = 0;
  int _loadGeneration = 0;

  @override
  void initState() {
    super.initState();
    unawaited(_loadPdf());
  }

  @override
  void didUpdateWidget(covariant PdfViewerWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.pdfUrl != widget.pdfUrl) {
      unawaited(_loadPdf());
    }
  }

  Future<void> _loadPdf() async {
    final loadGeneration = ++_loadGeneration;

    setState(() {
      isLoading = true;
      errorMessage = null;
      localPath = null;
      currentPage = 1;
      totalPages = 0;
    });

    try {
      // PdfUtils keeps bundled PDFs offline and caches downloaded PDFs locally.
      final pdfPath = await PdfUtils.getPdfPath(widget.pdfUrl);
      if (pdfPath == null) {
        throw Exception('Could not load PDF from bundled assets or network');
      }

      if (!mounted || loadGeneration != _loadGeneration) return;
      setState(() {
        localPath = pdfPath;
        isLoading = false;
      });
    } catch (e) {
      if (!mounted || loadGeneration != _loadGeneration) return;
      setState(() {
        isLoading = false;
        errorMessage = 'Error loading PDF: $e';
      });
    }
  }

  @override
  void dispose() {
    _loadGeneration++;
    super.dispose();
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
      return _buildError(errorMessage!);
    }

    final pdfPath = localPath;
    if (pdfPath == null) {
      return const Center(child: Text('PDF not available'));
    }

    return Column(
      children: [
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
                onPressed: currentPage > 1 && pdfController.isReady
                    ? () {
                        unawaited(pdfController.goToPage(
                          pageNumber: currentPage - 1,
                          duration: const Duration(milliseconds: 150),
                        ));
                      }
                    : null,
                icon: const Icon(Icons.navigate_before),
                tooltip: 'Previous Page',
              ),
              Text(
                'Page $currentPage of $totalPages',
                style: Theme.of(context).textTheme.titleSmall,
              ),
              IconButton(
                onPressed: currentPage < totalPages && pdfController.isReady
                    ? () {
                        unawaited(pdfController.goToPage(
                          pageNumber: currentPage + 1,
                          duration: const Duration(milliseconds: 150),
                        ));
                      }
                    : null,
                icon: const Icon(Icons.navigate_next),
                tooltip: 'Next Page',
              ),
            ],
          ),
        ),
        Expanded(
          child: PdfViewer.file(
            pdfPath,
            key: ValueKey(pdfPath),
            controller: pdfController,
            params: PdfViewerParams(
              backgroundColor: Theme.of(context).scaffoldBackgroundColor,
              onViewerReady: (document, controller) {
                if (!mounted) return;
                setState(() {
                  totalPages = controller.pageCount;
                  currentPage = controller.pageNumber ?? 1;
                });
              },
              onPageChanged: (pageNumber) {
                if (!mounted || pageNumber == null) return;
                setState(() {
                  currentPage = pageNumber;
                });
              },
              loadingBannerBuilder: (context, bytesDownloaded, totalBytes) {
                return Center(
                  child: CircularProgressIndicator(
                    value: totalBytes == null || totalBytes == 0
                        ? null
                        : bytesDownloaded / totalBytes,
                  ),
                );
              },
              errorBannerBuilder: (context, error, stackTrace, documentRef) {
                return _buildError('Error opening PDF: $error');
              },
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildError(String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 64, color: Colors.red),
          const SizedBox(height: 16),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: Colors.red),
          ),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _loadPdf,
            child: const Text('Retry'),
          ),
        ],
      ),
    );
  }
}
