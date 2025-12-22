#!/bin/bash

# Script to remove debug print statements for production build
# This will comment out debugPrint statements but keep them for future debugging

echo "🧹 Removing debug statements for production build..."

# Find all Dart files and comment out debugPrint statements
find lib -name "*.dart" -type f -exec sed -i '' 's/^\([[:space:]]*\)debugPrint(/\1\/\/ debugPrint(/g' {} \;

echo "✅ Debug statements have been commented out"
echo "📝 To restore debug statements, run: git checkout lib/"
