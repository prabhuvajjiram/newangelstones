import "package:timezone/timezone.dart" as tz;
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import '../models/notification_payload.dart';
import 'package:flutter/material.dart';

class NotificationScheduler {
  final FlutterLocalNotificationsPlugin _plugin = FlutterLocalNotificationsPlugin();

Future<void> scheduleDaily(NotificationPayload payload, TimeOfDay time) async {
  final androidDetails = AndroidNotificationDetails(
    'scheduled_channel',
    'Scheduled Notifications',
  );
  final details = NotificationDetails(android: androidDetails);

  await _plugin.zonedSchedule(
    0,
    payload.title,
    payload.body,
    _nextInstanceOf(time),
    details,
    androidScheduleMode: AndroidScheduleMode.exactAllowWhileIdle,
    payload: payload.deepLink,
    matchDateTimeComponents: DateTimeComponents.time,
  );
}

  tz.TZDateTime _nextInstanceOf(TimeOfDay time) {
    final now = tz.TZDateTime.now(tz.local);
    var scheduled = tz.TZDateTime(tz.local, now.year, now.month, now.day, time.hour, time.minute);
    if (scheduled.isBefore(now)) {
      scheduled = scheduled.add(const Duration(days: 1));
    }
    return scheduled;
  }
}
