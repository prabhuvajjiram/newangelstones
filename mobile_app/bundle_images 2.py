#!/usr/bin/env python3
"""
Image Bundling Script for Angel Granites Mobile App

This script downloads all product images from the Angel Stones API,
optimizes them for mobile (resize + compress), and organizes them 
as Flutter assets for offline availability.

Requirements:
    pip install Pillow

Usage:
    python3 bundle_images.py

The script will:
1. Fetch all product categories from the API
2. Download all images from each category
3. Optimize for mobile (max 1024px, WebP/JPEG, 85% quality)
4. Organize them in assets/products/ directory
5. Generate a manifest file for the app
6. Update pubspec.yaml with asset declarations
"""

import os
import sys
import json
import urllib.request
import urllib.parse
from pathlib import Path
from typing import List, Dict, Set, Tuple
import time

try:
    from PIL import Image
    import io
except ImportError:
    print("❌ Error: Pillow library not found")
    print("📦 Install it with: pip3 install Pillow")
    sys.exit(1)

# Configuration
BASE_URL = "https://www.theangelstones.com"
API_ENDPOINT = f"{BASE_URL}/get_directory_files.php"
ASSETS_DIR = Path(__file__).parent / "assets" / "products"
MANIFEST_FILE = Path(__file__).parent / "assets" / "product_manifest.json"
MAX_RETRIES = 3
RETRY_DELAY = 2  # seconds

# Image optimization settings
MAX_IMAGE_SIZE = 1024  # Max width/height in pixels for mobile
IMAGE_QUALITY = 85     # JPEG quality (1-100)
USE_WEBP = False       # Use WebP format (better compression, but check Flutter support)
PROGRESSIVE_JPEG = True  # Progressive loading for better UX


def fetch_json(url: str, retries: int = MAX_RETRIES) -> Dict:
    """Fetch JSON data from URL with retries"""
    for attempt in range(retries):
        try:
            with urllib.request.urlopen(url, timeout=30) as response:
                return json.loads(response.read().decode('utf-8'))
        except Exception as e:
            if attempt < retries - 1:
                print(f"⚠️  Retry {attempt + 1}/{retries} after error: {e}")
                time.sleep(RETRY_DELAY)
            else:
                raise Exception(f"Failed to fetch {url}: {e}")

def optimize_image(image_data: bytes) -> Tuple[bytes, str]:
    """
    Optimize image for mobile display
    Returns: (optimized_bytes, format)
    """
    try:
        # Open image from bytes
        img = Image.open(io.BytesIO(image_data))
        
        # Convert RGBA to RGB if saving as JPEG
        if img.mode in ('RGBA', 'LA', 'P') and not USE_WEBP:
            # Create white background
            background = Image.new('RGB', img.size, (255, 255, 255))
            if img.mode == 'P':
                img = img.convert('RGBA')
            background.paste(img, mask=img.split()[-1] if img.mode in ('RGBA', 'LA') else None)
            img = background
        
        # Calculate new size maintaining aspect ratio
        width, height = img.size
        if width > MAX_IMAGE_SIZE or height > MAX_IMAGE_SIZE:
            if width > height:
                new_width = MAX_IMAGE_SIZE
                new_height = int(height * (MAX_IMAGE_SIZE / width))
            else:
                new_height = MAX_IMAGE_SIZE
                new_width = int(width * (MAX_IMAGE_SIZE / height))
            
            # High-quality resize
            img = img.resize((new_width, new_height), Image.Resampling.LANCZOS)
        
        # Save optimized image to bytes
        output = io.BytesIO()
        if USE_WEBP:
            img.save(output, format='WEBP', quality=IMAGE_QUALITY, method=6)
            format_ext = 'webp'
        else:
            img.save(output, format='JPEG', quality=IMAGE_QUALITY, 
                    optimize=True, progressive=PROGRESSIVE_JPEG)
            format_ext = 'jpg'
        
        return output.getvalue(), format_ext
        
    except Exception as e:
        print(f"    ⚠️  Optimization failed: {e}, using original")
        return image_data, 'jpg'

