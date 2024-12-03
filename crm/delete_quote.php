<?php
require_once 'includes/config.php';
require_once 'session_check.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quote_id'])) {
    header('Location: quotes.php');
    exit;
}

try {
    $quote_id = (int)$_POST['quote_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete quote items first (due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM quote_items WHERE quote_id = ?");
    $stmt->execute([$quote_id]);
    
    // Then delete the quote
    $stmt = $pdo->prepare("DELETE FROM quotes WHERE id = ?");
    $stmt->execute([$quote_id]);
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success_message'] = "Quote deleted successfully.";
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = "Error deleting quote: " . $e->getMessage();
}

header('Location: quotes.php');
exit;
?>
