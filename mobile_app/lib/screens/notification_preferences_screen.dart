import 'package:flutter/material.dart';
import '../services/notification_service.dart';
import '../models/notification_preferences.dart';

class NotificationPreferencesScreen extends StatefulWidget {
  const NotificationPreferencesScreen({super.key});

  @override
  State<NotificationPreferencesScreen> createState() => _NotificationPreferencesScreenState();
}

class _NotificationPreferencesScreenState extends State<NotificationPreferencesScreen> {
  final NotificationService _service = NotificationService.instance;

  late NotificationPreferences _prefs;

  @override
  void initState() {
    super.initState();
    _prefs = _service.preferences;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notification Preferences')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          SwitchListTile(
            title: const Text('New Colors'),
            value: _prefs.newColors,
            onChanged: (val) {
              setState(() => _prefs.newColors = val);
            },
          ),
          SwitchListTile(
            title: const Text('New Products'),
            value: _prefs.newProducts,
            onChanged: (val) {
              setState(() => _prefs.newProducts = val);
            },
          ),
          SwitchListTile(
            title: const Text('Inventory Updates'),
            value: _prefs.inventoryUpdates,
            onChanged: (val) {
              setState(() => _prefs.inventoryUpdates = val);
            },
          ),
          SwitchListTile(
            title: const Text('Special Offers'),
            value: _prefs.specialOffers,
            onChanged: (val) {
              setState(() => _prefs.specialOffers = val);
            },
          ),
        ],
      ),
    );
  }
}
