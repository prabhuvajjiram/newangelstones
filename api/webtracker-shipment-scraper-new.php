<?php
/**
 * WebTracker Shipment Scraper (New Version)
 * 
 * This script scrapes shipment data from WebTracker Wisegrid for Angel Stones
 * using the exact same authentication approach as the successful auto script.
 * 
 * Usage:
 * php api/webtracker-shipment-scraper-new.php [--test] [--verbose]
 */

// Include database configuration
require_once(__DIR__ . '/shipment_db_config.php');

/**
 * Initialize cURL session with common options
 */
function initCurl() {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification - required for WebTracker
        CURLOPT_SSL_VERIFYHOST => 0       // Disable host verification - required for WebTracker
    ]);
    return $ch;
}

// Setup logging
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/shipment-scraper.log';
$timestamp = date('Y-m-d-H-i-s');

// Command line parameters
$testMode = false;
$verboseMode = false;

// Check if running from command line
if (php_sapi_name() === 'cli') {
    if (isset($argv) && is_array($argv)) {
        foreach ($argv as $arg) {
            if ($arg === '--test') {
                $testMode = true;
                break;
            }
        }
    }
} else {
    // Web mode
    $testMode = isset($_GET['test']) && $_GET['test'] === '1';
}

if (php_sapi_name() === 'cli') {
    if (isset($argv) && is_array($argv)) {
        foreach ($argv as $arg) {
            if ($arg === '--verbose') {
                $verboseMode = true;
                break;
            }
        }
    }
} else {
    $verboseMode = isset($_GET['verbose']) && $_GET['verbose'] === '1';
}

// Initialize the log file
if ($testMode) {
    file_put_contents($logFile, "--- WebTracker Shipment Scraper (TEST MODE): $timestamp ---\n");
    echo "\n==== RUNNING IN TEST MODE - NO DATABASE CHANGES WILL BE MADE ====\n\n";
} else {
    file_put_contents($logFile, "--- WebTracker Shipment Scraper: $timestamp ---\n");
}

if ($verboseMode) {
    echo "Verbose mode enabled - detailed output will be shown\n";
}

/**
 * Log a message to the log file and console
 */
function logDebug($message, $type = 'INFO') {
    global $logFile, $verboseMode;
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$type] $message\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    if ($verboseMode || $type === 'ERROR' || $type === 'WARNING') {
        echo $logMessage;
    }
}

/**
 * Save HTML content to a debug file
 */
function saveDebugHTML($filename, $content) {
    global $logDir, $testMode, $verboseMode;
    
    // Always save in test mode, otherwise only in verbose mode
    if ($testMode || $verboseMode) {
        $path = $logDir . '/' . $filename;
        file_put_contents($path, $content);
        logDebug("Saved HTML to $path");
    }
}

/**
 * Extract shipment data from the HTML table
 */
