<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Validate required fields
    $required_fields = ['item_id', 'movement_type', 'quantity', 'item_type'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Get values from POST
    $item_id = $_POST['item_id'];
    $item_type = $_POST['item_type'];
    $movement_type = strtolower($_POST['movement_type']);
    $quantity = intval($_POST['quantity']);
    $source_warehouse_id = !empty($_POST['source_warehouse_id']) ? $_POST['source_warehouse_id'] : null;
    $destination_warehouse_id = !empty($_POST['destination_warehouse_id']) ? $_POST['destination_warehouse_id'] : null;
    $reference_type = !empty($_POST['reference_type']) ? $_POST['reference_type'] : null;
    $reference_id = !empty($_POST['reference_id']) ? $_POST['reference_id'] : null;
    $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
    $created_by = $_SESSION['user_name'] ?? 'System';

    // Validate warehouse requirements based on movement type
    switch ($movement_type) {
        case 'in':
            if (empty($destination_warehouse_id)) {
                throw new Exception('Destination warehouse is required for stock in');
            }
            break;
        case 'out':
            if (empty($source_warehouse_id)) {
                throw new Exception('Source warehouse is required for stock out');
            }
            break;
        case 'transfer':
            if (empty($source_warehouse_id) || empty($destination_warehouse_id)) {
                throw new Exception('Both source and destination warehouses are required for transfer');
            }
            if ($source_warehouse_id === $destination_warehouse_id) {
                throw new Exception('Source and destination warehouses must be different');
            }
            break;
        case 'adjustment':
            if (empty($source_warehouse_id)) {
                throw new Exception('Source warehouse is required for adjustment');
            }
            break;
        default:
            throw new Exception('Invalid movement type');
    }

    // Start transaction
    $db->beginTransaction();

    // Insert movement record
    $query = "INSERT INTO product_movements (
        item_id, item_type, movement_type, quantity, 
        source_warehouse_id, destination_warehouse_id,
        reference_type, reference_id, notes, created_by
    ) VALUES (
        :item_id, :item_type, :movement_type, :quantity,
        :source_warehouse_id, :destination_warehouse_id,
        :reference_type, :reference_id, :notes, :created_by
    )";

    $stmt = $db->prepare($query);
    $stmt->execute([
        'item_id' => $item_id,
        'item_type' => $item_type,
        'movement_type' => $movement_type,
        'quantity' => $quantity,
        'source_warehouse_id' => $source_warehouse_id,
        'destination_warehouse_id' => $destination_warehouse_id,
        'reference_type' => $reference_type,
        'reference_id' => $reference_id,
        'notes' => $notes,
        'created_by' => $created_by
    ]);

    // Update inventory based on movement type and item type
    switch ($movement_type) {
        case 'in':
            // Add stock to destination warehouse
            updateInventory($db, $item_id, $destination_warehouse_id, $quantity, $item_type);
            break;
            
        case 'out':
            // Remove stock from source warehouse
            updateInventory($db, $item_id, $source_warehouse_id, -$quantity, $item_type);
            break;
            
        case 'transfer':
            // Remove from source and add to destination
            updateInventory($db, $item_id, $source_warehouse_id, -$quantity, $item_type);
            updateInventory($db, $item_id, $destination_warehouse_id, $quantity, $item_type);
            break;
            
        case 'adjustment':
            // Update stock in source warehouse
            updateInventory($db, $item_id, $source_warehouse_id, $quantity, $item_type);
            break;
    }

    $db->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in save_product_movement.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Helper function to update inventory
function updateInventory($db, $item_id, $warehouse_id, $quantity_change, $item_type) {
    // Determine which table to use based on item_type
    $table_prefix = $item_type === 'finished_product' ? 'finished_products' : 'raw_materials';
    
    // Check if inventory record exists
    $query = "SELECT id, quantity FROM {$table_prefix}_inventory 
              WHERE {$table_prefix}_id = ? AND warehouse_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$item_id, $warehouse_id]);
    $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inventory) {
        // Update existing record
        $new_quantity = $inventory['quantity'] + $quantity_change;
        if ($new_quantity < 0) {
            throw new Exception('Insufficient stock in warehouse');
        }

        $query = "UPDATE {$table_prefix}_inventory 
                 SET quantity = ?, 
                     status = CASE 
                         WHEN quantity <= 0 THEN 'out_of_stock'
                         WHEN quantity <= min_stock_level THEN 'low_stock'
                         ELSE 'in_stock'
                     END
                 WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$new_quantity, $inventory['id']]);
    } else {
        // Create new record
        if ($quantity_change < 0) {
            throw new Exception('Cannot remove stock from empty inventory');
        }

        $query = "INSERT INTO {$table_prefix}_inventory 
                 ({$table_prefix}_id, warehouse_id, quantity, status) 
                 VALUES (?, ?, ?, 'in_stock')";
        $stmt = $db->prepare($query);
        $stmt->execute([$item_id, $warehouse_id, $quantity_change]);
    }
}
