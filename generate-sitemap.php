<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to XML
header('Content-Type: application/xml; charset=utf-8');

// Function to safely get file modification time
function safeFileMtime($file) {
    return file_exists($file) ? date('Y-m-d', filemtime($file)) : date('Y-m-d');
}

// Function to generate a clean URL slug
function generateSlug($string) {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $string));
    return trim($slug, '-');
}

// Get base URL from constant or use default
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://www.theangelstones.com';
$today = date('Y-m-d');
$lastWeek = date('Y-m-d', strtotime('-7 days'));

// Track URLs to prevent duplicates
$urls = [];

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

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
  
  <?php
  // 1. Homepage (with alternate URLs)
  addUrl($urls, $baseUrl . '/', $today, 'daily', '1.0');
  
  // 2. SPA Routes
  $spaRoutes = [
      '/#home' => ['changefreq' => 'daily', 'priority' => '1.0'],
      '/#featured-products' => ['changefreq' => 'weekly', 'priority' => '0.9'],
      '/#variety-of-granites' => ['changefreq' => 'weekly', 'priority' => '0.9'],
      '/#our-product' => ['changefreq' => 'monthly', 'priority' => '0.9'],
      '/#why-choose-as' => ['changefreq' => 'monthly', 'priority' => '0.8'],
      '/#get-in-touch' => ['changefreq' => 'monthly', 'priority' => '0.9'],
      '/colors' => ['changefreq' => 'weekly', 'priority' => '0.9']
  ];
  
  // Get last modified time from index.html
  $lastmod = safeFileMtime(__DIR__ . '/index.html');
  
  foreach ($spaRoutes as $path => $data) {
      addUrl($urls, $baseUrl . $path, $lastmod, $data['changefreq'], $data['priority']);
  }
  
  // 3. Color Pages
  $colorDataFile = __DIR__ . '/api/color.json';
  if (file_exists($colorDataFile)) {
      $colors = json_decode(file_get_contents($colorDataFile), true);
      if (json_last_error() === JSON_ERROR_NONE && !empty($colors['itemListElement'])) {
          foreach ($colors['itemListElement'] as $item) {
              if (isset($item['item']['name'])) {
                  $slug = generateSlug($item['item']['name']);
                  $colorUrl = $baseUrl . '/colors/' . $slug;
                  addUrl($urls, $colorUrl, $today, 'monthly', '0.7');
              }
          }
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
          <?php if ($urlData['loc'] === rtrim($baseUrl, '/') . '/'): ?>
          <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($urlData['loc']) ?>#what-we-offer"/>
          <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($urlData['loc']) ?>#our-product"/>
          <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($urlData['loc']) ?>#get-in-touch"/>
          <?php endif; ?>
      </url>
      <?php
  }
  ?>
</urlset>
