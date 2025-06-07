<?php
/**
 * Update Sitemap Script
 * 
 * This script generates a new sitemap.xml file by executing generate-sitemap.php
 * and saves it as sitemap.xml in the root directory.
 * 
 * Set up as a cron job to run daily:
 * 0 0 * * * php /path/to/update-sitemap.php >/dev/null 2>&1
 */

// Include the generator which contains the generateSitemap() function
require_once __DIR__ . '/generate-sitemap.php';

// Path to save the sitemap
$outputPath = __DIR__ . '/sitemap.xml';

// Generate the sitemap content
$sitemapContent = generateSitemap();

// Save to sitemap.xml and set proper permissions
file_put_contents($outputPath, $sitemapContent);
chmod($outputPath, 0644);
