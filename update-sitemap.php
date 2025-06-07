<?php
/**
 * Update Sitemap Script
 * 
 * This script generates a new sitemap.xml file by calling generate-sitemap.php
 * and saves it as sitemap.xml in the root directory.
 * 
 * Set up as a cron job to run daily:
 * 0 0 * * * php /path/to/update-sitemap.php >/dev/null 2>&1
 */

// Path to the sitemap generator and output file
$generatorPath = __DIR__ . '/generate-sitemap.php';
$outputPath = __DIR__ . '/sitemap.xml';

// Generate the sitemap content
$sitemapContent = file_get_contents($generatorPath);

// Save to sitemap.xml
if (file_put_contents($outputPath, $sitemapContent) !== false) {
    // Set proper permissions
    chmod($outputPath, 0644);
    echo "Sitemap updated successfully at " . date('Y-m-d H:i:s') . "\n";
} else {
    echo "Failed to update sitemap\n";
}