def download_image(url: str, dest_path: Path, retries: int = MAX_RETRIES) -> bool:
    """Download and optimize an image file with retries"""
    for attempt in range(retries):
        try:
            # Download image data
            with urllib.request.urlopen(url, timeout=30) as response:
                image_data = response.read()
            
            # Optimize image
            optimized_data, format_ext = optimize_image(image_data)
            
            # Update dest_path extension if needed
            if USE_WEBP or format_ext != dest_path.suffix[1:]:
                dest_path = dest_path.with_suffix(f'.{format_ext}')
            
            # Save optimized image
            with open(dest_path, 'wb') as f:
                f.write(optimized_data)
            
            # Print size reduction info
            original_size = len(image_data) / 1024
            optimized_size = len(optimized_data) / 1024
            reduction = ((original_size - optimized_size) / original_size * 100) if original_size > 0 else 0
            
            if reduction > 5:  # Only print if significant reduction
                print(f"({original_size:.0f}KB → {optimized_size:.0f}KB, -{reduction:.0f}%)", end=' ')
            
            return True
            
        except Exception as e:
            if attempt < retries - 1:
                print(f"    ⚠️  Retry {attempt + 1}/{retries} for {dest_path.name}")
                time.sleep(RETRY_DELAY)
            else:
                print(f"    ❌ Failed to download {url}: {e}")
                return Falsee:
            if attempt < retries - 1:
                print(f"    ⚠️  Retry {attempt + 1}/{retries} for {dest_path.name}")
                time.sleep(RETRY_DELAY)
            else:
                print(f"    ❌ Failed to download {url}: {e}")
                return False

        # Sanitize filename
        safe_filename = sanitize_filename(img_name)
        if not safe_filename.endswith(('.jpg', '.jpeg', '.png', '.gif', '.webp')):
            # Try to get extension from path
            ext = Path(img_path).suffix
            if ext:
                safe_filename += ext
            else:
                safe_filename += '.jpg'  # Default extension
        
        # Remove extension since optimize_image may change it
        safe_filename_noext = Path(safe_filename).stem
        # Download and optimize
        print(f"   [{idx}/{len(images)}] {safe_filename_noext}...", end=' ')
        if download_image(img_url, dest_path):
            # Get actual saved filename (extension may have changed)
            actual_files = list(category_dir.glob(f"{safe_filename_noext}.*"))
            if actual_files:
                actual_file = actual_files[0]
                actual_filename = actual_file.name
                print("✅")
                downloaded_images.append({
                    'original_name': img_name,
                    'filename': actual_filename,
                    'asset_path': f"assets/products/{safe_category_name}/{actual_filename}",
                    'size': actual_file.stat().st_size
                })
            else:
                print("❌")
        else:
            print("❌")
    
    return data.get('files', [])

def sanitize_filename(filename: str) -> str:
    """Sanitize filename for Flutter assets"""
    # Remove or replace special characters
    safe_name = filename.replace(' ', '_').replace('-', '_')
    # Keep only alphanumeric, underscore, and extension
    return ''.join(c for c in safe_name if c.isalnum() or c in '._')

def sanitize_category_name(category: str) -> str:
    """Sanitize category name for directory"""
    # Replace spaces and special chars with underscores
    return category.replace(' ', '_').replace('-', '_').replace('/', '_')

def download_category_images(category_name: str) -> Dict:
    """Download all images for a category and return metadata"""
    print(f"\n📂 Processing category: {category_name}")
    
    # Get images for this category
    images = get_category_images(category_name)
    
    if not images:
        print(f"   ⚠️  No images found")
        return {'name': category_name, 'images': []}
    
    print(f"   Found {len(images)} images")
    
    # Create category directory
    safe_category_name = sanitize_category_name(category_name)
    category_dir = ASSETS_DIR / safe_category_name
    category_dir.mkdir(parents=True, exist_ok=True)
    
    # Download each image
    downloaded_images = []
    for idx, img in enumerate(images, 1):
        img_name = img.get('name', '')
        img_path = img.get('path', '')
        
        if not img_path:
            continue
        
        # Build full URL
        if img_path.startswith('http'):
            img_url = img_path
        else:
            img_url = f"{BASE_URL}/{img_path}"
        
        # Sanitize filename
        safe_filename = sanitize_filename(img_name)
        if not safe_filename.endswith(('.jpg', '.jpeg', '.png', '.gif')):
            # Try to get extension from path
            ext = Path(img_path).suffix
            if ext:
                safe_filename += ext
            else:
                safe_filename += '.jpg'  # Default extension
        
        dest_path = category_dir / safe_filename
        
        # Download
        print(f"   [{idx}/{len(images)}] {safe_filename}...", end=' ')
        if download_image(img_url, dest_path):
            print("✅")
            downloaded_images.append({
                'original_name': img_name,
                'filename': safe_filename,
                'asset_path': f"assets/products/{safe_category_name}/{safe_filename}",
                'size': dest_path.stat().st_size if dest_path.exists() else 0
            })
        else:
            print("❌")
    
    print(f"   ✅ Downloaded {len(downloaded_images)}/{len(images)} images")
    
    return {
        'name': category_name,
        'safe_name': safe_category_name,
        'images': downloaded_images,
        'total_count': len(images),
        'downloaded_count': len(downloaded_images)
    }

