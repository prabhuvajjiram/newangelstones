#!/usr/bin/env python3
"""
PDF Bundling Script for Angel Stones Mobile App

This script downloads all PDF catalogs from the server and prepares them
for bundling with the mobile app for offline access.

Unlike images, PDFs don't need optimization - we just download them as-is.
The hybrid loading strategy will check bundled PDFs first, then fall back
to network for newly added PDFs.

Usage:
    python3 bundle_pdfs.py

Requirements:
    - requests library (install via: pip3 install requests)
"""

import os
import sys
import json
import requests
from pathlib import Path
from datetime import datetime
from typing import List, Dict, Optional

# =============================================================================
# Configuration
# =============================================================================

# Base URL for the API
BASE_URL = "https://www.theangelstones.com"

# API endpoint for fetching directory contents
GET_DIRECTORY_FILES_ENDPOINT = f"{BASE_URL}/get_directory_files.php"

# Known PDFs on the server (add new PDFs here as they're added to the website)
# Format: (url_path, display_name, category)
KNOWN_PDFS = [
    ("images/specials/pdfs/Green_Special_Flyer.pdf", "Green Special Flyer", "specials"),
    ("images/specials/pdfs/Blue_Special_Flyer.pdf", "Blue Special Flyer", "specials"),
]

# You can also add other catalogs here:
# ("path/to/catalog.pdf", "Main Catalog", "catalogs"),
# ("path/to/brochure.pdf", "Company Brochure", "brochures"),

# Output directory for bundled PDFs (relative to script location)
OUTPUT_DIR = Path("assets/pdfs")

# Manifest file path
MANIFEST_PATH = Path("assets/pdf_manifest.json")

# pubspec.yaml path
PUBSPEC_PATH = Path("pubspec.yaml")

# Maximum file size for PDFs (10 MB warning threshold)
MAX_PDF_SIZE_MB = 10

# =============================================================================
# Helper Functions
# =============================================================================

def download_all_pdfs() -> List[Dict]:
    """
    Download all PDFs from configured list.
    
    Returns:
        List of PDF metadata dictionaries
    """
    all_pdfs = []
    
    print(f"\n📂 Processing {len(KNOWN_PDFS)} known PDFs...")
    
    for pdf_url_path, display_name, category in KNOWN_PDFS:
        print(f"\n📄 Processing: {display_name}")
        
        # Create category subdirectory
        category_output_dir = OUTPUT_DIR / category
        category_output_dir.mkdir(parents=True, exist_ok=True)
        
        # Get filename from URL path
        pdf_filename = pdf_url_path.split('/')[-1]
        output_path = category_output_dir / pdf_filename
        
        # Build full URL
        pdf_url = f"{BASE_URL}/{pdf_url_path}"
        
        # Skip if already exists
        if output_path.exists():
            print(f"⏭️  Skipping (already exists): {pdf_filename}")
            file_size = output_path.stat().st_size
            all_pdfs.append({
                "name": pdf_filename,
                "display_name": display_name,
                "category": category,
                "path": str(output_path.relative_to(Path.cwd())),
                "asset_path": f"assets/pdfs/{category}/{pdf_filename}",
                "size_bytes": file_size,
                "size_mb": round(file_size / (1024 * 1024), 2),
                "url": pdf_url,
                "downloaded_at": datetime.now().isoformat()
            })
            continue
        
        # Download PDF
        try:
            print(f"⬇️  Downloading from: {pdf_url}")
            response = requests.get(pdf_url, timeout=60, stream=True)
            response.raise_for_status()
            
            # Write PDF to file
            with open(output_path, 'wb') as f:
                for chunk in response.iter_content(chunk_size=8192):
                    f.write(chunk)
            
            # Get file size
            file_size = output_path.stat().st_size
            file_size_mb = file_size / (1024 * 1024)
            
            # Warning if file is large
            if file_size_mb > MAX_PDF_SIZE_MB:
                print(f"⚠️  Large PDF: {file_size_mb:.2f} MB (consider if this should be bundled)")
            
            print(f"✅ Downloaded: {pdf_filename} ({file_size_mb:.2f} MB)")
            
            # Add metadata
            all_pdfs.append({
                "name": pdf_filename,
                "display_name": display_name,
                "category": category,
                "path": str(output_path.relative_to(Path.cwd())),
                "asset_path": f"assets/pdfs/{category}/{pdf_filename}",
                "size_bytes": file_size,
                "size_mb": round(file_size_mb, 2),
                "url": pdf_url,
                "downloaded_at": datetime.now().isoformat()
            })
            
        except requests.exceptions.RequestException as e:
            print(f"❌ Error downloading PDF {display_name}: {e}")
        except IOError as e:
            print(f"❌ Error writing PDF file {output_path}: {e}")
    
    return all_pdfs


