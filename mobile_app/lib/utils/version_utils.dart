import 'package:flutter/services.dart';
import 'package:package_info_plus/package_info_plus.dart';

class VersionUtils {
  static PackageInfo? _cachedPackageInfo;
  
  /// Get package info (cached for performance)
  static Future<PackageInfo> _getPackageInfo() async {
    if (_cachedPackageInfo != null) return _cachedPackageInfo!;
    
    try {
      _cachedPackageInfo = await PackageInfo.fromPlatform();
      return _cachedPackageInfo!;
    } catch (e) {
      // Fallback to pubspec reading if PackageInfo fails
      return await _getFallbackPackageInfo();
    }
  }
  
  /// Fallback method using pubspec.yaml
  static Future<PackageInfo> _getFallbackPackageInfo() async {
    try {
      final pubspecContent = await rootBundle.loadString('pubspec.yaml');
      final versionMatch = RegExp(r'version:\s+([0-9]+\.[0-9]+\.[0-9]+)\+([0-9]+)').firstMatch(pubspecContent);
      
      if (versionMatch != null) {
        return PackageInfo(
          appName: 'Angel Granites',
          packageName: 'com.angelgranites.app',
          version: versionMatch.group(1)!,
          buildNumber: versionMatch.group(2)!,
        );
      }
    } catch (e) {
      // Ultimate fallback
    }
    
    return PackageInfo(
      appName: 'Angel Granites',
      packageName: 'com.angelgranites.app',
      version: '2.4.0',
      buildNumber: '27',
    );
  }
  
  /// Get app version (cached for performance)
  static Future<String> getAppVersion() async {
    final packageInfo = await _getPackageInfo();
    return packageInfo.version;
  }
  
  /// Get build number (cached for performance)
  static Future<String> getBuildNumber() async {
    final packageInfo = await _getPackageInfo();
    return packageInfo.buildNumber;
  }
  
  /// Get full version string (version + build number)
  static Future<String> getFullVersion() async {
    final version = await getAppVersion();
    final buildNumber = await getBuildNumber();
    return '$version+$buildNumber';
  }
  
  /// Get app name
  static Future<String> getAppName() async {
    final packageInfo = await _getPackageInfo();
    return packageInfo.appName;
  }
  
  /// Get package name
  static Future<String> getPackageName() async {
    final packageInfo = await _getPackageInfo();
    return packageInfo.packageName;
  }
  
  /// Clear version cache (useful for testing or if version changes)
  static void clearCache() {
    _cachedPackageInfo = null;
  }
}
