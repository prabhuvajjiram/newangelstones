#!/usr/bin/env python3
"""
Unified Asset Bundling Script for Angel Stones Mobile App

This script automatically discovers and downloads ALL assets from the server:
- Product images from all categories
- PDF catalogs and flyers
- Any other assets you add to the server

NO HARDCODING - fully automatic discovery!
When you add new products or PDFs to the server, just run this script again.

Usage:
    python3 bundle_assets.py

Requirements:
    pip3 install -r requirements.txt
"""

import os
import sys
import json
import requests
from pathlib import Path
from datetime import datetime
from typing import List, Dict, Optional, Tuple
from io import BytesIO

# Try to import Pillow for image optimization (optional)
try:
    from PIL import Image
    PILLOW_AVAILABLE = True
except ImportError:
    PILLOW_AVAILABLE = False
    print("⚠️  Pillow not installed - images will not be optimized")
    print("   Install with: pip3 install Pillow")

# =============================================================================
# Configuration
# =============================================================================

BASE_URL = "https://www.theangelstones.com"
API_ENDPOINT = f"{BASE_URL}/get_directory_files.php"

# Asset discovery configuration
ASSET_ROOTS = [
    {"path": "products", "type": "images", "output": "assets/products"},
]

# API endpoint for specials (returns PDF list)
SPECIALS_API = f"{BASE_URL}/api/specials.php?action=list"

# API endpoint for colors (returns color images)
COLORS_API = f"{BASE_URL}/api/color.json"

# Image optimization settings (only used if Pillow is available)
MAX_IMAGE_SIZE = 1024  # Max width or height in pixels
IMAGE_QUALITY = 85     # JPEG quality (1-100)
PROGRESSIVE_JPEG = True

# File size limits
MAX_IMAGE_SIZE_MB = 5   # Warning threshold
MAX_PDF_SIZE_MB = 10    # Warning threshold

# Manifest files
IMAGE_MANIFEST = Path("assets/product_manifest.json")
PDF_MANIFEST = Path("assets/pdf_manifest.json")
PUBSPEC_PATH = Path("pubspec.yaml")

# =============================================================================
# Discovery Functions
# =============================================================================

def discover_files(directory: str, file_types: List[str] = None, depth: int = 0, max_depth: int = 3) -> List[Dict]:
    """
    Recursively discover all files in a directory on the server.
    
    Args:
        directory: Directory path on server
        file_types: Optional list of file extensions to filter (e.g., ['.jpg', '.pdf'])
        depth: Current recursion depth (internal use)
        max_depth: Maximum recursion depth to prevent infinite loops
    
    Returns:
        List of file dictionaries with metadata
    """
    if depth >= max_depth:
        print(f"   ⚠️  Max depth reached for {directory}")
        return []
    
    try:
        print(f"{'  ' * depth}🔍 Querying API: {directory}")
        params = {"directory": directory}
        response = requests.get(API_ENDPOINT, params=params, timeout=30)
        response.raise_for_status()
        
        data = response.json()
        files = data.get("files", [])
        
        if not files:
            print(f"{'  ' * depth}   (empty)")
            return []
        
        print(f"{'  ' * depth}   Got {len(files)} items")
        discovered = []
        
        for file_info in files:
            file_name = file_info.get("name", "")
            file_path = file_info.get("path", "")
            
            # Check if the PATH has a file extension (including query params)
            # Remove query params first
            path_without_query = file_path.split('?')[0] if '?' in file_path else file_path
            path_lower = path_without_query.lower()
            has_file_extension = any(path_lower.endswith(ext) for ext in ['.jpg', '.jpeg', '.png', '.webp', '.gif', '.pdf'])
            
            # If path has file extension, it's definitely a file
            if has_file_extension:
                # Check if it matches our filter
                if file_types:
                    if any(path_lower.endswith(ext) for ext in file_types):
                        discovered.append(file_info)
                else:
                    discovered.append(file_info)
            else:
                # No file extension in path = it's a directory, recurse into it
                if file_path:
                    # Extract the path relative to "images/"
                    subdir_query = file_path
                    if file_path.startswith("images/"):
                        subdir_query = file_path[7:]  # Remove "images/" prefix
                    
                    print(f"{'  ' * depth}   📁 Subdirectory: {file_name}")
                    discovered.extend(discover_files(subdir_query, file_types, depth + 1, max_depth))
        
        return discovered
        
    except requests.exceptions.RequestException as e:
        print(f"{'  ' * depth}   ❌ Request error: {e}")
        return []
    except json.JSONDecodeError as e:
        print(f"{'  ' * depth}   ❌ JSON error: {e}")
        return []


