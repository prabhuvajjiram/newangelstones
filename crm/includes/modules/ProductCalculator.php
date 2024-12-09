<?php
class ProductCalculator {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function calculateSertopMeasurements($product, $length, $breadth, $size, $quantity, $colorMarkup = 0, $monumentMarkup = 0) {
        $sqft = ($length * $breadth) / 144;
        $cubicFeet = ($length * $breadth * floatval($size)) / 1728 * $quantity;
        $basePrice = $product['base_price'] * $sqft;
        
        // Calculate markups
        $colorMarkupAmount = $basePrice * ($colorMarkup / 100);
        $monumentMarkupAmount = $basePrice * ($monumentMarkup / 100);
        
        // Calculate final price
        $pricePerUnit = $basePrice + $colorMarkupAmount + $monumentMarkupAmount;
        $totalPrice = $pricePerUnit * $quantity;
        
        return [
            'sqft' => $sqft,
            'cubicFeet' => $cubicFeet,
            'basePrice' => $basePrice,
            'totalPrice' => $totalPrice
        ];
    }

    public function calculateBaseMeasurements($product, $length, $breadth, $size, $quantity, $colorMarkup = 0, $monumentMarkup = 0) {
        $sqft = ($length * $breadth) / 144;
        $cubicFeet = ($length * $breadth * floatval($size)) / 1728 * $quantity;
        $basePrice = $product['base_price'] * $sqft;
        
        // Calculate markups
        $colorMarkupAmount = $basePrice * ($colorMarkup / 100);
        $monumentMarkupAmount = $basePrice * ($monumentMarkup / 100);
        
        // Calculate final price
        $pricePerUnit = $basePrice + $colorMarkupAmount + $monumentMarkupAmount;
        $totalPrice = $pricePerUnit * $quantity;
        
        return [
            'sqft' => $sqft,
            'cubicFeet' => $cubicFeet,
            'basePrice' => $basePrice,
            'totalPrice' => $totalPrice
        ];
    }

    public function calculateMarkerMeasurements($product, $length, $breadth, $size, $quantity, $colorMarkup = 0, $monumentMarkup = 0) {
        $sqft = ($length * $breadth) / 144;
        $thickness = isset($product['thickness_inches']) ? floatval($product['thickness_inches']) : 4.00;
        $cubicFeet = ($length * $breadth * $thickness) / 1728 * $quantity;
        $basePrice = $product['base_price'] * $sqft;
        
        // Calculate markups
        $colorMarkupAmount = $basePrice * ($colorMarkup / 100);
        $monumentMarkupAmount = $basePrice * ($monumentMarkup / 100);
        
        // Calculate final price
        $pricePerUnit = $basePrice + $colorMarkupAmount + $monumentMarkupAmount;
        $totalPrice = $pricePerUnit * $quantity;
        
        return [
            'sqft' => $sqft,
            'cubicFeet' => $cubicFeet,
            'basePrice' => $basePrice,
            'totalPrice' => $totalPrice
        ];
    }

    public function calculateSlantMeasurements($product, $length, $breadth, $size, $quantity, $colorMarkup = 0, $monumentMarkup = 0) {
        $sqft = ($length * $breadth) / 144;
        $cubicFeet = ($length * $breadth * floatval($size)) / 1728 * $quantity;
        $basePrice = $product['base_price'] * $sqft;
        
        // Calculate markups
        $colorMarkupAmount = $basePrice * ($colorMarkup / 100);
        $monumentMarkupAmount = $basePrice * ($monumentMarkup / 100);
        
        // Calculate final price
        $pricePerUnit = $basePrice + $colorMarkupAmount + $monumentMarkupAmount;
        $totalPrice = $pricePerUnit * $quantity;
        
        return [
            'sqft' => $sqft,
            'cubicFeet' => $cubicFeet,
            'basePrice' => $basePrice,
            'totalPrice' => $totalPrice
        ];
    }
}
