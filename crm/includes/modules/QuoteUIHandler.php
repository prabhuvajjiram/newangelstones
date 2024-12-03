<?php
class QuoteUIHandler {
    private $productRepository;
    private $calculator;

    public function __construct($productRepository, $calculator) {
        $this->productRepository = $productRepository;
        $this->calculator = $calculator;
    }

    public function getInitialData() {
        return [
            'sertop' => $this->productRepository->getSertopProducts(),
            'base' => $this->productRepository->getBaseProducts(),
            'marker' => $this->productRepository->getMarkerProducts(),
            'slant' => $this->productRepository->getSlantProducts()
        ];
    }

    public function getSizeDisplay($type, $size) {
        if (strtolower($type) === 'marker') {
            return $size . ' SQFT';
        }
        return $size . ' inch';
    }

    public function calculateMeasurements($type, $product, $length, $breadth, $size, $quantity) {
        switch(strtolower($type)) {
            case 'sertop':
                return $this->calculator->calculateSertopMeasurements($product, $length, $breadth, $size, $quantity);
            case 'base':
                return $this->calculator->calculateBaseMeasurements($product, $length, $breadth, $size, $quantity);
            case 'marker':
                return $this->calculator->calculateMarkerMeasurements($product, $length, $breadth, $size, $quantity);
            case 'slant':
                return $this->calculator->calculateSlantMeasurements($product, $length, $breadth, $size, $quantity);
            default:
                return null;
        }
    }
}
