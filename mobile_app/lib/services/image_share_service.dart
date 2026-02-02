import 'dart:io';
import 'package:share_plus/share_plus.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';

/// Service to handle sharing images to photo library and other apps
class ImageShareService {
  /// Share an image from URL or local file path
  /// Returns true if successful, false otherwise
  static Future<bool> shareImage({
    required String imageUrl,
    required String fileName,
    String? productName,
    String? productCode,
  }) async {
    try {
      // Get the image file
      final File imageFile = await _getImageFile(imageUrl, fileName);
      
      // Build share message with product info
      String shareText = 'Check out this product from Angel Granites!';
      if (productName != null && productName.isNotEmpty) {
        shareText = 'Check out: $productName';
        if (productCode != null && productCode.isNotEmpty) {
          shareText += ' ($productCode)';
        }
        shareText += ' from Angel Granites!';
      }
      
      // Share the image
      await Share.shareXFiles(
        [XFile(imageFile.path)],
        text: shareText,
      );
      
      return true;
    } catch (e) {
      print('❌ Error sharing image: $e');
      return false;
    }
  }
  
  /// Get image file - either from cache or download it
  static Future<File> _getImageFile(String imageUrl, String fileName) async {
    // Check if it's a local file path
    if (!imageUrl.startsWith('http')) {
      return File(imageUrl);
    }
    
    // Try to get from temp directory first
    final tempDir = await getTemporaryDirectory();
    final tempFile = File('${tempDir.path}/$fileName');
    
    if (await tempFile.exists()) {
      return tempFile;
    }
    
    // Download the image
    final response = await http.get(Uri.parse(imageUrl))
        .timeout(const Duration(seconds: 30));
    
    if (response.statusCode == 200) {
      await tempFile.writeAsBytes(response.bodyBytes);
      return tempFile;
    } else {
      throw Exception('Failed to download image: ${response.statusCode}');
    }
  }
}