def discover_all_assets() -> Dict[str, List[Dict]]:
    """
    Discover all assets from configured root directories.
    
    Returns:
        Dictionary with asset types as keys and file lists as values
    """
    print("🔍 Discovering assets from server...")
    
    all_assets = {}
    
    # Discover dynamic assets (images from API)
    for root_config in ASSET_ROOTS:
        path = root_config["path"]
        asset_type = root_config["type"]
        
        print(f"\n📂 Scanning: {path}")
        
        # Determine file types to look for
        if asset_type == "images":
            file_types = ['.jpg', '.jpeg', '.png', '.webp']
        elif asset_type == "pdfs":
            file_types = ['.pdf']
        else:
            file_types = None  # Get all files
        
        # Discover files
        files = discover_files(path, file_types)
        
        if files:
            print(f"   ✅ Found {len(files)} {asset_type}")
            all_assets[asset_type] = all_assets.get(asset_type, []) + [
                {**f, "root_config": root_config} for f in files
            ]
        else:
            print(f"   ⚠️  No {asset_type} found")
    
    # Discover PDFs from specials API
    print(f"\n📄 Discovering PDFs from specials API...")
    try:
        response = requests.get(SPECIALS_API, timeout=30)
        response.raise_for_status()
        data = response.json()
        
        if data.get("success") and "specials" in data:
            specials = data["specials"]
            pdf_list = []
            
            for special in specials:
                pdf_filename = special.get("filename", "")
                pdf_url_path = special.get("url", "").lstrip("/")  # Remove leading /
                display_name = special.get("title", pdf_filename)
                
                if pdf_filename and pdf_url_path:
                    pdf_list.append({
                        "name": pdf_filename,
                        "display_name": display_name,
                        "path": pdf_url_path,
                        "category": "specials",
                        "size": special.get("size", ""),
                        "root_config": {
                            "path": "/".join(pdf_url_path.split('/')[:-1]),
                            "type": "pdfs",
                            "output": "assets/pdfs/specials"
                        }
                    })
                    print(f"   📄 {display_name} ({special.get('size', 'unknown size')})")
            
            all_assets["pdfs"] = all_assets.get("pdfs", []) + pdf_list
            print(f"   ✅ Found {len(pdf_list)} PDFs")
        else:
            print(f"   ⚠️  No PDFs found in specials API")
    
    except Exception as e:
        print(f"   ❌ Error fetching PDFs from specials API: {e}")
    
    # Discover color images from color API
    print(f"\n🎨 Discovering color images from color API...")
    try:
        response = requests.get(COLORS_API, timeout=30)
        response.raise_for_status()
        data = response.json()
        
        color_list = []
        if "itemListElement" in data:
            for item in data["itemListElement"]:
                color_item = item.get("item", {})
                color_name = color_item.get("name", "")
                images = color_item.get("image", [])
                
                if images and len(images) > 0:
                    image_url = images[0].get("url", "")
                    if image_url:
                        # Extract path from URL
                        color_path = image_url.replace(BASE_URL + "/", "")
                        color_filename = color_path.split("/")[-1]
                        
                        color_list.append({
                            "name": color_filename,
                            "display_name": color_name,
                            "path": color_path,
                            "category": "colors",
                            "root_config": {
                                "path": "images/colors",
                                "type": "images",
                                "output": "assets/colors"
                            }
                        })
                        print(f"   🎨 {color_name}")
            
            all_assets["images"] = all_assets.get("images", []) + color_list
            print(f"   ✅ Found {len(color_list)} color images")
        else:
            print(f"   ⚠️  No colors found in API")
    
    except Exception as e:
        print(f"   ❌ Error fetching colors from API: {e}")
    
    return all_assets


# =============================================================================
# Image Processing Functions
# =============================================================================

