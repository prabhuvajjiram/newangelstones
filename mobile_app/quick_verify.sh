#!/bin/bash

# Quick Android 15+ Compatibility Verification
# Fast checks before uploading to Google Play Console

echo "🚀 Quick Android 15+ Compatibility Check"
echo "========================================"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_check() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
        FAILED=1
    fi
}

FAILED=0

echo ""
echo "1. Checking for deprecated XML attributes..."
DEPRECATED_XML=$(grep -r "android:statusBarColor\|android:navigationBarColor\|android:navigationBarDividerColor" android/app/src/main/res/ 2>/dev/null || true)
if [ -z "$DEPRECATED_XML" ]; then
    print_check 0 "No deprecated XML attributes"
else
    print_check 1 "Found deprecated XML attributes"
    echo "$DEPRECATED_XML"
fi

echo ""
echo "2. Verifying Target SDK..."
TARGET_SDK=$(grep "targetSdk" android/app/build.gradle.kts | grep -o '[0-9]\+')
if [ "$TARGET_SDK" = "35" ]; then
    print_check 0 "Target SDK is 35 (Android 15)"
else
    print_check 1 "Target SDK is $TARGET_SDK, should be 35"
fi

echo ""
echo "3. Flutter Analysis..."
flutter analyze > /dev/null 2>&1
print_check $? "Flutter analysis passed"

echo ""
echo "4. SystemUIService implementation..."
if [ -f "lib/services/system_ui_service.dart" ]; then
    print_check 0 "SystemUIService exists"
else
    print_check 1 "SystemUIService missing"
fi

echo ""
echo "5. MainActivity edge-to-edge setup..."
MAINACTIVITY_PATH=$(find android/app/src/main/kotlin -name "MainActivity.kt" 2>/dev/null | head -1)
if [ -n "$MAINACTIVITY_PATH" ] && grep -q "WindowCompat.setDecorFitsSystemWindows" "$MAINACTIVITY_PATH"; then
    print_check 0 "MainActivity configured for edge-to-edge"
else
    print_check 1 "MainActivity edge-to-edge setup missing"
fi

echo ""
echo "6. Build test..."
flutter build apk --debug > /dev/null 2>&1
print_check $? "Debug build successful"

echo ""
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 ALL CHECKS PASSED!${NC}"
    echo ""
    echo "✅ Your app should be compatible with Android 15+"
    echo "✅ Deprecated API warnings should be resolved"
    echo "✅ Ready for Google Play Console upload"
    echo ""
    echo "📋 To build for release:"
    echo "flutter build appbundle --release"
else
    echo -e "${RED}❌ SOME CHECKS FAILED${NC}"
    echo "Please fix the issues above before uploading"
    exit 1
fi
