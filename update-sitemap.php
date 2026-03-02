<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set time limit for long-running scripts
set_time_limit(300); // 5 minutes

// Function to safely get file modification time
function safeFileMtime($file) {
    return file_exists($file) ? date('Y-m-d', filemtime($file)) : date('Y-m-d');
}

// Function to generate a clean URL slug
function generateSlug($string) {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $string));
    return trim($slug, '-');
}

// Function to add URL if not already added
function addUrl(&$urls, $loc, $lastmod, $changefreq, $priority, $hreflang = null) {
    // Use stripped URL as dedup key only; preserve original $loc (with trailing slash) in output
    $dedupKey = rtrim($loc, '/');
    
    // Skip if URL already exists
    if (isset($urls[$dedupKey])) {
        return false;
    }
    
    $urls[$dedupKey] = [
        'loc' => $loc,
        'lastmod' => $lastmod,
        'changefreq' => $changefreq,
        'priority' => $priority,
        'hreflang' => $hreflang,
        'images' => []  // image sitemap extension
    ];
    
    return true;
}

// Add an image to an existing URL entry
// $imageUrl: absolute image URL (no query strings)
// $title:    SEO-friendly alt/title text
// $caption:  optional caption
function addImage(&$urls, $pageUrl, $imageUrl, $title, $caption = '') {
    $dedupKey = rtrim($pageUrl, '/');
    if (!isset($urls[$dedupKey])) {
        return;
    }
    // Strip cache-busting query strings (e.g. ?v=12345) from image URLs
    $cleanImageUrl = strtok($imageUrl, '?');
    // Avoid duplicate images on the same page
    foreach ($urls[$dedupKey]['images'] as $existing) {
        if ($existing['loc'] === $cleanImageUrl) return;
    }
    $urls[$dedupKey]['images'][] = [
        'loc'     => $cleanImageUrl,
        'title'   => $title,
        'caption' => $caption
    ];
}

// Function to validate XML
function isValidXml($xmlString) {
    if (trim($xmlString) == '') {
        return false;
    }
    
    libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'utf-8');
    $doc->loadXML($xmlString);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    
    return empty($errors);
}

