<?php
/**
 * Get Color Images - Dynamically fetches color images from the images/colors directory
 * For Angel Stones website - Enhanced Version with Descriptions
 * This version supports both traditional web display and mobile app integration
 */

// Performance monitoring
$start_time = microtime(true);
$start_memory = memory_get_usage();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON with caching headers for performance
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Add caching headers for better performance (cache for 15 minutes)
$cache_time = 15 * 60; // 15 minutes
header('Cache-Control: public, max-age=' . $cache_time);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime(__FILE__)) . ' GMT');

// Color descriptions extracted from colors.json
$colorDescriptions = [
    'bahama-blue' => [
        'name' => 'Bahama Blue',
        'description' => 'Distinctive Bahama Blue granite with unique blue tones, perfect for creating striking monuments and memorials.',
    ],
    'baltic-green' => [
        'name' => 'Baltic Green',
        'description' => 'Unique Baltic Green granite showcases deep forest green tones with nice accents, creating distinctive memorial stones.',
    ],
    'dark-barre-gray' => [
        'name' => 'Dark Barre Gray',
        'description' => 'Classic Dark Barre Gray granite features consistent dark gray tones that provide an elegant and timeless appearance for monuments.',
    ],
    'forest-green' => [
        'name' => 'Forest Green',
        'description' => 'Elegant Forest Green granite features rich, deep green tones that create serene and dignified memorial monuments.',
    ],
    'georgia-gray' => [
        'name' => 'Georgia Gray',
        'description' => 'Premium Georgia Gray granite with a consistent, elegant gray tone, ideal for monuments and memorials.',
    ],
    'green-breeze' => [
        'name' => 'Green Breeze',
        'description' => 'Luxurious Green Breeze granite features gentle green hues with subtle patterns, creating an elegant and peaceful memorial.',
    ],
    'green-dream' => [
        'name' => 'Green Dream',
        'description' => 'Vibrant Green Dream granite offers rich emerald tones with unique patterns, making it perfect for distinctive memorials.',
    ],
    'green-pearl' => [
        'name' => 'Green Pearl',
        'description' => 'Distinctive Green Pearl granite offers a light green background with pearl-like iridescence, creating sophisticated and elegant monuments.',
    ],
    'green-wave-quartzite' => [
        'name' => 'Green Wave Quartzite',
        'description' => 'Extraordinary Green Wave Quartzite features dramatic flowing patterns of green and white, creating spectacular, one-of-a-kind memorials.',
    ],
    'imperial-green' => [
        'name' => 'Imperial Green',
        'description' => 'Majestic Imperial Green granite features rich forest green tones with elegant accents, perfect for creating impressive and enduring monuments.',
    ],
    'jet-black' => [
        'name' => 'Jet Black',
        'description' => 'Premium Jet Black granite provides a deep, consistent black color that creates sophisticated, elegant, and timeless memorials.',
    ],
    'medium-barre-gray' => [
        'name' => 'Medium Barre Gray',
        'description' => 'Classic Medium Barre Gray granite features a light to medium gray tone with subtle texture, providing an elegant and versatile option for memorials.',
    ],
    'nh-red' => [
        'name' => 'NH Red',
        'description' => 'Vibrant NH Red granite features warm red and pink tones that create distinctive and memorable monuments with a unique character.',
    ],
    'olive-green' => [
        'name' => 'Olive Green',
        'description' => 'Subtle Olive Green granite offers understated elegance with its muted green tones, perfect for creating harmonious and serene memorials.',
    ],
    'oriental-green' => [
        'name' => 'Oriental Green',
        'description' => 'Elegant Oriental Green granite with rich, deep green tones, perfect for creating sophisticated memorials and monuments.',
    ],
    'pacific-gray' => [
        'name' => 'Pacific Gray',
        'description' => 'Sophisticated Pacific Gray granite with cool, soothing gray tones, ideal for creating elegant and timeless memorial monuments.',
    ],
    'queens-green' => [
        'name' => 'Queens Green',
        'description' => 'Luxurious Queens Green granite with rich, deep emerald tones, perfect for creating distinguished and regal memorial monuments.',
    ],
    'rain-forest-green' => [
        'name' => 'Rain Forest Green',
        'description' => 'Vibrant Rain Forest Green granite with lush, natural green tones and subtle patterns reminiscent of tropical foliage, perfect for creating striking and memorable memorial monuments.',
    ],
    'silk-blue' => [
        'name' => 'Silk Blue',
        'description' => 'High-quality Blue granite ideal for monuments and memorials.',
    ],
    'sanfrancisco-green' => [
        'name' => 'Sanfrancisco Green',
        'description' => 'Sanfrancisco Green Granite features distinctive tones and patterns, making it an elegant choice for striking memorials and headstones.',
    ],
    'tropical-green' => [
        'name' => 'Tropical Green',
        'description' => 'Striking Tropical Green granite offers vibrant green tones with dramatic speckling, creating bold and memorable memorial monuments.',
    ],
    'aurora' => [
        'name' => 'Aurora',
        'description' => 'Aurora Granite features distinctive tones and patterns, making it an elegant choice for striking memorials and headstones.',
    ],
    'blue-pearl' => [
        'name' => 'Blue Pearl',
        'description' => 'Premium Blue Pearl granite features a distinctive blue sheen with silver speckles, making it a stunning choice for elegant monuments and headstones.',
    ],
    'galaxy' => [
        'name' => 'Galaxy',
        'description' => 'Galaxy Granite features distinctive tones and patterns, making it an elegant choice for striking memorials and headstones.',
    ],
    'green' => [
        'name' => 'Green',
        'description' => 'Green Granite features distinctive tones and patterns, making it an elegant choice for striking memorials and headstones.',
    ],
    'himalayan-blue' => [
        'name' => 'Himalayan Blue',
        'description' => 'Striking Himalayan Blue granite features deep blue tones with white and gray veining, creating elegant and distinctive memorials.',
    ],
    'indian-black' => [
        'name' => 'Indian Black',
        'description' => 'Indian Black Granite features distinctive tones and patterns, making it an elegant choice for striking memorials and headstones.',
    ],
    'paradiso' => [
        'name' => 'Paradiso',
        'description' => 'Exceptional Paradiso granite features deep brown tones with gold and burgundy accents, creating unique and luxurious memorials.',
    ],
    'vizag-blue' => [
        'name' => 'Vizag Blue',
        'description' => 'Vizag Blue granite offers a beautiful blue-gray color with subtle grain patterns, perfect for elegant and timeless memorials.',
    ],
    'coral-gray' => [
        'name' => 'Coral Gray',
        'description' => 'Elegant Coral Gray granite features warm gray tones with subtle coral undertones, creating beautiful and distinctive memorial monuments.',
    ],
    'white-and-red' => [
        'name' => 'White And Red',
        'description' => 'White And Red Granite features distinctive tones and patterns, making it an elegant choice for striking memorials and headstones.',
    ],
];

