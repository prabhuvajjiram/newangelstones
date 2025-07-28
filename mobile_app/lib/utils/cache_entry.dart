class CacheEntry<T> {
  final T data;
  final DateTime timestamp;
  CacheEntry(this.data) : timestamp = DateTime.now();

  bool isExpired(Duration ttl) {
    return DateTime.now().difference(timestamp) > ttl;
  }
}
