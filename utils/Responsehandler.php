<?php
/**
 * Response Handler
 * Formats and sends API responses
 */

class ResponseHandler {
    /**
     * Send API response
     * @param int $statusCode HTTP status code
     * @param array $data Response data
     */
    public function sendResponse($statusCode, $data) {
        // Set HTTP status code
        http_response_code($statusCode);
        
        // Add timestamp to response
        $data['timestamp'] = date('Y-m-d H:i:s');
        
        // Output JSON response
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }
}