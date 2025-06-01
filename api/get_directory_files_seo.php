<?php
/**
 * Enhanced API for directory files with SEO metadata
 * Extends the functionality of get_directory_files.php to include color descriptions and SEO data
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: max-age=3600'); // Cache for 1 hour
header('Access-Control-Allow-Origin: *');

// Base directory for colors
$base_dir = '../images/colors/';

// SEO metadata for granite colors
$colorMetadata = [
    'Georgia Gray' => [
        'description' => 'Premium Georgia Gray granite with a consistent, elegant gray tone, ideal for monuments and memorials.',
        'keywords' => 'Georgia Gray, gray granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Georgia Gray Granite | Premium Monument Stone',
        'image_alt' => 'Georgia Gray Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'GG-101',
        'origin' => 'Georgia, USA',
        'popularity' => 98, // Higher means more popular, used for sorting
    ],
    'Blue Pearl' => [
        'description' => 'Premium Blue Pearl granite with a distinctive blue sheen, perfect for elegant monuments and headstones.',
        'keywords' => 'Blue Pearl, blue granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Blue Pearl Granite | Premium Monument Stone',
        'image_alt' => 'Blue Pearl Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'BP-203',
        'origin' => 'Norway',
        'popularity' => 95,
    ],
    'Bahama Blue' => [
        'description' => 'Distinctive Bahama Blue granite with unique blue tones, perfect for creating striking monuments and memorials.',
        'keywords' => 'Bahama Blue, blue granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Bahama Blue Granite | Premium Monument Stone',
        'image_alt' => 'Bahama Blue Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'BB-304',
        'origin' => 'Brazil',
        'popularity' => 92,
    ],
    'Black Galaxy' => [
        'description' => 'Luxurious Black Galaxy granite features a deep black background with gold and copper flecks that shimmer like stars.',
        'keywords' => 'Black Galaxy, black granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Black Galaxy Granite | Premium Monument Stone',
        'image_alt' => 'Black Galaxy Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'BG-405',
        'origin' => 'India',
        'popularity' => 90,
    ],
    'Impala Black' => [
        'description' => 'Elegant Impala Black granite offers a consistent dark gray to black color, perfect for timeless memorials.',
        'keywords' => 'Impala Black, black granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Impala Black Granite | Premium Monument Stone',
        'image_alt' => 'Impala Black Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'IB-506',
        'origin' => 'South Africa',
        'popularity' => 88,
    ],
    'Imperial Red' => [
        'description' => 'Vibrant Imperial Red granite provides a rich, warm red background with black mineral deposits, creating a striking memorial.',
        'keywords' => 'Imperial Red, red granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Imperial Red Granite | Premium Monument Stone',
        'image_alt' => 'Imperial Red Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'IR-607',
        'origin' => 'India',
        'popularity' => 85,
    ],
    'Indian Black' => [
        'description' => 'Classic Indian Black granite offers a deep, consistent black color that creates elegant, sophisticated memorials.',
        'keywords' => 'Indian Black, black granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Indian Black Granite | Premium Monument Stone',
        'image_alt' => 'Indian Black Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'IB-708',
        'origin' => 'India',
        'popularity' => 94,
    ],
    'Vizag Blue' => [
        'description' => 'Exotic Vizag Blue granite features striking blue tones with black mineral deposits, creating unique and memorable monuments.',
        'keywords' => 'Vizag Blue, blue granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Vizag Blue Granite | Premium Monument Stone',
        'image_alt' => 'Vizag Blue Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'VB-809',
        'origin' => 'India',
        'popularity' => 87,
    ],
    'Desert Brown' => [
        'description' => 'Warm Desert Brown granite presents rich brown tones with golden flecks, perfect for creating inviting memorial stones.',
        'keywords' => 'Desert Brown, brown granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Desert Brown Granite | Premium Monument Stone',
        'image_alt' => 'Desert Brown Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'DB-910',
        'origin' => 'Saudi Arabia',
        'popularity' => 82,
    ],
    'Baltic Green' => [
        'description' => 'Unique Baltic Green granite showcases deep forest green tones with black accents, creating distinctive memorial stones.',
        'keywords' => 'Baltic Green, green granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Baltic Green Granite | Premium Monument Stone',
        'image_alt' => 'Baltic Green Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'BG-011',
        'origin' => 'Finland',
        'popularity' => 80,
    ],
    'Forest Green' => [
        'description' => 'Elegant Forest Green granite features rich, deep green tones that create serene and dignified memorial monuments.',
        'keywords' => 'Forest Green, green granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Forest Green Granite | Premium Monument Stone',
        'image_alt' => 'Forest Green Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'FG-112',
        'origin' => 'India',
        'popularity' => 83,
    ],
];

// Get directory parameter
$directory = isset($_GET['directory']) ? $_GET['directory'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name'; // Default sort by name

// Validate directory path (simple security check)
if (strpos($directory, '..') !== false) {
    echo json_encode(['error' => 'Invalid directory path']);
    exit;
}

// Handle search
if (!empty($search)) {
    // For this example, we'll just simulate a search result with color metadata
    $searchResults = [];
    
    // Search in color names
    foreach ($colorMetadata as $colorName => $metadata) {
        if (stripos($colorName, $search) !== false || 
            stripos($metadata['description'], $search) !== false ||
            stripos($metadata['keywords'], $search) !== false) {
            
            // Build normalized filename (this would need to match your actual file naming convention)
            $normalizedName = strtolower(str_replace(' ', '-', $colorName));
            $filename = $normalizedName . '.jpg';
            
            // Check if the file exists (fallbacks)
            $filepath = $base_dir . $filename;
            if (!file_exists($filepath)) {
                $filename = $normalizedName . '.png';
                $filepath = $base_dir . $filename;
                
                if (!file_exists($filepath)) {
                    // Skip if file doesn't exist
                    continue;
                }
            }
            
            $searchResults[] = [
                'name' => $colorName,
                'path' => 'images/colors/' . $filename,
                'type' => 'color',
                'metadata' => $metadata,
                'popularity' => $metadata['popularity']
            ];
        }
    }
    
    // Sort search results
    if ($sort === 'popularity') {
        usort($searchResults, function($a, $b) {
            return $b['popularity'] - $a['popularity'];
        });
    } else {
        usort($searchResults, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
    }
    
    echo json_encode([
        'directory' => 'search',
        'search' => $search,
        'files' => $searchResults
    ]);
    exit;
}

// Handle colors directory
if ($directory === 'colors') {
    $colorFiles = [];
    
    // Check if the directory exists
    if (is_dir($base_dir)) {
        $files = scandir($base_dir);
        
        foreach ($files as $file) {
            // Skip system files
            if ($file === '.' || $file === '..' || $file === '.DS_Store') {
                continue;
            }
            
            $filepath = $base_dir . $file;
            
            // Get file info
            $fileInfo = pathinfo($filepath);
            $extension = strtolower($fileInfo['extension'] ?? '');
            
            // Skip non-image files
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                continue;
            }
            
            // Extract color name from filename
            $colorName = str_replace(['-', '_'], ' ', $fileInfo['filename']);
            $colorName = ucwords($colorName);
            
            // Check if we have metadata for this color
            $metadata = $colorMetadata[$colorName] ?? [
                'description' => 'Premium ' . $colorName . ' granite for monuments and headstones.',
                'keywords' => $colorName . ', granite, monument stone, headstone',
                'seo_title' => $colorName . ' Granite | Premium Monument Stone',
                'image_alt' => $colorName . ' Granite - Premium Monument Stone by Angel Stones',
                'color_code' => 'GR-' . rand(100, 999),
                'origin' => 'International',
                'popularity' => 70
            ];
            
            $colorFiles[] = [
                'name' => $colorName,
                'path' => 'images/colors/' . $file,
                'type' => 'color',
                'metadata' => $metadata,
                'popularity' => $metadata['popularity'] ?? 70
            ];
        }
        
        // Sort files
        if ($sort === 'popularity') {
            usort($colorFiles, function($a, $b) {
                return $b['popularity'] - $a['popularity'];
            });
        } else {
            usort($colorFiles, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }
        
        echo json_encode([
            'directory' => $directory,
            'files' => $colorFiles
        ]);
        exit;
    }
}

// If we're not handling colors or search, use standard directory file listing
$target_dir = '../' . $directory;

if (is_dir($target_dir)) {
    $files = scandir($target_dir);
    $fileList = [];
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.DS_Store') {
            continue;
        }
        
        $filepath = $target_dir . '/' . $file;
        $fileInfo = pathinfo($filepath);
        
        $fileList[] = [
            'name' => $fileInfo['filename'],
            'path' => $directory . '/' . $file,
            'type' => is_dir($filepath) ? 'directory' : 'file'
        ];
    }
    
    echo json_encode([
        'directory' => $directory,
        'files' => $fileList
    ]);
} else {
    echo json_encode([
        'error' => 'Directory not found',
        'directory' => $directory
    ]);
}
?>