def generate_manifest(pdfs: List[Dict]) -> None:
    """
    Generate a JSON manifest file with all PDF metadata.
    
    Args:
        pdfs: List of PDF metadata dictionaries
    """
    manifest = {
        "generated_at": datetime.now().isoformat(),
        "total_pdfs": len(pdfs),
        "total_size_mb": round(sum(p["size_mb"] for p in pdfs), 2),
        "pdfs": pdfs
    }
    
    # Create parent directory if needed
    MANIFEST_PATH.parent.mkdir(parents=True, exist_ok=True)
    
    # Write manifest
    with open(MANIFEST_PATH, 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)
    
    print(f"\n✅ Generated manifest: {MANIFEST_PATH}")
    print(f"   Total PDFs: {manifest['total_pdfs']}")
    print(f"   Total Size: {manifest['total_size_mb']} MB")


def update_pubspec_yaml(pdfs: List[Dict]) -> None:
    """
    Update pubspec.yaml to include PDF assets.
    
    Args:
        pdfs: List of PDF metadata dictionaries
    """
    try:
        # Read current pubspec.yaml
        with open(PUBSPEC_PATH, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        
        # Find the assets section
        assets_index = -1
        for i, line in enumerate(lines):
            if 'assets:' in line and not line.strip().startswith('#'):
                assets_index = i
                break
        
        if assets_index == -1:
            print("⚠️  Could not find 'assets:' section in pubspec.yaml")
            return
        
        # Check if PDF assets are already listed
        pdf_assets_exist = any('assets/pdfs/' in line for line in lines)
        
        if pdf_assets_exist:
            print("✅ PDF assets already declared in pubspec.yaml")
            return
        
        # Add PDF assets directory
        indent = "    "
        pdf_asset_line = f"{indent}- assets/pdfs/\n"
        
        # Find where to insert (after assets: line)
        insert_index = assets_index + 1
        
        # Insert the PDF assets line
        lines.insert(insert_index, pdf_asset_line)
        
        # Write back to pubspec.yaml
        with open(PUBSPEC_PATH, 'w', encoding='utf-8') as f:
            f.writelines(lines)
        
        print("✅ Updated pubspec.yaml with PDF assets")
        
    except Exception as e:
        print(f"❌ Error updating pubspec.yaml: {e}")


# =============================================================================
# Main Script
# =============================================================================

def main():
    """Main script execution."""
    print("=" * 70)
    print("PDF Bundling Script for Angel Stones Mobile App")
    print("=" * 70)
    
    # Create output directory
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    print(f"📁 Output directory: {OUTPUT_DIR}")
    
    # Download all PDFs
    print(f"\n🚀 Starting PDF download...")
    pdfs = download_all_pdfs()
    
    if not pdfs:
        print("\n❌ No PDFs were downloaded!")
        sys.exit(1)
    
    # Generate manifest
    print(f"\n📝 Generating manifest...")
    generate_manifest(pdfs)
    
    # Update pubspec.yaml
    print(f"\n📝 Updating pubspec.yaml...")
    update_pubspec_yaml(pdfs)
    
    # Summary
    print("\n" + "=" * 70)
    print("✅ PDF bundling complete!")
    print("=" * 70)
    print(f"Total PDFs bundled: {len(pdfs)}")
    print(f"Total size: {sum(p['size_mb'] for p in pdfs):.2f} MB")
    print(f"\nNext steps:")
    print(f"1. Run: flutter pub get")
    print(f"2. Test offline PDF loading in the app")
    print(f"3. New PDFs added to server will automatically load from network")
    print("=" * 70)


if __name__ == "__main__":
    main()
