#!/bin/bash
# Quick script to rebuild features.bundle.js with updated inventory-modal.js

echo "🔨 Rebuilding features.bundle.js..."

cd "$(dirname "$0")"

# Backup current bundle
cp js/features.bundle.js js/features.bundle.js.backup

# The bundle contains these files in this order:
# 1. featured-products.js
# 2. color-carousel.js (needed for dynamic color loading!)
# 3. color-gallery.js  
# 4. promotion-banner.js
# 5. category-carousel.js
# 6. legal-modals.js
# 7. deep-linking.js
# 8. inventory-modal.js

cat > js/features.bundle.js << 'EOF'
// Features Bundle - Combined JavaScript for Angel Stones website
// Generated: $(date)
// Version: 2.4.0

EOF

# Combine all the source files
cat js/featured-products.js >> js/features.bundle.js
echo "" >> js/features.bundle.js
cat js/color-carousel.js >> js/features.bundle.js
echo "" >> js/features.bundle.js
cat js/color-gallery.js >> js/features.bundle.js
echo "" >> js/features.bundle.js
cat js/promotion-banner.js >> js/features.bundle.js
echo "" >> js/features.bundle.js
cat js/category-carousel.js >> js/features.bundle.js
echo "" >> js/features.bundle.js
cat js/legal-modals.js >> js/features.bundle.js
echo "" >> js/features.bundle.js
cat js/deep-linking.js >> js/features.bundle.js
echo "" >> js/features.bundle.js
cat js/inventory-modal.js >> js/features.bundle.js

echo "✅ Bundle rebuilt successfully!"
echo "📦 Size: $(wc -c < js/features.bundle.js | awk '{print int($1/1024)"KB"}')"
echo ""
echo "Old bundle backed up to: js/features.bundle.js.backup"
