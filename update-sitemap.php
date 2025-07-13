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
    // Normalize URL
    $normalizedLoc = rtrim($loc, '/');
    
    // Skip if URL already exists
    if (isset($urls[$normalizedLoc])) {
        return false;
    }
    
    $urls[$normalizedLoc] = [
        'loc' => $normalizedLoc,
        'lastmod' => $lastmod,
        'changefreq' => $changefreq,
        'priority' => $priority,
        'hreflang' => $hreflang
    ];
    
    return true;
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

    // 2. Main pages
    $mainPages = [
        '/about' => ['changefreq' => 'monthly', 'priority' => '0.8'],
        '/contact' => ['changefreq' => 'monthly', 'priority' => '0.8'],
        '/gallery' => ['changefreq' => 'weekly', 'priority' => '0.8'],
        '/colors' => ['changefreq' => 'weekly', 'priority' => '0.8'],
        '/discovered.html' => ['changefreq' => 'weekly', 'priority' => '0.7'],
        '/privacy-policy.html' => ['changefreq' => 'monthly', 'priority' => '0.5'],
        '/terms-of-service.html' => ['changefreq' => 'monthly', 'priority' => '0.5'],
        '/sms-terms.html' => ['changefreq' => 'monthly', 'priority' => '0.5']
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
    $colorsJsonFile = __DIR__ . '/api/color.json';
    if (file_exists($colorsJsonFile)) {
        $colorsJson = file_get_contents($colorsJsonFile);
        if ($colorsJson) {
            $colors = json_decode($colorsJson, true);
            if (isset($colors['itemListElement']) && is_array($colors['itemListElement'])) {
                foreach ($colors['itemListElement'] as $item) {
                    if (isset($item['item']['url'])) {
                        addUrl($urls, $item['item']['url'], $today, 'weekly', '0.7');
                    }
                    if (isset($item['item']['image'][0]['url'])) {
                        addUrl($urls, $item['item']['image'][0]['url'], $today, 'weekly', '0.7');
                    }
                }
            }
        }
    }
    
    // 4. Color images from directory
    $colorsDir = __DIR__ . '/images/colors/';
    if (is_dir($colorsDir)) {
        $colorImages = glob($colorsDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        $colorMap = [
            'bluepearl' => 'blue-pearl',
            'galaxy' => 'black-galaxy',
            'vizag-blue' => 'vizag-blue',
            'himalayan-blue' => 'himalayan-blue',
            'indian-black' => 'indian-black',
            'paradiso' => 'paradiso',
            'white-and-red' => 'white-red',
            'Bahama-Blue' => 'bahama-blue',
            'Baltic Green' => 'baltic-green',
            'Dark Barre Gray' => 'dark-barre-gray',
            'Forest Green' => 'forest-green',
            'Georgia Gray' => 'georgia-gray',
            'Green Breeze' => 'green-breeze',
            'Green Dream' => 'green-dream',
            'Green Pearl' => 'green-pearl',
            'Green Wave Quartzite' => 'green-wave-quartzite',
            'Imperial Green' => 'imperial-green',
            'Jet-Black' => 'jet-black',
            'Medium Barre Gray' => 'medium-barre-gray',
            'NH-Red' => 'nh-red',
            'Olive Green' => 'olive-green',
            'Oriental Green' => 'oriental-green',
            'Pacific Gray' => 'pacific-gray',
            'Queens Green' => 'queens-green',
            'Rain Forest Green' => 'rain-forest-green',
            'Rustic Brown' => 'rustic-brown',
            'Sanfrancisco Green' => 'sanfrancisco-green',
            'Tropical Green' => 'tropical-green',
            'aurora' => 'aurora',
            'green' => 'green'
        ];
        
        foreach ($colorImages as $imagePath) {
            $filename = pathinfo($imagePath, PATHINFO_FILENAME);
            if (isset($colorMap[$filename])) {
                $slug = $colorMap[$filename];
                $lastmod = date('Y-m-d', filemtime($imagePath)) ?: $today;
                addUrl($urls, $baseUrl . '/images/colors/' . $slug, $lastmod, 'weekly', '0.7');
            }
        }
    }
    
    // Start XML output
    $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
    $output .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
    $output .= ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9';
    $output .= ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
    
    // Add URLs to the output
    foreach ($urls as $urlData) {
        $output .= '  <url>' . "\n";
        $output .= '    <loc>' . htmlspecialchars($urlData['loc'], ENT_XML1) . '</loc>' . "\n";
        $output .= '    <lastmod>' . htmlspecialchars($urlData['lastmod'], ENT_XML1) . '</lastmod>' . "\n";
        $output .= '    <changefreq>' . htmlspecialchars($urlData['changefreq'], ENT_XML1) . '</changefreq>' . "\n";
        $output .= '    <priority>' . htmlspecialchars($urlData['priority'], ENT_XML1) . '</priority>' . "\n";
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
