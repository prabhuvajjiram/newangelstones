<?php
// Test script to verify sitemap generation and display it in a browser-friendly format

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get the base URL
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    // Adjust the path if your site is in a subdirectory
    $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $path = rtrim($path, '/');
    return $protocol . $domainName . $path;
}

// Set the base URL for the sitemap
define('BASE_URL', getBaseUrl());

try {
    // Start output buffering to capture the sitemap output
    ob_start();
    include __DIR__ . '/generate-sitemap.php';
    $sitemapContent = ob_get_clean();

    if (empty($sitemapContent)) {
        throw new Exception('Sitemap content is empty. Please check the sitemap generator.');
    }
} catch (Exception $e) {
    die('Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
}

// Save to sitemap.xml
file_put_contents(__DIR__ . '/sitemap.xml', $sitemapContent);

// Display in browser
header('Content-Type: text/plain; charset=utf-8');
echo "Sitemap generated successfully at " . date('Y-m-d H:i:s') . "\n\n";
if (function_exists('simplexml_load_string')) {
    $xml = simplexml_load_string($sitemapContent);
    if ($xml) {
        echo "Sitemap contains " . count($xml->url) . " URLs\n\n";
        echo "First 5 URLs (if available):\n";
        $count = 0;
        foreach ($xml->url as $url) {
            if ($count++ >= 5) break;
            echo "- " . $url->loc . " (Last Modified: " . ($url->lastmod ?? 'N/A') . ")\n";
        }
    } else {
        echo "Could not parse XML. Raw output (first 1000 chars):\n\n" . 
             htmlspecialchars(substr($sitemapContent, 0, 1000));
    }
} else {
    echo "SimpleXML extension not available. Raw output (first 1000 chars):\n\n" . 
         htmlspecialchars(substr($sitemapContent, 0, 1000));
}

echo "\n\nSitemap has been saved to: " . __DIR__ . '/sitemap.xml';
?>