// Main function to generate the sitemap
function generateSitemap() {
    // Get base URL from constant or use default
    $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://www.theangelstones.com';
    $today = date('Y-m-d');
    $lastWeek = date('Y-m-d', strtotime('-7 days'));
    
    // Track URLs to prevent duplicates
    $urls = [];

    // 1. Homepage with alternate URLs
    addUrl($urls, $baseUrl . '/', $today, 'daily', '1.0');

    // 2. Main pages (includes SPA section URLs for Google Sitelinks)
    $mainPages = [
        '/monuments/'         => ['changefreq' => 'weekly',  'priority' => '0.9'],
        '/inventory/'         => ['changefreq' => 'weekly',  'priority' => '0.9'],
        '/granite-colors/'    => ['changefreq' => 'monthly', 'priority' => '0.8'],
        '/benches/'           => ['changefreq' => 'weekly',  'priority' => '0.8'],
        '/designs/'           => ['changefreq' => 'weekly',  'priority' => '0.8'],
        '/contact/'           => ['changefreq' => 'monthly', 'priority' => '0.8'],
        '/discovered.html'    => ['changefreq' => 'weekly',  'priority' => '0.7'],
        '/privacy-policy.html'    => ['changefreq' => 'monthly', 'priority' => '0.5'],
        '/terms-of-service.html'  => ['changefreq' => 'monthly', 'priority' => '0.5'],
        '/sms-terms.html'         => ['changefreq' => 'monthly', 'priority' => '0.5'],
    ];
    
    foreach ($mainPages as $path => $data) {
        // Ensure the path starts with a slash
        $path = '/' . ltrim($path, '/');
        addUrl($urls, $baseUrl . $path, $today, $data['changefreq'], $data['priority']);
    }
    
    // 2.5 Product Category Pages
    $productCategories = [
        'mbna_2025' => ['name' => 'MBNA 2025', 'count' => 26],
        'monuments' => ['name' => 'Monuments', 'count' => 28],
        'columbarium' => ['name' => 'Columbarium', 'count' => 1],
        'designs' => ['name' => 'Designs', 'count' => 1],
        'benches' => ['name' => 'Benches', 'count' => 3]
    ];
    
    foreach ($productCategories as $categorySlug => $categoryData) {
        $categoryUrl = $baseUrl . '/?category=' . $categorySlug;
        addUrl($urls, $categoryUrl, $today, 'weekly', '0.8');
    }
    
    // 3. Color pages from color.json
    // Add each /colors/<slug> page URL and attach its granite color image
    // using the Google Image Sitemap extension (<image:image> inside <url>).
    $colorsJsonFile = __DIR__ . '/api/color.json';
    if (file_exists($colorsJsonFile)) {
        $colorsJson = file_get_contents($colorsJsonFile);
        if ($colorsJson) {
            $colors = json_decode($colorsJson, true);
            if (isset($colors['itemListElement']) && is_array($colors['itemListElement'])) {
                foreach ($colors['itemListElement'] as $item) {
                    $product = $item['item'] ?? [];
                    $pageUrl = $product['url'] ?? '';
                    if (!$pageUrl || preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $pageUrl)) {
                        continue; // skip image paths
                    }
                    addUrl($urls, $pageUrl, $today, 'weekly', '0.7');

                    // Attach the product's color image
                    $images = $product['image'] ?? [];
                    if (!empty($images)) {
                        $img = $images[0];
                        $imgUrl  = $img['url']     ?? '';
                        $caption = $img['caption'] ?? '';
                        $title   = $product['name'] . ' - Premium Granite Monument Color';
                        if ($imgUrl) {
                            addImage($urls, $pageUrl, $imgUrl, $title, $caption);
                        }
                    }
                }
            }
        }
    }

    // 4. Product images for category pages
    // Load each category's product cache file and attach all product images
    // to the corresponding ?category=<slug> sitemap entry using the image: extension.
    // This is the correct way to expose product images — not as separate <loc> entries.
    $categoryImageConfig = [
        'mbna_2025'   => ['cache' => 'products_MBNA_2025.json',  'label' => 'MBNA 2025 Granite Monument'],
        'monuments'   => ['cache' => 'products_Monuments.json',  'label' => 'Custom Granite Monument'],
        'columbarium' => ['cache' => 'products_columbarium.json','label' => 'Granite Columbarium Unit'],
        'designs'     => ['cache' => 'products_Designs.json',    'label' => 'Custom Granite Design'],
        'benches'     => ['cache' => 'products_Benches.json',    'label' => 'Granite Memorial Bench'],
    ];

    foreach ($categoryImageConfig as $slug => $config) {
        $categoryPageUrl = $baseUrl . '/?category=' . $slug;
        $cacheFile = __DIR__ . '/cache/' . $config['cache'];
        if (!file_exists($cacheFile)) continue;

        $cacheData = json_decode(file_get_contents($cacheFile), true);
        $files = $cacheData['files'] ?? [];
        $label = $config['label'];

        foreach ($files as $file) {
            $rawPath = $file['path'] ?? '';
            if (!$rawPath) continue;
            // Strip cache-busting query and build absolute URL
            $cleanPath = strtok($rawPath, '?');
            $imageAbsUrl = $baseUrl . '/' . ltrim($cleanPath, '/');
            $productName = $file['name'] ?? basename($cleanPath);
            $title   = $label . ' — ' . strtoupper($productName);
            $caption = $label . ' ' . strtoupper($productName) . ' by Angel Granites';
            addImage($urls, $categoryPageUrl, $imageAbsUrl, $title, $caption);
        }
    }
    
    // Build XML output with Google Image Sitemap extension
    $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
    $output .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
    $output .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
    $output .= ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9';
    $output .= ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
    $output .= ' http://www.google.com/schemas/sitemap-image/1.1';
    $output .= ' http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd">' . "\n";

    foreach ($urls as $urlData) {
        $output .= '  <url>' . "\n";
        $output .= '    <loc>' . htmlspecialchars($urlData['loc'], ENT_XML1) . '</loc>' . "\n";
        $output .= '    <lastmod>' . htmlspecialchars($urlData['lastmod'], ENT_XML1) . '</lastmod>' . "\n";
        $output .= '    <changefreq>' . htmlspecialchars($urlData['changefreq'], ENT_XML1) . '</changefreq>' . "\n";
        $output .= '    <priority>' . htmlspecialchars($urlData['priority'], ENT_XML1) . '</priority>' . "\n";
        // Append image entries (Google Image Sitemap extension)
        foreach ($urlData['images'] as $img) {
            $output .= '    <image:image>' . "\n";
            $output .= '      <image:loc>' . htmlspecialchars($img['loc'], ENT_XML1) . '</image:loc>' . "\n";
            if (!empty($img['title'])) {
                $output .= '      <image:title>' . htmlspecialchars($img['title'], ENT_XML1) . '</image:title>' . "\n";
            }
            if (!empty($img['caption'])) {
                $output .= '      <image:caption>' . htmlspecialchars($img['caption'], ENT_XML1) . '</image:caption>' . "\n";
            }
            $output .= '    </image:image>' . "\n";
        }
        $output .= '  </url>' . "\n";
    }

    $output .= '</urlset>';
    
    return $output;
}

// Main execution
$sitemapContent = generateSitemap();

// Validate the XML before saving
if (!isValidXml($sitemapContent)) {
    error_log('Error: Generated sitemap is not valid XML');
    exit(1);
}

// Save to file
$sitemapPath = __DIR__ . '/sitemap.xml';
$tempPath = $sitemapPath . '.tmp';

// Write to temporary file first
if (file_put_contents($tempPath, $sitemapContent) === false) {
    error_log('Error: Could not write to temporary sitemap file');
    exit(1);
}

// Rename temporary file to final name (atomic operation)
if (!rename($tempPath, $sitemapPath)) {
    error_log('Error: Could not rename temporary sitemap file');
    unlink($tempPath); // Clean up
    exit(1);
}

// Set proper permissions
chmod($sitemapPath, 0644);

echo "Sitemap generated successfully at: " . $sitemapPath . "\n";

exit(0);
