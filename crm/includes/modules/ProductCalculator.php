<?php
class ProductCalculator {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function validateInputs($length, $breadth, $thickness, $quantity) {
        if (!is_numeric($length) || $length <= 0) {
            error_log("Invalid length value: " . $length);
            throw new InvalidArgumentException("Length must be a positive number");
        }
        if (!is_numeric($breadth) || $breadth <= 0) {
            error_log("Invalid breadth value: " . $breadth);
            throw new InvalidArgumentException("Breadth must be a positive number");
        }
        if (!is_numeric($thickness) || $thickness <= 0) {
            error_log("Invalid thickness value: " . $thickness);
            throw new InvalidArgumentException("Thickness must be a positive number");
        }
        if (!is_numeric($quantity) || $quantity <= 0) {
            error_log("Invalid quantity value: " . $quantity);
            throw new InvalidArgumentException("Quantity must be a positive number");
        }
    }

    private function calculateDimensions($length, $breadth, $thickness, $quantity) {
        $sqft = ($length * $breadth) / 144;
        $cubicFeet = ($length * $breadth * $thickness) / 1728 * $quantity;
        
        error_log("Dimension calculations: length={$length}, breadth={$breadth}, thickness={$thickness}, quantity={$quantity}");
        error_log("Results: sqft={$sqft}, cubicFeet={$cubicFeet}");
        
        return ['sqft' => $sqft, 'cubicFeet' => $cubicFeet];
    }

    private function calculatePricing($basePrice, $sqft, $quantity, $colorMarkup, $monumentMarkup) {
        $rawBasePrice = $basePrice * $sqft;
        $colorMarkupAmount = $rawBasePrice * ($colorMarkup / 100);
        $monumentMarkupAmount = $rawBasePrice * ($monumentMarkup / 100);
        $pricePerUnit = $rawBasePrice + $colorMarkupAmount + $monumentMarkupAmount;
        $totalPrice = $pricePerUnit * $quantity;
        
        error_log("Price calculations: basePrice={$basePrice}, sqft={$sqft}, quantity={$quantity}");
        error_log("Markups: color={$colorMarkup}%, monument={$monumentMarkup}%");
        error_log("Results: pricePerUnit={$pricePerUnit}, totalPrice={$totalPrice}");
        
        return ['basePrice' => $rawBasePrice, 'totalPrice' => $totalPrice];
    }

    public function calculateSertopMeasurements($product, $length, $breadth, $size, $quantity, $colorMarkup = 0, $monumentMarkup = 0) {
        error_log("Starting Sertop measurements calculation");
        
        // Use size_inches (passed as $size) for thickness
        $thickness = floatval($size);
        
        // Validate all inputs
        $this->validateInputs($length, $breadth, $thickness, $quantity);
        
        // Calculate dimensions
        $dimensions = $this->calculateDimensions($length, $breadth, $thickness, $quantity);
        
        // Calculate pricing
        $pricing = $this->calculatePricing($product['base_price'], $dimensions['sqft'], $quantity, $colorMarkup, $monumentMarkup);
        
        return [
            'sqft' => $dimensions['sqft'],
            'cubicFeet' => $dimensions['cubicFeet'],
            'basePrice' => $pricing['basePrice'],
            'totalPrice' => $pricing['totalPrice']
        ];
    }

    public function calculateBaseMeasurements($product, $length, $breadth, $size, $quantity, $colorMarkup = 0, $monumentMarkup = 0) {
        error_log("Starting Base measurements calculation");
        
        // Use size_inches (passed as $size) for thickness
        $thickness = floatval($size);
        
        // Validate all inputs
        $this->validateInputs($length, $breadth, $thickness, $quantity);
        
        // Calculate dimensions
        $dimensions = $this->calculateDimensions($length, $breadth, $thickness, $quantity);
        
        // Calculate pricing
        $pricing = $this->calculatePricing($product['base_price'], $dimensions['sqft'], $quantity, $colorMarkup, $monumentMarkup);
        
        return [
            'sqft' => $dimensions['sqft'],
            'cubicFeet' => $dimensions['cubicFeet'],
            'basePrice' => $pricing['basePrice'],
            'totalPrice' => $pricing['totalPrice']
        ];
    }

    public function calculateMarkerMeasurements($product, $length, $breadth, $size, $quantity, $colorMarkup = 0, $monumentMarkup = 0) {
        error_log("Starting Marker measurements calculation");
        
        // For markers, prefer thickness_inches if available, fallback to length_inches
        $thickness = isset($product['thickness_inches']) ? 
            floatval($product['thickness_inches']) : 
            floatval($product['length_inches']);
            
        error_log("Marker thickness determined: " . $thickness);
        
        // Validate all inputs
        $this->validateInputs($length, $breadth, $thickness, $quantity);
        
        // Calculate dimensions
        $dimensions = $this->calculateDimensions($length, $breadth, $thickness, $quantity);
        
        // Calculate pricing
        $pricing = $this->calculatePricing($product['base_price'], $dimensions['sqft'], $quantity, $colorMarkup, $monumentMarkup);
        
        return [
            'sqft' => $dimensions['sqft'],
            'cubicFeet' => $dimensions['cubicFeet'],
            'basePrice' => $pricing['basePrice'],
            'totalPrice' => $pricing['totalPrice']
        ];
    }

    public function calculateSlantMeasurements($product, $length, $breadth, $size, $quantity, $colorMarkup = 0, $monumentMarkup = 0) {
        error_log("Starting Slant measurements calculation");
        
        // Use size_inches (passed as $size) for thickness
        $thickness = floatval($size);
        
        // Validate all inputs
        $this->validateInputs($length, $breadth, $thickness, $quantity);
        
        // Calculate dimensions
        $dimensions = $this->calculateDimensions($length, $breadth, $thickness, $quantity);
        
        // Calculate pricing
        $pricing = $this->calculatePricing($product['base_price'], $dimensions['sqft'], $quantity, $colorMarkup, $monumentMarkup);
        
        return [
            'sqft' => $dimensions['sqft'],
            'cubicFeet' => $dimensions['cubicFeet'],
            'basePrice' => $pricing['basePrice'],
            'totalPrice' => $pricing['totalPrice']
        ];
    }
}
