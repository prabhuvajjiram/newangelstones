import 'package:flutter/material.dart';
import '../services/notification_service.dart';

class NotificationPermissionPrompt extends StatelessWidget {
  const NotificationPermissionPrompt({super.key});

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Enable Notifications'),
      content: const Text('Stay updated with the latest products and offers.'),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('No Thanks'),
        ),
        ElevatedButton(
          onPressed: () async {
            // Store the navigator context before async operation
            final navigator = Navigator.of(context);
            await NotificationService.instance.initialize();
            // Use the stored navigator
            if (navigator.canPop()) {
              navigator.pop();
            }
          },
          child: const Text('Allow'),
        ),
      ],
    );
  }
}