def optimize_image(image_data: bytes) -> Tuple[bytes, Dict]:
    """
    Optimize an image by resizing and compressing.
    
    Args:
        image_data: Original image bytes
        
    Returns:
        Tuple of (optimized_bytes, stats_dict)
    """
    if not PILLOW_AVAILABLE:
        return image_data, {"optimized": False}
    
    try:
        # Open image
        img = Image.open(BytesIO(image_data))
        original_size = len(image_data)
        original_format = img.format
        
        # Convert RGBA to RGB for JPEG
        if img.mode == 'RGBA':
            background = Image.new('RGB', img.size, (255, 255, 255))
            background.paste(img, mask=img.split()[3])
            img = background
        elif img.mode != 'RGB':
            img = img.convert('RGB')
        
        # Resize if needed
        if img.width > MAX_IMAGE_SIZE or img.height > MAX_IMAGE_SIZE:
            img.thumbnail((MAX_IMAGE_SIZE, MAX_IMAGE_SIZE), Image.Resampling.LANCZOS)
        
        # Save optimized
        output = BytesIO()
        save_kwargs = {
            'format': 'JPEG',
            'quality': IMAGE_QUALITY,
            'optimize': True,
        }
        if PROGRESSIVE_JPEG:
            save_kwargs['progressive'] = True
        
        img.save(output, **save_kwargs)
        optimized_data = output.getvalue()
        optimized_size = len(optimized_data)
        
        reduction = ((original_size - optimized_size) / original_size) * 100
        
        return optimized_data, {
            "optimized": True,
            "original_size": original_size,
            "optimized_size": optimized_size,
            "reduction_percent": round(reduction, 1),
            "original_format": original_format,
        }
        
    except Exception as e:
        print(f"⚠️  Optimization failed: {e}")
        return image_data, {"optimized": False, "error": str(e)}


# =============================================================================
# Download Functions
# =============================================================================

def download_asset(file_info: Dict, output_path: Path, optimize: bool = False) -> Optional[Dict]:
    """
    Download an asset file from the server.
    
    Args:
        file_info: File information from discovery
        output_path: Local path to save file
        optimize: Whether to optimize images
        
    Returns:
        Asset metadata dictionary or None on failure
    """
    try:
        # Get URL
        file_path = file_info.get("path", "")
        if not file_path:
            return None
        
        url = f"{BASE_URL}/{file_path}"
        # Clean filename - remove query parameters
        raw_name = file_info.get("name", file_path.split('/')[-1])
        file_name = raw_name.split('?')[0] if '?' in raw_name else raw_name
        
        # Check if already exists
        if output_path.exists():
            # Force remove to prevent macOS creating "file 2.jpg" duplicates
            output_path.unlink()
            print(f"   🗑️  Removed existing: {file_name}")
        
        # Ensure parent directory exists
        output_path.parent.mkdir(parents=True, exist_ok=True)
        
        # Download
        response = requests.get(url, timeout=60, stream=True)
        response.raise_for_status()
        
        # Get data
        data = response.content
        original_size = len(data)
        
        # Optimize if requested and it's an image
        stats = {}
        if optimize and file_name.lower().endswith(('.jpg', '.jpeg', '.png', '.webp')):
            data, stats = optimize_image(data)
        
        # Create parent directory
        output_path.parent.mkdir(parents=True, exist_ok=True)
        
        # Write file
        with open(output_path, 'wb') as f:
            f.write(data)
        
        final_size = len(data)
        final_size_mb = final_size / (1024 * 1024)
        
        # Build metadata
        metadata = {
            "name": file_name,
            "path": str(output_path),
            "url": url,
            "size_bytes": final_size,
            "size_mb": round(final_size_mb, 2),
            "downloaded_at": datetime.now().isoformat(),
        }
        
        # Add optimization stats if available
        if stats.get("optimized"):
            metadata["optimization"] = stats
            reduction = stats.get("reduction_percent", 0)
            print(f"   ✅ {file_name} ({final_size_mb:.2f} MB, {reduction:.0f}% smaller)")
        else:
            print(f"   ✅ {file_name} ({final_size_mb:.2f} MB)")
        
        # Warn about large files
        is_image = file_name.lower().endswith(('.jpg', '.jpeg', '.png', '.webp'))
        is_pdf = file_name.lower().endswith('.pdf')
        
        if is_image and final_size_mb > MAX_IMAGE_SIZE_MB:
            print(f"   ⚠️  Large image file!")
        elif is_pdf and final_size_mb > MAX_PDF_SIZE_MB:
            print(f"   ⚠️  Large PDF file!")
        
        return metadata
        
    except requests.exceptions.RequestException as e:
        print(f"   ❌ Download failed: {e}")
        return None
    except IOError as e:
        print(f"   ❌ Write failed: {e}")
        return None


