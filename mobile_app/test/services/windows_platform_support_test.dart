import 'package:angel_granites_app/screens/webview_screen.dart';
import 'package:angel_granites_app/services/firebase_service.dart';
import 'package:angel_granites_app/services/notification_service.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  tearDown(() {
    debugDefaultTargetPlatformOverride = null;
  });

  test('Windows skips Firebase services that do not have Windows plugins', () {
    debugDefaultTargetPlatformOverride = TargetPlatform.windows;

    expect(FirebaseService.isSupportedPlatform, isFalse);
    expect(NotificationService.isSupportedPlatform, isFalse);
  });

  testWidgets('Windows portal screen offers the system browser fallback',
      (tester) async {
    debugDefaultTargetPlatformOverride = TargetPlatform.windows;

    await tester.pumpWidget(
      const MaterialApp(
        home: WebViewScreen(
          url: 'https://monument.business/GV/Account/Login',
          title: 'Customer Portal',
        ),
      ),
    );

    expect(
      find.text(
        'Customer Portal opens securely in your default browser on this device.',
      ),
      findsOneWidget,
    );
    expect(
        find.widgetWithText(FilledButton, 'Open in Browser'), findsOneWidget);

    debugDefaultTargetPlatformOverride = null;
    await tester.pumpWidget(const SizedBox.shrink());
  });
}
