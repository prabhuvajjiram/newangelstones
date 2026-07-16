import 'package:angel_granites_app/services/api_service.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ApiService.parseFeaturedCategoryFiles', () {
    test('parses the live directory API category format', () {
      final categories = ApiService.parseFeaturedCategoryFiles([
        {
          'name': 'Benches',
          'path': 'images/products/Benches',
          'thumbnail': 'images/products/Benches/DF-32 Black.jpg',
        },
        {
          'name': 'Monuments',
          'path': 'images/products/Monuments',
          'thumbnail': 'images/products/Monuments/AG-946.jpg',
        },
      ]);

      expect(categories, hasLength(2));
      expect(categories.first['id'], 'Benches');
      expect(categories.first['name'], 'Benches');
      expect(
        categories.first['image'],
        'https://theangelstones.com/images/products/Benches/DF-32 Black.jpg',
      );
    });

    test('accepts the legacy products path and ignores image files', () {
      final categories = ApiService.parseFeaturedCategoryFiles([
        {
          'name': 'Designs',
          'path': 'products/Designs',
          'thumbnail': 'https://cdn.example.com/design.jpg',
        },
        {
          'name': 'AG-946.jpg',
          'path': 'images/products/Monuments/AG-946.jpg',
        },
        {'name': 'unrelated', 'path': 'images/unrelated'},
      ]);

      expect(categories, hasLength(1));
      expect(categories.single['id'], 'Designs');
      expect(categories.single['image'], 'https://cdn.example.com/design.jpg');
    });
  });
}
