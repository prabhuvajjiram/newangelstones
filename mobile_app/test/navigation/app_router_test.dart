import 'package:flutter_test/flutter_test.dart';
import 'package:angel_granites_app/navigation/app_router.dart';

void main() {
  group('AppRouter.normalizeExternalLocation', () {
    test('maps the website app root to Flutter home', () {
      expect(
        AppRouter.normalizeExternalLocation(
          Uri.parse('https://theangelstones.com/app'),
        ),
        '/',
      );
    });

    test('maps inventory App Links and preserves the search query', () {
      expect(
        AppRouter.normalizeExternalLocation(
          Uri.parse('https://theangelstones.com/app/inventory?query=AG-298'),
        ),
        '/inventory?query=AG-298',
      );
    });

    test('leaves native Flutter routes unchanged', () {
      expect(
        AppRouter.normalizeExternalLocation(Uri.parse('/inventory')),
        isNull,
      );
    });
  });
}
