<?php
// Set proper content type for JSON
header('Content-Type: application/json');

// Disable error display in output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Load PHPExcel
require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Autoloader.php';

// Simple error handling
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

// Configure error handler to prevent HTML errors from corrupting JSON output
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    return true; // Don't execute PHP's internal error handler
});

try {
    // Get search parameters
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $itemsPerPage = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $containerToLoad = isset($_GET['container']) ? trim($_GET['container']) : '';
    
    // Read Excel file
    $file = 'Inventory_Report.xlsx';
    
    if (!file_exists($file)) {
        handleError('Inventory report file not found');
    }
    
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Get all rows
    $data = [];
    $headers = null;
    $rowIndex = 1;
    
    while ($row = $worksheet->rangeToArray('A' . $rowIndex . ':M' . $rowIndex)) {
        if ($rowIndex == 1) {
            // First row is headers
            $headers = $row[0];
        } else {
            // Data rows
            $rowData = array_combine($headers, $row[0]);
            $data[] = $rowData;
        }
        $rowIndex++;
    }
    
    // Process the data
    $inventoryData = processInventoryData($data, $searchTerm, $page, $itemsPerPage, $containerToLoad);
    
    // Output JSON response
    echo json_encode($inventoryData, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Main exception: " . $e->getMessage());
    handleError($e->getMessage());
}

/**
 * Process inventory data from Excel
 * 
 * @param array $data Raw data from Excel
 * @param string $searchTerm Optional search term
 * @param int $page Current page number
 * @param int $itemsPerPage Number of items per page
 * @param string $containerToLoad Specific container to load details for
 * @return array Processed inventory data
 */
function processInventoryData($data, $searchTerm = '', $page = 1, $itemsPerPage = 20, $containerToLoad = '') {
    // Initialize containers
    $containers = [];
    $allItems = [];
    
    // Process each row
    foreach ($data as $row) {
        // Skip rows with missing required data
        if (empty($row['Container']) || empty($row['Product_Type'])) {
            continue;
        }
        
        // Clean up data
        $row = array_map('trim', $row);
        
        // Get container ID
        $containerId = $row['Container'];
        
        // Initialize container if not exists
        if (!isset($containers[$containerId])) {
            $containers[$containerId] = [
                'containerId' => $containerId,
                'name' => $containerId,
                'status' => 'Delivered', // Default status
                'itemCount' => 0,
                'items' => []
            ];
            
            // Set status based on container ID (AS-03 and AS-09 are in transit)
            if ($containerId == 'AS-03' || $containerId == 'AS-09') {
                $containers[$containerId]['status'] = 'In Transit';
            }
        }
        
        // Add item to container
        $containers[$containerId]['items'][] = $row;
        $containers[$containerId]['itemCount']++;
        $allItems[] = $row;
    }
    
    // Apply search filtering if needed
    if (!empty($searchTerm)) {
        $filteredItems = array_filter($allItems, function($item) use ($searchTerm) {
            foreach ($item as $value) {
                if (stripos($value, $searchTerm) !== false) {
                    return true;
                }
            }
            return false;
        });
        
        // Rebuild containers with filtered items
        $tempContainers = [];
        foreach ($filteredItems as $item) {
            $containerId = $item['Container'];
            
            if (!isset($tempContainers[$containerId])) {
                $tempContainers[$containerId] = [
                    'containerId' => $containerId,
                    'name' => $containerId,
                    'status' => $containers[$containerId]['status'],
                    'itemCount' => 0,
                    'items' => []
                ];
            }
            
            $tempContainers[$containerId]['items'][] = $item;
            $tempContainers[$containerId]['itemCount']++;
        }
        
        $containers = $tempContainers;
    }
    
    // For detailed container view
    if (!empty($containerToLoad)) {
        if (isset($containers[$containerToLoad])) {
            $container = $containers[$containerToLoad];
            $totalItems = count($container['items']);
            $totalPages = ceil($totalItems / $itemsPerPage);
            $offset = ($page - 1) * $itemsPerPage;
            
            $container['items'] = array_slice($container['items'], $offset, $itemsPerPage);
            
            return [
                'container' => $container,
                'pagination' => [
                    'page' => $page,
                    'itemsPerPage' => $itemsPerPage,
                    'totalItems' => $totalItems,
                    'totalPages' => $totalPages
                ],
                'searchTerm' => $searchTerm
            ];
        } else {
            return [
                'error' => 'Container not found',
                'containers' => array_values($containers),
                'totalItems' => count($allItems),
                'totalContainers' => count($containers),
                'searchTerm' => $searchTerm
            ];
        }
    }
    
    // Return summary list of containers
    foreach ($containers as &$container) {
        if (count($container['items']) > 3) {
            $container['items'] = array_slice($container['items'], 0, 3);
            $container['hasMoreItems'] = true;
        } else {
            $container['hasMoreItems'] = false;
        }
    }
    
    // Calculate statistics
    $stats = [
        'totalItems' => count($allItems),
        'totalContainers' => count($containers),
        'deliveredContainers' => 0,
        'transitContainers' => 0
    ];
    
    foreach ($containers as $container) {
        if ($container['status'] === 'Delivered') {
            $stats['deliveredContainers']++;
        } else if ($container['status'] === 'In Transit') {
            $stats['transitContainers']++;
        }
    }
    
    return [
        'containers' => array_values($containers),
        'stats' => $stats,
        'searchTerm' => $searchTerm
    ];
}
