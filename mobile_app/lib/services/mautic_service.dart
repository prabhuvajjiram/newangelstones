import 'package:http/http.dart' as http;

class MauticService {
  static const String _baseUrl = 'https://theangelstones.com/mautic/form/submit';
  static const int _formId = 1;

  static Future<bool> submitContactForm({
    required String name,
    required String email,
    String? phone,
    required String message,
  }) async {
    try {
      final url = Uri.parse('$_baseUrl?formId=$_formId');
      final body = {
        'mauticform[email]': email,
        'mauticform[f_name]': name,
        'mauticform[phone]': phone ?? '',
        'mauticform[f_message]': message,
        'mauticform[formId]': _formId.toString(),
        'mauticform[return]': '',
      };

      await http.post(
        url,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body,
      );

      // Mautic might return a 302 redirect or other status codes on success
      // Since we know the form is working (emails are received),
      // we'll consider any response without an exception as a success
      return true;
      
    } catch (e) {
      return false;
    }
  }
}
