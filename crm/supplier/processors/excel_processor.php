<?php
require_once __DIR__ . '/../../includes/SimpleXLSX/src/SimpleXLSX.php';

function processExcelInvoice($invoice) {
    global $pdo;

    try {
        // Parse Excel file
        $xlsx = SimpleXLSX::parse($invoice['file_path']);
        if (!$xlsx) {
            throw new Exception('Failed to parse Excel file');
        }

        $rows = $xlsx->rows();
        if (empty($rows)) {
            throw new Exception('No data found in Excel file');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Clear existing items if any
        $stmt = $pdo->prepare("DELETE FROM supplier_invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoice['id']]);

        // Get template mapping for this supplier
        $stmt = $pdo->prepare("
            SELECT mapping_rules 
            FROM supplier_invoice_templates 
            WHERE supplier_id = ? AND file_type = 'excel' AND is_active = 1 
            LIMIT 1
        ");
        $stmt->execute([$invoice['supplier_id']]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            throw new Exception('No active template found for this supplier');
        }

        $mapping = json_decode($template['mapping_rules'], true);
        if (!$mapping) {
            throw new Exception('Invalid template mapping');
        }

        // Initialize totals
        $totalAmount = 0;
        $totalCBM = 0;

        // Process each row
        for ($i = $mapping['start_row']; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Extract data using column mapping
            $item = [
                'invoice_id' => $invoice['id'],
                'product_code' => $row[$mapping['columns']['product_code']] ?? '',
                'description' => $row[$mapping['columns']['description']] ?? '',
                'quantity' => (int)($row[$mapping['columns']['quantity']] ?? 0),
                'unit' => $row[$mapping['columns']['unit']] ?? '',
                'unit_price' => (float)($row[$mapping['columns']['unit_price']] ?? 0),
                'fob_price' => isset($mapping['columns']['fob_price']) ? 
                    (float)($row[$mapping['columns']['fob_price']] ?? 0) : null,
                'cbm' => isset($mapping['columns']['cbm']) ? 
                    (float)($row[$mapping['columns']['cbm']] ?? 0) : null
            ];

            // Skip if required fields are empty
            if (empty($item['product_code']) || empty($item['quantity']) || empty($item['unit_price'])) {
                continue;
            }

            // Calculate total price
            $item['total_price'] = $item['quantity'] * $item['unit_price'];
            $totalAmount += $item['total_price'];
            if ($item['cbm']) {
                $totalCBM += $item['cbm'];
            }

            // Insert item
            $stmt = $pdo->prepare("
                INSERT INTO supplier_invoice_items (
                    invoice_id, product_code, description, quantity, unit,
                    unit_price, total_price, fob_price, cbm, created_at
                ) VALUES (
                    :invoice_id, :product_code, :description, :quantity, :unit,
                    :unit_price, :total_price, :fob_price, :cbm, NOW()
                )
            ");
            $stmt->execute($item);
        }

        // Update invoice totals
        $stmt = $pdo->prepare("
            UPDATE supplier_invoices 
            SET total_amount = ?, status = 'processed', processed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$totalAmount, $invoice['id']]);

        // Commit transaction
        $pdo->commit();

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception('Error processing Excel file: ' . $e->getMessage());
    }
}
