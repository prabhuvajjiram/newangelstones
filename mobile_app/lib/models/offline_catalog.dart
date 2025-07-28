class OfflineCatalog {
  final String version;
  final DateTime lastSync;

  OfflineCatalog({required this.version, required this.lastSync});

  factory OfflineCatalog.fromJson(Map<String, dynamic> json) {
    return OfflineCatalog(
      version: json['version'] as String? ?? '1.0',
      lastSync: DateTime.parse(json['lastSync'] as String? ?? DateTime.now().toIso8601String()),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'version': version,
      'lastSync': lastSync.toIso8601String(),
    };
  }
}
