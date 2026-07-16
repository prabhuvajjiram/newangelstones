import 'package:flutter/material.dart';
import '../services/notification_service.dart';

typedef NotificationPermissionRequest = Future<bool> Function();
typedef NotificationPromptSeenCallback = Future<void> Function();

class NotificationPermissionPrompt extends StatefulWidget {
  const NotificationPermissionPrompt({
    super.key,
    this.requestPermission,
    this.markPromptSeen,
  });

  final NotificationPermissionRequest? requestPermission;
  final NotificationPromptSeenCallback? markPromptSeen;

  @override
  State<NotificationPermissionPrompt> createState() =>
      _NotificationPermissionPromptState();
}

class _NotificationPermissionPromptState
    extends State<NotificationPermissionPrompt> {
  bool _isRequesting = false;

  Future<void> _decline() async {
    if (_isRequesting) return;

    final navigator = Navigator.of(context);
    await (widget.markPromptSeen?.call() ??
        NotificationService.instance.markPermissionPromptSeen());
    if (mounted && navigator.canPop()) navigator.pop();
  }

  Future<void> _allow() async {
    if (_isRequesting) return;

    setState(() => _isRequesting = true);
    final navigator = Navigator.of(context);
    final messenger = ScaffoldMessenger.maybeOf(context);
    String? feedback;

    try {
      final granted = await (widget.requestPermission?.call() ??
          NotificationService.instance.requestPermission());
      if (!granted) {
        feedback =
            'Notifications were not enabled. You can allow them later in '
            'System Settings.';
      }
    } catch (error) {
      debugPrint('Notification permission request failed: $error');
      feedback = 'Notifications could not be enabled. Check System Settings > '
          'Notifications > Angel Granites.';
    }

    if (!mounted) return;
    if (navigator.canPop()) navigator.pop();
    if (feedback != null) {
      messenger?.showSnackBar(SnackBar(content: Text(feedback)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Enable Notifications'),
      content: const Text('Stay updated with the latest products and offers.'),
      actions: [
        TextButton(
          onPressed: _isRequesting ? null : _decline,
          child: const Text('No Thanks'),
        ),
        ElevatedButton(
          onPressed: _isRequesting ? null : _allow,
          child: _isRequesting
              ? const SizedBox.square(
                  dimension: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Allow'),
        ),
      ],
    );
  }
}
