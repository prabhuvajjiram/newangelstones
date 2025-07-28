import 'package:flutter/material.dart';
import '../models/sync_status.dart';

class SyncProgressIndicator extends StatelessWidget {
  final Stream<SyncStatus> statusStream;
  const SyncProgressIndicator({super.key, required this.statusStream});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<SyncStatus>(
      stream: statusStream,
      builder: (context, snapshot) {
        final status = snapshot.data;
        if (status == null || status.state == SyncState.idle) {
          return const SizedBox.shrink();
        }
        if (status.state == SyncState.syncing) {
          return LinearProgressIndicator(value: status.progress);
        }
        if (status.state == SyncState.error) {
          return const Text('Sync failed', style: TextStyle(color: Colors.red));
        }
        return const SizedBox.shrink();
      },
    );
  }
}
