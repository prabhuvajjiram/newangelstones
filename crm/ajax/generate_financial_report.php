<?php
require_once '../includes/config.php';
require_once '../session_check.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $response = [];
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    if (isset($_GET['report_type'])) {
        switch ($_GET['report_type']) {
            case 'summary':
                // Get revenue summary
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(total_amount) as total_revenue,
                        COUNT(*) as total_orders
                    FROM orders
                    WHERE order_date BETWEEN ? AND ?
                    AND status = 'completed'
                ");
                $stmt->execute([$start_date, $end_date]);
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $summary['avg_order_value'] = $summary['total_orders'] > 0 
                    ? $summary['total_revenue'] / $summary['total_orders'] 
                    : 0;
                
                $response['data'] = $summary;
                break;

            case 'product_sales':
                // Get sales by product
                $stmt = $pdo->prepare("
                    SELECT 
                        p.name as product_name,
                        SUM(oi.quantity) as units_sold,
                        SUM(oi.quantity * oi.price) as revenue,
                        SUM(oi.quantity * p.cost) as cost,
                        SUM(oi.quantity * (oi.price - p.cost)) as profit
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.order_date BETWEEN ? AND ?
                    AND o.status = 'completed'
                    GROUP BY p.id, p.name
                    ORDER BY revenue DESC
                ");
                $stmt->execute([$start_date, $end_date]);
                $product_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($product_sales as &$product) {
                    $product['profit_margin'] = $product['revenue'] > 0 
                        ? ($product['profit'] / $product['revenue']) * 100 
                        : 0;
                }
                
                $response['data'] = $product_sales;
                break;

            case 'monthly_trend':
                // Get monthly revenue trend
                $stmt = $pdo->prepare("
                    SELECT 
                        DATE_FORMAT(order_date, '%Y-%m') as month,
                        SUM(total_amount) as revenue
                    FROM orders
                    WHERE order_date BETWEEN ? AND ?
                    AND status = 'completed'
                    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                    ORDER BY month
                ");
                $stmt->execute([$start_date, $end_date]);
                $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response['data'] = [
                    'labels' => array_column($monthly_data, 'month'),
                    'values' => array_column($monthly_data, 'revenue')
                ];
                break;
                
            default:
                throw new Exception('Invalid report type');
        }
        
        $response['success'] = true;
    } else {
        throw new Exception('Report type not specified');
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
