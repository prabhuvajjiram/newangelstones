import 'package:angel_granites_app/models/inventory_item.dart';
import 'package:angel_granites_app/utils/inventory_search.dart';
import 'package:flutter_test/flutter_test.dart';

InventoryItem item({
  required String code,
  required String description,
  String size = '',
  String design = '',
  String color = '',
  String type = '',
  String location = '',
  int quantity = 1,
}) {
  return InventoryItem(
    code: code,
    description: description,
    size: size,
    design: design,
    color: color,
    type: type,
    location: location,
    quantity: quantity,
  );
}

void main() {
  group('InventorySearch', () {
    test('puts an exact size match before weaker matches', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'TOKEN-MATCH',
            description: 'Tablet 4-0 with 2-4 base and 0-8 thickness',
          ),
          item(
            code: 'EXACT-SIZE',
            description: 'Upright monument',
            size: '4-0 X 0-8 X 2-4',
          ),
          item(
            code: 'PARTIAL',
            description: '4-0 x 2-4 monument',
          ),
        ],
        query: '4-0x2-4x0-8',
      );

      expect(results.map((result) => result.code), [
        'EXACT-SIZE',
        'TOKEN-MATCH',
      ]);
    });

    test('recognizes height and thickness entered in an alternate order', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'OTHER-DIMENSIONS',
            description: 'Tablet 4-0 with 2-4 and 0-8 in its notes',
          ),
          item(
            code: 'SAME-DIMENSIONS',
            description: 'Serpentine monument',
            size: '4-0 X 0-8 X 2-4',
          ),
        ],
        query: '4-0 x 2-4 x 0-8',
      );

      expect(results.first.code, 'SAME-DIMENSIONS');
    });

    test('matches dimensions despite separator and multiplication-sign changes',
        () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'DIMENSION',
            description: 'Serpentine monument',
            size: '4-0 X 2-4 X 0-8',
          ),
        ],
        query: '4 0 × 2 4 × 0 8',
      );

      expect(results.single.code, 'DIMENSION');
    });

    test('matches a compact two-dimension query without spaces around x', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'MATCH',
            description: 'Tablet',
            size: '3-4 X 2-4 X 0-8',
          ),
          item(
            code: 'OTHER',
            description: 'Tablet',
            size: '3-6 X 2-4 X 0-6',
          ),
        ],
        query: '3-4x0-8',
      );

      expect(
        InventorySearch.normalizeQuery('3-4x0-8'),
        InventorySearch.normalizeQuery('3-4 x 0-8'),
      );
      expect(results.map((result) => result.code), ['MATCH']);
    });

    test('keeps a partially typed multi-dimension query size-aware', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'MATCH-2-4',
            description: 'Tablet',
            size: '3-0 X 2-4 X 0-8',
          ),
          item(
            code: 'MATCH-2-0',
            description: 'Flat marker',
            size: '3-0 X 2-0 X 0-4',
          ),
          item(
            code: 'WRONG-LENGTH',
            description: 'Base',
            size: '3-6 X 1-2 X 0-8',
          ),
          item(
            code: 'IME-2025-05-09-270',
            description: 'Sample',
            size: '0-6 X 0-4 X 0-0.39',
          ),
        ],
        query: '3-0x2',
      );

      expect(
        results.map((result) => result.code),
        ['MATCH-2-0', 'MATCH-2-4'],
      );
    });

    test('requires every completed component in a two-dimension query', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'MATCH-NORMAL-ORDER',
            description: 'Tablet',
            size: '3-0 X 2-4 X 0-8',
          ),
          item(
            code: 'MATCH-STORED-ORDER',
            description: 'Tablet',
            size: '3-0 X 0-8 X 2-4',
          ),
          item(
            code: 'ONLY-FIRST-COMPONENT',
            description: 'Base',
            size: '3-0 X 1-0 X 0-6',
          ),
          item(
            code: 'IME-2025-05-09-270',
            description: 'Sample',
            size: '0-6 X 0-4 X 0-0.39',
          ),
        ],
        query: '3-0x2-4',
      );

      expect(
        results.map((result) => result.code),
        ['MATCH-NORMAL-ORDER', 'MATCH-STORED-ORDER'],
      );
    });

    test('prioritizes exact product code over a description mention', () {
      final results = InventorySearch.filterAndRank(
        [
          item(code: 'OTHER', description: 'Compatible with AG-396'),
          item(code: 'AG-396', description: 'Polished monument'),
        ],
        query: 'ag 396',
      );

      expect(results.first.code, 'AG-396');
    });

    test('uses quantity as a tie-breaker for equally relevant items', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'LOW',
            description: 'Gray monument',
            color: 'Gray',
            quantity: 1,
          ),
          item(
            code: 'HIGH',
            description: 'Gray monument',
            color: 'Gray',
            quantity: 8,
          ),
        ],
        query: 'gray',
      );

      expect(results.map((result) => result.code), ['HIGH', 'LOW']);
    });

    test('understands a spoken availability question for a design code', () {
      final results = InventorySearch.filterAndRank(
        [
          item(code: 'OTHER', description: 'Plain curved bench'),
          item(
            code: 'BENCH',
            description: 'Premium Jet Black curved bench as per AG-298',
            quantity: 2,
          ),
        ],
        query: 'Is AG-298 available?',
      );

      expect(InventorySearch.searchTerms('Is AG-298 available?'), 'ag 298');
      expect(results.single.code, 'BENCH');
    });

    test('treats headstone as a broad noun in a heart search', () {
      final results = InventorySearch.filterAndRank(
        [
          item(code: 'PLAIN', description: 'Gray serpentine tablet'),
          item(
            code: 'HEART',
            description: 'Premium Jet Black Tablet, Double Heart',
          ),
        ],
        query: 'Do you have heart head stones?',
      );

      expect(results.single.code, 'HEART');
    });

    test('matches a complete size component in a thickness question', () {
      final results = InventorySearch.filterAndRank(
        [
          item(code: 'SIX', description: 'Tablet', size: '3-0 X 0-6 X 2-4'),
          item(code: 'EIGHT', description: 'Tablet', size: '4-0 X 0-8 X 2-4'),
        ],
        query: 'Are any 0-8 thickness stones available?',
      );

      expect(results.single.code, 'EIGHT');
    });

    test('ignores spoken filler around a full dimension search', () {
      final results = InventorySearch.filterAndRank(
        [
          item(code: 'OTHER', description: 'Tablet', size: '4-0 X 0-6 X 2-4'),
          item(code: 'EXACT', description: 'Tablet', size: '4-0 X 0-8 X 2-4'),
        ],
        query: 'Is the full 4-0 X 2-4 X 0-8 available in stock?',
      );

      expect(results.single.code, 'EXACT');
    });

    test('applies type, color, and location together', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'MATCH',
            description: 'Premium Jet Black tablet',
            size: '4-0 X 0-8 X 2-4',
            type: 'Tablet',
            color: 'Premium Jet Black',
            location: 'Elberton',
          ),
          item(
            code: 'WRONG-LOCATION',
            description: 'Premium Jet Black tablet',
            size: '4-0 X 0-8 X 2-4',
            type: 'Tablet',
            color: 'Premium Jet Black',
            location: 'Barre',
          ),
          item(
            code: 'WRONG-COLOR',
            description: 'India Red tablet',
            size: '4-0 X 0-8 X 2-4',
            type: 'Tablet',
            color: 'India Red',
            location: 'Elberton',
          ),
          item(
            code: 'WRONG-TYPE',
            description: 'Premium Jet Black base',
            size: '4-0 X 0-8 X 2-4',
            type: 'Base',
            color: 'Premium Jet Black',
            location: 'Elberton',
          ),
        ],
        query: '4-0 x 2-4 x 0-8',
        type: 'tablet',
        color: 'premium jet black',
        location: 'elberton',
      );

      expect(results.map((result) => result.code), ['MATCH']);
    });

    test('does not weaken filters when a combination has no match', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'COLOR-ONLY',
            description: 'Premium Jet Black tablet',
            type: 'Tablet',
            color: 'Premium Jet Black',
            location: 'Barre',
          ),
          item(
            code: 'LOCATION-ONLY',
            description: 'India Red tablet',
            type: 'Tablet',
            color: 'India Red',
            location: 'Elberton',
          ),
        ],
        type: 'Tablet',
        color: 'Premium Jet Black',
        location: 'Elberton',
      );

      expect(results, isEmpty);
    });

    test('matches filter values exactly instead of by partial text', () {
      final results = InventorySearch.filterAndRank(
        [
          item(
            code: 'BLACK',
            description: 'Black tablet',
            type: 'Tablet',
            color: 'Black',
            location: 'Elberton',
          ),
          item(
            code: 'PREMIUM-JET-BLACK',
            description: 'Premium Jet Black tablet',
            type: 'Tablet',
            color: 'Premium Jet Black',
            location: 'Elberton',
          ),
        ],
        color: 'Black',
      );

      expect(results.map((result) => result.code), ['BLACK']);
    });
  });
}
