<?php
/**
 * RingCentral Integration Fix Script
 * 
 * This script applies fixes to the RingCentral integration to ensure
 * proper message handling between the CRM and RingCentral.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Function to safely run a database query and handle errors
function runQuery($db, $query, $description, $params = []) {
    try {
        if (count($params) > 0) {
            $stmt = $db->prepare($query);
            $result = $stmt->execute($params);
        } else {
            $result = $db->exec($query);
        }
        return [
            'success' => true,
            'description' => $description,
            'result' => $result
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'description' => $description,
            'error' => $e->getMessage()
        ];
    }
}

// Apply fixes and report results
$results = [];
$success = true;

try {
    // Get database connection
    $db = getDb();
    
    // 1. First, check if the compatibility view exists
    $viewExists = false;
    try {
        $check = $db->query("SHOW TABLES LIKE 'chat_messages_compat'");
        $viewExists = ($check->rowCount() > 0);
    } catch (Exception $e) {
        // View doesn't exist
    }
    
    if (!$viewExists) {
        // Create the compatibility view to handle column name variations
        $createViewQuery = "CREATE OR REPLACE VIEW chat_messages_compat AS 
            SELECT 
                id,
                " . (hasColumn($db, 'chat_messages', 'session_id') ? 'session_id' : 'sessionid') . " AS session_id,
                message,
                sender_type,
                sender_id,
                COALESCE(
                    " . (hasColumn($db, 'chat_messages', 'message_id') ? 'message_id' : 'NULL') . ", 
                    " . (hasColumn($db, 'chat_messages', 'ring_central_message_id') ? 'ring_central_message_id' : 'NULL') . "
                ) AS message_id,
                " . (hasColumn($db, 'chat_messages', 'ring_central_message_id') ? 'ring_central_message_id' : 'NULL') . " AS ring_central_message_id,
                status,
                created_at,
                " . (hasColumn($db, 'chat_messages', 'metadata') ? 'metadata' : "NULL AS metadata") . "
            FROM chat_messages";
        
        $results[] = runQuery($db, $createViewQuery, "Creating compatibility view for chat_messages");
    } else {
        $results[] = [
            'success' => true,
            'description' => "Compatibility view already exists",
            'result' => null
        ];
    }
    
    // 2. Update the polling endpoint to use the compatibility view
    $pollMessagesFile = __DIR__ . '/api/poll_messages.php';
    if (file_exists($pollMessagesFile)) {
        $pollMessagesContent = file_get_contents($pollMessagesFile);
        
        // Check if the file needs to be updated
        if (strpos($pollMessagesContent, 'chat_messages_compat') === false) {
            // Replace the table reference with compatibility view
            $updatedContent = str_replace(
                "\$messagesTable = 'chat_messages';",
                "\$messagesTable = 'chat_messages_compat';", 
                $pollMessagesContent
            );
            
            // Add fallback mechanism if the view doesn't exist
            $fallbackCode = "
    // If the compat view doesn't exist, fall back to the original table
    try {
        \$checkView = \$db->query(\"SHOW TABLES LIKE 'chat_messages_compat'\");
        if (\$checkView->rowCount() === 0) {
            \$messagesTable = 'chat_messages';
            \$query = \"SELECT * FROM \$messagesTable 
                      WHERE \$sessionIdColumn = ?\$where 
                      ORDER BY created_at ASC
                      LIMIT 100\";
            logMessage(\"Compatibility view not found, using original table\", 'WARN');
        }
    } catch (Exception \$e) {
        \$messagesTable = 'chat_messages';
        \$query = \"SELECT * FROM \$messagesTable 
                  WHERE \$sessionIdColumn = ?\$where 
                  ORDER BY created_at ASC
                  LIMIT 100\";
        logMessage(\"Error checking for compatibility view: \" . \$e->getMessage(), 'WARN');
    }";
            
            // Insert fallback code after the query definition
            $updatedContent = str_replace(
                "LIMIT 100\";",
                "LIMIT 100\";\n" . $fallbackCode,
                $updatedContent
            );
            
            // Save the updated file
            file_put_contents($pollMessagesFile, $updatedContent);
            
            $results[] = [
                'success' => true,
                'description' => "Updated poll_messages.php to use compatibility view",
                'result' => null
            ];
        } else {
            $results[] = [
                'success' => true,
                'description' => "poll_messages.php already uses compatibility view",
                'result' => null
            ];
        }
    } else {
        $results[] = [
            'success' => false,
            'description' => "Could not find poll_messages.php file",
            'error' => "File not found at: $pollMessagesFile"
        ];
        $success = false;
    }
    
    // 3. Fix the webhook.php to handle agent messages correctly
    $webhookFile = __DIR__ . '/api/webhook.php';
    if (file_exists($webhookFile)) {
        $webhookContent = file_get_contents($webhookFile);
        
        // Check if validation token handling is present
        if (strpos($webhookContent, 'HTTP_VALIDATION_TOKEN') === false) {
            // Add validation token handling
            $validationCode = "
// CRITICAL: Handle RingCentral validation token first (required for webhook creation)
\$validationToken = \$_SERVER['HTTP_VALIDATION_TOKEN'] ?? null;
if (!empty(\$validationToken)) {
    logMessage('Validation request received with token: ' . \$validationToken, 'INFO');
    header(\"Validation-Token: {\$validationToken}\");
    http_response_code(200);
    exit; // Exit after returning the validation token, no further processing needed
}
";
            
            // Insert validation code after the webhook received log
            $updatedWebhookContent = str_replace(
                "logMessage('Webhook received');",
                "logMessage('Webhook received');\n" . $validationCode,
                $webhookContent
            );
            
            // Save the updated file
            file_put_contents($webhookFile, $updatedWebhookContent);
            
            $results[] = [
                'success' => true,
                'description' => "Updated webhook.php to handle validation tokens",
                'result' => null
            ];
        } else {
            $results[] = [
                'success' => true,
                'description' => "webhook.php already handles validation tokens",
                'result' => null
            ];
        }
    } else {
        $results[] = [
            'success' => false,
            'description' => "Could not find webhook.php file",
            'error' => "File not found at: $webhookFile"
        ];
        $success = false;
    }
    
    // 4. Check for helper functions handling both message_id columns
    $helpersFile = __DIR__ . '/api/helpers.php';
    if (file_exists($helpersFile)) {
        $helpersContent = file_get_contents($helpersFile);
        
        // Check if the storeMessageDynamic function uses dynamic column names
        if (strpos($helpersContent, 'getDynamicColumnName') !== false) {
            $results[] = [
                'success' => true,
                'description' => "helpers.php already uses dynamic column names",
                'result' => null
            ];
        } else {
            $results[] = [
                'success' => false,
                'description' => "helpers.php needs to be updated to use dynamic column names",
                'error' => "Manual update required"
            ];
            $success = false;
        }
    } else {
        $results[] = [
            'success' => false,
            'description' => "Could not find helpers.php file",
            'error' => "File not found at: $helpersFile"
        ];
        $success = false;
    }
    
    // 5. Verify the database structure
    // Check if both message_id and ring_central_message_id columns exist
    $messageIdExists = hasColumn($db, 'chat_messages', 'message_id');
    $rcMessageIdExists = hasColumn($db, 'chat_messages', 'ring_central_message_id');
    
    if ($messageIdExists && $rcMessageIdExists) {
        $results[] = [
            'success' => true,
            'description' => "Both message_id and ring_central_message_id columns exist",
            'result' => null
        ];
    } else if ($messageIdExists) {
        // Add ring_central_message_id column
        $addColumnQuery = "ALTER TABLE chat_messages ADD COLUMN ring_central_message_id VARCHAR(64) NULL AFTER message_id";
        $results[] = runQuery($db, $addColumnQuery, "Adding ring_central_message_id column");
    } else if ($rcMessageIdExists) {
        // Add message_id column
        $addColumnQuery = "ALTER TABLE chat_messages ADD COLUMN message_id VARCHAR(64) NULL AFTER sender_id";
        $results[] = runQuery($db, $addColumnQuery, "Adding message_id column");
    } else {
        // Both columns are missing
        $addColumnsQuery = "ALTER TABLE chat_messages 
                           ADD COLUMN message_id VARCHAR(64) NULL AFTER sender_id,
                           ADD COLUMN ring_central_message_id VARCHAR(64) NULL AFTER message_id";
        $results[] = runQuery($db, $addColumnsQuery, "Adding both message ID columns");
    }
    
} catch (Exception $e) {
    $results[] = [
        'success' => false,
        'description' => "Unexpected error",
        'error' => $e->getMessage()
    ];
    $success = false;
}

// Helper function to check if a column exists in a table
function hasColumn($db, $table, $column) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM $table LIKE '$column'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RingCentral Integration Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>RingCentral Integration Fix</h1>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <h4>Integration Fixed Successfully!</h4>
            <p>All required updates have been applied to ensure proper message handling between your CRM and RingCentral.</p>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <h4>Partial Fix Applied</h4>
            <p>Some updates were successfully applied, but there are issues that may need manual intervention.</p>
        </div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-header">Fix Results</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Operation</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['description']); ?></td>
                                <td>
                                    <?php if ($result['success']): ?>
                                    <span class="badge bg-success">Success</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!$result['success'] && isset($result['error'])) {
                                        echo htmlspecialchars($result['error']);
                                    } else if (isset($result['result']) && $result['result'] !== null) {
                                        echo htmlspecialchars($result['result']);
                                    } else {
                                        echo "No issues";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">Next Steps</div>
            <div class="card-body">
                <h5>Run the following to ensure integration is working:</h5>
                <ol>
                    <li>Ensure your subscription is active: <a href="direct_subscription_create.php" class="btn btn-sm btn-primary">Check/Create Subscription</a></li>
                    <li>Run the database fix to ensure all tables are correctly set up: <a href="fix_messages_table.php" class="btn btn-sm btn-primary">Fix DB Tables</a></li>
                    <li>Test your chat with actual RingCentral integration: <a href="test_chat.html" class="btn btn-sm btn-primary">Test Chat</a></li>
                    <li>If you're still having issues, use the debug tools: <a href="debug_messages.php" class="btn btn-sm btn-warning">Debug Messages</a></li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <h5>Production Deployment</h5>
                    <p>After confirming everything works in your testing environment, upload these fixes to your production server:</p>
                    <ul>
                        <li>Upload <code>fix_ringcentral_integration.php</code> to production and run it</li>
                        <li>Ensure <code>chat_messages_compat</code> view exists in production</li>
                        <li>Verify your RingCentral subscription is active in production</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
