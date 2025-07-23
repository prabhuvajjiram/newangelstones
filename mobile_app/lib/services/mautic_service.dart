import 'package:http/http.dart' as http;

class MauticService {
  static const String _baseUrl = 'https://theangelstones.com/mautic/form/submit';
  static const int _contactFormId = 1;
  static const int _quoteFormId = 2;

  static Future<bool> submitContactForm({
    required String name,
    required String email,
    String? phone,
    required String message,
  }) async {
    try {
      final url = Uri.parse('$_baseUrl?formId=$_contactFormId');
      final body = {
        'mauticform[email]': email,
        'mauticform[f_name]': name,
        'mauticform[phone]': phone ?? '',
        'mauticform[f_message]': message,
        'mauticform[formId]': _contactFormId.toString(),
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
      final url = Uri.parse('$_baseUrl?formId=$_quoteFormId');
      final body = {
        'mauticform[f_name]': name,
        'mauticform[email]': email,
        'mauticform[phone]': phone,
        'mauticform[project_details]': projectDetails,
        'mauticform[cart_items]': cartItems,
        'mauticform[total_quantity]': totalQuantity.toString(),
        'mauticform[formId]': _quoteFormId.toString(),
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
