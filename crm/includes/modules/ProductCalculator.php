<?php
class ProductCalculator {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function calculateSertopMeasurements($product, $length, $breadth, $size, $quantity) {
        $sqft = ($length * $breadth) / 144;
        $cubicFeet = ($length * $breadth * floatval($size)) / 1728 * $quantity;
        $basePrice = $product['base_price'] * $sqft;
        
        return [
            'sqft' => $sqft,
            'cubicFeet' => $cubicFeet,
            'basePrice' => $basePrice
        ];
    }

    public function calculateBaseMeasurements($product, $length, $breadth, $size, $quantity) {
        $sqft = ($length * $breadth) / 144;
        $cubicFeet = ($length * $breadth * floatval($size)) / 1728 * $quantity;
        $basePrice = $product['base_price'] * $sqft;
        
        return [
            'sqft' => $sqft,
            'cubicFeet' => $cubicFeet,
            'basePrice' => $basePrice
        ];
    }

    public function calculateMarkerMeasurements($product, $length, $breadth, $size, $quantity) {
        $sqft = ($length * $breadth) / 144;
        // Use thickness_inches from product, fallback to 4.00 if not set
        $thickness = isset($product['thickness_inches']) ? floatval($product['thickness_inches']) : 4.00;
        $cubicFeet = ($length * $breadth * $thickness) / 1728 * $quantity;
        $basePrice = $product['base_price'] * $sqft;
        
        return [
            'sqft' => $sqft,
            'cubicFeet' => $cubicFeet,
            'basePrice' => $basePrice
        ];
    }

    public function calculateSlantMeasurements($product, $length, $breadth, $size, $quantity) {
        $sqft = ($length * $breadth) / 144;
        $cubicFeet = ($length * $breadth * floatval($size)) / 1728 * $quantity;
        $basePrice = $product['base_price'] * $sqft;
        
        return [
            'sqft' => $sqft,
            'cubicFeet' => $cubicFeet,
            'basePrice' => $basePrice
        ];
    }
}
