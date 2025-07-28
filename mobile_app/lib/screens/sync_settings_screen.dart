import 'package:flutter/material.dart';
import '../config/offline_config.dart';

class SyncSettingsScreen extends StatelessWidget {
  const SyncSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sync Settings')),
      body: const Padding(
        padding: EdgeInsets.all(16),
        child: Text('Configure sync frequency and storage limits here.'),
      ),
    );
  }
}
