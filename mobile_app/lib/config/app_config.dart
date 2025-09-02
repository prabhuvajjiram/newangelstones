import '../utils/version_utils.dart';

class AppConfig {
  // App Information
  static const String appName = 'Angel Granites';
  static const String companyName = 'Angel Granites LLC.';
  static const String tagline = 'Elevating granites preserving memories';
  
  // Version is now dynamically loaded from pubspec.yaml
  static Future<String> get version => VersionUtils.getAppVersion();
  static Future<String> get buildNumber => VersionUtils.getBuildNumber();
  static Future<String> get fullVersion => VersionUtils.getFullVersion();
  
  // Startup Company Branding
  static const String appDescription = 'Discover premium granite and stone solutions for your projects. Browse our extensive catalog, get instant quotes, and connect with stone experts.';
  
  // Helper method to get version info for display
  static Future<Map<String, String>> getVersionInfo() async {
    return {
      'version': await version,
      'buildNumber': await buildNumber,
      'fullVersion': await fullVersion,
    };
  }
  
  // Contact Information
  static const String supportEmail = 'info@angelgranites.com';
  static const String websiteUrl = 'https://angelgranites.com';
  static const String privacyPolicyUrl = 'https://angelgranites.com/privacy-policy.html';
  static const String termsOfServiceUrl = 'https://angelgranites.com/terms-of-service.html';
  
  // App Store Information
  static const String appStoreId = '6748974666'; // Apple App Store ID
  static const String playStoreId = 'com.angelgranites.app'; // Google Play Store ID
  
  // Feature Flags
  static const bool enableAnalytics = true;
  static const bool enableCrashlytics = true;
  static const bool enablePushNotifications = true;
  static const bool enableOfflineMode = true;
  
  // Performance Settings
  static const int apiTimeoutSeconds = 30;
  static const int imageLoadTimeoutSeconds = 15;
  static const int maxCacheSize = 100; // MB
  
  // Startup Company Features
  static const bool showOnboarding = true;
  static const bool enableReferralProgram = false; // Future feature
  static const bool enableLoyaltyProgram = false; // Future feature
  
  // Social Media (for future marketing)
  static const String facebookUrl = 'https://facebook.com/angelgranites';
  static const String instagramUrl = 'https://instagram.com/angelgranites';
  static const String linkedinUrl = 'https://linkedin.com/company/angelgranites';
}
