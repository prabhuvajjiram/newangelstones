<?php
class QuoteRepository {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getQuote($quote_id) {
        if (!$quote_id) return null;
        
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM quotes WHERE id = ?");
            $stmt->execute([$quote_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching quote: " . $e->getMessage());
            return null;
        }
    }

    public function getQuoteItems($quote_id) {
        if (!$quote_id) return [];
        
        try {
            $stmt = $this->pdo->prepare("SELECT qi.*, scr.color_name 
                FROM quote_items qi 
                LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id 
                WHERE qi.quote_id = ?");
            $stmt->execute([$quote_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching quote items: " . $e->getMessage());
            return [];
        }
    }

    public function getCustomer($customer_id) {
        if (!$customer_id) return null;
        
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$customer_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching customer: " . $e->getMessage());
            return null;
        }
    }

    public function getStoneColors() {
        try {
            $stmt = $this->pdo->prepare("SELECT id, color_name, price_increase_percentage as price_increase FROM stone_color_rates ORDER BY color_name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching stone colors: " . $e->getMessage());
            return [];
        }
    }

    public function getSpecialMonuments() {
        try {
            $stmt = $this->pdo->prepare("SELECT id, sp_name as name, sp_value as price_increase_percentage FROM special_monument ORDER BY sp_value");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } catch (PDOException $e) {
            error_log("Error fetching special monuments: " . $e->getMessage());
            return [];
        }
    }
}
