<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        global $db_host, $db_name, $db_user, $db_pass;
        
        try {
            $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, $db_user, $db_pass, $options);
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

/**
 * Get database connection using PDO
 */
function getDbConnection() {
    try {
        return Database::getInstance()->getConnection();
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        if (isAjaxRequest()) {
            sendJsonResponse(false, "Database connection error");
            exit;
        }
        throw $e;
    }
}

/**
 * Send JSON response for AJAX requests
 */
function sendJsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

/**
 * Generates a unique quote number
 * Format: AS-YYYY-XXXXX (e.g., AS-2023-00001)
 */
function generateQuoteNumber() {
    $pdo = getDbConnection();
    $year = date('Y');
    $prefix = "AS-{$year}-";
    
    try {
        // Get the latest quote number for this year
        $stmt = $pdo->prepare("
            SELECT quote_number 
            FROM quotes 
            WHERE quote_number LIKE :prefix 
            ORDER BY quote_number DESC 
            LIMIT 1
        ");
        $stmt->execute(['prefix' => $prefix . '%']);
        $lastNumber = $stmt->fetchColumn();
        
        if ($lastNumber) {
            // Extract the numeric part and increment
            $number = intval(substr($lastNumber, -5)) + 1;
        } else {
            // Start with 1 if no quotes exist for this year
            $number = 1;
        }
        
        // Format the new quote number with leading zeros
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating quote number: " . $e->getMessage());
        if (isAjaxRequest()) {
            sendJsonResponse(false, "Failed to generate quote number");
        }
        throw new Exception("Failed to generate quote number");
    }
}

/**
 * Format currency amount
 */
function formatCurrency($amount) {
    return number_format((float)$amount, 2, '.', ',');
}

/**
 * Format date to a readable format
 */
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

/**
 * Handle DataTables server-side processing
 */
function handleDataTablesRequest($query, $columns, $searchColumns = [], $where = '1=1') {
    $pdo = getDbConnection();
    
    // Get request parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Build search condition
    $searchWhere = [];
    if (!empty($search)) {
        foreach ($searchColumns as $col) {
            $searchWhere[] = "$col LIKE :search";
        }
    }
    
    // Combine WHERE conditions
    if (!empty($searchWhere)) {
        $where .= " AND (" . implode(" OR ", $searchWhere) . ")";
    }
    
    try {
        // Count total records
        $stmt = $pdo->query("SELECT COUNT(*) FROM ($query) as counted WHERE $where");
        $recordsTotal = $stmt->fetchColumn();
        
        // Prepare the main query
        $sql = "$query WHERE $where";
        
        // Add ORDER BY if specified
        if (isset($_POST['order']) && isset($_POST['order'][0]['column'])) {
            $orderColumn = intval($_POST['order'][0]['column']);
            $orderDir = strtoupper($_POST['order'][0]['dir']) === 'DESC' ? 'DESC' : 'ASC';
            
            if (isset($columns[$orderColumn])) {
                $sql .= " ORDER BY " . $columns[$orderColumn] . " $orderDir";
            }
        }
        
        // Add LIMIT
        $sql .= " LIMIT :start, :length";
        
        // Prepare and execute the query
        $stmt = $pdo->prepare($sql);
        
        // Bind search parameter if needed
        if (!empty($search)) {
            $searchParam = "%$search%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':length', $length, PDO::PARAM_INT);
        $stmt->execute();
        
        // Fetch results
        $data = $stmt->fetchAll();
        
        // Return response
        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal, // If using search, this would be different
            'data' => $data
        ];
        
    } catch (PDOException $e) {
        error_log("DataTables error: " . $e->getMessage());
        if (isAjaxRequest()) {
            sendJsonResponse(false, "Error processing request");
        }
        throw $e;
    }
}
