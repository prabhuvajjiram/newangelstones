# Angel Granites Mobile App

**Version:** 3.0.0+28  
**Flutter:** 3.38.4+ | **Dart:** 3.10.3+

B2B mobile app for granite monuments and memorial products.

---

## Quick Setup

### Prerequisites
- Flutter 3.38.4+
- Xcode 16.4+ (iOS)
- Android Studio with SDK 36+ (Android)
- Python 3 with Pillow: `pip3 install Pillow`

### Install & Run
```bash
cd mobile_app
flutter pub get

# iOS
cd ios && pod install && cd ..
flutter run

# Android
flutter run -d <device_id>
```

---

## Key Features

### 1. Offline-First Architecture
- **120 bundled assets**: 116 images + 4 PDFs (14.9 MB)
- **Hybrid loading**: Bundled → Network → Cache
- **Instant offline**: Images load <10ms from bundle
- **Auto-updates**: New products load from API (no app update needed)

### 2. Asset Bundling System
All product images optimized and bundled for offline use.

**Bundle Assets:**
```bash
cd /Users/prabuvajjiram/Documents/newangelstones
python3 bundle_assets.py
# Downloads 120 images + 4 PDFs from production
# Optimizes images (70% size reduction)
# Generates manifests automatically
```

**Results:**
- Original: 2-5 MB per image
- Optimized: 300-600 KB per image  
- Quality: Visually identical on mobile
- Offline: Instant loading

### 3. Native In-App Reviews
- Triggers after 3 app launches
- Native iOS/Android review dialog
- 7-day cooldown if declined
- No app exit required

### 4. Firebase Integration
- Authentication
- Cloud Firestore
- Push notifications (FCM)
- Analytics
- Crashlytics
- Remote Config

---

## Project Structure

```
lib/
├── main.dart                    # App entry point
├── config/
│   ├── app_config.dart         # URLs, settings
│   └── firebase_options.dart   # Firebase config
├── models/                      # Data models
├── screens/                     # UI screens
├── widgets/                     # Reusable components
├── services/                    # Business logic
│   ├── api_service.dart        # Network calls
│   ├── inventory_service.dart  # Product data
│   └── review_prompt_service.dart
├── utils/
│   ├── image_utils.dart        # Hybrid image loading
│   └── pdf_utils.dart          # Hybrid PDF loading
└── theme/                       # App styling

assets/
├── products/                    # 116 bundled images (flat)
├── pdfs/specials/              # 4 bundled PDFs
├── product_manifest.json       # Image manifest
├── pdf_manifest.json           # PDF manifest
└── colors.json                 # Color swatches
```

---

## Development

### Running Locally
```bash
# Hot reload enabled
flutter run

# Specific device
flutter run -d emulator-5554
```

### Building Release
```bash
# Android
flutter build appbundle --release

# iOS  
flutter build ios --release
```

### Testing Offline
1. Run app with WiFi on
2. Browse products (images cache)
3. Turn off WiFi
4. Browse again - bundled images instant, others cached

### Testing Review Prompt
```bash
flutter run
# Close/reopen app 3 times
# Wait 5 seconds on home screen
# Native review dialog appears

# Reset for testing:
# Call ReviewPromptService.resetReviewState()
```

---

## Quarterly Maintenance (Every 2 Months)

### Before Release
```bash
# 1. Update Flutter & dependencies
flutter upgrade
flutter pub upgrade

# 2. Re-bundle latest assets
cd /Users/prabuvajjiram/Documents/newangelstones
python3 bundle_assets.py

# 3. Verify no errors
cd mobile_app
flutter analyze --no-fatal-infos
flutter clean && flutter pub get

# 4. Test offline mode (WiFi off)
flutter run
```

### Version Bump
Update in `pubspec.yaml`:
```yaml
version: X.Y.Z+BUILD  # X.Y.Z = semantic version, BUILD = build number
```

### Critical Tests
- [ ] Product images load (online + offline)
- [ ] PDFs open in viewer
- [ ] Search works
- [ ] Forms submit successfully
- [ ] Push notifications work
- [ ] Review prompt appears (3 launches)

---

## Deployment

### Android (Play Store)
```bash
flutter clean
flutter pub get
flutter build appbundle --release

# Upload: build/app/outputs/bundle/release/app-release.aab
```

**Android 15+ Checklist:**
- [ ] `targetSdkVersion="35"` or higher
- [ ] Edge-to-edge works (status/nav bars transparent)
- [ ] No deprecated API warnings
- [ ] Test on Android 15 emulator
- [ ] Upload to Internal Testing first

### iOS (App Store)
```bash
flutter clean
flutter pub get  
flutter build ios --release

# Open Xcode, archive, and upload
```

---

## Troubleshooting

### iOS Build Fails
```bash
rm -rf ios/Pods ios/Podfile.lock
cd ios && pod install --repo-update && cd ..
flutter clean && flutter pub get
```

### Android License Errors
```bash
flutter doctor --android-licenses
```

### Images Not Loading Offline
1. Verify bundling: `ls assets/products/ | wc -l` (should be ~116)
2. Check manifest: `cat assets/product_manifest.json`
3. Run `flutter pub get` to register assets
4. Check logs for "✅ Found bundled asset" messages

### Python Errors
```bash
pip3 install --upgrade Pillow requests
python3 --version  # Needs 3.7+
```

---

## File Locations

### Important Files
- **Main config**: `lib/config/app_config.dart`
- **Bundle script**: `/Users/prabuvajjiram/Documents/newangelstones/bundle_assets.py`
- **Firebase config**: `lib/config/firebase_options.dart`
- **Image loading**: `lib/utils/image_utils.dart`
- **PDF loading**: `lib/utils/pdf_utils.dart`

### Asset Manifests
- Products: `assets/product_manifest.json` (auto-generated)
- PDFs: `assets/pdf_manifest.json` (auto-generated)

---

## Performance

### App Size
- Base: ~50 MB
- With bundled assets: ~65 MB
- Industry standard for B2B (Google Play ~200MB, Costco ~300MB)

### Load Times
- Bundled images: <10ms (instant)
- Network images (first): 200-500ms
- Cached images: <50ms

---

## Support

### Common Commands
```bash
flutter doctor -v           # Check setup
flutter clean              # Clean build
flutter pub get            # Get dependencies
flutter analyze            # Check for errors
flutter pub outdated       # Check updates
```

### Debug Logs
Look for emoji indicators:
- ✅ Success
- ❌ Error
- ⚠️ Warning
- 📡 Network fetch
- 💾 Cache hit

### Getting Help
1. Check `flutter doctor -v`
2. Run `flutter clean && flutter pub get`
3. Check console logs for errors
4. Verify API endpoints accessible

---

## Future Enhancements

### Planned
- [ ] WebP format for smaller images
- [ ] Selective bundling (popular categories only)
- [ ] Progressive background downloads
- [ ] Delta updates (changed images only)

### Metrics to Track
- [ ] Offline usage rate
- [ ] Image load times (bundle vs network)
- [ ] Review conversion rate
- [ ] Bandwidth savings
- [ ] App size impact on downloads

---

**Last Updated**: December 6, 2025  
**Maintained By**: Angel Granites Development Team
