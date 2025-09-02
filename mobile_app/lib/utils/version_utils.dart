import 'package:flutter/services.dart';

class VersionUtils {
  static String? _cachedVersion;
  static String? _cachedBuildNumber;
  
  /// Get app version from pubspec.yaml (cached for performance)
  static Future<String> getAppVersion() async {
    if (_cachedVersion != null) return _cachedVersion!;
    
    try {
      final pubspecContent = await rootBundle.loadString('pubspec.yaml');
      final versionMatch = RegExp(r'version:\s+([0-9]+\.[0-9]+\.[0-9]+)\+([0-9]+)').firstMatch(pubspecContent);
      
      if (versionMatch != null) {
        _cachedVersion = versionMatch.group(1)!;
        _cachedBuildNumber = versionMatch.group(2)!;
        return _cachedVersion!;
      }
    } catch (e) {
      // Fallback if reading fails
      return '2.2.5';
    }
    
    return '2.2.5'; // Fallback version
  }
  
  /// Get build number from pubspec.yaml (cached for performance)
  static Future<String> getBuildNumber() async {
    if (_cachedBuildNumber != null) return _cachedBuildNumber!;
    
    // This will also populate _cachedBuildNumber
    await getAppVersion();
    return _cachedBuildNumber ?? '25';
  }
  
  /// Get full version string (version + build number)
  static Future<String> getFullVersion() async {
    final version = await getAppVersion();
    final buildNumber = await getBuildNumber();
    return '$version+$buildNumber';
  }
  
  /// Clear version cache (useful for testing or if version changes)
  static void clearCache() {
    _cachedVersion = null;
    _cachedBuildNumber = null;
  }
}
