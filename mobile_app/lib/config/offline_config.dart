class OfflineConfig {
  OfflineConfig._();

  static const String databaseName = 'offline_catalog.db';
  static const int databaseVersion = 2;
  static const Duration maxCacheAge = Duration(days: 7);
  static const Duration syncInterval = Duration(hours: 6);
}
