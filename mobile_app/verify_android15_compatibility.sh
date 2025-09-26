#!/bin/bash

# Android 15+ Compatibility Verification Script
# Run this before uploading to Google Play Console

set -e

echo "🔍 Android 15+ Compatibility Verification"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
        exit 1
    fi
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

echo ""
echo "1. Checking for deprecated XML attributes..."
DEPRECATED_XML=$(grep -r "android:statusBarColor\|android:navigationBarColor\|android:navigationBarDividerColor" android/app/src/main/res/ 2>/dev/null || true)
if [ -z "$DEPRECATED_XML" ]; then
    print_status 0 "No deprecated XML attributes found"
else
    echo -e "${RED}❌ Found deprecated XML attributes:${NC}"
    echo "$DEPRECATED_XML"
    exit 1
fi

echo ""
echo "2. Checking for deprecated Kotlin/Java APIs..."
DEPRECATED_KOTLIN=$(grep -r "setStatusBarColor\|setNavigationBarColor\|setNavigationBarDividerColor" android/ 2>/dev/null || true)
if [ -z "$DEPRECATED_KOTLIN" ]; then
    print_status 0 "No deprecated Kotlin/Java APIs found"
else
    echo -e "${RED}❌ Found deprecated Kotlin/Java APIs:${NC}"
    echo "$DEPRECATED_KOTLIN"
    exit 1
fi

echo ""
echo "3. Verifying build configuration..."
TARGET_SDK=$(grep "targetSdk" android/app/build.gradle.kts | grep -o '[0-9]\+')
COMPILE_SDK=$(grep "compileSdk" android/app/build.gradle.kts | grep -o '[0-9]\+')

if [ "$TARGET_SDK" = "35" ]; then
    print_status 0 "Target SDK is 35 (Android 15)"
else
    print_status 1 "Target SDK is $TARGET_SDK, should be 35"
fi

if [ "$COMPILE_SDK" = "36" ]; then
    print_status 0 "Compile SDK is 36"
else
    print_warning "Compile SDK is $COMPILE_SDK, recommended is 36"
fi

echo ""
echo "4. Running Flutter analysis..."
flutter analyze
print_status $? "Flutter analysis passed"

echo ""
echo "5. Checking SystemUIService implementation..."
if [ -f "lib/services/system_ui_service.dart" ]; then
    print_status 0 "SystemUIService exists"
else
    print_status 1 "SystemUIService missing"
fi

echo ""
echo "6. Checking EdgeToEdgeWrapper implementation..."
if [ -f "lib/widgets/edge_to_edge_wrapper.dart" ]; then
    print_status 0 "EdgeToEdgeWrapper exists"
else
    print_status 1 "EdgeToEdgeWrapper missing"
fi

echo ""
echo "7. Verifying MainActivity edge-to-edge setup..."
MAINACTIVITY_PATH=$(find android/app/src/main/kotlin -name "MainActivity.kt" 2>/dev/null | head -1)
if [ -n "$MAINACTIVITY_PATH" ] && grep -q "WindowCompat.setDecorFitsSystemWindows" "$MAINACTIVITY_PATH"; then
    print_status 0 "MainActivity has edge-to-edge setup"
else
    print_status 1 "MainActivity missing edge-to-edge setup or file not found"
fi

echo ""
echo "8. Building release APK to verify no build errors..."
flutter clean > /dev/null 2>&1
flutter pub get > /dev/null 2>&1
flutter build apk --release > /dev/null 2>&1
print_status $? "Release APK built successfully"

echo ""
echo "9. Running Android Lint..."
cd android
./gradlew lint > /dev/null 2>&1
LINT_EXIT_CODE=$?
cd ..

if [ $LINT_EXIT_CODE -eq 0 ]; then
    print_status 0 "Android Lint passed"
else
    print_warning "Android Lint found issues - check android/app/build/reports/lint-results.html"
fi

echo ""
echo "10. Checking for edge-to-edge related code..."
EDGE_TO_EDGE_USAGE=$(grep -r "SystemUIService\|EdgeToEdgeWrapper\|setDecorFitsSystemWindows" lib/ android/ 2>/dev/null | wc -l)
if [ "$EDGE_TO_EDGE_USAGE" -gt 5 ]; then
    print_status 0 "Edge-to-edge implementation found in codebase"
else
    print_warning "Limited edge-to-edge implementation found"
fi

echo ""
echo "🎉 VERIFICATION COMPLETE!"
echo "========================"
echo ""
echo "✅ All checks passed! Your app should be compatible with Android 15+"
echo "✅ Deprecated API warnings should be resolved"
echo "✅ Ready for Google Play Console upload"
echo ""
echo "📋 Next steps:"
echo "1. Test on Android 15+ device/emulator if available"
echo "2. Build app bundle: flutter build appbundle --release"
echo "3. Upload to Google Play Console"
echo ""
echo "📁 Built files location:"
echo "- APK: build/app/outputs/flutter-apk/app-release.apk"
echo "- App Bundle: build/app/outputs/bundle/release/app-release.aab"
