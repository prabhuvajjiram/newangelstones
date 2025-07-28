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
          color: Colors.red,
          padding: const EdgeInsets.all(8),
          child: const Text(
            'Offline mode',
            style: TextStyle(color: Colors.white),
            textAlign: TextAlign.center,
          ),
        );
      },
    );
  }
}
