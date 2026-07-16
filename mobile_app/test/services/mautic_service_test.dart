import 'package:angel_granites_app/services/mautic_service.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

MauticFormClient _client(
  Future<http.Response> Function(http.Request request) handler, {
  Duration timeout = const Duration(seconds: 1),
}) {
  return MauticFormClient(
    httpClient: MockClient(handler),
    baseUrlProvider: () async => 'https://example.com/form/submit',
    contactFormIdProvider: () async => 11,
    quoteFormIdProvider: () async => 22,
    timeout: timeout,
  );
}

void main() {
  test('contact submission reports success only for successful HTTP status',
      () async {
    final successfulClient = _client(
      (request) async => http.Response('', 302),
    );
    final failingClient = _client(
      (request) async => http.Response('Server error', 500),
    );

    expect(
      await successfulClient.submitContactForm(
        name: 'Prabu',
        email: 'test@example.com',
        message: 'Please contact me',
      ),
      isTrue,
    );
    expect(
      await failingClient.submitContactForm(
        name: 'Prabu',
        email: 'test@example.com',
        message: 'Please contact me',
      ),
      isFalse,
    );
  });

  test('quote submission returns false when the request times out', () async {
    final client = _client(
      (request) async {
        await Future<void>.delayed(const Duration(milliseconds: 50));
        return http.Response('', 200);
      },
      timeout: const Duration(milliseconds: 1),
    );

    final result = await client.submitQuoteRequest(
      name: 'Prabu',
      email: 'test@example.com',
      phone: '555-0100',
      projectDetails: 'Tablet project',
      cartItems: 'One tablet',
      totalQuantity: 1,
    );

    expect(result, isFalse);
  });
}