// Base directory for color images
$colorsDir = __DIR__ . '/images/colors/';

// Check if directory exists
if (!is_dir($colorsDir)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Colors directory not found at: ' . $colorsDir,
        'current_dir' => __DIR__
    ], JSON_PRETTY_PRINT);
    exit;
}

// Log directory being scanned
error_log("Scanning directory: " . $colorsDir);

// Get all image files
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$colors = [];

// Read directory
$files = scandir($colorsDir);

// Log files found
error_log("Files in directory: " . print_r($files, true));

foreach ($files as $file) {
    // Skip hidden files and directories
    if ($file[0] === '.') {
        continue;
    }
    
    // Get file info
    $filePath = $colorsDir . $file;
    $fileInfo = pathinfo($filePath);
    $ext = strtolower($fileInfo['extension'] ?? '');
    
    // Check if it's an allowed image type and a file (not directory)
    if (is_file($filePath) && in_array($ext, $allowedExtensions)) {
        // Get name from filename by replacing hyphens/underscores with spaces
        $name = $fileInfo['filename'];
        $normalizedName = strtolower(str_replace([' ', '.'], ['-', ''], $name));
        $displayName = str_replace(['-', '_'], ' ', $name);
        $displayName = ucwords($displayName);
        
        // Create relative path for web access
        $relativePath = 'images/colors/' . $file;
        $webPath = $relativePath;
        
        // Get file modification time
        $fileTime = @filemtime($filePath);
        if ($fileTime === false) {
            $fileTime = time(); // Fallback to current time if can't get mtime
        }
        
        // For debugging
        error_log("Image added - Name: $displayName, Path: $webPath, Size: " . filesize($filePath) . " bytes");
        
        // Check if file is readable
        if (!is_readable($filePath)) {
            error_log("Warning: Cannot read file: $filePath");
            continue;
        }
        
        // Get description from our database or generate a more specific one based on the color name
        if (isset($colorDescriptions[$normalizedName])) {
            $description = $colorDescriptions[$normalizedName]['description'];
        } elseif (isset($colorDescriptions[strtolower($name)])) {
            $description = $colorDescriptions[strtolower($name)]['description'];
        } else {
            // Enhanced adjectives, features, and uses for elegance and emotional appeal
            $adjectives = [
                'elegant', 'distinctive', 'timeless', 'refined', 'exquisite',
                'bold', 'serene', 'graceful', 'striking', 'sophisticated'
            ];
        
            $features = [
                'rich natural veining', 'subtle color gradients', 'unique surface textures',
                'deep, lasting hues', 'captivating mineral patterns', 'harmonious color tones',
                'naturally varied aesthetics', 'organic flow of patterns', 'enduring visual appeal'
            ];
        
            $uses = [
                'memorializing loved ones with dignity', 'crafting timeless tributes',
                'designing elegant and lasting monuments', 'creating headstones with meaning',
                'honoring memories with sophistication', 'shaping graceful remembrance pieces'
            ];
        
            // Select random elements for dynamic and varied descriptions
            $adjective = $adjectives[array_rand($adjectives)];
            $feature = $features[array_rand($features)];
            $use = $uses[array_rand($uses)];
        
            // Create the final natural-sounding description
            $description = ucfirst($adjective) . ' ' . $displayName . ' granite features ' .
                           $feature . ', ideal for ' . $use . '.';
        }
        
        // Create schema.org compatible structure
        $schemaItem = [
            '@type' => 'Product',
            'name' => "$displayName Granite",
            'description' => $description,
            'image' => [
                [
                    '@type' => 'ImageObject',
                    'url' => "https://www.theangelstones.com/$webPath",
                    'width' => '800',
                    'height' => '800',
                    'caption' => "$displayName Granite Color Sample"
                ]
            ],
            'category' => ['Granite Colors', 'Memorial Stones'],
            'material' => 'Granite',
            'additionalProperty' => [
                [
                    '@type' => 'PropertyValue',
                    'name' => 'Material Type',
                    'value' => 'Granite'
                ],
                [
                    '@type' => 'PropertyValue',
                    'name' => 'Color',
                    'value' => $displayName
                ]
            ]
        ];
        
        // Add to colors array with additional metadata
        $colors[] = [
            'name' => $displayName,
            'path' => $webPath,
            'filename' => $file,
            'size' => filesize($filePath),
            'modified' => $fileTime,
            'type' => mime_content_type($filePath),
            'description' => $description,
            'schema' => $schemaItem
        ];
    }
}

