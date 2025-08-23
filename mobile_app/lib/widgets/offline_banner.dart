import 'package:flutter/material.dart';
import '../services/connectivity_service.dart';

class OfflineBanner extends StatelessWidget {
  final ConnectivityService connectivityService;
  const OfflineBanner({super.key, required this.connectivityService});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<bool>(
      stream: connectivityService.onConnectivityChanged,
      initialData: true,
      builder: (context, snapshot) {
        final online = snapshot.data ?? true;
        if (online) return const SizedBox.shrink();
        return Container(
          width: double.infinity,
          color: Colors.orange.shade700,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              const Icon(Icons.wifi_off, color: Colors.white, size: 20),
              const SizedBox(width: 8),
              const Expanded(
                child: Text(
                  'You\'re offline. Some features may be limited.',
                  style: TextStyle(color: Colors.white, fontSize: 14),
                ),
              ),
              TextButton(
                onPressed: () {
                  // Trigger a connectivity check
                  connectivityService.isOnline;
                },
                child: const Text(
                  'Retry',
                  style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
