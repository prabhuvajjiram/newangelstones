<?php
class ActivityManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Log an activity with enhanced details
     */
    public function logActivity($customerId, $activityType, $title, $categoryId, $description = null, $importance = 'medium', $companyId = null, $tags = []) {
        $stmt = $this->pdo->prepare("
            INSERT INTO activity_timeline (
                customer_id, activity_type, title, category_id, 
                activity_description, importance, associated_company_id,
                tags
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $result = $stmt->execute([
            $customerId,
            $activityType,
            $title,
            $categoryId,
            $description,
            $importance,
            $companyId,
            $tags ? json_encode($tags) : null
        ]);

        if ($result) {
            $this->updateAnalytics($customerId, $categoryId, $companyId);
        }

        return $result;
    }

    /**
     * Get filtered activity timeline
     */
    public function getActivityTimeline($filters = [], $page = 1, $limit = 50) {
        $conditions = ['1=1'];
        $params = [];
        
        if (!empty($filters['customer_id'])) {
            $conditions[] = 'at.customer_id = ?';
            $params[] = $filters['customer_id'];
        }
        
        if (!empty($filters['category_id'])) {
            $conditions[] = 'at.category_id = ?';
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['importance'])) {
            $conditions[] = 'at.importance = ?';
            $params[] = $filters['importance'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = 'at.created_at >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = 'at.created_at <= ?';
            $params[] = $filters['date_to'];
        }
        
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT at.*, 
                   ac.name as category_name, 
                   ac.icon as category_icon,
                   ac.color as category_color,
                   c.name as company_name
            FROM activity_timeline at
            LEFT JOIN activity_categories ac ON at.category_id = ac.id
            LEFT JOIN companies c ON at.associated_company_id = c.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY at.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activity analytics
     */
    public function getActivityAnalytics($customerId = null, $dateFrom = null, $dateTo = null) {
        $conditions = ['1=1'];
        $params = [];
        
        if ($customerId) {
            $conditions[] = 'customer_id = ?';
            $params[] = $customerId;
        }
        
        if ($dateFrom) {
            $conditions[] = 'date >= ?';
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $conditions[] = 'date <= ?';
            $params[] = $dateTo;
        }
        
        $sql = "
            SELECT 
                aa.date,
                ac.name as category_name,
                SUM(aa.activity_count) as total_activities
            FROM activity_analytics aa
            LEFT JOIN activity_categories ac ON aa.category_id = ac.id
            WHERE " . implode(' AND ', $conditions) . "
            GROUP BY aa.date, ac.name
            ORDER BY aa.date DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Export activities to CSV
     */
    public function exportActivities($userId, $filters = []) {
        // Log export request
        $stmt = $this->pdo->prepare("
            INSERT INTO activity_export_logs (user_id, filter_criteria, status)
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$userId, json_encode($filters)]);
        $exportId = $this->pdo->lastInsertId();
        
        // Get activities based on filters
        $activities = $this->getActivityTimeline($filters, 1, 1000000); // Large limit for export
        
        // Generate CSV file
        $filename = 'activity_export_' . date('Y-m-d_His') . '.csv';
        $filepath = '../exports/' . $filename;
        
        $fp = fopen($filepath, 'w');
        
        // Write headers
        fputcsv($fp, [
            'Date', 'Category', 'Type', 'Title', 'Description',
            'Importance', 'Customer', 'Company', 'Tags'
        ]);
        
        // Write data
        foreach ($activities as $activity) {
            fputcsv($fp, [
                $activity['created_at'],
                $activity['category_name'],
                $activity['activity_type'],
                $activity['title'],
                $activity['activity_description'],
                $activity['importance'],
                $activity['customer_name'],
                $activity['company_name'],
                $activity['tags'] ? implode(', ', json_decode($activity['tags'], true)) : ''
            ]);
        }
        
        fclose($fp);
        
        // Update export log
        $stmt = $this->pdo->prepare("
            UPDATE activity_export_logs 
            SET status = 'completed',
                file_path = ?,
                record_count = ?
            WHERE id = ?
        ");
        $stmt->execute([$filepath, count($activities), $exportId]);
        
        return [
            'export_id' => $exportId,
            'filename' => $filename,
            'record_count' => count($activities)
        ];
    }

    /**
     * Update activity analytics
     */
    private function updateAnalytics($customerId, $categoryId, $companyId) {
        $date = date('Y-m-d');
        
        // Try to update existing record
        $stmt = $this->pdo->prepare("
            INSERT INTO activity_analytics (
                date, category_id, customer_id, company_id, activity_count
            ) VALUES (
                ?, ?, ?, ?, 1
            ) ON DUPLICATE KEY UPDATE
                activity_count = activity_count + 1
        ");
        
        return $stmt->execute([$date, $categoryId, $customerId, $companyId]);
    }

    /**
     * Get activity categories
     */
    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM activity_categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