// Sort colors alphabetically by name
usort($colors, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Calculate performance metrics
$end_time = microtime(true);
$end_memory = memory_get_usage();
$execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
$memory_used = round(($end_memory - $start_memory) / 1024, 2); // Convert to KB
$peak_memory = round(memory_get_peak_usage() / 1024, 2); // Convert to KB

// Create schema.org compatible structure for the full response
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Angel Stones Granite Color Varieties',
    'description' => 'Explore our premium granite colors for monuments and headstones.',
    'itemListOrder' => 'Unordered',
    'url' => 'https://www.theangelstones.com/colors',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => 'https://www.theangelstones.com/colors'
    ],
    'itemListElement' => []
];

// Add each color as a list item
$position = 1;
foreach ($colors as $color) {
    $schemaData['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'item' => $color['schema']
    ];
}
$schemaData['numberOfItems'] = count($colors);

// Return JSON response with performance metrics
$response = [
    'success' => true,
    'count' => count($colors),
    'directory' => 'images/colors/',
    'colors' => $colors,
    'schema' => $schemaData,
    'timestamp' => time(),
    'performance' => [
        'execution_time_ms' => $execution_time,
        'memory_used_kb' => $memory_used,
        'peak_memory_kb' => $peak_memory,
        'files_processed' => count($files) - 2, // subtract . and ..
        'colors_found' => count($colors)
    ],
    'debug' => [
        'document_root' => isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'Not available',
        'script_dir' => __DIR__,
        'colors_dir' => $colorsDir,
        'files_found' => count($files) - 2, // subtract . and ..
        'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'CLI',
        'php_version' => PHP_VERSION,
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit')
    ]
];

// Log performance for monitoring
error_log("Color API Performance - Execution: {$execution_time}ms, Memory: {$memory_used}KB, Colors: " . count($colors));

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
