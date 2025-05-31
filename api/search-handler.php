<?php
/**
 * SEO-Optimized Search Handler for Angel Stones
 * Specifically enhanced for granite color searches
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: max-age=3600'); // Cache for 1 hour
header('Access-Control-Allow-Origin: *');

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'relevance';

// Exit if no query
if (empty($query)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No search query provided',
        'results' => []
    ]);
    exit;
}

// Function to score color match relevance
function scoreColorMatch($colorName, $query) {
    $score = 0;
    
    // Exact match gets highest score
    if (strtolower($colorName) === strtolower($query)) {
        return 100;
    }
    
    // Color name starts with query
    if (stripos($colorName, $query) === 0) {
        $score += 80;
    }
    // Query is contained in color name
    else if (stripos($colorName, $query) !== false) {
        $score += 60;
    }
    
    // Word boundary match (e.g. "Gray" in "Georgia Gray")
    if (preg_match('/\b' . preg_quote($query, '/') . '\b/i', $colorName)) {
        $score += 30;
    }
    
    // Calculate similarity percentage
    $similarity = similar_text(strtolower($colorName), strtolower($query), $percent);
    $score += $percent / 2; // Add up to 50 points for similarity
    
    return $score;
}

// Base directory for colors
$base_dir = '../images/colors/';

// Function to automatically generate metadata for a color based on its name
function generateColorMetadata($colorName) {
    // Extract color type/category
    $colorCategories = [];
    $colorKeywords = [
        'black' => ['black', 'dark', 'galaxy', 'impala', 'indian'],
        'blue' => ['blue', 'pearl', 'bahama', 'vizag'],
        'brown' => ['brown', 'mahogany', 'desert', 'tan'],
        'green' => ['green', 'forest', 'baltic', 'emerald'],
        'gray' => ['gray', 'grey', 'georgia'],
        'red' => ['red', 'imperial', 'ruby', 'indian', 'multi'],
        'white' => ['white', 'snow', 'alaska'],
        'pink' => ['pink', 'rose', 'blush']    
    ];
    
    $popularColors = [
        'Georgia Gray', 'Blue Pearl', 'Black Galaxy', 'Indian Black', 'American Black',
        'Bahama Blue', 'Imperial Red'
    ];
    
    // Detect categories based on name
    foreach ($colorKeywords as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($colorName, $keyword) !== false) {
                $colorCategories[] = $category;
                break;
            }
        }
    }
    
    // If no categories detected, use 'multicolor'
    if (empty($colorCategories)) {
        $colorCategories[] = 'multicolor';
    }
    
    // Determine if this is a popular color
    $isPopular = in_array($colorName, $popularColors);
    if ($isPopular) {
        $colorCategories[] = 'popular';
    }
    
    // Generate origin based on common patterns in name
    $origin = 'International';
    if (stripos($colorName, 'american') !== false || stripos($colorName, 'georgia') !== false) {
        $origin = 'USA';
        $colorCategories[] = 'domestic';
    } else if (stripos($colorName, 'canadian') !== false) {
        $origin = 'Canada';
        $colorCategories[] = 'domestic';
    } else if (stripos($colorName, 'indian') !== false || stripos($colorName, 'galaxy') !== false) {
        $origin = 'India';
        $colorCategories[] = 'imported';
    } else if (stripos($colorName, 'baltic') !== false) {
        $origin = 'Finland';
        $colorCategories[] = 'imported';
    } else if (stripos($colorName, 'blue pearl') !== false) {
        $origin = 'Norway';
        $colorCategories[] = 'imported';
    } else if (stripos($colorName, 'bahama') !== false) {
        $origin = 'Brazil';
        $colorCategories[] = 'imported';
    } else if (stripos($colorName, 'impala') !== false) {
        $origin = 'South Africa';
        $colorCategories[] = 'imported';
    } else {
        $colorCategories[] = 'imported';
    }
    
    // Calculate popularity score (higher for known popular colors)
    $popularity = $isPopular ? rand(90, 98) : rand(70, 89);
    
    // Generate a color code
    $prefix = strtoupper(substr(str_replace(' ', '', $colorName), 0, 2));
    $code = $prefix . '-' . rand(100, 999);
    
    // Generate description and keywords based on color name and categories
    $categoryDesc = !empty($colorCategories) ? $colorCategories[0] : 'premium';
    $description = "Premium {$colorName} granite features stunning {$categoryDesc} tones, perfect for creating elegant monuments and memorial headstones.";
    $keywords = "{$colorName}, {$categoryDesc} granite, monument stone, headstone, memorial granite";
    
    // For certain colors, use more specific descriptions
    if (stripos($colorName, 'black galaxy') !== false) {
        $description = "Luxurious Black Galaxy granite features a deep black background with gold and copper flecks that shimmer like stars.";
    } else if (stripos($colorName, 'blue pearl') !== false) {
        $description = "Premium Blue Pearl granite with a distinctive blue sheen, perfect for elegant monuments and headstones.";
    } else if (stripos($colorName, 'georgia gray') !== false) {
        $description = "Premium Georgia Gray granite with a consistent, elegant gray tone, ideal for monuments and memorials.";
    }
    
    return [
        'description' => $description,
        'keywords' => $keywords,
        'seo_title' => "{$colorName} Granite | Premium Monument Stone",
        'image_alt' => "{$colorName} Granite - Premium Monument Stone by Angel Stones",
        'color_code' => $code,
        'origin' => $origin,
        'popularity' => $popularity,
        'categories' => array_unique($colorCategories)
    ];
}

// Base metadata for common colors - will be used as fallbacks and for popular colors
$baseColorMetadata = [
    'Georgia Gray' => [
        'description' => 'Premium Georgia Gray granite with a consistent, elegant gray tone, ideal for monuments and memorials.',
        'keywords' => 'Georgia Gray, gray granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Georgia Gray Granite | Premium Monument Stone',
        'image_alt' => 'Georgia Gray Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'GG-101',
        'origin' => 'Georgia, USA',
        'popularity' => 98,
        'categories' => ['gray', 'popular', 'domestic']
    ],
    'Blue Pearl' => [
        'description' => 'Premium Blue Pearl granite with a distinctive blue sheen, perfect for elegant monuments and headstones.',
        'keywords' => 'Blue Pearl, blue granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Blue Pearl Granite | Premium Monument Stone',
        'image_alt' => 'Blue Pearl Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'BP-203',
        'origin' => 'Norway',
        'popularity' => 95,
        'categories' => ['blue', 'premium', 'imported']
    ],
    'Black Galaxy' => [
        'description' => 'Luxurious Black Galaxy granite features a deep black background with gold and copper flecks that shimmer like stars.',
        'keywords' => 'Black Galaxy, black granite, monument stone, headstone, memorial granite',
        'seo_title' => 'Black Galaxy Granite | Premium Monument Stone',
        'image_alt' => 'Black Galaxy Granite - Premium Monument Stone by Angel Stones',
        'color_code' => 'BG-405',
        'origin' => 'India',
        'popularity' => 90,
        'categories' => ['black', 'premium', 'imported']
    ]
];

// Dynamically discover all colors from the images directory
$colorMetadata = [];
if (is_dir($base_dir)) {
    $files = scandir($base_dir);
    
    foreach ($files as $file) {
        // Skip system files
        if ($file === '.' || $file === '..' || $file === '.DS_Store') {
            continue;
        }
        
        // Get file info
        $fileInfo = pathinfo($base_dir . $file);
        $extension = strtolower($fileInfo['extension'] ?? '');
        
        // Skip non-image files
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            continue;
        }
        
        // Extract color name from filename
        $colorName = str_replace(['-', '_'], ' ', $fileInfo['filename']);
        $colorName = ucwords($colorName); // Capitalize each word
        
        // Check if we have base metadata for this color
        if (isset($baseColorMetadata[$colorName])) {
            $colorMetadata[$colorName] = $baseColorMetadata[$colorName];
        } else {
            // Generate metadata automatically
            $colorMetadata[$colorName] = generateColorMetadata($colorName);
        }
    }
}

// If no colors were found, use the base metadata as a fallback
if (empty($colorMetadata)) {
    $colorMetadata = $baseColorMetadata;
}

// Base directory for colors
$base_dir = '../images/colors/';

// Process search results
$results = [];
$colorsByRelevance = [];

// First pass: Score all colors for relevance to the query
foreach ($colorMetadata as $colorName => $metadata) {
    // Check color name
    $nameScore = scoreColorMatch($colorName, $query);
    
    // Check keywords
    $keywordScore = 0;
    if (stripos($metadata['keywords'], $query) !== false) {
        $keywordScore = 50;
    }
    
    // Check description
    $descScore = 0;
    if (stripos($metadata['description'], $query) !== false) {
        $descScore = 30;
    }
    
    // Check categories (color type like "blue", "black", etc.)
    $categoryScore = 0;
    if (isset($metadata['categories']) && in_array(strtolower($query), $metadata['categories'])) {
        $categoryScore = 70;
    }
    
    // Calculate total relevance score
    $totalScore = max($nameScore, $keywordScore, $descScore, $categoryScore);
    
    // Only include results with a minimum relevance
    if ($totalScore > 20) {
        $colorsByRelevance[$colorName] = [
            'score' => $totalScore,
            'metadata' => $metadata,
            'popularity' => $metadata['popularity'] ?? 50
        ];
    }
}

// Sort results based on selected criteria
if ($sort === 'relevance') {
    // Sort by relevance score (default)
    uasort($colorsByRelevance, function($a, $b) {
        return $b['score'] - $a['score'];
    });
} else if ($sort === 'popularity') {
    // Sort by popularity
    uasort($colorsByRelevance, function($a, $b) {
        return $b['popularity'] - $a['popularity'];
    });
} else if ($sort === 'name') {
    // Sort by name
    uksort($colorsByRelevance, function($a, $b) {
        return strcmp($a, $b);
    });
}

// Build final results array
foreach ($colorsByRelevance as $colorName => $data) {
    // Build normalized filename
    $normalizedName = strtolower(str_replace(' ', '-', $colorName));
    $filename = $normalizedName . '.jpg';
    
    // Check if the file exists (fallbacks)
    $filepath = $base_dir . $filename;
    if (!file_exists($filepath)) {
        $filename = $normalizedName . '.png';
        $filepath = $base_dir . $filename;
        
        if (!file_exists($filepath)) {
            // Try with underscores instead of hyphens
            $filename = strtolower(str_replace(' ', '_', $colorName)) . '.jpg';
            $filepath = $base_dir . $filename;
            
            if (!file_exists($filepath)) {
                $filename = strtolower(str_replace(' ', '_', $colorName)) . '.png';
                $filepath = $base_dir . $filename;
                
                if (!file_exists($filepath)) {
                    // Use placeholder if no image found
                    $filename = 'placeholder.jpg';
                }
            }
        }
    }
    
    // Build result item
    $results[] = [
        'name' => $colorName,
        'path' => 'images/colors/' . $filename,
        'type' => 'color',
        'relevance' => $data['score'],
        'metadata' => $data['metadata'],
        'url_fragment' => '/colors/' . $normalizedName
    ];
}

// If we're only returning color search results for a specific type
if ($type === 'colors') {
    echo json_encode([
        'status' => 'success',
        'query' => $query,
        'total' => count($results),
        'results' => $results
    ]);
    exit;
}

// For 'all' type, we need to combine with regular search results
// This would typically be implemented to include other content types
// For now, we'll just return the color results

echo json_encode([
    'status' => 'success',
    'query' => $query,
    'total' => count($results),
    'results' => $results
]);
