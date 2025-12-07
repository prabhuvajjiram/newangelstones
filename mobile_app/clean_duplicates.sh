#!/bin/bash
# Clean duplicate files created by macOS/file watchers
# Run this if you see files with " 2.jpg", " 3.jpg" suffixes

echo "🧹 Cleaning duplicate files..."

# Count before
before_products=$(find assets/products -name "* 2.*" -o -name "* 3.*" -o -name "* 4.*" | wc -l | xargs)
before_colors=$(find assets/colors -name "* 2.*" -o -name "* 3.*" -o -name "* 4.*" | wc -l | xargs)

echo "Found $before_products duplicates in products/"
echo "Found $before_colors duplicates in colors/"

# Remove duplicates
find assets/products -name "* 2.*" -delete 2>/dev/null
find assets/products -name "* 3.*" -delete 2>/dev/null
find assets/products -name "* 4.*" -delete 2>/dev/null
find assets/products -name "* 5.*" -delete 2>/dev/null
find assets/colors -name "* 2.*" -delete 2>/dev/null
find assets/colors -name "* 3.*" -delete 2>/dev/null
find assets/colors -name "* 4.*" -delete 2>/dev/null
find assets/pdfs/specials -name "* 2.*" -delete 2>/dev/null
find assets/pdfs/specials -name "* 3.*" -delete 2>/dev/null

# Count after
after_products=$(ls assets/products/ | wc -l | xargs)
after_colors=$(ls assets/colors/ | wc -l | xargs)

echo ""
echo "✅ Cleanup complete!"
echo "Products: $after_products files"
echo "Colors: $after_colors files"
echo ""
echo "💡 Tip: If duplicates keep appearing, it's likely:"
echo "   1. macOS Finder/Preview creating copies when opening files"
echo "   2. VS Code file watcher conflicts"
echo "   3. Cloud sync (iCloud, Dropbox) creating conflicts"
echo ""
echo "To prevent:"
echo "   - Don't open image files from Finder while app is running"
echo "   - Close Preview/Photos apps"
echo "   - Add assets/ to .gitignore if not already there"
