<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

// Ensure user is logged in
requireLogin();

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get and decode JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['quote_id']) || !is_numeric($input['quote_id'])) {
        throw new Exception('Invalid quote ID');
    }
    
    $quote_id = (int)$input['quote_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // First delete related quote items
    $stmt = $pdo->prepare("DELETE FROM quote_items WHERE quote_id = ?");
    $stmt->execute([$quote_id]);
    
    // Then delete the quote
    $stmt = $pdo->prepare("DELETE FROM quotes WHERE id = ?");
    $stmt->execute([$quote_id]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Quote deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if there was an error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in delete_quote.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
