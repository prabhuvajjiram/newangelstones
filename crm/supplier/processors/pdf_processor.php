<?php
require_once __DIR__ . '/../../includes/SimplePDF/src/SimplePDF.php';

function processPDFInvoice($invoice) {
    global $pdo;

    try {
        // Extract text from PDF
        $text = SimplePDF::parse($invoice['file_path']);
        if (empty($text)) {
            throw new Exception('No text content found in PDF file');
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
            WHERE supplier_id = ? AND file_type = 'pdf' AND is_active = 1 
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

        // Split text into lines
        $lines = preg_split('/\r\n|\r|\n/', $text);

        // Initialize totals
        $totalAmount = 0;
        $totalCBM = 0;
        $inItemsSection = false;

        // Process each line
        foreach ($lines as $line) {
            // Check if we're in the items section using the start/end markers
            if (isset($mapping['items_start_marker']) && 
                strpos($line, $mapping['items_start_marker']) !== false) {
                $inItemsSection = true;
                continue;
            }

            if (isset($mapping['items_end_marker']) && 
                strpos($line, $mapping['items_end_marker']) !== false) {
                $inItemsSection = false;
                continue;
            }

            if (!$inItemsSection) {
                continue;
            }

            // Skip empty lines
            if (trim($line) === '') {
                continue;
            }

            // Extract item data using regex patterns from template
            $item = [
                'invoice_id' => $invoice['id'],
                'product_code' => '',
                'description' => '',
                'quantity' => 0,
                'unit' => '',
                'unit_price' => 0,
                'fob_price' => null,
                'cbm' => null
            ];

            // Extract data using regex patterns
            foreach ($mapping['patterns'] as $field => $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $item[$field] = trim($matches[1]);
                }
            }

            // Skip if required fields are empty or invalid
            if (empty($item['product_code']) || 
                empty($item['quantity']) || 
                empty($item['unit_price'])) {
                continue;
            }

            // Convert numeric fields
            $item['quantity'] = (int)$item['quantity'];
            $item['unit_price'] = (float)$item['unit_price'];
            if (isset($item['fob_price'])) {
                $item['fob_price'] = (float)$item['fob_price'];
            }
            if (isset($item['cbm'])) {
                $item['cbm'] = (float)$item['cbm'];
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
        throw new Exception('Error processing PDF file: ' . $e->getMessage());
    }
}
