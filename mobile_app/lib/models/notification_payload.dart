class NotificationPayload {
  final String title;
  final String body;
  final String? deepLink;
  final Map<String, dynamic>? data;

  NotificationPayload({
    required this.title,
    required this.body,
    this.deepLink,
    this.data,
  });
}
