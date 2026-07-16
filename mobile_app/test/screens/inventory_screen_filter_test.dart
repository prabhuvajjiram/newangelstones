import 'package:angel_granites_app/models/inventory_item.dart';
import 'package:angel_granites_app/screens/inventory_screen.dart';
import 'package:angel_granites_app/services/inventory_service.dart';
import 'package:angel_granites_app/utils/inventory_search.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

class _FakeInventoryService extends InventoryService {
  _FakeInventoryService(this.items);

  final List<InventoryItem> items;

  String? lastQuery;
  String? lastType;
  String? lastColor;
  String? lastLocation;

  @override
  List<String> get availableTypes => ['Base', 'Tablet'];

  @override
  List<String> get availableColors => ['India Red', 'Premium Jet Black'];

  @override
  List<String> get availableLocations => ['Barre', 'Elberton'];

  @override
  Future<void> initialize() async {}

  @override
  Future<List<InventoryItem>> refreshInventory({bool force = false}) async =>
      items;

  @override
  Future<List<InventoryItem>> fetchInventory({
    int page = 1,
    int pageSize = 1000,
    String? searchQuery,
    String? type,
    String? color,
    String? location,
    bool forceRefresh = false,
  }) async {
    lastQuery = searchQuery;
    lastType = type;
    lastColor = color;
    lastLocation = location;
    return InventorySearch.filterAndRank(
      items,
      query: searchQuery,
      type: type,
      color: color,
      location: location,
    );
  }

  @override
  Future<List<InventoryItem>> loadLocalInventory() async => items;
}

InventoryItem _item({
  required String code,
  required String description,
  required String type,
  required String color,
  required String location,
}) {
  return InventoryItem(
    code: code,
    description: description,
    type: type,
    color: color,
    location: location,
    size: '4-0 X 0-8 X 2-4',
    quantity: 1,
  );
}

Future<void> _selectFilter(
  WidgetTester tester, {
  required String chipLabel,
  required String option,
}) async {
  await tester.tap(find.widgetWithText(FilterChip, chipLabel));
  await tester.pumpAndSettle();
  final dialog = find.byType(AlertDialog);
  await tester.tap(
    find.descendant(of: dialog, matching: find.text(option)),
  );
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('type, color, and location filters stay combined',
      (tester) async {
    final service = _FakeInventoryService([
      _item(
        code: 'MATCH',
        description: 'Matching Elberton tablet',
        type: 'Tablet',
        color: 'India Red',
        location: 'Elberton',
      ),
      _item(
        code: 'WRONG-LOCATION',
        description: 'Barre tablet',
        type: 'Tablet',
        color: 'India Red',
        location: 'Barre',
      ),
      _item(
        code: 'WRONG-COLOR',
        description: 'Black Elberton tablet',
        type: 'Tablet',
        color: 'Premium Jet Black',
        location: 'Elberton',
      ),
      _item(
        code: 'WRONG-TYPE',
        description: 'Elberton base',
        type: 'Base',
        color: 'India Red',
        location: 'Elberton',
      ),
    ]);

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: InventoryScreen(inventoryService: service),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await _selectFilter(tester, chipLabel: 'Type', option: 'Tablet');
    await _selectFilter(tester, chipLabel: 'Color', option: 'India Red');
    await _selectFilter(tester, chipLabel: 'Location', option: 'Elberton');

    expect(service.lastType, 'Tablet');
    expect(service.lastColor, 'India Red');
    expect(service.lastLocation, 'Elberton');
    expect(find.text('Matching Elberton tablet'), findsOneWidget);
    expect(find.text('Barre tablet'), findsNothing);
    expect(find.text('Black Elberton tablet'), findsNothing);
    expect(find.text('Elberton base'), findsNothing);
  });

  testWidgets('Clear Filters preserves the search query', (tester) async {
    final service = _FakeInventoryService([
      _item(
        code: 'MATCH',
        description: 'Matching Elberton tablet',
        type: 'Tablet',
        color: 'India Red',
        location: 'Elberton',
      ),
    ]);

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: InventoryScreen(inventoryService: service),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextField), 'matching');
    await tester.pump(const Duration(milliseconds: 600));
    await tester.pumpAndSettle();
    await _selectFilter(tester, chipLabel: 'Type', option: 'Tablet');

    await tester.tap(find.text('Clear Filters'));
    await tester.pumpAndSettle();

    final searchField = tester.widget<TextField>(find.byType(TextField));
    expect(searchField.controller?.text, 'matching');
    expect(service.lastQuery, 'matching');
    expect(service.lastType, isNull);
  });
}
