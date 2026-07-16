import 'package:http/http.dart' as http;

import '../config/security_config.dart';

class MauticService {
  static final MauticFormClient _client = MauticFormClient();

  static Future<bool> submitContactForm({
    required String name,
    required String email,
    String? phone,
    required String message,
  }) {
    return _client.submitContactForm(
      name: name,
      email: email,
      phone: phone,
      message: message,
    );
  }

  static Future<bool> submitQuoteRequest({
    required String name,
    required String email,
    required String phone,
    required String projectDetails,
    required String cartItems,
    required int totalQuantity,
  }) {
    return _client.submitQuoteRequest(
      name: name,
      email: email,
      phone: phone,
      projectDetails: projectDetails,
      cartItems: cartItems,
      totalQuantity: totalQuantity,
    );
  }
}

/// HTTP boundary for Mautic form submissions.
///
/// The injectable dependencies keep status and timeout behavior testable while
/// [MauticService] preserves the existing static API used by the screens.
class MauticFormClient {
  MauticFormClient({
    http.Client? httpClient,
    Future<String> Function()? baseUrlProvider,
    Future<int> Function()? contactFormIdProvider,
    Future<int> Function()? quoteFormIdProvider,
    this.timeout = SecurityConfig.defaultTimeout,
  })  : _httpClient = httpClient ?? http.Client(),
        _baseUrlProvider = baseUrlProvider ?? SecurityConfig.getMauticBaseUrl,
        _contactFormIdProvider =
            contactFormIdProvider ?? SecurityConfig.getMauticContactFormId,
        _quoteFormIdProvider =
            quoteFormIdProvider ?? SecurityConfig.getMauticQuoteFormId;

  final http.Client _httpClient;
  final Future<String> Function() _baseUrlProvider;
  final Future<int> Function() _contactFormIdProvider;
  final Future<int> Function() _quoteFormIdProvider;
  final Duration timeout;

  Future<bool> submitContactForm({
    required String name,
    required String email,
    String? phone,
    required String message,
  }) async {
    try {
      final formId = await _contactFormIdProvider();
      final response = await _postForm(
        formId: formId,
        body: {
          'mauticform[email]': email,
          'mauticform[f_name]': name,
          'mauticform[phone]': phone ?? '',
          'mauticform[f_message]': message,
          'mauticform[formId]': formId.toString(),
          'mauticform[return]': '',
        },
      );
      return _isSuccessful(response.statusCode);
    } catch (_) {
      return false;
    }
  }

  Future<bool> submitQuoteRequest({
    required String name,
    required String email,
    required String phone,
    required String projectDetails,
    required String cartItems,
    required int totalQuantity,
  }) async {
    try {
      final formId = await _quoteFormIdProvider();
      final combinedDetails =
          'PROJECT DETAILS:\n$projectDetails\n\nITEMS REQUESTED:\n$cartItems';

      final response = await _postForm(
        formId: formId,
        body: {
          'mauticform[f_name]': name,
          'mauticform[email]': email,
          'mauticform[phone]': phone,
          'mauticform[project_details]': combinedDetails,
          // Preserve the existing field for older Mautic form mappings.
          'mauticform[cart_items]': cartItems,
          'mauticform[total_quantity]': totalQuantity.toString(),
          'mauticform[formId]': formId.toString(),
          'mauticform[return]': '',
          'mauticform[formName]': 'quoteform',
          'mauticform[submit]': '1',
        },
      );
      return _isSuccessful(response.statusCode);
    } catch (_) {
      return false;
    }
  }

  Future<http.Response> _postForm({
    required int formId,
    required Map<String, String> body,
  }) async {
    final baseUri = Uri.parse(await _baseUrlProvider());
    final url = baseUri.replace(queryParameters: {
      ...baseUri.queryParameters,
      'formId': formId.toString(),
    });

    return _httpClient
        .post(
          url,
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: body,
        )
        .timeout(timeout);
  }

  static bool _isSuccessful(int statusCode) =>
      statusCode >= 200 && statusCode < 400;
}
