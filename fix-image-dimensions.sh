#!/bin/bash

# Script to add width/height attributes to images for better CLS scores
# This improves Cumulative Layout Shift by reserving space for images before they load

echo "🔧 Adding image dimensions to prevent layout shifts..."

# Backup the original file
cp index.html index.html.backup-perf

# Add dimensions to title-star.svg images (30x30)
sed -i '' 's|<img src="images/title-star.svg" alt="Angel Stones">|<img src="images/title-star.svg" alt="Angel Stones" width="30" height="30">|g' index.html

# Add dimensions to product category images (350x350)
sed -i '' 's|<img src="images/products/MBNA_2025/AG-116.jpg" alt="MBNA 2025" loading="lazy"|<img src="images/products/MBNA_2025/AG-116.jpg" alt="MBNA 2025" width="350" height="350" loading="lazy" decoding="async"|g' index.html

sed -i '' 's|<img src="images/products/Monuments/granite-monuments-project02.jpg" alt="Monuments" loading="lazy"|<img src="images/products/Monuments/granite-monuments-project02.jpg" alt="Monuments" width="350" height="350" loading="lazy" decoding="async"|g' index.html

sed -i '' 's|<img src="images/products/columbarium/customized-designs-project03.jpg" alt="Columbarium" loading="lazy"|<img src="images/products/columbarium/customized-designs-project03.jpg" alt="Columbarium" width="350" height="350" loading="lazy" decoding="async"|g' index.html

sed -i '' 's|<img src="images/products/Designs/chess.jpg" alt="Designs" loading="lazy"|<img src="images/products/Designs/chess.jpg" alt="Designs" width="350" height="350" loading="lazy" decoding="async"|g' index.html

sed -i '' 's|<img src="images/products/Benches/Fountain2.jpg" alt="Benches" loading="lazy"|<img src="images/products/Benches/Fountain2.jpg" alt="Benches" width="350" height="350" loading="lazy" decoding="async"|g' index.html

# Add dimensions to quarry image (768x576)
sed -i '' 's|src="images/quarry-cropped-768.jpg"|src="images/quarry-cropped-768.jpg" width="768" height="576"|g' index.html

# Add dimensions to App Store badge
sed -i '' 's|<img alt="Download on the App Store" src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" class="app-badge-img" />|<img alt="Download on the App Store" src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" class="app-badge-img" width="135" height="40" />|g' index.html

echo "✅ Image dimensions added successfully!"
echo "📋 Backup saved to: index.html.backup-perf"
echo ""
echo "📊 Performance improvements:"
echo "   - Added explicit width/height to prevent CLS"
echo "   - Added decoding='async' for non-blocking rendering"
echo "   - Images will now reserve space before loading"
