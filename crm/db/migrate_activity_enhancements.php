<?php
require_once '../includes/config.php';

try {
    // Set PDO attributes for this script
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Create activity_categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            icon VARCHAR(50),
            color VARCHAR(20),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default categories if not exists
    $categories = [
        ['Email', 'envelope', '#007bff', 'Email communications'],
        ['Phone', 'phone', '#28a745', 'Phone calls and messages'],
        ['Meeting', 'calendar', '#ffc107', 'In-person or virtual meetings'],
        ['Note', 'sticky-note', '#6c757d', 'General notes and comments'],
        ['Task', 'check-square', '#17a2b8', 'Tasks and to-dos'],
        ['Document', 'file', '#dc3545', 'Document related activities'],
        ['System', 'cog', '#6610f2', 'System generated activities']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO activity_categories (name, icon, color, description) VALUES (?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    
    // Function to check if column exists
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("
            SELECT 1 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return $stmt->fetch(PDO::FETCH_COLUMN) !== false;
    }
    
    // Add new columns to activity_timeline one by one
    $columns = [
        'category_id' => "ADD COLUMN category_id INT AFTER activity_type",
        'importance' => "ADD COLUMN importance ENUM('low', 'medium', 'high') DEFAULT 'medium'",
        'is_private' => "ADD COLUMN is_private BOOLEAN DEFAULT FALSE",
        'associated_company_id' => "ADD COLUMN associated_company_id INT",
        'tags' => "ADD COLUMN tags JSON",
        'title' => "ADD COLUMN title VARCHAR(255)"
    ];
    
    foreach ($columns as $column => $sql) {
        if (!columnExists($pdo, 'activity_timeline', $column)) {
            $pdo->exec("ALTER TABLE activity_timeline $sql");
            echo "Added column $column to activity_timeline\n";
        }
    }
    
    // Add foreign keys separately
    if (columnExists($pdo, 'activity_timeline', 'category_id')) {
        try {
            $pdo->exec("ALTER TABLE activity_timeline 
                       ADD CONSTRAINT fk_activity_category 
                       FOREIGN KEY (category_id) REFERENCES activity_categories(id)");
            echo "Added foreign key for category_id\n";
        } catch (PDOException $e) {
            // Foreign key might already exist
            echo "Note: Foreign key for category_id - " . $e->getMessage() . "\n";
        }
    }
    
    if (columnExists($pdo, 'activity_timeline', 'associated_company_id')) {
        try {
            $pdo->exec("ALTER TABLE activity_timeline 
                       ADD CONSTRAINT fk_activity_company 
                       FOREIGN KEY (associated_company_id) REFERENCES companies(id)");
            echo "Added foreign key for associated_company_id\n";
        } catch (PDOException $e) {
            // Foreign key might already exist
            echo "Note: Foreign key for associated_company_id - " . $e->getMessage() . "\n";
        }
    }
    
    // Create activity_analytics table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_analytics (
            id INT PRIMARY KEY AUTO_INCREMENT,
            date DATE NOT NULL,
            category_id INT,
            customer_id INT,
            company_id INT,
            activity_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES activity_categories(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (company_id) REFERENCES companies(id),
            UNIQUE KEY date_category_customer (date, category_id, customer_id)
        )
    ");
    
    // Create activity_export_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_export_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            export_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            filter_criteria JSON,
            record_count INT,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            file_path VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Add indexes
    $indexes = [
        'activity_timeline_category' => 'CREATE INDEX idx_activity_timeline_category ON activity_timeline(category_id)',
        'activity_timeline_importance' => 'CREATE INDEX idx_activity_timeline_importance ON activity_timeline(importance)',
        'activity_analytics_date' => 'CREATE INDEX idx_activity_analytics_date ON activity_analytics(date)'
    ];
    
    foreach ($indexes as $name => $sql) {
        try {
            $pdo->exec($sql);
            echo "Added index $name\n";
        } catch (PDOException $e) {
            // Index might already exist
            echo "Note: Index $name - " . $e->getMessage() . "\n";
        }
    }
    
    echo "Activity enhancements migration completed successfully.\n";
    
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
