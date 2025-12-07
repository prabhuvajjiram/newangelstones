import 'package:flutter/material.dart';
import '../utils/image_utils.dart';
import '../theme/app_theme.dart';

/// Asset sync management screen
/// Shows bundled vs downloaded assets, allows manual sync
class AssetSyncScreen extends StatefulWidget {
  const AssetSyncScreen({super.key});

  @override
  State<AssetSyncScreen> createState() => _AssetSyncScreenState();
}

class _AssetSyncScreenState extends State<AssetSyncScreen> {
  bool _isSyncing = false;
  Map<String, dynamic> _syncStatus = {};
  String _lastSyncMessage = '';

  @override
  void initState() {
    super.initState();
    _loadSyncStatus();
  }

  void _loadSyncStatus() {
    setState(() {
      _syncStatus = ImageUtils.getSyncStatus();
    });
  }

  Future<void> _syncNow() async {
    setState(() {
      _isSyncing = true;
      _lastSyncMessage = 'Syncing...';
    });

    try {
      final newCount = await ImageUtils.syncNewAssets();
      setState(() {
        _isSyncing = false;
        _lastSyncMessage = newCount > 0
            ? 'Downloaded $newCount new images'
            : 'Already up to date!';
        _loadSyncStatus();
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_lastSyncMessage),
            backgroundColor: newCount > 0 ? Colors.green : Colors.blue,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } catch (e) {
      setState(() {
        _isSyncing = false;
        _lastSyncMessage = 'Sync failed: $e';
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_lastSyncMessage),
            backgroundColor: Colors.red,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final bundledCount = _syncStatus['bundled_count'] ?? 0;
    final downloadedCount = _syncStatus['downloaded_count'] ?? 0;
    final totalCount = _syncStatus['total_count'] ?? 0;
    final lastSync = _syncStatus['last_sync'];

    return Scaffold(
      backgroundColor: const Color(0xFF121212),
      appBar: AppBar(
        title: const Text('Asset Sync'),
        backgroundColor: AppTheme.primaryColor,
        foregroundColor: Colors.white,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Status Card
          Card(
            elevation: 2,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Asset Status',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 16),
                  _buildStatRow(
                    icon: Icons.inventory,
                    label: 'Bundled (with app)',
                    value: '$bundledCount images',
                    color: Colors.green,
                  ),
                  const SizedBox(height: 12),
                  _buildStatRow(
                    icon: Icons.download,
                    label: 'Downloaded (from server)',
                    value: '$downloadedCount images',
                    color: Colors.blue,
                  ),
                  const Divider(height: 24),
                  _buildStatRow(
                    icon: Icons.photo_library,
                    label: 'Total Available',
                    value: '$totalCount images',
                    color: AppTheme.primaryColor,
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Sync Info Card
          Card(
            elevation: 2,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Sync Information',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (lastSync != null) ...[
                    Row(
                      children: [
                        const Icon(Icons.access_time, size: 20, color: Colors.grey),
                        const SizedBox(width: 8),
                        Text(
                          'Last sync: ${_formatDateTime(lastSync.toString())}',
                          style: const TextStyle(color: Colors.grey),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                  ],
                  if (_lastSyncMessage.isNotEmpty) ...[
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.info_outline, size: 20, color: Colors.blue),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _lastSyncMessage,
                              style: const TextStyle(color: Colors.blue),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Sync Button
          SizedBox(
            height: 50,
            child: ElevatedButton(
              onPressed: _isSyncing ? null : _syncNow,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primaryColor,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: _isSyncing
                  ? const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation(Colors.white),
                          ),
                        ),
                        SizedBox(width: 12),
                        Text('Syncing...'),
                      ],
                    )
                  : const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.sync),
                        SizedBox(width: 8),
                        Text('Sync Now'),
                      ],
                    ),
            ),
          ),

          const SizedBox(height: 24),

          // Info Card
          Card(
            color: Colors.blue.shade50,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.lightbulb_outline, color: Colors.blue.shade700),
                      const SizedBox(width: 8),
                      Text(
                        'How it works',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.blue.shade700,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    '• Bundled images are included with the app (instant access)\n'
                    '• New images from server are downloaded automatically\n'
                    '• Tap "Sync Now" to check for new images manually\n'
                    '• All images work offline once downloaded',
                    style: TextStyle(height: 1.5),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatRow({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
  }) {
    return Row(
      children: [
        Icon(icon, color: color, size: 24),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 14,
                  color: Colors.grey,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  String _formatDateTime(String isoString) {
    try {
      final dateTime = DateTime.parse(isoString);
      final now = DateTime.now();
      final difference = now.difference(dateTime);

      if (difference.inMinutes < 1) {
        return 'Just now';
      } else if (difference.inHours < 1) {
        return '${difference.inMinutes}m ago';
      } else if (difference.inDays < 1) {
        return '${difference.inHours}h ago';
      } else {
        return '${difference.inDays}d ago';
      }
    } catch (e) {
      return 'Unknown';
    }
  }
}
