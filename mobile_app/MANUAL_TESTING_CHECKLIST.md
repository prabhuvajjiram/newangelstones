# Manual Testing Checklist for Android 15+ Compatibility

## Before Upload to Play Console

### 1. Automated Verification ✅
Run the quick verification script:
```bash
./quick_verify.sh
```

### 2. Build App Bundle for Play Store
```bash
# Clean and build release app bundle
flutter clean
flutter pub get
flutter build appbundle --release

# Verify the bundle was created
ls -la build/app/outputs/bundle/release/app-release.aab
```

### 3. Visual Testing on Device/Emulator

#### Create Android 15 Emulator (if you don't have one)
1. Open Android Studio
2. Tools → AVD Manager
3. Create Virtual Device
4. Choose any device (Pixel 7 recommended)
5. Select **API Level 35** (Android 15)
6. Download system image if needed
7. Create and start emulator

#### Install and Test
```bash
# Install on emulator/device
flutter install --release
```

#### Visual Checklist
- [ ] **Status Bar**: Transparent/translucent, content doesn't overlap
- [ ] **Navigation Bar**: Transparent/translucent, proper spacing
- [ ] **Home Screen**: Content properly inset from system bars
- [ ] **Gallery Screen**: Full-screen immersive mode works
- [ ] **Rotation**: Edge-to-edge works in both portrait/landscape
- [ ] **Notch/Cutout**: Content doesn't get hidden behind notch
- [ ] **Dark/Light Mode**: System bars adapt properly

### 4. Advanced Verification Tools

#### A. Android Studio APK Analyzer
1. Open Android Studio
2. **Build → Analyze APK**
3. Select: `build/app/outputs/flutter-apk/app-release.apk`
4. Check **AndroidManifest.xml** for:
   - `targetSdkVersion="35"`
   - No deprecated attributes

#### B. Bundletool Analysis (Google's Official Tool)
```bash
# Download bundletool from: https://github.com/google/bundletool/releases
# Then run:

# Validate the app bundle
java -jar bundletool.jar validate --bundle=build/app/outputs/bundle/release/app-release.aab

# Should output: "No issues found."
```

#### C. Gradle Lint Report
```bash
cd android
./gradlew lint

# Check report at: android/app/build/reports/lint-results.html
# Look for any deprecation warnings
```

### 5. Play Console Pre-Upload Check

#### Internal Testing Track (Recommended)
1. Upload to **Internal Testing** track first
2. Wait for processing (usually 10-15 minutes)
3. Check for warnings in Play Console
4. If no warnings → promote to production

#### Direct Production Upload
Only if you're confident after all above tests pass.

### 6. Common Issues and Solutions

#### Issue: "Edge-to-edge may not display for all users"
**Solution**: ✅ Fixed with our implementation
- MainActivity uses `WindowCompat.setDecorFitsSystemWindows(window, false)`
- SystemUIService handles system bar colors programmatically

#### Issue: "Your app uses deprecated APIs"
**Solution**: ✅ Fixed by removing XML attributes
- No `android:statusBarColor` in any styles.xml
- No `android:navigationBarColor` in any styles.xml
- No `android:navigationBarDividerColor` usage

#### Issue: Content behind system bars
**Solution**: Use EdgeToEdgeWrapper in your screens
```dart
return EdgeToEdgeWrapper.normal(
  child: YourScreenContent(),
);
```

### 7. Final Pre-Upload Checklist

- [ ] `./quick_verify.sh` passes all checks
- [ ] App bundle builds successfully
- [ ] Visual testing on Android 15+ device/emulator
- [ ] No lint warnings about deprecated APIs
- [ ] Internal testing upload (optional but recommended)

### 8. Upload Commands

```bash
# Final build for Play Store
flutter clean
flutter pub get
flutter build appbundle --release

# The file to upload:
# build/app/outputs/bundle/release/app-release.aab
```

### 9. Post-Upload Monitoring

After uploading to Play Console:
1. Wait for processing (10-30 minutes)
2. Check **Release → Setup → App content** for warnings
3. Check **Release → Testing → Internal testing** for any issues
4. Monitor crash reports in first 24 hours

### 10. Emergency Rollback Plan

If issues are found after release:
1. **Halt rollout** in Play Console (if gradual rollout is enabled)
2. **Roll back** to previous version
3. Fix issues and re-upload

---

## Success Indicators

✅ **No warnings in Play Console after upload**
✅ **App displays correctly on Android 15+ devices**
✅ **No crashes related to system UI**
✅ **Edge-to-edge experience works as expected**

Your app is now ready for Android 15+ and should pass all Play Console checks! 🎉