def generate_manifest(categories: List[Dict]) -> None:
    """Generate JSON manifest file"""
    print("\n📝 Generating manifest...")
    
    manifest = {
        'version': '1.0',
        'generated_at': time.strftime('%Y-%m-%d %H:%M:%S'),
        'base_url': BASE_URL,
        'categories': categories,
        'total_images': sum(cat['downloaded_count'] for cat in categories),
        'total_size_bytes': sum(
            sum(img['size'] for img in cat['images'])
            for cat in categories
        )
    }
    
    with open(MANIFEST_FILE, 'w') as f:
        json.dump(manifest, f, indent=2)
    
    total_size_mb = manifest['total_size_bytes'] / (1024 * 1024)
    print(f"✅ Manifest created: {manifest['total_images']} images, {total_size_mb:.2f} MB")
    print(f"   Saved to: {MANIFEST_FILE}")

def update_pubspec_yaml(categories: List[Dict]) -> None:
    """Update pubspec.yaml with asset declarations"""
    print("\n📝 Updating pubspec.yaml...")
    
    pubspec_path = Path(__file__).parent / "pubspec.yaml"
    
    if not pubspec_path.exists():
        print("❌ pubspec.yaml not found")
        return
    
    # Read current pubspec.yaml
    with open(pubspec_path, 'r') as f:
        lines = f.readlines()
    
    # Find the assets section
    assets_index = -1
    for i, line in enumerate(lines):
        if 'assets:' in line and not line.strip().startswith('#'):
            assets_index = i
            break
    
    if assets_index == -1:
        print("❌ Could not find assets section in pubspec.yaml")
        return
    
    # Build new asset entries
    new_assets = []
    new_assets.append("    - assets/product_manifest.json\n")
    
    for category in categories:
        safe_name = category['safe_name']
        new_assets.append(f"    - assets/products/{safe_name}/\n")
    
    # Find where to insert (after existing assets)
    insert_index = assets_index + 1
    while insert_index < len(lines) and lines[insert_index].startswith('    -'):
        insert_index += 1
    
    # Check if products assets already exist
    has_products = any('assets/products/' in line for line in lines)
    has_manifest = any('product_manifest.json' in line for line in lines)
    
    if not has_products or not has_manifest:
        # Insert new assets
        for asset in reversed(new_assets):
            if asset.strip() not in ''.join(lines):
                lines.insert(insert_index, asset)
        
        # Write back
        with open(pubspec_path, 'w') as f:
            f.writelines(lines)
        
        print("✅ pubspec.yaml updated")
    else:
        print("ℹ️  pubspec.yaml already has product assets")

def main():
    """Main execution function"""
    print("🚀 Angel Granites Image Bundling Script")
    print("=" * 50)
    
    try:
        # Create assets directory
        ASSETS_DIR.mkdir(parents=True, exist_ok=True)
        
        # Fetch categories
        categories = get_product_categories()
        
        # Download images for each category
        category_metadata = []
        for category in categories:
            category_name = category.get('name', '')
            if not category_name:
                continue
            
            metadata = download_category_images(category_name)
            category_metadata.append(metadata)
        
        # Generate manifest
        generate_manifest(category_metadata)
        
        # Update pubspec.yaml
        update_pubspec_yaml(category_metadata)
        
        print("\n" + "=" * 50)
        print("✅ Image bundling complete!")
        print(f"   Total categories: {len(category_metadata)}")
        total_images = sum(cat['downloaded_count'] for cat in category_metadata)
        print(f"   Total images: {total_images}")
        print(f"   Location: {ASSETS_DIR}")
        print("\n⚠️  IMPORTANT: Run 'flutter pub get' to register new assets")
        print("⚠️  NOTE: This will significantly increase app size")
        
    except KeyboardInterrupt:
        print("\n\n⚠️  Interrupted by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

if __name__ == '__main__':
    main()
