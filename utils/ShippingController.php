<?php
/**
 * Shipping Controller
 * Handles all shipping-related API endpoints
 */

require_once 'utils/ResponseHandler.php';

class ShippingController {
    // Database connection
    private $conn;
    // Response handler
    private $responseHandler;
    
    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
        $this->responseHandler = new ResponseHandler();
    }
    
    /**
     * Get all shipping IDs
     * Endpoint: /list
     */
    public function getAllShippingIds() {
        try {
            // Prepare SQL query
            $query = "SELECT shipping_id FROM shipping_table ORDER BY shipping_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            // Get row count
            $numRows = $stmt->rowCount();
            
            // Check if any records found
            if ($numRows > 0) {
                // Initialize array for data
                $shippingData = [];
                $shippingData['count'] = $numRows;
                $shippingData['shipping_ids'] = [];
                
                // Get all records
                while ($row = $stmt->fetch()) {
                    array_push($shippingData['shipping_ids'], $row['shipping_id']);
                }
                
                // Send successful response
                $this->responseHandler->sendResponse(200, $shippingData);
            } else {
                // No records found
                $this->responseHandler->sendResponse(404, ['message' => 'No shipping records found']);
            }
        } catch (PDOException $e) {
            // Database error
            $this->responseHandler->sendResponse(500, ['message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get shipping details by ID
     * Endpoint: /shipping?id=X
     * @param string $id Shipping ID
     */
    public function getShippingDetails($id) {
        try {
            // Validate shipping ID
            if (empty($id)) {
                $this->responseHandler->sendResponse(400, ['message' => 'Shipping ID cannot be empty']);
                return;
            }
            
            // Prepare SQL query
            $query = "SELECT * FROM shipping_table WHERE shipping_id = :id";
            $stmt = $this->conn->prepare($query);
            
            // Clean and bind data
            $id = htmlspecialchars(strip_tags($id));
            $stmt->bindParam(':id', $id);
            
            // Execute query
            $stmt->execute();
            
            // Check if record found
            if ($stmt->rowCount() > 0) {
                // Get record
                $row = $stmt->fetch();
                
                // Send successful response
                $this->responseHandler->sendResponse(200, $row);
            } else {
                // No record found
                $this->responseHandler->sendResponse(404, [
                    'message' => 'Shipping ID not found',
                    'shipping_id' => $id
                ]);
            }
        } catch (PDOException $e) {
            // Database error
            $this->responseHandler->sendResponse(500, ['message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}