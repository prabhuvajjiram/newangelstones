import 'package:angel_granites_app/main.dart';
import 'package:angel_granites_app/models/inventory_item.dart';
import 'package:angel_granites_app/screens/contact_screen.dart';
import 'package:angel_granites_app/screens/enhanced_cart_screen.dart';
import 'package:angel_granites_app/screens/inventory_screen.dart';
import 'package:angel_granites_app/screens/quote_request_screen.dart';
import 'package:angel_granites_app/services/inventory_service.dart';
import 'package:angel_granites_app/state/cart_state.dart';
import 'package:angel_granites_app/state/saved_items_state.dart';
import 'package:angel_granites_app/theme/app_theme.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

class _AccessibleInventoryService extends InventoryService {
  final List<InventoryItem> _items = [
    InventoryItem(
      code: 'AG-298',
      description: 'Premium Jet Black heart headstone',
      type: 'Tablet',
      color: 'Premium Jet Black',
      location: 'Elberton',
      size: '4-0 X 0-8 X 2-4',
      quantity: 2,
    ),
  ];

  @override
  List<String> get availableTypes => ['Tablet'];

  @override
  List<String> get availableColors => ['Premium Jet Black'];

  @override
  List<String> get availableLocations => ['Elberton'];

  @override
  Future<void> initialize() async {}

  @override
  Future<List<InventoryItem>> fetchInventory({
    int page = 1,
    int pageSize = 1000,
    String? searchQuery,
    String? type,
    String? color,
    String? location,
    bool forceRefresh = false,
  }) async =>
      _items;

  @override
  Future<List<InventoryItem>> loadLocalInventory() async => _items;
}

Future<void> _expectAccessibilityGuidelines(WidgetTester tester) async {
  await expectLater(tester, meetsGuideline(labeledTapTargetGuideline));
  await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
  await expectLater(tester, meetsGuideline(iOSTapTargetGuideline));
  await expectLater(tester, meetsGuideline(textContrastGuideline));
}

void main() {
  testWidgets('primary shell meets Flutter accessibility guidelines',
      (WidgetTester tester) async {
    debugDefaultTargetPlatformOverride = TargetPlatform.windows;
    final semantics = tester.ensureSemantics();

    await tester.pumpWidget(const MyApp());
    await tester.pump(const Duration(milliseconds: 500));

    await _expectAccessibilityGuidelines(tester);

    // Flush app startup timers before disposing the root widget.
    await tester.pump(const Duration(seconds: 5));
    await tester.pumpWidget(const SizedBox.shrink());
    await tester.pump();
    semantics.dispose();
    debugDefaultTargetPlatformOverride = null;
  });

  testWidgets('inventory meets Flutter accessibility guidelines',
      (WidgetTester tester) async {
    final semantics = tester.ensureSemantics();

    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.darkTheme,
        home: Scaffold(
          body: InventoryScreen(
            inventoryService: _AccessibleInventoryService(),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await _expectAccessibilityGuidelines(tester);
    semantics.dispose();
  });

  testWidgets('contact meets Flutter accessibility guidelines',
      (WidgetTester tester) async {
    final semantics = tester.ensureSemantics();

    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.darkTheme,
        home: const Scaffold(body: ContactScreen()),
      ),
    );
    await tester.pumpAndSettle();

    await _expectAccessibilityGuidelines(tester);
    semantics.dispose();
  });

  testWidgets('cart and quote flows meet Flutter accessibility guidelines',
      (WidgetTester tester) async {
    final semantics = tester.ensureSemantics();

    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider(create: (_) => CartState()),
          ChangeNotifierProvider(create: (_) => SavedItemsState()),
        ],
        child: MaterialApp(
          theme: AppTheme.darkTheme,
          home: const EnhancedCartScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();
    await _expectAccessibilityGuidelines(tester);

    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.darkTheme,
        home: const QuoteRequestScreen(
          cartItems: [],
          totalQuantity: 0,
        ),
      ),
    );
    await tester.pumpAndSettle();
    await _expectAccessibilityGuidelines(tester);
    semantics.dispose();
  });

  testWidgets('inventory search is first in keyboard traversal order',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.darkTheme,
        home: Scaffold(
          body: InventoryScreen(
            inventoryService: _AccessibleInventoryService(),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.sendKeyEvent(LogicalKeyboardKey.tab);
    await tester.pump();

    final searchField = tester.widget<TextField>(find.byType(TextField));
    expect(searchField.focusNode?.hasFocus, isTrue);
  });

  testWidgets('core screens support 200 percent text and reduced motion',
      (WidgetTester tester) async {
    await tester.binding.setSurfaceSize(const Size(390, 844));
    addTearDown(() => tester.binding.setSurfaceSize(null));

    const mediaQuery = MediaQueryData(
      size: Size(390, 844),
      textScaler: TextScaler.linear(2),
      highContrast: true,
      disableAnimations: true,
    );

    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.highContrastDarkTheme,
        home: MediaQuery(
          data: mediaQuery,
          child: Scaffold(
            body: InventoryScreen(
              inventoryService: _AccessibleInventoryService(),
            ),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);

    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.highContrastDarkTheme,
        home: const MediaQuery(
          data: mediaQuery,
          child: Scaffold(body: ContactScreen()),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
  });

  test('high contrast theme exposes maximum-contrast surfaces and outlines',
      () {
    final theme = AppTheme.highContrastDarkTheme;

    expect(theme.scaffoldBackgroundColor, Colors.black);
    expect(theme.colorScheme.surface, Colors.black);
    expect(theme.colorScheme.onSurface, Colors.white);
    expect(theme.colorScheme.outline, Colors.white);
  });
}