def download_all_assets(assets: Dict[str, List[Dict]]) -> Dict[str, List[Dict]]:
    """
    Download all discovered assets.
    
    Args:
        assets: Dictionary of asset types and file lists
        
    Returns:
        Dictionary of downloaded asset metadata by type
    """
    print("\n📥 Downloading assets...")
    
    downloaded = {}
    
    for asset_type, files in assets.items():
        print(f"\n{'='*70}")
        print(f"Processing {len(files)} {asset_type}")
        print('='*70)
        
        downloaded[asset_type] = []
        
        for file_info in files:
            root_config = file_info.get("root_config", {})
            output_base = Path(root_config.get("output", f"assets/{asset_type}"))
            
            # Get the file path and clean it
            file_path = file_info.get("path", "")
            root_path = root_config.get("path", "")
            
            # Remove query parameters from the path
            clean_path = file_path.split('?')[0] if '?' in file_path else file_path
            
            # Get relative path from root
            if clean_path.startswith(root_path):
                rel_path = clean_path[len(root_path):].lstrip('/')
            else:
                # Extract just the filename from the path
                rel_path = clean_path.split('/')[-1]
            
            # Replace underscores with hyphens for consistency
            rel_path = rel_path.replace('_', '-')
            
            output_path = output_base / rel_path
            
            # Download with optimization for images
            optimize = asset_type == "images" and PILLOW_AVAILABLE
            metadata = download_asset(file_info, output_path, optimize)
            
            if metadata:
                # Add asset path for Flutter
                metadata["asset_path"] = str(output_path).replace("assets/", "assets/", 1)
                downloaded[asset_type].append(metadata)
        
        print(f"\n✅ Downloaded {len(downloaded[asset_type])} {asset_type}")
    
    return downloaded


# =============================================================================
# Manifest Functions
# =============================================================================

def generate_manifests(downloaded: Dict[str, List[Dict]]) -> None:
    """
    Generate manifest files for each asset type.
    
    Args:
        downloaded: Dictionary of downloaded assets by type
    """
    print("\n📝 Generating manifests...")
    
    # Generate image manifest
    if "images" in downloaded:
        images = downloaded["images"]
        manifest = {
            "generated_at": datetime.now().isoformat(),
            "total_images": len(images),
            "total_size_mb": round(sum(img["size_mb"] for img in images), 2),
            "images": images,
        }
        
        IMAGE_MANIFEST.parent.mkdir(parents=True, exist_ok=True)
        with open(IMAGE_MANIFEST, 'w', encoding='utf-8') as f:
            json.dump(manifest, f, indent=2, ensure_ascii=False)
        
        print(f"   ✅ {IMAGE_MANIFEST} ({manifest['total_images']} images, {manifest['total_size_mb']} MB)")
    
    # Generate PDF manifest
    if "pdfs" in downloaded:
        pdfs = downloaded["pdfs"]
        manifest = {
            "generated_at": datetime.now().isoformat(),
            "total_pdfs": len(pdfs),
            "total_size_mb": round(sum(pdf["size_mb"] for pdf in pdfs), 2),
            "pdfs": pdfs,
        }
        
        PDF_MANIFEST.parent.mkdir(parents=True, exist_ok=True)
        with open(PDF_MANIFEST, 'w', encoding='utf-8') as f:
            json.dump(manifest, f, indent=2, ensure_ascii=False)
        
        print(f"   ✅ {PDF_MANIFEST} ({manifest['total_pdfs']} PDFs, {manifest['total_size_mb']} MB)")


