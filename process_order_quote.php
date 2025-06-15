<?php
// Include PHPMailer classes
require_once __DIR__ . '/crm/vendor/phpmailer/PHPMailer.php';
// Define secure access constant to satisfy security check
define('SECURE_ACCESS', true);
// Include email configuration file
require_once 'email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// No debug logging in production

// Initialize response
$response = ['status' => 'error', 'message' => 'An unknown error occurred'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        
        // Configure SMTP settings from email_config.php
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME; 
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Set email content type
        $mail->isHTML(true);
        
        // Set from and to addresses
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress('da@theangelstones.com', 'Angel Stones Team'); // Using specific email for order quotes
        
        // Set email subject based on form type
        $formType = isset($_POST['form_type']) ? $_POST['form_type'] : 'Quote';
        $mail->Subject = "Angel Stones - New " . $formType . " Request";
        
        // Start building email content
        $emailContent = "<html><body>";
        $emailContent .= "<h1>Angel Stones - " . $formType . " Form Submission</h1>";
        
        // Customer Information
        $emailContent .= "<h2>Customer Information</h2>";
        $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $emailContent .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
        
        // Add customer fields
        $customerFields = [
            'customer_name' => 'Name',
            'customer_company' => 'Company',
            'customer_email' => 'Email',
            'customer_phone' => 'Phone',
            'customer_address' => 'Address',
            'customer_city' => 'City',
            'customer_state' => 'State',
            'customer_zip' => 'ZIP'
        ];
        
        foreach ($customerFields as $field => $label) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                $value = htmlspecialchars($_POST[$field]);
                $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$value}</td></tr>";
            }
        }
        
        $emailContent .= "</table>";
        
        // Payment Information
        $emailContent .= "<h2>Payment & Shipping Details</h2>";
        $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $emailContent .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
        
        // Payment Terms
        if (isset($_POST['payment_terms'])) {
            $paymentTerms = htmlspecialchars($_POST['payment_terms']);
            $emailContent .= "<tr><td><strong>Payment Terms</strong></td><td>{$paymentTerms}</td></tr>";
        }
        
        // Payment Type
        if (isset($_POST['payment_type'])) {
            $paymentType = htmlspecialchars($_POST['payment_type']);
            $emailContent .= "<tr><td><strong>Payment Type</strong></td><td>{$paymentType}</td></tr>";
            
            if ($paymentType === 'Credit Card' && isset($_POST['cc_last_four'])) {
                $ccLastFour = htmlspecialchars($_POST['cc_last_four']);
                $emailContent .= "<tr><td><strong>Card Last Four</strong></td><td>{$ccLastFour}</td></tr>";
            }
        }
        
        // Trucker Information
        if (isset($_POST['trucker_info']) && !empty($_POST['trucker_info'])) {
            $truckerInfo = htmlspecialchars($_POST['trucker_info']);
            $emailContent .= "<tr><td><strong>Trucker Information</strong></td><td>{$truckerInfo}</td></tr>";
        }
        
        // Terms
        if (isset($_POST['terms']) && !empty($_POST['terms'])) {
            $terms = htmlspecialchars($_POST['terms']);
            $emailContent .= "<tr><td><strong>Terms</strong></td><td>{$terms}</td></tr>";
        }
        
        $emailContent .= "</table>";
        
        // Shipping Information
        $emailContent .= "<h2>Shipping Information</h2>";
        $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $emailContent .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
        
        // Check if same as billing
        if (isset($_POST['same_as_billing']) && $_POST['same_as_billing'] === 'on') {
            $emailContent .= "<tr><td colspan='2'><strong>Same as Customer Information</strong></td></tr>";
        } else {
            // Add shipping fields
            $shippingFields = [
                'shipping_name' => 'Name',
                'shipping_company' => 'Company',
                'shipping_address1' => 'Address Line 1',
                'shipping_address2' => 'Address Line 2',
                'shipping_city' => 'City',
                'shipping_state' => 'State',
                'shipping_zip' => 'ZIP',
                'shipping_phone' => 'Phone',
                'shipping_email' => 'Email'
            ];
            
            foreach ($shippingFields as $field => $label) {
                if (isset($_POST[$field]) && !empty($_POST[$field])) {
                    $value = htmlspecialchars($_POST[$field]);
                    $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$value}</td></tr>";
                }
            }
        }
        
        $emailContent .= "</table>";
        
        // Products Information
        $emailContent .= "<h2>Product Information</h2>";
        
        if (isset($_POST['products']) && is_array($_POST['products'])) {
            $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            $emailContent .= "<tr style='background-color: #f2f2f2;'>";
            $emailContent .= "<th>#</th><th>Product</th><th>Quantity</th><th>Price</th><th>Additional Charges</th><th>Total</th>";
            $emailContent .= "</tr>";
        
            $rowNumber = 1;
            foreach ($_POST['products'] as $product) {
                $productName = htmlspecialchars($product['name'] ?? '');
                $quantity = htmlspecialchars($product['quantity'] ?? '1');
                $price = htmlspecialchars($product['price'] ?? '0.00');
                $additionalChargesTotal = 0;
                
                // Build detailed product information for email
                $productDetails = "";
                
                // Add Product Type if available
                if (isset($product['product_type']) && is_array($product['product_type'])) {
                    $productDetails .= "<br><strong>Product Type:</strong> ";
                    $typeLabels = [];
                    foreach ($product['product_type'] as $type => $isSelected) {
                        if ($isSelected) {
                            $typeLabels[] = htmlspecialchars($type);
                        }
                    }
                    $productDetails .= implode(", ", $typeLabels);
                }
                
                // Add Manufacturing Type if available
                if (isset($product['manufacturing_type'])) {
                    $productDetails .= "<br><strong>Manufacturing:</strong> " . htmlspecialchars($product['manufacturing_type']);
                }
                
                if (isset($product['manufacturing_option']) && !empty($product['manufacturing_option'])) {
                    $productDetails .= " - " . htmlspecialchars($product['manufacturing_option']);
                }
        
                // Calculate additional charges and add side details to email
                if (isset($product['sides']) && is_array($product['sides'])) {
                    $sideNumber = 1;
                    foreach ($product['sides'] as $side) {
                        $productDetails .= "<br><br><strong>Side {$sideNumber} Details:</strong>";
                        
                        // Add side notes if available
                        if (isset($side['notes']) && !empty($side['notes'])) {
                            $productDetails .= "<br>Notes: " . htmlspecialchars($side['notes']);
                        }
                        
                        // Add S/B CARVING details and charge
                        if (isset($side['sb_carving']['enabled'])) {
                            $productDetails .= "<br>S/B CARVING: ";
                            if (isset($side['sb_carving']['type'])) {
                                $productDetails .= htmlspecialchars($side['sb_carving']['type']);
                            }
                            
                            if (isset($side['sb_carving']['style']) && !empty($side['sb_carving']['style'])) {
                                $productDetails .= " - Style: " . htmlspecialchars($side['sb_carving']['style']);
                            }
                            
                            if (isset($side['sb_carving']['text']) && !empty($side['sb_carving']['text'])) {
                                $productDetails .= "<br>Text: " . htmlspecialchars($side['sb_carving']['text']);
                            }
                            
                            if (isset($side['sb_carving']['charge']) && floatval($side['sb_carving']['charge']) > 0) {
                                $productDetails .= "<br>Charge: $" . number_format(floatval($side['sb_carving']['charge']), 2);
                                $additionalChargesTotal += floatval($side['sb_carving']['charge']);
                            }
                        }
        
                        // Add ETCHING details and charge
                        if (isset($side['etching']['enabled'])) {
                            $productDetails .= "<br>ETCHING: ";
                            if (isset($side['etching']['type'])) {
                                $productDetails .= htmlspecialchars($side['etching']['type']);
                            }
                            
                            if (isset($side['etching']['text']) && !empty($side['etching']['text'])) {
                                $productDetails .= "<br>Text: " . htmlspecialchars($side['etching']['text']);
                            }
                            
                            if (isset($side['etching']['charge']) && floatval($side['etching']['charge']) > 0) {
                                $productDetails .= "<br>Charge: $" . number_format(floatval($side['etching']['charge']), 2);
                                $additionalChargesTotal += floatval($side['etching']['charge']);
                            }
                        }
        
                        // Add DEDO details and charge
                        if (isset($side['dedo']['enabled'])) {
                            $productDetails .= "<br>Recess & Mount DEDO";
                            if (isset($side['dedo']['charge']) && floatval($side['dedo']['charge']) > 0) {
                                $productDetails .= ": $" . number_format(floatval($side['dedo']['charge']), 2);
                                $additionalChargesTotal += floatval($side['dedo']['charge']);
                            }
                        }
        
                        // Add DOMESTIC ADD ON details and charge
                        if (isset($side['domestic_addon']['enabled'])) {
                            $productDetails .= "<br>DOMESTIC ADD ON:";
                            
                            if (isset($side['domestic_addon']['dim1']) && !empty($side['domestic_addon']['dim1'])) {
                                $productDetails .= " (" . htmlspecialchars($side['domestic_addon']['dim1']);
                                
                                if (isset($side['domestic_addon']['dim2']) && !empty($side['domestic_addon']['dim2'])) {
                                    $productDetails .= " x " . htmlspecialchars($side['domestic_addon']['dim2']);
                                }
                                
                                $productDetails .= ")";
                            }
                            
                            if (isset($side['domestic_addon']['charge']) && floatval($side['domestic_addon']['charge']) > 0) {
                                $productDetails .= "<br>Charge: $" . number_format(floatval($side['domestic_addon']['charge']), 2);
                                $additionalChargesTotal += floatval($side['domestic_addon']['charge']);
                            }
                        }
        
                        // Add DIGITIZATION charge
                        if (isset($side['digitization']['enabled'])) {
                            $productDetails .= "<br>DIGITIZATION";
                            if (isset($side['digitization']['charge']) && floatval($side['digitization']['charge']) > 0) {
                                $productDetails .= ": $" . number_format(floatval($side['digitization']['charge']), 2);
                                $additionalChargesTotal += floatval($side['digitization']['charge']);
                            }
                        }
        
                        // Add miscellaneous charge
                        if (isset($side['misc_charge']) && floatval($side['misc_charge']) > 0) {
                            $productDetails .= "<br>Additional Charges: $" . number_format(floatval($side['misc_charge']), 2);
                            $additionalChargesTotal += floatval($side['misc_charge']);
                        }
                        
                        $sideNumber++;
                    }
                }
        
                $subtotal = floatval($price) * intval($quantity);
                $total = $subtotal + $additionalChargesTotal;
        
                $emailContent .= "<tr>";
                $emailContent .= "<td>" . $rowNumber++ . "</td>";
                $emailContent .= "<td>" . $productName;
                // Add the detailed product information
                $emailContent .= $productDetails;
                $emailContent .= "</td>";
                $emailContent .= "<td>" . $quantity . "</td>";
                $emailContent .= "<td>$" . number_format(floatval($price), 2) . "</td>";
                $emailContent .= "<td>$" . number_format(floatval($additionalChargesTotal), 2) . "</td>";
                $emailContent .= "<td>$" . number_format(floatval($total), 2) . "</td>";
                $emailContent .= "</tr>";
            }
        
            // Add summary totals
            $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0.00;
            $additionalChargesTotal = isset($_POST['additional_charges_total']) ? floatval($_POST['additional_charges_total']) : 0.00;
            $taxRate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0.00;
            $taxAmount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0.00;
            $grandTotal = isset($_POST['grand_total']) ? floatval($_POST['grand_total']) : 0.00;
        
            $emailContent .= "<tr>";
            $emailContent .= "<td colspan='5' style='text-align: right;'><strong>Subtotal:</strong></td>";
            $emailContent .= "<td>$" . number_format(floatval($subtotal), 2) . "</td>";
            $emailContent .= "</tr>";
        
            $emailContent .= "<tr>";
            $emailContent .= "<td colspan='5' style='text-align: right;'><strong>Additional Charges:</strong></td>";
            $emailContent .= "<td>$" . number_format(floatval($additionalChargesTotal), 2) . "</td>";
            $emailContent .= "</tr>";
        
            $emailContent .= "<tr>";
            $emailContent .= "<td colspan='5' style='text-align: right;'><strong>Tax (" . number_format(floatval($taxRate), 2) . "%):</strong></td>";
            $emailContent .= "<td>$" . number_format(floatval($taxAmount), 2) . "</td>";
            $emailContent .= "</tr>";
        
            $emailContent .= "<tr style='background-color: #f2f2f2;'>";
            $emailContent .= "<td colspan='5' style='text-align: right;'><strong>TOTAL:</strong></td>";
            $emailContent .= "<td><strong>$" . number_format(floatval($grandTotal), 2) . "</strong></td>";
            $emailContent .= "</tr>";
        
            $emailContent .= "</table>";
        } else {
            $emailContent .= "<p>No product information provided.</p>";
        }
        
        // Notes Section
        if (isset($_POST['notes']) && !empty($_POST['notes'])) {
            $emailContent .= "<h2>Additional Notes</h2>";
            $emailContent .= "<p>" . nl2br(htmlspecialchars($_POST['notes'])) . "</p>";
        }
        
        // Close HTML
        $emailContent .= "</body></html>";
        
        // Set email body
        $mail->Body = $emailContent;
        
        // Process file attachments
        $uploadedFiles = [];
        
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif',
                'application/pdf',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
        
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'xls', 'xlsx'];
        
            // Count valid files
            $fileCount = count($_FILES['attachments']['name']);
        
            for ($i = 0; $i < $fileCount; $i++) {
                // Skip if there was an upload error
                if ($_FILES['attachments']['error'][$i] != 0) {
                    continue;
                }
        
                $fileName = $_FILES['attachments']['name'][$i];
                $fileTmpPath = $_FILES['attachments']['tmp_name'][$i];
                $fileSize = $_FILES['attachments']['size'][$i];
                $fileType = $_FILES['attachments']['type'][$i];
        
                // Get file extension
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
                // Validate file extension and MIME type
                if (!in_array($fileExtension, $allowedExtensions) || !in_array($fileType, $allowedMimes)) {
                    continue; // Skip invalid files
                }
        
                // Basic virus check - rename file with safe name
                $safeFileName = md5($fileName . time()) . '.' . $fileExtension;
                $uploadDir = __DIR__ . '/uploads/';
        
                // Create uploads directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
        
                $uploadPath = $uploadDir . $safeFileName;
        
                // Move the file to the upload directory
                if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                    $mail->addAttachment($uploadPath, $fileName);
                    $uploadedFiles[] = $uploadPath; // Remember file for cleanup
                }
            }
        }
        
        
        // Send email
        try {
            if ($mail->send()) {
                // Email sent successfully
                $response = [
                    'status' => 'success',
                    'message' => 'Your quote/order has been submitted successfully!'
                ];
                
                // Clean up temporary files
                foreach ($uploadedFiles as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            } else {
                // Email sending failed
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to send email. Please try again or contact support.'
                ];
            }
        } catch (Exception $e) {
            $response = [
                'status' => 'error',
                'message' => 'Failed to send email. Please try again or contact customer support.'
            ];
        }
        // Email sending complete
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => 'An error occurred while processing your request. Please try again later.'
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
