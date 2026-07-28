<?php
/**
 * Rebuild the public sitemap from the deployed Next.js static export.
 *
 * This script is intended for cPanel cron/CLI use. It deliberately indexes
 * only the canonical public-site routes and ignores preserved operational
 * applications such as CRM, chat, forms, credit applications and giveaways.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
set_time_limit(300);

const BASE_URL = 'https://www.theangelstones.com';

$root = __DIR__;
$routes = [
    '',
    'inventory',
    'granite-colors',
    'products-services',
    'flyers',
    'locations',
    'resources',
    'contact',
    'monuments',
    'mbna-2025',
    'benches',
    'designs',
    'columbarium',
    'privacy-policy',
    'terms-of-service',
    'sms-terms',
];

foreach (['colors', 'designs', 'locations', 'resources'] as $parent) {
    $directories = glob($root . '/' . $parent . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($directories as $directory) {
        if (is_file($directory . '/index.html')) {
            $routes[] = $parent . '/' . basename($directory);
        }
    }
}

$routes = array_values(array_unique($routes));
sort($routes, SORT_STRING);

function textFromMeta(DOMXPath $xpath, string $attribute, string $value): string
{
    $query = sprintf(
        '//meta[translate(@%s, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="%s"]/@content',
        $attribute,
        strtolower($value)
    );
    $node = $xpath->query($query)?->item(0);
    return $node ? trim($node->nodeValue) : '';
}

function readPageMetadata(string $file): array
{
    $html = file_get_contents($file);
    if ($html === false) {
        throw new RuntimeException('Unable to read ' . $file);
    }

    libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $document->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    $xpath = new DOMXPath($document);

    $canonicalNode = $xpath->query(
        '//link[translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="canonical"]/@href'
    )?->item(0);
    $titleNode = $document->getElementsByTagName('title')->item(0);

    return [
        'canonical' => $canonicalNode ? trim($canonicalNode->nodeValue) : '',
        'title' => $titleNode ? trim($titleNode->textContent) : '',
        'description' => textFromMeta($xpath, 'name', 'description'),
        'image' => textFromMeta($xpath, 'property', 'og:image'),
    ];
}

function priorityFor(string $route): string
{
    if ($route === '') return '1.0';
    if (in_array($route, ['inventory', 'granite-colors', 'monuments'], true)) return '0.9';
    if (
        preg_match('#^(colors|designs)/#', $route) ||
        in_array($route, [
            'products-services',
            'flyers',
            'locations',
            'resources',
            'contact',
            'mbna-2025',
            'benches',
            'designs',
            'columbarium',
        ], true)
    ) {
        return '0.8';
    }
    if (preg_match('#^(locations|resources)/#', $route)) return '0.8';
    return '0.5';
}

function frequencyFor(string $route): string
{
    if ($route === 'inventory') return 'daily';
    if (
        $route === '' ||
        in_array($route, ['granite-colors', 'flyers', 'monuments', 'mbna-2025', 'benches', 'designs', 'columbarium', 'resources'], true)
    ) {
        return 'weekly';
    }
    return 'monthly';
}

function xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$entries = [];
foreach ($routes as $route) {
    $file = $route === ''
        ? $root . '/index.html'
        : $root . '/' . $route . '/index.html';
    if (!is_file($file)) {
        continue;
    }

    $metadata = readPageMetadata($file);
    $expectedCanonical = BASE_URL . '/' . ($route === '' ? '' : $route . '/');
    if ($metadata['canonical'] !== $expectedCanonical) {
        throw new RuntimeException(
            sprintf(
                'Canonical mismatch for %s: expected %s, found %s',
                $file,
                $expectedCanonical,
                $metadata['canonical'] ?: '(missing)'
            )
        );
    }

    $entries[] = [
        'loc' => $expectedCanonical,
        'lastmod' => date('Y-m-d', filemtime($file) ?: time()),
        'changefreq' => frequencyFor($route),
        'priority' => priorityFor($route),
        'title' => $metadata['title'],
        'description' => $metadata['description'],
        'image' => $metadata['image'],
    ];
}

if (count($entries) < 50) {
    throw new RuntimeException(
        'Refusing to replace sitemap: only ' . count($entries) . ' public pages were found.'
    );
}

$output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
$output .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

foreach ($entries as $entry) {
    $output .= "  <url>\n";
    $output .= '    <loc>' . xml($entry['loc']) . "</loc>\n";
    $output .= '    <lastmod>' . xml($entry['lastmod']) . "</lastmod>\n";
    $output .= '    <changefreq>' . xml($entry['changefreq']) . "</changefreq>\n";
    $output .= '    <priority>' . xml($entry['priority']) . "</priority>\n";
    if ($entry['image'] !== '') {
        $output .= "    <image:image>\n";
        $output .= '      <image:loc>' . xml($entry['image']) . "</image:loc>\n";
        if ($entry['title'] !== '') {
            $output .= '      <image:title>' . xml($entry['title']) . "</image:title>\n";
        }
        if ($entry['description'] !== '') {
            $output .= '      <image:caption>' . xml($entry['description']) . "</image:caption>\n";
        }
        $output .= "    </image:image>\n";
    }
    $output .= "  </url>\n";
}
$output .= "</urlset>\n";

libxml_use_internal_errors(true);
$validation = new DOMDocument('1.0', 'UTF-8');
if (!$validation->loadXML($output)) {
    $errors = libxml_get_errors();
    libxml_clear_errors();
    throw new RuntimeException(
        'Generated sitemap is invalid XML: ' .
        implode('; ', array_map(static fn($error) => trim($error->message), $errors))
    );
}
libxml_clear_errors();

$target = $root . '/sitemap.xml';
$temporary = $target . '.tmp';
if (file_put_contents($temporary, $output, LOCK_EX) === false) {
    throw new RuntimeException('Unable to write temporary sitemap.');
}
if (!rename($temporary, $target)) {
    @unlink($temporary);
    throw new RuntimeException('Unable to atomically replace sitemap.xml.');
}
chmod($target, 0644);

echo 'Sitemap generated successfully with ' . count($entries) . " canonical public pages.\n";
