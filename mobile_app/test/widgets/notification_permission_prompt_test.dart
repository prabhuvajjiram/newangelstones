import 'package:angel_granites_app/widgets/notification_permission_prompt.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  Future<void> showPermissionPrompt(
    WidgetTester tester, {
    required Future<bool> Function() requestPermission,
  }) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Builder(
            builder: (context) => ElevatedButton(
              onPressed: () => showDialog<void>(
                context: context,
                builder: (_) => NotificationPermissionPrompt(
                  requestPermission: requestPermission,
                ),
              ),
              child: const Text('Open prompt'),
            ),
          ),
        ),
      ),
    );
    await tester.tap(find.text('Open prompt'));
    await tester.pumpAndSettle();
  }

  testWidgets('closes and explains when the platform request fails',
      (tester) async {
    await showPermissionPrompt(
      tester,
      requestPermission: () async => throw Exception('not allowed'),
    );

    await tester.tap(find.text('Allow'));
    await tester.pumpAndSettle();

    expect(find.text('Enable Notifications'), findsNothing);
    expect(
      find.textContaining('Notifications could not be enabled'),
      findsOneWidget,
    );
  });

  testWidgets('closes and explains when permission is denied', (tester) async {
    await showPermissionPrompt(
      tester,
      requestPermission: () async => false,
    );

    await tester.tap(find.text('Allow'));
    await tester.pumpAndSettle();

    expect(find.text('Enable Notifications'), findsNothing);
    expect(
      find.textContaining('Notifications were not enabled'),
      findsOneWidget,
    );
  });
}
