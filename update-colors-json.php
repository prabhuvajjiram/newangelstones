<?php
// Script to update color.json based on actual images in /images/colors/

// Directory containing color images
$colorsDir = __DIR__ . '/images/colors/';
$colorImages = glob($colorsDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

// Map of image filenames to color data templates
$colorTemplates = [
    'american-black' => [
        'name' => 'American Black Granite',
        'description' => 'Classic American Black granite provides a deep, consistent black color with subtle speckling, creating sophisticated and timeless memorial monuments.'
    ],
    'autumn-brown' => [
        'name' => 'Autumn Brown Granite',
        'description' => 'Warm Autumn Brown granite features rich, earthy brown tones with subtle hints of gold, creating inviting and serene memorial monuments.'
    ],
    'bahama-blue' => [
        'name' => 'Bahama Blue Granite',
        'description' => 'Distinctive Bahama Blue granite with unique blue tones, perfect for creating striking monuments and memorials.'
    ],
    'baltic-green' => [
        'name' => 'Baltic Green Granite',
        'description' => 'Unique Baltic Green granite showcases deep forest green tones with black accents, creating distinctive memorial stones.'
    ],
    'bluepearl' => [
        'name' => 'Blue Pearl Granite',
        'description' => 'Premium Blue Pearl granite features a distinctive blue sheen with silver and black speckles, making it a stunning choice for elegant monuments and headstones.'
    ],
    'canadian-mahogany' => [
        'name' => 'Canadian Mahogany Granite',
        'description' => 'Premium Canadian Mahogany granite features deep brown-red tones, providing a rich and warm appearance for elegant memorial monuments.'
    ],
    'charcoal-gray' => [
        'name' => 'Charcoal Gray Granite',
        'description' => 'Sophisticated Charcoal Gray granite offers a deep, rich gray tone with subtle variations, creating elegant and understated memorial monuments.'
    ],
    'colonial-rose' => [
        'name' => 'Colonial Rose Granite',
        'description' => 'Elegant Colonial Rose granite features soft pink tones with subtle patterns, creating warm and inviting memorial monuments.'
    ],
    'dakota-mahogany' => [
        'name' => 'Dakota Mahogany Granite',
        'description' => 'Rich Dakota Mahogany granite features deep reddish-brown tones with black accents, creating warm and elegant memorial monuments.'
    ],
    'dark-barre-gray' => [
        'name' => 'Dark Barre Gray Granite',
        'description' => 'Classic Dark Barre Gray granite features consistent dark gray tones that provide an elegant and timeless appearance for monuments.'
    ],
    'forest-green' => [
        'name' => 'Forest Green Granite',
        'description' => 'Elegant Forest Green granite features rich, deep green tones that create serene and dignified memorial monuments.'
    ],
    'georgia-gray' => [
        'name' => 'Georgia Gray Granite',
        'description' => 'Premium Georgia Gray granite with a consistent, elegant gray tone, ideal for monuments and memorials.'
    ],
    'green-breeze' => [
        'name' => 'Green Breeze Granite',
        'description' => 'Luxurious Green Breeze granite features gentle green hues with subtle patterns, creating an elegant and peaceful memorial.'
    ],
    'green-dream' => [
        'name' => 'Green Dream Granite',
        'description' => 'Vibrant Green Dream granite offers rich emerald tones with unique patterns, making it perfect for distinctive memorials.'
    ],
    'green-pearl' => [
        'name' => 'Green Pearl Granite',
        'description' => 'Distinctive Green Pearl granite offers a light green background with pearl-like iridescence, creating sophisticated and elegant monuments.'
    ],
    'green-wave-quartzite' => [
        'name' => 'Green Wave Quartzite',
        'description' => 'Extraordinary Green Wave Quartzite features dramatic flowing patterns of green and white, creating spectacular, one-of-a-kind memorials.'
    ],
    'himalayan-blue' => [
        'name' => 'Himalayan Blue Granite',
        'description' => 'Striking Himalayan Blue granite features deep blue tones with white and gray veining, creating elegant and distinctive memorials.'
    ],
    'imperial-green' => [
        'name' => 'Imperial Green Granite',
        'description' => 'Majestic Imperial Green granite features rich forest green tones with black accents, perfect for creating impressive and enduring monuments.'
    ],
    'india-red' => [
        'name' => 'India Red Granite',
        'description' => 'Vibrant India Red granite features rich red tones with black and gray accents, creating bold and striking memorial monuments.'
    ],
    'jet-black' => [
        'name' => 'Jet Black Granite',
        'description' => 'Premium Jet Black granite provides a deep, consistent black color that creates sophisticated, elegant, and timeless memorials.'
    ],
    'medium-barre-gray' => [
        'name' => 'Medium Barre Gray Granite',
        'description' => 'Classic Medium Barre Gray granite features a light to medium gray tone with subtle texture, providing an elegant and versatile option for memorials.'
    ],
    'mountain-red' => [
        'name' => 'Mountain Red Granite',
        'description' => 'Rich Mountain Red granite showcases deep red tones with black accents, creating bold and distinguished memorial monuments.'
    ],
    'nh-red' => [
        'name' => 'NH Red Granite',
        'description' => 'Vibrant NH Red granite features warm red and pink tones that create distinctive and memorable monuments with a unique character.'
    ],
    'nordic-black' => [
        'name' => 'Nordic Black Granite',
        'description' => 'Premium Nordic Black granite provides a deep, consistent black color with a fine grain, creating sophisticated and timeless memorial monuments.'
    ],
    'olive-green' => [
        'name' => 'Olive Green Granite',
        'description' => 'Subtle Olive Green granite offers understated elegance with its muted green tones, perfect for creating harmonious and serene memorials.'
    ],
    'pacific-green' => [
        'name' => 'Pacific Green Granite',
        'description' => 'Unique Pacific Green granite offers rich forest green tones with subtle veining, creating elegant and distinguished memorials.'
    ],
    'paradiso' => [
        'name' => 'Paradiso Granite',
        'description' => 'Exceptional Paradiso granite features deep brown and black tones with gold and burgundy accents, creating unique and luxurious memorials.'
    ],
    'rose-arcadia' => [
        'name' => 'Rose Arcadia Granite',
        'description' => 'Beautiful Rose Arcadia granite features gentle pink tones with delicate patterns, creating warm and comforting memorial monuments.'
    ],
    'salisbury-pink' => [
        'name' => 'Salisbury Pink Granite',
        'description' => 'Delicate Salisbury Pink granite offers soft pink tones with a gentle texture, creating memorial monuments with a warm and inviting appearance.'
    ],
    'tropical-green' => [
        'name' => 'Tropical Green Granite',
        'description' => 'Striking Tropical Green granite offers vibrant green tones with dramatic black speckling, creating bold and memorable memorial monuments.'
    ],
    'vermillion-pink' => [
        'name' => 'Vermillion Pink Granite',
        'description' => 'Vibrant Vermillion Pink granite features rich pink and red tones that create warm and striking memorial monuments with a distinctive presence.'
    ],
    'vizag-blue' => [
        'name' => 'Vizag Blue Granite',
        'description' => 'Vizag Blue granite offers a beautiful blue-gray color with subtle grain patterns, perfect for elegant and timeless memorials.'
    ],
    // Default template for any unmatched colors
    'default' => [
        'name' => 'Granite',
        'description' => 'High-quality granite perfect for memorials and monuments.'
    ]
];

// Base structure for the JSON
$colorsData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Angel Stones Granite Color Varieties',
    'description' => 'Explore our premium granite colors for monuments and headstones. Choose from a wide variety of high-quality granites for your memorial needs.',
    'itemListOrder' => 'Unordered',
    'url' => 'https://www.theangelstones.com/colors',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => 'https://www.theangelstones.com/colors'
    ],
    'itemListElement' => []
];

