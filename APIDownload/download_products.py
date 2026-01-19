"""
PSGranite Products Downloader
Downloads all products from the PSGranite API with pagination support.
Uses built-in urllib (no external dependencies).
"""

import urllib.request
import urllib.error
import gzip
import json
import time
import ssl
from datetime import datetime

# Configuration
API_URL = "https://salesapi.psgranite.com/api/products"
PAGE_SIZE = 100  # Increase from 10 to reduce API calls (15770/100 = ~158 pages)

# You'll need to update this token - it expires!
# Get a fresh token from browser dev tools after logging in
BEARER_TOKEN = "eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJtaWNoaWdhbmhlYWRzdG9uZXNAZ21haWwuY29tIiwiZXhwIjoxNzY4ODA5Njg3LCJ0eXBlIjoiYXV0aG9yaXphdGlvbiJ9.QDYpw7S4JQqrcuYfly9AZFxPtRFefhXGKiJhkkoG57M-ezskkYeRykX9ViHnE_DY5wtxnhl_fz9Qsk3J-6J9AQ"

# SSL context (for Windows compatibility)
ssl_context = ssl.create_default_context()

def fetch_page(page_num, page_limit=PAGE_SIZE):
    """Fetch a single page of products using urllib"""
    headers = {
        "Accept": "application/json, text/plain, */*",
        "Accept-Encoding": "gzip",
        "Accept-Language": "en-US,en;q=0.9",
        "Access-Control-Allow-Origin": "*",
        "Authorization": f"Bearer {BEARER_TOKEN}",
        "App-Pagination-Num": str(page_num),
        "App-Pagination-Limit": str(page_limit),
        "Origin": "https://sales.psgranite.com",
        "Referer": "https://sales.psgranite.com/",
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36"
    }
    
    req = urllib.request.Request(API_URL, headers=headers)
    
    try:
        response = urllib.request.urlopen(req, context=ssl_context)
        status_code = response.getcode()
        
        # Read response data
        data = response.read()
        if response.headers.get('Content-Encoding') == 'gzip':
            data = gzip.decompress(data)
        
        json_data = json.loads(data.decode('utf-8'))
        
        return {
            "data": json_data.get("data", []),
            "total_records": int(response.headers.get("app-pagination-total-records", 0)),
            "total_pages": int(response.headers.get("app-pagination-total-pages", 0)),
            "has_next": response.headers.get("app-pagination-has-next-page", "false").lower() == "true",
            "current_page": int(response.headers.get("app-pagination-current-page-num", 0))
        }
    except urllib.error.HTTPError as e:
        if e.code == 401:
            raise Exception("Authentication failed! Token may have expired. Please update BEARER_TOKEN.")
        else:
            raise Exception(f"API error: {e.code} - {e.reason}")

def download_all_products():
    """Download all products with pagination"""
    all_products = []
    page_num = 0
    
    print("=" * 60)
    print("PSGranite Products Downloader")
    print("=" * 60)
    
    # First request to get total count
    print("\nFetching first page to get total count...")
    first_page = fetch_page(0)
    total_records = first_page["total_records"]
    total_pages = first_page["total_pages"]
    
    print(f"Total products: {total_records:,}")
    print(f"Total pages (at {PAGE_SIZE}/page): {total_pages:,}")
    print(f"Estimated time: ~{total_pages * 0.5 / 60:.1f} minutes\n")
    
    all_products.extend(first_page["data"])
    print(f"Page 0: Downloaded {len(first_page['data'])} products (Total: {len(all_products):,})")
    
    # Fetch remaining pages
    page_num = 1
    while page_num < total_pages:
        try:
            time.sleep(0.3)  # Be nice to the server
            
            page_data = fetch_page(page_num)
            products = page_data["data"]
            all_products.extend(products)
            
            # Progress indicator
            progress = (page_num + 1) / total_pages * 100
            print(f"Page {page_num}: Downloaded {len(products)} products (Total: {len(all_products):,}) [{progress:.1f}%]")
            
            page_num += 1
            
        except Exception as e:
            print(f"\nError on page {page_num}: {e}")
            print("Retrying in 5 seconds...")
            time.sleep(5)
            continue
    
    return all_products

def save_products(products, filename=None):
    """Save products to JSON file"""
    if filename is None:
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"psgranite_products_{timestamp}.json"
    
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(products, f, indent=2, ensure_ascii=False)
    
    return filename

def save_products_csv(products, filename=None):
    """Save products to CSV file"""
    import csv
    
    if filename is None:
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"psgranite_products_{timestamp}.csv"
    
    if not products:
        print("No products to save!")
        return None
    
    # Get all unique keys from products
    all_keys = set()
    for p in products:
        all_keys.update(p.keys())
    
    fieldnames = sorted(list(all_keys))
    
    with open(filename, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(products)
    
    return filename

if __name__ == "__main__":
    try:
        # Download all products
        products = download_all_products()
        
        print("\n" + "=" * 60)
        print(f"Download complete! Total products: {len(products):,}")
        print("=" * 60)
        
        # Save to JSON
        json_file = save_products(products)
        print(f"\nSaved to JSON: {json_file}")
        
        # Save to CSV
        csv_file = save_products_csv(products)
        print(f"Saved to CSV: {csv_file}")
        
        # Print sample product
        if products:
            print("\nSample product structure:")
            print(json.dumps(products[0], indent=2))
            
    except KeyboardInterrupt:
        print("\n\nDownload interrupted by user.")
    except Exception as e:
        print(f"\nError: {e}")
        print("\nIf token expired, login to https://sales.psgranite.com and get a new token from browser dev tools:")
        print("1. Press F12 -> Network tab")
        print("2. Look for any API request to salesapi.psgranite.com")
        print("3. Copy the 'authorization' header value (after 'Bearer ')")
        print("4. Update BEARER_TOKEN in this script")