def update_pubspec_yaml(downloaded: Dict[str, List[Dict]]) -> None:
    """
    Update pubspec.yaml to include all asset directories.
    
    Args:
        downloaded: Dictionary of downloaded assets
    """
    print("\n📝 Updating pubspec.yaml...")
    
    try:
        with open(PUBSPEC_PATH, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        
        # Find assets section
        assets_index = -1
        for i, line in enumerate(lines):
            if 'assets:' in line and not line.strip().startswith('#'):
                assets_index = i
                break
        
        if assets_index == -1:
            print("   ⚠️  No 'assets:' section found in pubspec.yaml")
            return
        
        # Collect unique asset directories
        asset_dirs = set()
        for asset_list in downloaded.values():
            for asset in asset_list:
                asset_path = Path(asset.get("path", ""))
                if asset_path.exists():
                    # Add directory path
                    dir_path = str(asset_path.parent).replace("\\", "/")
                    if dir_path.startswith("assets/"):
                        asset_dirs.add(f"{dir_path}/")
        
        # Check which directories are already declared
        existing_dirs = set()
        for line in lines[assets_index:]:
            stripped = line.strip()
            if stripped.startswith('- assets/'):
                existing_dirs.add(stripped[2:])  # Remove '- '
            elif stripped and not stripped.startswith('#') and ':' in stripped:
                break  # End of assets section
        
        # Add missing directories
        new_dirs = asset_dirs - existing_dirs
        if new_dirs:
            insert_index = assets_index + 1
            indent = "    "
            
            for dir_path in sorted(new_dirs):
                lines.insert(insert_index, f"{indent}- {dir_path}\n")
                insert_index += 1
                print(f"   ✅ Added: {dir_path}")
            
            # Write back
            with open(PUBSPEC_PATH, 'w', encoding='utf-8') as f:
                f.writelines(lines)
        else:
            print("   ✅ All asset directories already declared")
        
    except Exception as e:
        print(f"   ❌ Error updating pubspec.yaml: {e}")


# =============================================================================
# Main Script
# =============================================================================

def clean_asset_directories():
    """Clean existing asset directories to prevent duplicates."""
    import shutil
    import os
    
    print("🧹 Cleaning existing assets...")
    
    directories_to_clean = [
        Path("assets/products"),
        Path("assets/colors"),
        Path("assets/pdfs/specials"),
    ]
    
    for dir_path in directories_to_clean:
        if dir_path.exists():
            # First, remove any duplicate files with " 2", " 3", etc.
            for file in dir_path.glob("**/*"):
                if file.is_file():
                    # Check for macOS duplicate pattern: "filename 2.ext"
                    if " 2." in file.name or " 3." in file.name or " 4." in file.name:
                        file.unlink()
                        print(f"   🗑️  Removed duplicate: {file.name}")
                    # Check for duplicate extensions (.jpg and .jpeg for same file)
                    elif file.suffix.lower() in ['.jpg', '.jpeg']:
                        stem = file.stem
                        parent = file.parent
                        # Check if both .jpg and .jpeg exist
                        jpg_file = parent / f"{stem}.jpg"
                        jpeg_file = parent / f"{stem}.jpeg"
                        if jpg_file.exists() and jpeg_file.exists():
                            # Keep .jpg, remove .jpeg
                            jpeg_file.unlink()
                            print(f"   🗑️  Removed duplicate extension: {jpeg_file.name}")
            
            # Then delete the entire directory
            shutil.rmtree(dir_path)
            print(f"   ✓ Cleaned {dir_path}")
    
    print()


def main():
    """Main script execution."""
    print("=" * 70)
    print("🚀 Unified Asset Bundling Script")
    print("   Angel Stones Mobile App")
    print("=" * 70)
    print()
    
    if not PILLOW_AVAILABLE:
        print("⚠️  Running without image optimization")
        print("   Install Pillow for smaller images: pip3 install Pillow")
        print()
    
    # Step 0: Clean existing assets to prevent duplicates
    clean_asset_directories()
    
    # Step 1: Discover assets
    assets = discover_all_assets()
    
    if not assets:
        print("\n❌ No assets discovered!")
        sys.exit(1)
    
    total_files = sum(len(files) for files in assets.values())
    print(f"\n✅ Discovered {total_files} total files")
    
    # Step 2: Download assets
    downloaded = download_all_assets(assets)
    
    total_downloaded = sum(len(files) for files in downloaded.values())
    if total_downloaded == 0:
        print("\n❌ No assets were downloaded!")
        sys.exit(1)
    
    # Step 3: Generate manifests
    generate_manifests(downloaded)
    
    # Step 3.5: Remove any duplicate files that were created during download
    print("\n🧹 Final duplicate check...")
    removed_count = 0
    for asset_dir in [Path("assets/products"), Path("assets/colors")]:
        if asset_dir.exists():
            for file in asset_dir.glob("**/*"):
                if file.is_file() and (" 2." in file.name or " 3." in file.name or " 4." in file.name):
                    file.unlink()
                    removed_count += 1
                    print(f"   🗑️  Removed: {file.name}")
    
    if removed_count > 0:
        print(f"   ✅ Removed {removed_count} duplicate files")
    else:
        print(f"   ✅ No duplicates found")
    
    # Step 4: Update pubspec.yaml
    update_pubspec_yaml(downloaded)
    
    # Summary
    print("\n" + "=" * 70)
    print("✅ Asset bundling complete!")
    print("=" * 70)
    
    for asset_type, files in downloaded.items():
        total_size = sum(f["size_mb"] for f in files)
        print(f"   {asset_type.capitalize()}: {len(files)} files ({total_size:.2f} MB)")
    
    print(f"\n📱 Next steps:")
    print(f"   1. Run: flutter pub get")
    print(f"   2. Test offline functionality in the app")
    print(f"   3. When you add new assets to the server, just run this script again!")
    print("=" * 70)


if __name__ == "__main__":
    print("DEBUG: Starting script...", flush=True)
    main()
