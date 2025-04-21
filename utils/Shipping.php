<?php
/**
 * Shipping Model
 * Represents the shipping database table structure
 */

class Shipping {
    // Database connection and table
    private $conn;
    private $table = 'shipping_table';
    
    // Shipping properties
    public $shipping_id;
    public $customer_name;
    public $destination;
    public $status;
    public $created_date;
    public $delivery_date;
    public $tracking_number;
    public $shipping_method;
    
    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Read all shipping IDs
     * @return PDOStatement
     */
    public function readAll() {
        // Create query
        $query = "SELECT shipping_id FROM " . $this->table . " ORDER BY shipping_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Read single shipping record
     * @return boolean
     */
    public function readSingle() {
        // Create query
        $query = "SELECT * FROM " . $this->table . " WHERE shipping_id = :shipping_id LIMIT 1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':shipping_id', $this->shipping_id);
        
        // Execute query
        $stmt->execute();
        
        // Get record
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If record exists, set properties
        if ($row) {
            $this->shipping_id = $row['shipping_id'];
            $this->customer_name = $row['customer_name'];
            $this->destination = $row['destination'];
            $this->status = $row['status'];
            $this->created_date = $row['created_date'];
            $this->delivery_date = $row['delivery_date'];
            $this->tracking_number = $row['tracking_number'];
            $this->shipping_method = $row['shipping_method'];
            return true;
        }
        
        return false;
    }
}