<?php
/**
 * Specials API Endpoint
 * Serves data for special offer PDFs
 */
require_once '../includes/SpecialsManager.php';

// Set proper content type for JSON response
header('Content-Type: application/json');

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Initialize SpecialsManager
$manager = new SpecialsManager();

// Handle different actions
switch ($action) {
    case 'list':
        // Return the list of all available specials
        $specials = $manager->getAllSpecials();
        
        // Sort by display order
        usort($specials, function($a, $b) {
            if ($a['order'] == $b['order']) {
                return strcmp($a['title'], $b['title']);
            }
            return $a['order'] - $b['order'];
        });
        
        echo json_encode([
            'success' => true,
            'specials' => $specials
        ]);
        break;
        
    case 'get':
        // Get details for a specific special
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($id)) {
            echo json_encode([
                'success' => false,
                'error' => 'Missing ID parameter'
            ]);
            break;
        }
        
        $special = $manager->getSpecialById($id);
        
        if ($special) {
            echo json_encode([
                'success' => true,
                'special' => $special
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Special not found'
            ]);
        }
        break;
    
    default:
        // Invalid action
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        break;
}
