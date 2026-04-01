// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter_test/flutter_test.dart';

import 'package:angel_granites_app/main.dart';

void main() {
  testWidgets('App smoke test', (WidgetTester tester) async {
    // Allow async operations and timers
    await tester.runAsync(() async {
      // Build our app
      await tester.pumpWidget(const MyApp());
      
      // Verify the app widget exists
      expect(find.byType(MyApp), findsOneWidget);
      
      // Pump a few frames to catch immediate build errors
      await tester.pump(const Duration(milliseconds: 100));
      await tester.pump(const Duration(milliseconds: 100));
      await tester.pump(const Duration(milliseconds: 100));
    });
  });
}