function extractShipmentData($html) {
    try {
        logDebug("Starting shipment data extraction from HTML");
        
        // Save the raw HTML for debugging
        saveDebugHTML("extraction-debug.html", $html);
        
        // First check if we have actual data
        if (empty($html)) {
            throw new Exception("No HTML content provided");
        }
        
        // Create a DOM document and load the HTML
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        
        // These are the exact column headers from WebTracker
        $expectedHeaders = [
            'Shipment#', 'Bill', 'Shipper', 'Consignee', 'Origin', 'ETD', 
            'Destination', 'ETA', 'Declaration Country/Region', 'Actual Pickup', 
            'Additional Terms', 'Booked Online', 'Charges', 'Charges Apply', 
            'Consignee Address', 'Consignee City', 'Consignee Full Address', 
            'Consignee Post Code', 'Consignee State', 'Container Mode', 
            'Containers', 'Currency', 'Current Discharge Port', 'Current Load Port', 
            'Current Vessel', 'Current Voy./Flight', 'Delivery Agent', 
            'Delivery Date', 'Delivery Required By', 'Estimated Delivery', 
            'Estimated Pickup', 'First Leg Load ATD', 'First Leg Load ETD', 
            'Goods Description', 'Goods Value', 'Inspection', 'Job Notes', 
            'Last Leg Discharge ATA', 'Last Leg Discharge ETA', 'Loading Meters', 
            'Main Discharge Port', 'Main Load Port', 'Main Vessel', 
            'Main Voy./Flight', 'Mode', 'On Board', 'Order Ref#', 
            'Owner\'s Ref#', 'Packs', 'Payment Term', 'Pickup Agent', 
            'Pickup Required By', 'Pieces Received', 'Received By', 
            'Received Date', 'Release Type', 'Service Level', 'Shipper Address', 
            'Shipper City', 'Shipper Full Address', 'Shipper Post Code', 
            'Shipper State', 'Shipper\'s Ref#', 'Storage Commences', 
            'TEU', 'Type', 'Volume', 'Weight'
        ];
        
        // Map of expected header text to database column names
        global $headerToColumnMap;
        $headerToColumnMap = [
            'Shipment#' => 'shipment_number',
            'Bill' => 'bill',
            'Shipper' => 'shipper',
            'Consignee' => 'consignee',
            'Origin' => 'origin',
            'ETD' => 'etd',
            'Destination' => 'destination',
            'ETA' => 'eta',
            'Declaration Country/Region' => 'declaration_country',
            'Actual Pickup' => 'actual_pickup',
            'Additional Terms' => 'additional_terms',
            'Booked Online' => 'booked_online',
            'Charges' => 'charges',
            'Charges Apply' => 'charges_apply',
            'Consignee Address' => 'consignee_address',
            'Consignee City' => 'consignee_city',
            'Consignee Full Address' => 'consignee_full_address',
            'Consignee Post Code' => 'consignee_post_code',
            'Consignee State' => 'consignee_state',
            'Container Mode' => 'container_mode',
            'Containers' => 'containers',
            'Currency' => 'currency',
            'Current Discharge Port' => 'current_discharge_port',
            'Current Load Port' => 'current_load_port',
            'Current Vessel' => 'current_vessel',
            'Current Voy./Flight' => 'current_voy_flight',
            'Delivery Agent' => 'delivery_agent',
            'Delivery Date' => 'delivery_date',
            'Delivery Required By' => 'delivery_required_by',
            'Estimated Delivery' => 'estimated_delivery',
            'Estimated Pickup' => 'estimated_pickup',
            'First Leg Load ATD' => 'first_leg_load_atd',
            'First Leg Load ETD' => 'first_leg_load_etd',
            'Goods Description' => 'goods_description',
            'Goods Value' => 'goods_value',
            'Inspection' => 'inspection',
            'Job Notes' => 'job_notes',
            'Last Leg Discharge ATA' => 'last_leg_discharge_ata',
            'Last Leg Discharge ETA' => 'last_leg_discharge_eta',
            'Loading Meters' => 'loading_meters',
            'Main Discharge Port' => 'main_discharge_port',
            'Main Load Port' => 'main_load_port',
            'Main Vessel' => 'main_vessel',
            'Main Voy./Flight' => 'main_voy_flight',
            'Mode' => 'mode',
            'On Board' => 'on_board',
            'Order Ref#' => 'order_ref',
            'Owner\'s Ref#' => 'owners_ref',
            'Packs' => 'packs',
            'Payment Term' => 'payment_term',
            'Pickup Agent' => 'pickup_agent',
            'Pickup Required By' => 'pickup_required_by',
            'Pieces Received' => 'pieces_received',
            'Received By' => 'received_by',
            'Received Date' => 'received_date',
            'Release Type' => 'release_type',
            'Service Level' => 'service_level',
            'Shipper Address' => 'shipper_address',
            'Shipper City' => 'shipper_city',
            'Shipper Full Address' => 'shipper_full_address',
            'Shipper Post Code' => 'shipper_post_code',
            'Shipper State' => 'shipper_state',
            'Shipper\'s Ref#' => 'shippers_ref',
            'Storage Commences' => 'storage_commences',
            'TEU' => 'teu',
            'Type' => 'type',
            'Volume' => 'volume',
            'Weight' => 'weight'
        ];
        
        // Also map our legacy field names to the new standard ones
        $standardFieldMapping = [
            'container_id' => 'Containers',
            'customer' => 'Consignee',
            'port_id' => 'Current Discharge Port',
            'port_date' => 'ETA',
            'location' => 'Destination',
            'est_departure' => 'ETD',
            'est_arrival' => 'ETA',
            'carrier' => 'Current Vessel',
            'status' => 'Additional Terms'
        ];
        
        // Approach 1: Look for the main grid table that contains shipment data
        logDebug("Approach 1: Looking for WebTracker search results grid");
        $tables = $xpath->query('//table[contains(@id, "SearchResultsDataGrid")]');
        if ($tables->length == 0) {
            $tables = $xpath->query('//table[contains(@id, "Grid")]');
        }
        
        if ($tables->length == 0) {
            // Try more general tables
            $tables = $xpath->query('//table');
            logDebug("No specific grid tables found, searching all tables: " . $tables->length);
        } else {
            logDebug("Found " . $tables->length . " grid tables");
        }
        
        $shipments = [];
        
        // Process each table to find one with shipment data
        foreach ($tables as $tableIndex => $table) {
            logDebug("Processing table #$tableIndex");
            
            // Find rows
            $rows = $xpath->query('.//tr', $table);
            
            // Skip empty tables
            if ($rows->length < 2) {
                continue;
            }
            
            // Find the header row (first row or one with class 'DetailsHeader')
            $headerRows = $xpath->query('.//tr[contains(@class, "DetailsHeader")]', $table);
            $headerRow = null;
            
            if ($headerRows->length > 0) {
                $headerRow = $headerRows->item(0);
            } else {
                $headerRow = $rows->item(0);
            }
            
            // Get header cells
            $headerCells = $xpath->query('.//td', $headerRow);
            if ($headerCells->length === 0) {
                // Try with th instead of td
                $headerCells = $xpath->query('.//th', $headerRow);
            }
            
            if ($headerCells->length === 0) {
                logDebug("No header cells found in table #$tableIndex");
                continue;
            }
            
            // Log header count for debugging
            logDebug("Found " . $headerCells->length . " header cells in table #$tableIndex");
            
            // Extract header texts
            $headers = [];
            $headerIndexes = [];
            
            for ($i = 0; $i < $headerCells->length; $i++) {
                $headerText = trim(strip_tags($headerCells->item($i)->textContent));
                $headers[$i] = $headerText;
                
                // Check if this is one of our expected headers
                foreach ($expectedHeaders as $expectedHeader) {
                    if (stripos($headerText, $expectedHeader) !== false || 
                        levenshtein(strtolower($headerText), strtolower($expectedHeader)) <= 2) {
                        $headerIndexes[$expectedHeader] = $i;
                        logDebug("Mapped header '$headerText' to expected '$expectedHeader'");
                        break;
                    }
                }
            }
            
            logDebug("Mapped " . count($headerIndexes) . " headers to expected headers");
            
            // Skip if we don't have at least the Shipment# header
            if (!isset($headerIndexes['Shipment#'])) {
                logDebug("Shipment# column not found in table headers");
                continue;
            }
            
            // Process data rows (skip header row)
            $startRow = ($headerRows->length > 0) ? 0 : 1; // If we found a DetailsHeader, start from 0, otherwise skip first row
            
            for ($i = $startRow; $i < $rows->length; $i++) {
                // Get index of the current header row
                $headerRowIndex = -1;
                for ($idx = 0; $idx < $rows->length; $idx++) {
                    if ($rows->item($idx) === $headerRow) {
                        $headerRowIndex = $idx;
                        break;
                    }
                }
                
                // Skip if this is the header row
                if ($i === $headerRowIndex) {
                    continue;
                }
                
                $row = $rows->item($i);
                $cells = $xpath->query('.//td', $row);
                
                if ($cells->length == 0) {
                    continue; // Skip empty rows
                }
                
                // Create a shipment record
                $shipment = [];
                $hasData = false;
                
                // Process mapped headers
                foreach ($headerIndexes as $headerText => $columnIndex) {
                    if ($columnIndex < $cells->length) {
                        $value = trim($cells->item($columnIndex)->textContent);
                        
                        if (!empty($value) && $value != '&nbsp;') {
                            $hasData = true;
                            
                            // Map to database column name
                            if (isset($headerToColumnMap[$headerText])) {
                                $columnName = $headerToColumnMap[$headerText];
                                $shipment[$columnName] = $value;
                                
                                // Log important fields for debugging
                                if (in_array($headerText, ['Shipper', 'Consignee', 'Charges'])) {
                                    logDebug("Found $headerText: '$value'", "DEBUG");
                                }
                            }
                            
                            // Also store with the original header name
                            $shipment[$headerText] = $value;
                        } else {
                            // Store empty values as NULL instead of empty strings
                            if (isset($headerToColumnMap[$headerText])) {
                                $columnName = $headerToColumnMap[$headerText];
                                $shipment[$columnName] = null;
                            }
                        }
                    }
                }
                
                // Direct access to important fields by position for error checking
                // In case our header mapping failed, try again using direct position access
                if ((!isset($shipment['shipper']) || $shipment['shipper'] === null) && isset($cells) && $cells->length > 2) {
                    $shipment['shipper'] = trim($cells->item(2)->textContent);
                    logDebug("Direct shipper extraction: " . $shipment['shipper'], "DEBUG");
                }
                
                if ((!isset($shipment['consignee']) || $shipment['consignee'] === null || $shipment['consignee'] === 'NC') && isset($cells) && $cells->length > 3) {
                    $shipment['consignee'] = trim($cells->item(3)->textContent);
                    logDebug("Direct consignee extraction: " . $shipment['consignee'], "DEBUG");
                }
                
                if ((!isset($shipment['charges']) || $shipment['charges'] === null) && isset($cells) && $cells->length > 12) {
                    $shipment['charges'] = trim($cells->item(12)->textContent);
                    logDebug("Direct charges extraction: " . $shipment['charges'], "DEBUG");
                }

                // Always force direct field extraction for critical fields to ensure data quality
                if (isset($cells) && $cells->length > 3) {
                    // These fields are so important we'll always use direct extraction
                    $shipment['shipper'] = trim($cells->item(2)->textContent);
                    $shipment['consignee'] = trim($cells->item(3)->textContent);
                    
                    // Direct map of important fields by position
                    $directFieldMap = [
                        // Core shipping info
                        0 => 'shipment_number',
                        1 => 'bill',
                        2 => 'shipper',
                        3 => 'consignee',
                        4 => 'origin',
                        5 => 'etd',
                        6 => 'destination',
                        7 => 'eta',
                        
                        // Charges
                        11 => 'booked_online',
                        12 => 'charges',
                        13 => 'charges_apply',
                        
                        // Consignee address details
                        14 => 'consignee_address',
                        15 => 'consignee_city',
                        16 => 'consignee_full_address',
                        17 => 'consignee_post_code',
                        18 => 'consignee_state',
                        
                        // Container details
                        19 => 'container_mode',
                        20 => 'containers',
                        
                        // Port & vessel info
                        22 => 'current_discharge_port',
                        23 => 'current_load_port',
                        24 => 'current_vessel',
                        25 => 'current_voy_flight',
                        26 => 'delivery_agent',
                        
                        // Dates and scheduling
                        31 => 'first_leg_load_atd',
                        32 => 'first_leg_load_etd',
                        33 => 'goods_description',
                        34 => 'goods_value',
                        35 => 'inspection',
                        37 => 'last_leg_discharge_ata',
                        38 => 'last_leg_discharge_eta',
                        
                        // Other important fields
                        44 => 'mode',
                        45 => 'on_board',
                        48 => 'packs',
                        49 => 'payment_term',
                        
                        // Shipper address details
                        57 => 'shipper_address',
                        58 => 'shipper_city',
                        59 => 'shipper_full_address',
                        60 => 'shipper_post_code',
                        61 => 'shipper_state',
                        63 => 'storage_commences',
                        64 => 'teu',
                        65 => 'type',
                        66 => 'volume',
                        67 => 'weight'
                    ];
                    
                    // Extract all fields we can by direct position
                    foreach ($directFieldMap as $position => $fieldName) {
                        if ($cells->length > $position) {
                            $value = trim($cells->item($position)->textContent);
                            if (!empty($value) && $value != '&nbsp;') {
                                $shipment[$fieldName] = $value;
                                if (in_array($fieldName, ['consignee_address', 'consignee_city', 'consignee_state', 'consignee_post_code', 'shipper_address'])) {
                                    logDebug("Direct extraction of $fieldName: '$value'", "DEBUG");
                                }
                            }
                        }
                    }
                    
                    logDebug("Completed direct field extraction for " . count($directFieldMap) . " fields", "DEBUG");
                }
                
                // Add standard fields mapping
                foreach ($standardFieldMapping as $standardField => $sourceField) {
                    if (isset($shipment[$sourceField]) && !isset($shipment[$standardField])) {
                        $shipment[$standardField] = $shipment[$sourceField];
                    }
                }
                
                // Only add shipments with data and a shipment number
                if ($hasData && isset($shipment['shipment_number'])) {
                    $shipments[] = $shipment;
                } elseif ($hasData && isset($shipment['Shipment#'])) {
                    $shipment['shipment_number'] = $shipment['Shipment#'];
                    $shipments[] = $shipment;
                }
            }
            
            if (!empty($shipments)) {
                logDebug("Successfully extracted " . count($shipments) . " shipments from table #$tableIndex");
                break; // Stop after finding a table with shipments
            }
        }
        
        // Approach 2: If no tables with shipment data were found, try direct pattern matching
        if (empty($shipments)) {
            logDebug("Approach 2: Using pattern matching to find shipment data");
            
            // Look for STP or STPU patterns which are likely shipment IDs
            if (preg_match_all('/STP[U]?\d+/i', $html, $matches)) {
                logDebug("Found " . count($matches[0]) . " potential shipment IDs");
                
                // Get surrounding text for each shipment ID to extract more data
                foreach ($matches[0] as $shipmentId) {
                    // Create a basic shipment record with the ID
                    $shipment = [
                        'shipment_number' => $shipmentId,
                        'Shipment#' => $shipmentId,
                        'containers' => $shipmentId,
                        'extraction_method' => 'pattern_matching'
                    ];
                    
                    // Try to find additional data near this shipment ID
                    $context = getTextContext($html, $shipmentId, 1000);
                    if ($context) {
                        // Look for common data patterns in the context
                        if (preg_match('/ANGEL STONES|AMMAN GRANITE/i', $context, $match)) {
                            $shipment['consignee'] = $match[0];
                        }
                        
                        if (preg_match('/(Savannah|Tacoma|New York)/i', $context, $match)) {
                            $shipment['destination'] = $match[0];
                        }
                        
                        if (preg_match('/(\d{1,2}-[A-Za-z]{3}-\d{2}\s+\d{1,2}:\d{2})/i', $context, $match)) {
                            $shipment['eta'] = $match[0];
                        }
                    }
                    
                    $shipments[] = $shipment;
                }
            }
        }
        
        // Final check
        if (empty($shipments)) {
            throw new Exception("No valid shipment data found in the provided HTML");
        }
        
        // Save the extracted data to a file for debugging
        file_put_contents(__DIR__ . '/../logs/extracted-shipments.json', json_encode($shipments, JSON_PRETTY_PRINT));
        
        return $shipments;
        
    } catch (Exception $e) {
        logDebug("Error extracting shipment data: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Helper function to get text context around a keyword
 */
function getTextContext($html, $keyword, $radius = 200) {
    $pos = stripos($html, $keyword);
    if ($pos === false) {
        return null;
    }
    
    $start = max(0, $pos - $radius);
    $length = strlen($keyword) + (2 * $radius);
    $context = substr($html, $start, $length);
    
    // Strip tags to get clean text
    return strip_tags($context);
}

function normalizeHeader($header) {
    // Convert to lowercase and remove special characters
    $header = strtolower(trim($header));
    $header = preg_replace('/[^a-z0-9\s]/', '', $header);
    $header = preg_replace('/\s+/', '_', $header);
    
    // Map common variations to standard names
    $headerMap = [
        'shipmentno' => 'shipment_number',
        'shipmentid' => 'shipment_number',
        'shipment_no' => 'shipment_number',
        'tracking' => 'tracking_number',
        'trackingno' => 'tracking_number',
        'tracking_no' => 'tracking_number',
        'billno' => 'bill',
        'bill_no' => 'bill',
        'billnumber' => 'bill',
        'origin' => 'origin',
        'destination' => 'destination',
        'dest' => 'destination',
        'status' => 'shipment_status',
        'shipmentstatus' => 'shipment_status',
        'currentstatus' => 'shipment_status',
        'date' => 'shipment_date',
        'shipmentdate' => 'shipment_date',
        'createdate' => 'creation_date',
        'created' => 'creation_date',
        'lastupdate' => 'last_update',
        'lastupdated' => 'last_update',
        'updatedate' => 'last_update',
        'weight' => 'weight',
        'pieces' => 'piece_count',
        'noofpieces' => 'piece_count',
        'shipper' => 'shipper',
        'consignee' => 'consignee',
        'reference' => 'reference_number',
        'refno' => 'reference_number',
        'ref_no' => 'reference_number',
        'service' => 'service_type',
        'servicetype' => 'service_type',
        'etd' => 'etd',
        'eta' => 'eta',
        'declaration_country' => 'declaration_country',
        'actual_pickup' => 'actual_pickup',
        'additional_terms' => 'additional_terms',
        'booked_online' => 'booked_online',
        'charges' => 'charges',
        'charges_apply' => 'charges_apply',
        'consignee_address' => 'consignee_address',
        'consignee_city' => 'consignee_city',
        'consignee_full_address' => 'consignee_full_address',
        'consignee_post_code' => 'consignee_post_code',
        'consignee_state' => 'consignee_state',
        'container_mode' => 'container_mode',
        'containers' => 'containers',
        'currency' => 'currency',
        'current_discharge_port' => 'current_discharge_port',
        'current_load_port' => 'current_load_port',
        'current_vessel' => 'current_vessel',
        'current_voy_flight' => 'current_voy_flight',
        'delivery_agent' => 'delivery_agent',
        'delivery_date' => 'delivery_date',
        'delivery_required_by' => 'delivery_required_by',
        'estimated_delivery' => 'estimated_delivery',
        'estimated_pickup' => 'estimated_pickup',
        'first_leg_load_atd' => 'first_leg_load_atd',
        'first_leg_load_etd' => 'first_leg_load_etd',
        'goods_description' => 'goods_description',
        'goods_value' => 'goods_value',
        'inspection' => 'inspection',
        'job_notes' => 'job_notes',
        'last_leg_discharge_ata' => 'last_leg_discharge_ata',
        'last_leg_discharge_eta' => 'last_leg_discharge_eta',
        'loading_meters' => 'loading_meters',
        'main_discharge_port' => 'main_discharge_port',
        'main_load_port' => 'main_load_port',
        'main_vessel' => 'main_vessel',
        'main_voy_flight' => 'main_voy_flight',
        'mode' => 'mode',
        'on_board' => 'on_board',
        'order_ref' => 'order_ref',
        'owners_ref' => 'owners_ref',
        'packs' => 'packs',
        'payment_term' => 'payment_term',
        'pickup_agent' => 'pickup_agent',
        'pickup_required_by' => 'pickup_required_by',
        'pieces_received' => 'pieces_received',
        'received_by' => 'received_by',
        'received_date' => 'received_date',
        'release_type' => 'release_type',
        'service_level' => 'service_level',
        'shipper_address' => 'shipper_address',
        'shipper_city' => 'shipper_city',
        'shipper_full_address' => 'shipper_full_address',
        'shipper_post_code' => 'shipper_post_code',
        'shipper_state' => 'shipper_state',
        'shippers_ref' => 'shippers_ref',
        'storage_commences' => 'storage_commences',
        'teu' => 'teu',
        'type' => 'type',
        'volume' => 'volume',
        'weight' => 'weight',
        'json_data' => 'json_data'
    ];
    
    return $headerMap[$header] ?? $header;
}

function normalizeValue($value) {
    if (is_array($value)) {
        return json_encode($value);
    }
    
    // Remove extra whitespace and normalize spaces
    $value = trim(preg_replace('/\s+/', ' ', $value));
    
    // Convert common date formats to standard format
    if (preg_match('/^\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/', $value)) {
        $date = date_create_from_format('d/m/Y', str_replace('-', '/', $value));
        if ($date) {
            return $date->format('Y-m-d');
        }
    }
    
    // Normalize status values
    $statusMap = [
        'delivered' => 'DELIVERED',
        'in transit' => 'IN_TRANSIT',
        'intransit' => 'IN_TRANSIT',
        'pending' => 'PENDING',
        'cancelled' => 'CANCELLED',
        'canceled' => 'CANCELLED',
        'completed' => 'DELIVERED',
        'picked up' => 'PICKED_UP',
        'pickup' => 'PICKED_UP',
        'on hold' => 'ON_HOLD',
        'exception' => 'EXCEPTION',
        'delayed' => 'DELAYED'
    ];
    
    $lowerValue = strtolower($value);
    if (isset($statusMap[$lowerValue])) {
        return $statusMap[$lowerValue];
    }
    
    return $value;
}

/**
 * Extract form fields (VIEWSTATE, EVENTVALIDATION, etc.) from HTML
 * @param string $html The HTML content
 * @return array Extracted form fields
 */
function extractFormFields($html) {
    $fields = [];
    $patterns = [
        '/__VIEWSTATE" value="([^"]+)"/',
        '/__EVENTVALIDATION" value="([^"]+)"/',
        '/__VIEWSTATEGENERATOR" value="([^"]+)"/',
        '/__EVENTTARGET" value="([^"]+)"/',
        '/__EVENTARGUMENT" value="([^"]+)"/',
        '/__LASTFOCUS" value="([^"]+)"/'
    ];
    
    $keys = [
        '__VIEWSTATE',
        '__EVENTVALIDATION',
        '__VIEWSTATEGENERATOR',
        '__EVENTTARGET',
        '__EVENTARGUMENT',
        '__LASTFOCUS'
    ];
    
    foreach ($patterns as $index => $pattern) {
        preg_match($pattern, $html, $matches);
        if (isset($matches[1])) {
            $fields[$keys[$index]] = $matches[1];
        } else {
            $fields[$keys[$index]] = '';
        }
    }
    
    return $fields;
}

/**
 * Parse HTTP response into header and body
 */
function parseResponse($response, $ch) {
    // Split response into header and body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return [$header, $body];
}

/**
 * Get database connection for Webtracker
 */
function getWebtrackerDbConnection() {
    try {
        logDebug("Connecting to database: " . DB_HOST . "/" . DB_NAME);
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        logDebug("Database connection successful");
        return $pdo;
    } catch (Exception $e) {
        logDebug("Database connection error: " . $e->getMessage(), "ERROR");
        return null;
    }
}

/**
 * Save shipment data to the database
 */
function saveShipmentsToDatabase($shipments) {
    global $headerToColumnMap;
    global $verboseMode;
    
    $pdo = getWebtrackerDbConnection();
    if (!$pdo) {
        throw new Exception("No database connection available");
    }
    
    try {
        if (empty($shipments)) {
            throw new Exception("No shipments to save");
        }
        
        logDebug("Preparing to save " . count($shipments) . " shipments to database");
        
        // First check if table exists and create it if needed
        createShipmentTableIfNotExists($pdo);
        
        // Get the actual existing columns in the database table
        $existingColumns = getExistingDatabaseColumns($pdo, 'shipment_tracking');
        logDebug("Found " . count($existingColumns) . " existing columns in the database");
        
        // Collect all required columns from the data
        $requiredColumns = ['id', 'shipment_number', 'last_updated'];
        foreach ($shipments as $shipment) {
            foreach ($shipment as $key => $value) {
                // Only add database column names, not the original header names
                if (in_array($key, array_values($headerToColumnMap)) && !in_array($key, $requiredColumns)) {
                    $requiredColumns[] = $key;
                }
            }
        }
        
        // Add any missing columns to the table
        alterShipmentTableSchema($pdo, $requiredColumns);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Track changes for logging
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        
        foreach ($shipments as $shipment) {
            if (!isset($shipment['shipment_number']) || empty($shipment['shipment_number'])) {
                logDebug("Skipping shipment with missing shipment number", "WARNING");
                continue;
            }
            
            // Prepare column list and values for SQL
            $columns = ['shipment_number', 'last_updated'];
            $values = [$shipment['shipment_number'], date('Y-m-d H:i:s')];
            $placeholders = ['?', '?'];
            $updatePairs = ['last_updated = ?'];
            
            // Add other columns
            foreach ($shipment as $key => $value) {
                // Skip original header columns (only use normalized column names)
                if (!in_array($key, array_values($headerToColumnMap)) || $key === 'shipment_number') {
                    continue;
                }
                
                // Skip empty values to avoid overwriting existing data with NULL
                if ($value === null && $value === '') {
                    continue;
                }
                
                $columns[] = $key;
                $values[] = $value;
                $placeholders[] = '?';
                $updatePairs[] = "$key = ?";
            }
            
            // Store full data as JSON (optional)
            $columns[] = 'full_data';
            $values[] = json_encode($shipment);
            $placeholders[] = '?';
            $updatePairs[] = "full_data = ?";
            
            // Prepare SQL statement that UPSERTS (INSERT or UPDATE if exists)
            $sql = "INSERT INTO shipment_tracking (" . implode(',', $columns) . ") 
                    VALUES (" . implode(',', $placeholders) . ")
                    ON DUPLICATE KEY UPDATE ";
            
            // Add update clause for all fields except shipment_number (which is the primary key)
            $sql .= implode(', ', $updatePairs);
            
            // Debug logging for the query parameters
            if ($verboseMode) {
                logDebug("SQL: $sql", "DEBUG");
                logDebug("Columns: " . implode(', ', $columns), "DEBUG");
                logDebug("Placeholders: " . implode(', ', $placeholders), "DEBUG");
                logDebug("Values count: " . count($values), "DEBUG");
                logDebug("Update pairs: " . implode(', ', $updatePairs), "DEBUG");
            }
            
            // For update values, we need to provide the parameters again
            // Let's make sure we have the right number of parameters
            $allValues = $values;
            
            // Add values for the UPDATE part (except shipment_number which is primary key)
            // The first parameter in $values is shipment_number, so we skip it
            for ($i = 1; $i < count($values); $i++) {
                $allValues[] = $values[$i];
            }
            
            $stmt = $pdo->prepare($sql);
            
            // Execute statement
            try {
                $result = $stmt->execute($allValues);
                if ($result) {
                    $rowCount = $stmt->rowCount();
                    if ($rowCount === 1) {
                        $created++;
                    } elseif ($rowCount === 2) {
                        $updated++;
                    } else {
                        $unchanged++;
                    }
                }
            } catch (PDOException $e) {
                logDebug("SQL Error for shipment {$shipment['shipment_number']}: " . $e->getMessage(), "ERROR");
                // Continue with next shipment instead of failing the entire batch
                continue;
            }
        }
        
        // Commit transaction
        $pdo->commit();
        logDebug("Successfully saved $created new shipments, updated $updated existing shipments, and left $unchanged unchanged");
        
        return $created + $updated;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        logDebug("Error saving shipments: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Get existing columns from database table
 */
function getExistingDatabaseColumns($pdo, $tableName) {
    try {
        $columns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tableName`");
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
        }
        
        return $columns;
    } catch (PDOException $e) {
        logDebug("Error getting columns: " . $e->getMessage(), "ERROR");
        return ['shipment_number', 'json_data', 'last_updated']; // Fallback to essential columns
    }
}

/**
 * Alter table schema to add missing columns
 */
function alterShipmentTableSchema($pdo, $requiredColumns) {
    try {
        $existingColumns = getExistingDatabaseColumns($pdo, 'shipment_tracking');
        $columnsToAdd = array_diff($requiredColumns, $existingColumns);
        
        if (!empty($columnsToAdd)) {
            logDebug("Adding " . count($columnsToAdd) . " missing columns to table");
            
            foreach ($columnsToAdd as $column) {
                $type = "VARCHAR(255)";
                
                // Special column types
                if ($column === 'json_data') {
                    $type = "LONGTEXT";
                } elseif ($column === 'last_updated') {
                    $type = "DATETIME";
                } elseif (in_array($column, ['consignee_full_address', 'shipper_full_address', 'goods_description', 'job_notes'])) {
                    $type = "TEXT";
                }
                
                $sql = "ALTER TABLE `shipment_tracking` ADD COLUMN `$column` $type NULL";
                try {
                    $pdo->exec($sql);
                    logDebug("Added column $column");
                } catch (PDOException $e) {
                    logDebug("Error adding column $column: " . $e->getMessage(), "ERROR");
                }
            }
        }
        
        return true;
    } catch (PDOException $e) {
        logDebug("Error altering table: " . $e->getMessage(), "ERROR");
        return false;
    }
}

/**
 * Create shipment_tracking table if it doesn't exist
 */
function createShipmentTableIfNotExists($pdo) {
    try {
        // Check if table exists
        $tableExists = false;
        
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'shipment_tracking'");
            $tableExists = ($stmt->rowCount() > 0);
        } catch (PDOException $e) {
            // Table likely doesn't exist, continue with creation
        }
        
        if (!$tableExists) {
            logDebug("Creating shipment_tracking table");
            
            // SQL to create the table
            $sql = "CREATE TABLE IF NOT EXISTS `shipment_tracking` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `shipment_number` varchar(100) NOT NULL,
                `bill` varchar(100) DEFAULT NULL,
                `shipper` varchar(255) DEFAULT NULL,
                `consignee` varchar(255) DEFAULT NULL,
                `origin` varchar(100) DEFAULT NULL,
                `etd` varchar(100) DEFAULT NULL,
                `destination` varchar(100) DEFAULT NULL,
                `eta` varchar(100) DEFAULT NULL,
                `declaration_country` varchar(100) DEFAULT NULL,
                `actual_pickup` varchar(100) DEFAULT NULL,
                `additional_terms` varchar(255) DEFAULT NULL,
                `booked_online` varchar(50) DEFAULT NULL,
                `charges` varchar(100) DEFAULT NULL,
                `charges_apply` varchar(100) DEFAULT NULL,
                `consignee_address` varchar(255) DEFAULT NULL,
                `consignee_city` varchar(100) DEFAULT NULL,
                `consignee_full_address` text DEFAULT NULL,
                `consignee_post_code` varchar(50) DEFAULT NULL,
                `consignee_state` varchar(100) DEFAULT NULL,
                `container_mode` varchar(100) DEFAULT NULL,
                `containers` varchar(255) DEFAULT NULL,
                `currency` varchar(50) DEFAULT NULL,
                `current_discharge_port` varchar(100) DEFAULT NULL,
                `current_load_port` varchar(100) DEFAULT NULL,
                `current_vessel` varchar(100) DEFAULT NULL,
                `current_voy_flight` varchar(100) DEFAULT NULL,
                `delivery_agent` varchar(100) DEFAULT NULL,
                `delivery_date` varchar(100) DEFAULT NULL,
                `delivery_required_by` varchar(100) DEFAULT NULL,
                `estimated_delivery` varchar(100) DEFAULT NULL,
                `estimated_pickup` varchar(100) DEFAULT NULL,
                `first_leg_load_atd` varchar(100) DEFAULT NULL,
                `first_leg_load_etd` varchar(100) DEFAULT NULL,
                `goods_description` text DEFAULT NULL,
                `goods_value` varchar(100) DEFAULT NULL,
                `inspection` varchar(100) DEFAULT NULL,
                `job_notes` text DEFAULT NULL,
                `last_leg_discharge_ata` varchar(100) DEFAULT NULL,
                `last_leg_discharge_eta` varchar(100) DEFAULT NULL,
                `loading_meters` varchar(100) DEFAULT NULL,
                `main_discharge_port` varchar(100) DEFAULT NULL,
                `main_load_port` varchar(100) DEFAULT NULL,
                `main_vessel` varchar(100) DEFAULT NULL,
                `main_voy_flight` varchar(100) DEFAULT NULL,
                `mode` varchar(100) DEFAULT NULL,
                `on_board` varchar(100) DEFAULT NULL,
                `order_ref` varchar(100) DEFAULT NULL,
                `owners_ref` varchar(100) DEFAULT NULL,
                `packs` varchar(100) DEFAULT NULL,
                `payment_term` varchar(100) DEFAULT NULL,
                `pickup_agent` varchar(100) DEFAULT NULL,
                `pickup_required_by` varchar(100) DEFAULT NULL,
                `pieces_received` varchar(100) DEFAULT NULL,
                `received_by` varchar(100) DEFAULT NULL,
                `received_date` varchar(100) DEFAULT NULL,
                `release_type` varchar(100) DEFAULT NULL,
                `service_level` varchar(100) DEFAULT NULL,
                `shipper_address` varchar(255) DEFAULT NULL,
                `shipper_city` varchar(100) DEFAULT NULL,
                `shipper_full_address` text DEFAULT NULL,
                `shipper_post_code` varchar(50) DEFAULT NULL,
                `shipper_state` varchar(100) DEFAULT NULL,
                `shippers_ref` varchar(100) DEFAULT NULL,
                `storage_commences` varchar(100) DEFAULT NULL,
                `teu` varchar(50) DEFAULT NULL,
                `type` varchar(100) DEFAULT NULL,
                `volume` varchar(100) DEFAULT NULL,
                `weight` varchar(100) DEFAULT NULL,
                
                /* Legacy fields for compatibility */
                `container_id` varchar(100) DEFAULT NULL,
                `customer` varchar(255) DEFAULT NULL,
                `port_id` varchar(100) DEFAULT NULL,
                `port_date` varchar(100) DEFAULT NULL,
                `location` varchar(255) DEFAULT NULL,
                `est_departure` varchar(100) DEFAULT NULL,
                `est_arrival` varchar(100) DEFAULT NULL,
                `carrier` varchar(100) DEFAULT NULL,
                `status` varchar(100) DEFAULT NULL,
                
                /* Special fields */
                `json_data` longtext DEFAULT NULL,
                `last_updated` datetime DEFAULT NULL,
                
                PRIMARY KEY (`id`),
                UNIQUE KEY `shipment_number` (`shipment_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $pdo->exec($sql);
            logDebug("Shipment tracking table created successfully");
        }
        
        return true;
    } catch (PDOException $e) {
        logDebug("Error creating shipment_tracking table: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Main function to run the script
 */
function main() {
    global $testMode, $verboseMode;
    
    try {
        if ($testMode) {
            echo "\n==== RUNNING IN TEST MODE - NO DATABASE CHANGES WILL BE MADE ====\n\n";
        }
        
        if ($verboseMode) {
            echo "Verbose mode enabled - detailed output will be shown\n";
        }
        
        // Configuration for scraping
        $baseUrl = 'https://d36prd.webtracker.wisegrid.net';
        $loginUrl = $baseUrl . '/Login/Login.aspx?ReturnUrl=%2fShipments%2fShipments.aspx';
        $shipmentsUrl = $baseUrl . '/Shipments/Shipments.aspx';
        
        // Authentication parameters
        $companyCode = 'ANGSTORAG';
        $email = 'info@theangelstones.com';
        $password = 'Angelstones@2025';
        
        // Create a cookie file
        $cookieJar = tempnam(sys_get_temp_dir(), 'cookie_');
        @unlink($cookieJar); // Start fresh
        
        // Initialize cURL
        $ch = initCurl();
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        
        // Step 1: Access login page
        logDebug("Accessing login page: $loginUrl");
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_POST, false);
        $response = curl_exec($ch);
        
        if (!$response) {
            throw new Exception("Failed to access login page: " . curl_error($ch));
        }
        
        list($header, $body) = parseResponse($response, $ch);
        saveDebugHTML("login-page.html", $body);
        
        // Look for form fields in previously saved logs if we can't extract them directly
        $viewstate = '';
        $eventValidation = '';
        
        // First try direct extraction
        if (preg_match('/<input[^>]*name="__VIEWSTATE"[^>]*value="([^"]*)"/', $body, $viewstateMatches)) {
            $viewstate = $viewstateMatches[1];
            logDebug("Extracted __VIEWSTATE directly from login page");
        }
        
        if (preg_match('/<input[^>]*name="__EVENTVALIDATION"[^>]*value="([^"]*)"/', $body, $eventValidationMatches)) {
            $eventValidation = $eventValidationMatches[1];
            logDebug("Extracted __EVENTVALIDATION directly from login page");
        }
        
        // If direct extraction failed, try to use saved values from logs
        if (empty($viewstate) || empty($eventValidation)) {
            logDebug("Direct form field extraction failed, checking logs folder");
            
            // Check if we have a saved login page HTML
            $savedLoginPage = __DIR__ . '/../logs/login-page.html';
            if (file_exists($savedLoginPage)) {
                $savedHtml = file_get_contents($savedLoginPage);
                
                if (preg_match('/<input[^>]*name="__VIEWSTATE"[^>]*value="([^"]*)"/', $savedHtml, $viewstateMatches)) {
                    $viewstate = $viewstateMatches[1];
                    logDebug("Extracted __VIEWSTATE from saved login page");
                }
                
                if (preg_match('/<input[^>]*name="__EVENTVALIDATION"[^>]*value="([^"]*)"/', $savedHtml, $eventValidationMatches)) {
                    $eventValidation = $eventValidationMatches[1];
                    logDebug("Extracted __EVENTVALIDATION from saved login page");
                }
            }
        }
        
        // If we still don't have the values, use hard-coded values as fallback
        if (empty($viewstate) || empty($eventValidation)) {
            logDebug("WARNING: Using hardcoded form values as fallback");
            // These would be values previously extracted from a successful login
            $viewstate = '/wEPDwUKLTkyMzU0MzIxMGRk';
            $eventValidation = '/wEdAAMvPf5CITrA8ANa6';
        }
        
        logDebug("Using form tokens: viewstate=" . substr($viewstate, 0, 20) . "..., validation=" . substr($eventValidation, 0, 20) . "...");
        
        // Step 2: Submit login form with all credentials
        logDebug("Submitting login form with company code: $companyCode, Email: $email");
        
        $postData = [
            '__EVENTTARGET' => '',
            '__EVENTARGUMENT' => '',
            '__VIEWSTATE' => $viewstate,
            '__EVENTVALIDATION' => $eventValidation,
            'CompanyCodeTextBox' => $companyCode,
            'LoginNameTextBox' => $email,
            'PasswordTextBox' => $password,
            'SigninBtn' => 'Login'
        ];
        
        // Step 3: Submit login form
        logDebug("Submitting login form");
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        
        $response = curl_exec($ch);
        
        if (!$response) {
            throw new Exception("Login submission failed: " . curl_error($ch));
        }
        
        list($header, $body) = parseResponse($response, $ch);
        saveDebugHTML("login-result.html", $body);
        
        // Check if we've been redirected to the shipments page (success indicator)
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        logDebug("Post-login URL: $finalUrl");
        
        if (strpos($finalUrl, 'Shipments.aspx') !== false) {
            logDebug("Login successful! Redirected to Shipments page");
        } else {
            logDebug("Not redirected to Shipments page, checking page content");
            
            // Check if login was successful by looking for sign out link or welcome message or redirect
            if (strpos($body, 'Sign Out') === false && 
                strpos($body, 'Welcome') === false && 
                strpos($body, 'Shipments') === false) {
                
                throw new Exception("Login failed - could not find success indicators on page");
            }
        }
        
        logDebug("Login successful!");
        
        // Step 4: Access the Shipments page if not already there
        if (strpos($finalUrl, 'Shipments.aspx') === false) {
            logDebug("Accessing shipments page: $shipmentsUrl");
            curl_setopt($ch, CURLOPT_URL, $shipmentsUrl);
            curl_setopt($ch, CURLOPT_POST, false);
            
            $response = curl_exec($ch);
            
            if (!$response) {
                throw new Exception("Failed to access shipments page: " . curl_error($ch));
            }
            
            list($header, $body) = parseResponse($response, $ch);
        }
        
        saveDebugHTML("shipments-page.html", $body);
        
        // Step 5: Extract search form fields
        if (!preg_match('/<input[^>]*name="__VIEWSTATE"[^>]*value="([^"]+)"/', $body, $viewstateMatches)) {
            throw new Exception("Could not extract __VIEWSTATE from shipments page");
        }
        
        if (!preg_match('/<input[^>]*name="__EVENTVALIDATION"[^>]*value="([^"]+)"/', $body, $eventValidationMatches)) {
            throw new Exception("Could not extract __EVENTVALIDATION from shipments page");
        }
        
        $viewstate = $viewstateMatches[1];
        $eventValidation = $eventValidationMatches[1];
        
        $findBtnId = 'ctl07$ctl01$FooterRow_FindButton';
        logDebug("Using Find button with ID: $findBtnId");
        
        // Create search form data with appropriate button
        $searchData = [
            '__EVENTTARGET' => '',
            '__EVENTARGUMENT' => '',
            '__VIEWSTATE' => $viewstate,
            '__EVENTVALIDATION' => $eventValidation,
            $findBtnId => ' Find '
        ];
        
        // Step 6: Submit search form to get all shipments
        logDebug("Submitting search to get all shipments");
        curl_setopt($ch, CURLOPT_URL, $shipmentsUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($searchData));
        
        $response = curl_exec($ch);
        
        if (!$response) {
            throw new Exception("Shipment search failed: " . curl_error($ch));
        }
        
        list($header, $body) = parseResponse($response, $ch);
        saveDebugHTML("search-results.html", $body);
        logDebug("Shipment search results received");
        
        // Step 7: Process the search results
        if (strpos($body, 'table') === false) {
            throw new Exception("No table found in search results");
        }
        
        logDebug("SUCCESS: Found table in search results");
        
        // Extract shipment data
        $shipments = extractShipmentData($body);
        
        if (empty($shipments)) {
            throw new Exception("No shipment data extracted from the table");
        }
        
        logDebug("Successfully extracted " . count($shipments) . " shipments");
        
        // Save to database
        $saved = 0;
        if (!$testMode) {
            $saved = saveShipmentsToDatabase($shipments);
            logDebug("Saved $saved shipments to database");
        } else {
            logDebug("TEST MODE: Would have saved " . count($shipments) . " shipments to database");
        }
        
        // Close cURL session
        curl_close($ch);
        
        return [
            'success' => true,
            'shipments' => $shipments,
            'saved' => $saved
        ];
        
    } catch (Exception $e) {
        logDebug("Scraping error: " . $e->getMessage(), "ERROR");
        if (isset($ch)) {
            curl_close($ch);
        }
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Run the main function
main();