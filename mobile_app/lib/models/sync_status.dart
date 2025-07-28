enum SyncState { idle, syncing, success, error }

class SyncStatus {
  final SyncState state;
  final double progress;
  final String? message;

  SyncStatus({required this.state, this.progress = 0, this.message});
}
