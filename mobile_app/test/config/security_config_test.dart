import 'package:angel_granites_app/config/security_config.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('SecurityConfig payment URL validation', () {
    test('accepts the configured Clover payment link', () {
      expect(
        SecurityConfig.isValidPaymentUrl(SecurityConfig.cloverPaymentUrl),
        isTrue,
      );
    });

    test('accepts Clover-hosted payment redirects', () {
      expect(
        SecurityConfig.isValidPaymentUrl(
          'https://www.clover.com/pay-widgets/example',
        ),
        isTrue,
      );
    });

    test('rejects the retired Converge payment host', () {
      expect(
        SecurityConfig.isValidPaymentUrl('https://www.convergepay.com/pay'),
        isFalse,
      );
    });

    test('rejects lookalike Clover hosts', () {
      expect(
        SecurityConfig.isValidPaymentUrl('https://clover.com.example.com/pay'),
        isFalse,
      );
    });
  });

  group('SecurityConfig embedded browser policy', () {
    test('allows trusted HTTPS destinations', () {
      expect(
        SecurityConfig.isAllowedWebViewUrl(
          'https://monument.business/GV/Account/Login',
        ),
        isTrue,
      );
      expect(
        SecurityConfig.isAllowedWebViewUrl(
          'https://checkout.clover.com/widget.html',
        ),
        isTrue,
      );
    });

    test('rejects insecure and lookalike destinations', () {
      expect(
        SecurityConfig.isAllowedWebViewUrl(
          'http://monument.business/GV/Account/Login',
        ),
        isFalse,
      );
      expect(
        SecurityConfig.isAllowedWebViewUrl(
          'https://monument.business.example.com/login',
        ),
        isFalse,
      );
    });

    test('redacts URL credentials, query parameters, and fragments', () {
      expect(
        SecurityConfig.redactUrlForLogging(
          'https://user:secret@checkout.clover.com/widget.html?apiKey=secret#token',
        ),
        'https://checkout.clover.com/widget.html',
      );
      expect(
        SecurityConfig.redactUrlForLogging('mailto:test@example.com'),
        'mailto:',
      );
    });
  });
}
