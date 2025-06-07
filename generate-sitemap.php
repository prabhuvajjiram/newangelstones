<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Main function to generate the sitemap
function generateSitemap() {
    // Get base URL from constant or use default
    $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://www.theangelstones.com';
    $today = date('Y-m-d');
    $lastWeek = date('Y-m-d', strtotime('-7 days'));
    
    // Track URLs to prevent duplicates
    $urls = [];

    // 1. Homepage with alternate URLs
    addUrl($urls, $baseUrl . '/', $today, 'daily', '1.0', [
        ['lang' => 'en', 'url' => $baseUrl . '/#what-we-offer'],
        ['lang' => 'en', 'url' => $baseUrl . '/#our-product'],
        ['lang' => 'en', 'url' => $baseUrl . '/#get-in-touch']
    ]);
    
    // 2. Main pages
    $mainPages = [
        '/about' => ['changefreq' => 'monthly', 'priority' => '0.8'],
        '/contact' => ['changefreq' => 'monthly', 'priority' => '0.8'],
        '/gallery' => ['changefreq' => 'weekly', 'priority' => '0.8'],
        '/colors' => ['changefreq' => 'weekly', 'priority' => '0.8'],
        '/discovered.html' => ['changefreq' => 'weekly', 'priority' => '0.7']
    ];
    
    foreach ($mainPages as $path => $data) {
        // Ensure the path starts with a slash
        $path = '/' . ltrim($path, '/');
        addUrl($urls, $baseUrl . $path, $today, $data['changefreq'], $data['priority']);
    }
    
    // 3. Color pages with direct image URLs (from color.json)
    $colorsJson = file_get_contents(__DIR__ . '/api/color.json');
    if ($colorsJson) {
        $colors = json_decode($colorsJson, true);
        if (isset($colors['itemListElement']) && is_array($colors['itemListElement'])) {
            foreach ($colors['itemListElement'] as $item) {
                if (isset($item['item']['image'][0]['url'])) {
                    // Get the direct image URL
                    $imageUrl = $item['item']['image'][0]['url'];
                    
                    // Add the image URL to sitemap
                    addUrl($urls, $imageUrl, $today, 'weekly', '0.7');
                    
                    // Also add the color page URL
                    if (isset($item['item']['url'])) {
                        addUrl($urls, $item['item']['url'], $today, 'weekly', '0.7');
                    }
                }
            }
        }
    }
    
    // Start XML output
    $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
    $output .= 'xmlns:xhtml="http://www.w3.org/1999/xhtml" ';
    $output .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    
    // Add URLs to the output
    foreach ($urls as $urlData) {
        $output .= '    <url>' . "\n";
        $output .= '        <loc>' . htmlspecialchars($urlData['loc']) . '</loc>' . "\n";
        $output .= '        <lastmod>' . $urlData['lastmod'] . '</lastmod>' . "\n";
        $output .= '        <changefreq>' . $urlData['changefreq'] . '</changefreq>' . "\n";
        $output .= '        <priority>' . $urlData['priority'] . '</priority>' . "\n";
        
        if (!empty($urlData['hreflang'])) {
            foreach ($urlData['hreflang'] as $hreflang) {
                $output .= '        <xhtml:link rel="alternate" hreflang="' . 
                    htmlspecialchars($hreflang['lang']) . '" href="' . 
                    htmlspecialchars($hreflang['url']) . '" />' . "\n";
            }
        }
        
        $output .= '    </url>' . "\n";
    }
    
    $output .= '</urlset>';
    
    return $output;
}

// Generate the sitemap content
$sitemapContent = generateSitemap();

// Output the content if this file is accessed directly
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/xml; charset=utf-8');
    header('X-Robots-Tag: noindex, follow', true);
    echo $sitemapContent;
}
  
  // 2. Static Pages
  $staticPages = [
      '/discovered.html' => ['lastmod' => '2025-05-27', 'changefreq' => 'weekly', 'priority' => '0.8'],
      '/colors' => ['lastmod' => '2025-05-30', 'changefreq' => 'weekly', 'priority' => '0.8']
  ];
  
  foreach ($staticPages as $path => $data) {
      addUrl($urls, $baseUrl . $path, $data['lastmod'], $data['changefreq'], $data['priority']);
  }
  
    // 3. Color Pages
  $colorsDir = __DIR__ . '/images/colors/';
  $colorImages = glob($colorsDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
  
  // Map of color names to their image filenames (without extension)
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
  
  // Add URLs for each color that has an image
  foreach ($colorImages as $imagePath) {
      $filename = pathinfo($imagePath, PATHINFO_FILENAME);
      if (isset($colorMap[$filename])) {
          $slug = $colorMap[$filename];
          $lastmod = '2025-05-30'; // Default last modified date
          addUrl($urls, $baseUrl . '/images/colors/' . $slug, $lastmod, 'weekly', '0.7');
      }
  }
  
  // 4. Product Categories (commented out since they're loaded in modals)
  // If you implement dedicated pages for these in the future, uncomment and update this section
  /*
  $categories = [
      'monuments' => ['lastmod' => $lastWeek, 'priority' => '0.9'],
      'headstones' => ['lastmod' => $lastWeek, 'priority' => '0.9'],
      'benches' => ['lastmod' => $lastWeek, 'priority' => '0.8'],
      'markers' => ['lastmod' => $lastWeek, 'priority' => '0.8']
  ];
  
  foreach ($categories as $slug => $data) {
      $url = $baseUrl . '/products/' . $slug;
      addUrl($urls, $url, $data['lastmod'], 'weekly', $data['priority']);
  }
  */
  
  // Output all URLs
  foreach ($urls as $urlData) {
      ?>
      <url>
          <loc><?= htmlspecialchars($urlData['loc']) ?></loc>
          <lastmod><?= $urlData['lastmod'] ?></lastmod>
          <changefreq><?= $urlData['changefreq'] ?></changefreq>
          <priority><?= $urlData['priority'] ?></priority>
          <?php if (!empty($urlData['hreflang'])): ?>
              <?php foreach ($urlData['hreflang'] as $hreflang): ?>
              <xhtml:link 
                  rel="alternate"
                  hreflang="<?= htmlspecialchars($hreflang['lang']) ?>"
                  href="<?= htmlspecialchars($hreflang['url']) ?>"/>
              <?php endforeach; ?>
          <?php endif; ?>
      </url>
      <?php
  }
  ?>
</urlset>