// Function to normalize filename for template matching
function normalizeFilename($filename) {
    // Convert to lowercase and replace spaces with hyphens
    $normalized = strtolower($filename);
    $normalized = str_replace(' ', '-', $normalized);
    
    // Handle specific filename variations
    $variations = [
        'blue-pearl' => 'bluepearl',
        'bluepearl' => 'bluepearl',
        'bahama-blue' => 'bahama-blue',
        'bahamablue' => 'bahama-blue',
        'baltic-green' => 'baltic-green',
        'balticgreen' => 'baltic-green',
        // Add more variations as needed
    ];
    
    return $variations[$normalized] ?? $normalized;
}

// Process each color image
$position = 1;
$processedColors = [];

foreach ($colorImages as $imagePath) {
    $filename = pathinfo($imagePath, PATHINFO_FILENAME);
    $normalized = normalizeFilename($filename);
    $template = $colorTemplates[$normalized] ?? $colorTemplates['default'];
    
    // Skip if we've already processed this color (avoid duplicates)
    if (in_array($normalized, $processedColors)) {
        continue;
    }
    
    $colorName = $template['name'];
    $description = $template['description'];
    $imageUrl = 'https://www.theangelstones.com/images/colors/' . basename($imagePath);
    
    // Generate clean URL slug from color name
    $slug = strtolower(str_replace(' ', '-', $colorName));
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Special case for Blue Pearl which has a space in the filename
    if ($normalized === 'bluepearl') {
        $slug = 'blue-pearl-granite';
    }
    
    // Generate SKU from color name (first 3 letters of first two words, or first 6 letters)
    $words = explode(' ', $colorName);
    $skuPrefix = '';
    
    if (count($words) >= 2) {
        $skuPrefix = strtoupper(substr($words[0], 0, 3) . substr($words[1], 0, 3));
    } else {
        $skuPrefix = strtoupper(substr($colorName, 0, 6));
    }
    
    $sku = 'GC-' . $skuPrefix . '-' . sprintf('%03d', $position);
    
    $colorData = [
        '@type' => 'ListItem',
        'position' => $position,
        'item' => [
            '@type' => 'Product',
            'name' => $colorName,
            'description' => $description,
            'image' => [
                [
                    '@type' => 'ImageObject',
                    'url' => $imageUrl,
                    'width' => '800',
                    'height' => '800',
                    'caption' => $colorName . ' Color Sample'
                ]
            ],
            'category' => [
                'Granite Colors',
                'Memorial Stones',
                strpos(strtolower($colorName), 'quartzite') !== false ? 'Quartzite Memorials' : 'Granite Memorials'
            ],
            'url' => 'https://www.theangelstones.com/colors/' . $slug,
            'sku' => $sku,
            'material' => strpos(strtolower($colorName), 'quartzite') !== false ? 'Quartzite' : 'Granite',
            'itemCondition' => 'https://schema.org/NewCondition',
            'additionalProperty' => [
                [
                    '@type' => 'PropertyValue',
                    'name' => 'Material Type',
                    'value' => strpos(strtolower($colorName), 'quartzite') !== false ? 'Quartzite' : 'Granite'
                ],
                [
                    '@type' => 'PropertyValue',
                    'name' => 'Finish',
                    'value' => 'Polished'
                ],
                [
                    '@type' => 'PropertyValue',
                    'name' => 'Usage',
                    'value' => 'Memorials, Monuments, Headstones'
                ]
            ],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'USD',
                'price' => '0',
                'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
                'availability' => 'https://schema.org/InStock',
                'url' => 'https://www.theangelstones.com/colors/' . $slug
            ],
            'brand' => [
                '@type' => 'Brand',
                'name' => 'Angel Stones',
                'logo' => 'https://www.theangelstones.com/images/logo.png'
            ]
        ]
    ];
    
    $colorsData['itemListElement'][] = $colorData;
    $processedColors[] = $normalized;
    $position++;
}

// Update the count
$colorsData['numberOfItems'] = count($colorsData['itemListElement']);

// Format JSON with pretty print
$json = json_encode($colorsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Save to file
$outputFile = __DIR__ . '/api/color.json';
if (file_put_contents($outputFile, $json)) {
    echo "Successfully updated color.json with " . count($colorImages) . " colors.\n";
} else {
    echo "Error writing to color.json\n";
}

// Output the first few items as a preview
echo "\nPreview (first 3 items):\n";
$preview = array_slice($colorsData['itemListElement'], 0, 3);
echo json_encode(['itemListElement' => $preview], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
?>
