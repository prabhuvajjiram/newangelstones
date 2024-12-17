<?php
class ProductRepository {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSertopProducts() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, model, size_inches as size, base_price
                FROM sertop_products 
                ORDER BY model
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("SERTOP Products Raw Data: " . print_r($results, true));
            
            // Add default dimensions based on size
            foreach ($results as &$product) {
                switch ((float)$product['size']) {
                    case 6.00:
                        $product['length_inches'] = 24;
                        $product['breadth_inches'] = 12;
                        break;
                    case 8.00:
                        $product['length_inches'] = 30;
                        $product['breadth_inches'] = 16;
                        break;
                    case 10.00:
                        $product['length_inches'] = 36;
                        $product['breadth_inches'] = 18;
                        break;
                    default:
                        $product['length_inches'] = 24;
                        $product['breadth_inches'] = 12;
                }
            }
            
            $organized = $this->organizeProducts($results);
            error_log("SERTOP Products Organized: " . print_r($organized, true));
            return $organized;
        } catch (PDOException $e) {
            error_log("Error fetching sertop products: " . $e->getMessage());
            return [];
        }
    }

    public function getBaseProducts() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, model, size_inches as size, base_price,
                       length_inches, breadth_inches
                FROM base_products 
                ORDER BY model
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("BASE Products Raw Data: " . print_r($results, true));
            return $this->organizeProducts($results);
        } catch (PDOException $e) {
            error_log("Error fetching base products: " . $e->getMessage());
            return [];
        }
    }

    public function getMarkerProducts() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, model, square_feet as size, base_price,
                       length_inches, breadth_inches, thickness_inches
                FROM marker_products 
                ORDER BY model
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("MARKER Products Raw Data: " . print_r($results, true));
            return $this->organizeProducts($results);
        } catch (PDOException $e) {
            error_log("Error fetching marker products: " . $e->getMessage());
            return [];
        }
    }

    public function getSlantProducts() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, model, size_inches as size, base_price,
                       length_inches, breadth_inches
                FROM slant_products 
                ORDER BY model
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("SLANT Products Raw Data: " . print_r($results, true));
            return $this->organizeProducts($results);
        } catch (PDOException $e) {
            error_log("Error fetching slant products: " . $e->getMessage());
            return [];
        }
    }

    private function organizeProducts($results) {
        $organized = [];
        foreach ($results as $product) {
            $size = number_format((float)$product['size'], 2);  // Ensure consistent decimal places
            if (!isset($organized[$size])) {
                $organized[$size] = [];
            }
            $organized[$size][] = [
                'id' => (int)$product['id'],
                'model' => $product['model'],
                'base_price' => (float)$product['base_price'],
                'length_inches' => isset($product['length_inches']) ? (float)$product['length_inches'] : 0,
                'breadth_inches' => isset($product['breadth_inches']) ? (float)$product['breadth_inches'] : 0,
                'thickness_inches' => isset($product['thickness_inches']) ? (float)$product['thickness_inches'] : 0
            ];
        }
        return $organized;
    }
}
