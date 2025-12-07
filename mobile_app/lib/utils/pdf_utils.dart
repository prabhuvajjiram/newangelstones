import 'dart:convert';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'dart:io';
import 'package:path_provider/path_provider.dart';
import 'package:http/http.dart' as http;

/// Utility class for loading PDFs with hybrid bundled/network strategy
/// 
/// Strategy:
/// 1. Check if PDF exists in bundled assets (instant, works offline)
/// 2. If not bundled, download from network URL and cache locally
/// 3. Always return a local file path for the PDF viewer
class PdfUtils {
  static Map<String, dynamic>? _manifest;
  static bool _manifestLoaded = false;

  /// Load the PDF manifest from bundled assets
  static Future<void> _loadManifest() async {
    if (_manifestLoaded) return;

    try {
      final manifestString = await rootBundle.loadString('assets/pdf_manifest.json');
      _manifest = json.decode(manifestString) as Map<String, dynamic>;
      _manifestLoaded = true;
      debugPrint('📄 PDF manifest loaded: ${_manifest?['total_pdfs']} PDFs available');
    } catch (e) {
      debugPrint('⚠️ Could not load PDF manifest: $e');
      _manifest = null;
      _manifestLoaded = true;
    }
  }

  /// Find a bundled PDF asset by matching the URL filename
  /// 
  /// Example: For URL "https://example.com/pdfs/catalog.pdf"
  /// Returns: "assets/pdfs/specials/catalog.pdf" if found in manifest
  static Future<String?> _findBundledPdf(String pdfUrl) async {
    await _loadManifest();

    if (_manifest == null) return null;

    try {
      // Extract filename from URL
      final uri = Uri.parse(pdfUrl);
      final urlFilename = uri.pathSegments.last.split('?').first; // Remove query params

      // Search manifest for matching PDF
      final List<dynamic> pdfs = _manifest!['pdfs'] as List<dynamic>? ?? [];
      
      for (final pdf in pdfs) {
        if (pdf is Map<String, dynamic>) {
          final String assetPath = pdf['asset_path'] as String? ?? '';
          final String pdfName = pdf['name'] as String? ?? '';
          
          // Match by filename
          if (pdfName == urlFilename || assetPath.endsWith(urlFilename)) {
            debugPrint('✅ Found bundled PDF: $assetPath');
            return assetPath;
          }
        }
      }

      debugPrint('📡 PDF not bundled: $urlFilename (will download from network)');
      return null;
    } catch (e) {
      debugPrint('⚠️ Error searching PDF manifest: $e');
      return null;
    }
  }

  /// Get local file path for a PDF
  /// 
  /// This handles both bundled and network PDFs:
  /// - If bundled: copies from assets to temp directory and returns path
  /// - If not bundled: downloads from URL, caches, and returns path
  /// 
  /// Args:
  ///   pdfUrl: The URL of the PDF (can be bundled or network)
  ///   forceBundledOnly: If true, only use bundled PDFs (for offline mode)
  /// 
  /// Returns:
  ///   Local file path to the PDF, or null if unavailable
  static Future<String?> getPdfPath(String pdfUrl, {bool forceBundledOnly = false}) async {
    try {
      // Step 1: Check if PDF is bundled
      final bundledPath = await _findBundledPdf(pdfUrl);
      
      if (bundledPath != null) {
        // Copy bundled PDF to temp directory for viewing
        return await _copyBundledPdfToTemp(bundledPath);
      }

      // Step 2: If not forcing bundled-only, try network download
      if (!forceBundledOnly) {
        debugPrint('📥 Downloading PDF from network: $pdfUrl');
        return await _downloadAndCachePdf(pdfUrl);
      }

      debugPrint('❌ PDF not available offline: $pdfUrl');
      return null;
    } catch (e) {
      debugPrint('❌ Error getting PDF path: $e');
      return null;
    }
  }

  /// Copy a bundled PDF from assets to a temporary location
  /// (Required because PDF viewers need a file path, not an asset path)
  static Future<String?> _copyBundledPdfToTemp(String assetPath) async {
    try {
      // Load PDF data from assets
      final ByteData data = await rootBundle.load(assetPath);
      final List<int> bytes = data.buffer.asUint8List();

      // Get temp directory
      final Directory tempDir = await getTemporaryDirectory();
      final String fileName = assetPath.split('/').last;
      final File tempFile = File('${tempDir.path}/$fileName');

      // Write to temp file
      await tempFile.writeAsBytes(bytes);
      
      debugPrint('✅ Copied bundled PDF to: ${tempFile.path}');
      return tempFile.path;
    } catch (e) {
      debugPrint('❌ Error copying bundled PDF: $e');
      return null;
    }
  }

  /// Download a PDF from network and cache it locally
  static Future<String?> _downloadAndCachePdf(String url) async {
    try {
      // Get app documents directory for caching
      final Directory appDocDir = await getApplicationDocumentsDirectory();
      
      // Create pdfs subdirectory if it doesn't exist
      final Directory pdfCacheDir = Directory('${appDocDir.path}/pdfs');
      if (!await pdfCacheDir.exists()) {
        await pdfCacheDir.create(recursive: true);
      }

      // Get filename from URL
      final Uri uri = Uri.parse(url);
      final String fileName = uri.pathSegments.last.split('?').first;
      final File cachedFile = File('${pdfCacheDir.path}/$fileName');

      // Check if already cached
      if (await cachedFile.exists()) {
        debugPrint('📦 Using cached PDF: ${cachedFile.path}');
        return cachedFile.path;
      }

      // Download PDF
      debugPrint('⬇️ Downloading PDF: $fileName');
      final response = await http.get(Uri.parse(url));

      if (response.statusCode == 200) {
        await cachedFile.writeAsBytes(response.bodyBytes);
        debugPrint('✅ Downloaded and cached PDF: ${cachedFile.path}');
        return cachedFile.path;
      } else {
        debugPrint('❌ Failed to download PDF: ${response.statusCode}');
        return null;
      }
    } catch (e) {
      debugPrint('❌ Error downloading PDF: $e');
      return null;
    }
  }

  /// Clear the PDF cache
  /// Useful for freeing up space or forcing re-download
  static Future<void> clearCache() async {
    try {
      final Directory appDocDir = await getApplicationDocumentsDirectory();
      final Directory pdfCacheDir = Directory('${appDocDir.path}/pdfs');

      if (await pdfCacheDir.exists()) {
        await pdfCacheDir.delete(recursive: true);
        debugPrint('✅ Cleared PDF cache');
      }
    } catch (e) {
      debugPrint('❌ Error clearing PDF cache: $e');
    }
  }

  /// Get size of PDF cache in bytes
  static Future<int> getCacheSize() async {
    try {
      final Directory appDocDir = await getApplicationDocumentsDirectory();
      final Directory pdfCacheDir = Directory('${appDocDir.path}/pdfs');

      if (!await pdfCacheDir.exists()) return 0;

      int totalSize = 0;
      await for (final FileSystemEntity entity in pdfCacheDir.list(recursive: true)) {
        if (entity is File) {
          totalSize += await entity.length();
        }
      }

      return totalSize;
    } catch (e) {
      debugPrint('❌ Error calculating cache size: $e');
      return 0;
    }
  }
}
