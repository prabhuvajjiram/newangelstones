<?php
require_once 'config.php';

/**
 * Generates a unique quote number
 * Format: AS-YYYY-XXXXX (e.g., AS-2023-00001)
 */
function generateQuoteNumber() {
    global $pdo;
    
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
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}
