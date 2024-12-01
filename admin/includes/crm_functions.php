<?php
require_once 'config.php';

// Lead Management Functions
class LeadManagement {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function calculateLeadScore($customerId) {
        $score = 0;
        
        // Get customer data
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        // Score based on completeness of profile
        if (!empty($customer['email'])) $score += 10;
        if (!empty($customer['phone'])) $score += 10;
        if (!empty($customer['address'])) $score += 5;
        if (!empty($customer['budget_range'])) $score += 15;
        if (!empty($customer['decision_timeframe'])) $score += 20;

        // Score based on engagement
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM quotes WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $quoteCount = $stmt->fetchColumn();
        $score += $quoteCount * 5;

        // Score based on communications
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM customer_communications WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $commCount = $stmt->fetchColumn();
        $score += $commCount * 3;

        // Update lead score
        $stmt = $this->db->prepare("UPDATE customers SET lead_score = ? WHERE id = ?");
        $stmt->execute([$score, $customerId]);

        return $score;
    }

    public function updateLeadStatus($customerId, $status) {
        $validStatuses = ['potential', 'active', 'inactive', 'converted'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid lead status");
        }

        $stmt = $this->db->prepare("UPDATE customers SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $customerId]);
    }

    public function getLeadsByScore($minScore = 0, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT c.*, COALESCE(c.lead_score, 0) as lead_score 
            FROM customers c 
            WHERE COALESCE(c.lead_score, 0) >= ? 
            ORDER BY lead_score DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $minScore, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Campaign Management Functions
class CampaignManagement {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createCampaign($data) {
        $stmt = $this->db->prepare("
            INSERT INTO campaigns (
                name, description, type, status, start_date, end_date,
                budget, target_audience, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['type'],
            'draft',
            $data['start_date'],
            $data['end_date'],
            $data['budget'],
            $data['target_audience'],
            $data['created_by']
        ]);
    }

    public function trackCampaignMetric($campaignId, $metricName, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO campaign_results (
                campaign_id, metric_name, metric_value, date_recorded
            ) VALUES (?, ?, ?, CURRENT_DATE)
        ");
        return $stmt->execute([$campaignId, $metricName, $value]);
    }

    public function getCampaignPerformance($campaignId) {
        $stmt = $this->db->prepare("
            SELECT cr.metric_name, 
                   AVG(cr.metric_value) as average_value,
                   MAX(cr.metric_value) as max_value,
                   MIN(cr.metric_value) as min_value,
                   COUNT(*) as measurement_count
            FROM campaign_results cr
            WHERE cr.campaign_id = ?
            GROUP BY cr.metric_name
        ");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Task Management Functions
class TaskManagement {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createTask($data) {
        $stmt = $this->db->prepare("
            INSERT INTO tasks (
                title, description, customer_id, quote_id, user_id,
                priority, status, due_date, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['customer_id'] ?: null,
            $data['quote_id'] ?: null,
            $data['user_id'],
            $data['priority'],
            'pending',
            $data['due_date'],
            $_SESSION['user_id']
        ]);
    }

    public function updateTaskStatus($taskId, $status) {
        $validStatuses = ['pending', 'in_progress', 'completed'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid task status");
        }

        $stmt = $this->db->prepare("
            UPDATE tasks 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $taskId]);
    }

    public function getTasksByUser($userId, $status = null) {
        $sql = "
            SELECT t.*, 
                   c.name as customer_name,
                   u.username as assigned_by_name
            FROM tasks t
            LEFT JOIN customers c ON t.customer_id = c.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.user_id = ?
        ";
        $params = [$userId];

        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY t.due_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTasksAssignedBy($userId) {
        $sql = "
            SELECT t.*, 
                   c.name as customer_name,
                   u.username as assigned_to_name
            FROM tasks t
            LEFT JOIN customers c ON t.customer_id = c.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.created_by = ?
            ORDER BY t.due_date ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskDetails($taskId) {
        $sql = "
            SELECT t.*, 
                   c.name as customer_name,
                   creator.username as assigned_by_name,
                   assignee.username as assigned_to_name
            FROM tasks t
            LEFT JOIN customers c ON t.customer_id = c.id
            LEFT JOIN users creator ON t.created_by = creator.id
            LEFT JOIN users assignee ON t.user_id = assignee.id
            WHERE t.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteTask($taskId) {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
        return $stmt->execute([$taskId]);
    }
}

// Document Management Functions
class DocumentManagement {
    private $db;
    private $uploadDir = '../uploads/documents/';

    public function __construct($db) {
        $this->db = $db;
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function uploadDocument($file, $data) {
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $this->uploadDir . $filename;

        // Upload file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload file");
        }

        // Save to database
        $stmt = $this->db->prepare("
            INSERT INTO customer_documents (
                customer_id, quote_id, document_type, file_name,
                file_path, file_size, uploaded_by, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['customer_id'],
            $data['quote_id'],
            $data['document_type'],
            $file['name'],
            $filename,
            $file['size'],
            $data['uploaded_by'],
            $data['notes']
        ]);
    }

    public function getCustomerDocuments($customerId) {
        $stmt = $this->db->prepare("
            SELECT cd.*, u.username as uploaded_by_name
            FROM customer_documents cd
            LEFT JOIN users u ON cd.uploaded_by = u.id
            WHERE cd.customer_id = ?
            ORDER BY cd.upload_date DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Communication Management Functions
class CommunicationManagement {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function logCommunication($data) {
        $stmt = $this->db->prepare("
            INSERT INTO customer_communications (
                customer_id, user_id, type, subject,
                content, status
            ) VALUES (?, ?, ?, ?, ?, 'completed')
        ");
        return $stmt->execute([
            $data['customer_id'],
            $data['user_id'],
            $data['type'],
            $data['subject'],
            $data['content']
        ]);
    }

    public function getEmailTemplate($templateType) {
        $stmt = $this->db->prepare("
            SELECT * FROM email_templates 
            WHERE template_type = ? 
            LIMIT 1
        ");
        $stmt->execute([$templateType]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function sendEmail($customerId, $templateType, $variables = []) {
        // Get customer data
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get email template
        $template = $this->getEmailTemplate($templateType);
        if (!$template) {
            throw new Exception("Email template not found");
        }

        // Replace variables in template
        $subject = $template['subject'];
        $body = $template['body'];
        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
            $body = str_replace('{' . $key . '}', $value, $body);
        }

        // TODO: Implement actual email sending logic here
        // For now, just log the communication
        return $this->logCommunication([
            'customer_id' => $customerId,
            'user_id' => $_SESSION['user_id'],
            'type' => 'email',
            'subject' => $subject,
            'content' => $body
        ]);
    }
}

// Reminder Management Functions
class ReminderManagement {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createReminder($userId, $type, $daysBefore) {
        $stmt = $this->db->prepare("
            INSERT INTO reminder_settings (
                user_id, reminder_type, days_before
            ) VALUES (?, ?, ?)
        ");
        return $stmt->execute([$userId, $type, $daysBefore]);
    }

    public function getDueReminders() {
        $sql = "
            SELECT rs.*, u.email as user_email, u.username
            FROM reminder_settings rs
            JOIN users u ON rs.user_id = u.id
            WHERE rs.is_active = 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Helper function to get database instance
function getCRMInstance($className) {
    global $pdo;
    
    if (!class_exists($className)) {
        throw new Exception("Class $className not found");
    }
    
    return new $className($pdo);
}
