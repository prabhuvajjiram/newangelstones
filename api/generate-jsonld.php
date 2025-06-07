<?php
// api/generate-jsonld.php
header('Content-Type: application/ld+json');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Load the color data
$colors = json_decode(file_get_contents(__DIR__ . '/color.json'), true);
if (!$colors) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to load color data']));
}

// Generate JSON-LD for the color list
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $colors['name'] ?? 'Granite Colors',
    'description' => $colors['description'] ?? 'Explore our collection of premium granite colors',
    'url' => 'https://www.theangelstones.com/colors',
    'numberOfItems' => count($colors['itemListElement'] ?? []),
    'itemListElement' => array_map(function($item) {
        $product = $item['item'] ?? [];
        $image = $product['image'] ?? [];
        
        // Handle both single image and array of images
        $imageUrl = '';
        if (is_array($image) && isset($image[0]['url'])) {
            $imageUrl = $image[0]['url'];
        } elseif (is_string($image)) {
            $imageUrl = $image;
        } elseif (isset($image['url'])) {
            $imageUrl = $image['url'];
        }
        
        return [
            '@type' => 'ListItem',
            'position' => $item['position'] ?? 0,
            'item' => [
                '@type' => 'Product',
                'name' => $product['name'] ?? '',
                'description' => $product['description'] ?? '',
                'image' => $imageUrl,
                'url' => 'https://www.theangelstones.com/colors/' . 
                         strtolower(str_replace(' ', '-', $product['name'] ?? '')),
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => 'USD',
                    'price' => '0',
                    'availability' => 'https://schema.org/InStock'
                ]
            ]
        ];
    }, $colors['itemListElement'] ?? [])
];

// Output the JSON-LD
echo json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);