import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:angel_stones_app/main.dart';

void main() {
  testWidgets('App loads', (WidgetTester tester) async {
    await tester.pumpWidget(const MyApp());
    expect(find.text('Angel Stones'), findsOneWidget);
  });
}
