<?php
class ContactManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Update customer's lifecycle stage
     */
    public function updateLifecycleStage($customerId, $stageId) {
        $stmt = $this->pdo->prepare("
            UPDATE customers 
            SET lifecycle_stage_id = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$stageId, $customerId]);
    }

    /**
     * Get all lifecycle stages
     */
    public function getLifecycleStages() {
        $stmt = $this->pdo->query("SELECT * FROM lifecycle_stages ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add custom field definition
     */
    public function addCustomField($fieldName, $displayName, $fieldType, $fieldGroup = 'General', $isRequired = false, $defaultValue = null, $options = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO custom_field_definitions 
            (field_name, display_name, field_type, field_group, is_required, default_value, options) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$fieldName, $displayName, $fieldType, $fieldGroup, $isRequired, $defaultValue, $options]);
    }

    /**
     * Set custom field value for a customer
     */
    public function setCustomFieldValue($customerId, $fieldId, $value) {
        $stmt = $this->pdo->prepare("
            INSERT INTO custom_field_values (customer_id, field_id, field_value) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)
        ");
        return $stmt->execute([$customerId, $fieldId, $value]);
    }

    /**
     * Get all custom field values for a customer
     */
    public function getCustomFieldValues($customerId) {
        $stmt = $this->pdo->prepare("
            SELECT cfd.*, cfv.field_value 
            FROM custom_field_definitions cfd 
            LEFT JOIN custom_field_values cfv ON cfd.id = cfv.field_id AND cfv.customer_id = ?
            ORDER BY cfd.field_group, cfd.sort_order
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Log activity in timeline
     */
    public function logActivity($customerId, $activityType, $title, $description = null, $performedBy = null, $metadata = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO activity_timeline 
            (customer_id, activity_type, title, description, performed_by, activity_date, metadata) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?)
        ");
        return $stmt->execute([
            $customerId,
            $activityType,
            $title,
            $description,
            $performedBy,
            $metadata ? json_encode($metadata) : null
        ]);
    }

    /**
     * Get activity timeline for a customer
     */
    public function getActivityTimeline($customerId, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT at.*, u.username as performed_by_name 
            FROM activity_timeline at 
            LEFT JOIN users u ON at.performed_by = u.id 
            WHERE at.customer_id = ? 
            ORDER BY at.activity_date DESC 
            LIMIT ?
        ");
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate lead score based on rules
     */
    public function calculateLeadScore($customerId) {
        $score = 0;
        
        // Get active scoring rules
        $stmt = $this->pdo->query("SELECT * FROM lead_scoring_rules WHERE is_active = 1");
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get customer data
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            return 0;
        }
        
        foreach ($rules as $rule) {
            $field = $rule['condition_field'];
            $operator = $rule['condition_operator'];
            $value = $rule['condition_value'];
            $points = $rule['score_value'];
            
            // Handle different types of conditions
            switch ($operator) {
                case 'equals':
                    if (isset($customer[$field]) && $customer[$field] == $value) {
                        $score += $points;
                    }
                    break;
                case 'not_equals':
                    if (isset($customer[$field]) && $customer[$field] != $value) {
                        $score += $points;
                    }
                    break;
                case 'contains':
                    if (isset($customer[$field]) && strpos($customer[$field], $value) !== false) {
                        $score += $points;
                    }
                    break;
                case 'greater_than':
                    if (isset($customer[$field]) && $customer[$field] > $value) {
                        $score += $points;
                    }
                    break;
                // Add more operators as needed
            }
        }
        
        // Update the lead score in the customers table
        $stmt = $this->pdo->prepare("UPDATE customers SET lead_score = ? WHERE id = ?");
        $stmt->execute([$score, $customerId]);
        
        return $score;
    }
}