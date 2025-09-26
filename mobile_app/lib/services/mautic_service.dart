import 'package:http/http.dart' as http;
import '../config/security_config.dart';

class MauticService {
  static Future<bool> submitContactForm({
    required String name,
    required String email,
    String? phone,
    required String message,
  }) async {
    try {
      final baseUrl = await SecurityConfig.getMauticBaseUrl();
      final formId = await SecurityConfig.getMauticContactFormId();
      final url = Uri.parse('$baseUrl?formId=$formId');
      final body = {
        'mauticform[email]': email,
        'mauticform[f_name]': name,
        'mauticform[phone]': phone ?? '',
        'mauticform[f_message]': message,
        'mauticform[formId]': formId.toString(),
        'mauticform[return]': '',
      };

      await http.post(
        url,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body,
      );
      return true;
    } catch (e) {
      return false;
    }
  }

  static Future<bool> submitQuoteRequest({
    required String name,
    required String email,
    required String phone,
    required String projectDetails,
    required String cartItems,
    required int totalQuantity,
  }) async {
    try {
      final baseUrl = await SecurityConfig.getMauticBaseUrl();
      final formId = await SecurityConfig.getMauticQuoteFormId();
      final url = Uri.parse('$baseUrl?formId=$formId');
      
      // Combine project details and cart items as requested
      final combinedDetails = 'PROJECT DETAILS:\n$projectDetails\n\nITEMS REQUESTED:\n$cartItems';
      
      final body = {
        'mauticform[f_name]': name,
        'mauticform[email]': email,
        'mauticform[phone]': phone,
        'mauticform[project_details]': combinedDetails,  // Combined details and items
        'mauticform[cart_items]': cartItems,  // Keep this for backward compatibility
        'mauticform[total_quantity]': totalQuantity.toString(),
        'mauticform[formId]': formId.toString(),
        'mauticform[return]': '',
        'mauticform[formName]': 'quoteform',
        'mauticform[submit]': '1',
      };

      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body,
      );

      return response.statusCode >= 200 && response.statusCode < 400;
    } catch (e) {
      return false;
    }
  }
}
