<?php
// Start session before any output
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database connection if needed
// require_once 'path_to_db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order/Quote Form - Angel Stones</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Color Selector CSS -->
    <link rel="stylesheet" href="css/color-selector.css">
    <!-- Mobile Responsive CSS -->
    <link rel="stylesheet" href="css/mobile-responsive.css">
    <style>
        /* Ultra-Modern Colorful Design for Angel Stones */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            /* Professional Business Color Palette */
            --primary-gradient: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            --secondary-gradient: linear-gradient(135deg, #2b6cb0 0%, #3182ce 100%);
            --accent-gradient: linear-gradient(135deg, #4299e1 0%, #63b3ed 100%);
            --success-gradient: linear-gradient(135deg, #38a169 0%, #48bb78 100%);
            --warning-gradient: linear-gradient(135deg, #d69e2e 0%, #ed8936 100%);
            --danger-gradient: linear-gradient(135deg, #e53e3e 0%, #f56565 100%);
            
            /* Professional Colors */
            --primary-color: #1a202c;
            --secondary-color: #2d3748;
            --accent-color: #3182ce;
            --success-color: #38a169;
            --warning-color: #d69e2e;
            --danger-color: #e53e3e;
            --blue-dark: #2c5282;
            --blue-light: #4299e1;
            --slate: #475569;
            --steel: #64748b;
            
            /* Neutral Colors */
            --white: #ffffff;
            --gray-50: #f7fafc;
            --gray-100: #edf2f7;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e0;
            --gray-400: #a0aec0;
            --gray-500: #718096;
            --gray-600: #4a5568;
            --gray-700: #2d3748;
            --gray-800: #1a202c;
            --gray-900: #171923;
            
            /* Modern Shadows */
            --shadow-xs: 0 0 0 1px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
            --shadow-glow: 0 0 20px rgba(102, 126, 234, 0.4);
            
            /* Modern Spacing */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-10: 2.5rem;
            --space-12: 3rem;
            --space-16: 4rem;
            --space-20: 5rem;
            
            /* Modern Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            --radius-3xl: 2rem;
            --radius-full: 9999px;
            
            /* Modern Transitions */
            --transition-fast: all 0.15s ease;
            --transition-normal: all 0.3s ease;
            --transition-slow: all 0.5s ease;
            --transition-bounce: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 25%, #4a5568 50%, #718096 75%, #a0aec0 100%);
            background-attachment: fixed;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 15px;
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            margin: 0;
            padding: var(--space-4);
        }

        .form-container {
            background: var(--white);
            border-radius: var(--radius-3xl);
            box-shadow: var(--shadow-2xl);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0;
            margin: 0 auto;
            max-width: 1400px;
            overflow: hidden;
            position: relative;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-gradient);
            z-index: 10;
        }

        .form-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-12) var(--space-8);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .form-header h4 {
            font-size: clamp(24px, 4vw, 36px);
            font-weight: 800;
            margin: 0 0 var(--space-3) 0;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-header p {
            font-size: 18px;
            margin: 0;
            opacity: 0.95;
            position: relative;
            z-index: 2;
            font-weight: 400;
        }

        .form-section {
            margin: 0;
            border: none;
            background: var(--white);
            position: relative;
        }

        .form-section:not(:last-child) {
            border-bottom: 1px solid var(--gray-100);
        }

        .form-section h5 {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--gray-800);
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            padding: var(--space-6) var(--space-8);
            border: none;
            position: relative;
            display: flex;
            align-items: center;
            border-left: 6px solid transparent;
            border-image: var(--accent-gradient) 1;
            transition: var(--transition-normal);
        }

        .form-section h5::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--accent-gradient);
            margin-right: var(--space-4);
            border-radius: var(--radius-full);
            box-shadow: 0 0 10px rgba(66, 153, 225, 0.5);
        }

        .form-section h5:hover {
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            transform: translateX(4px);
        }

        .form-section-inner {
            padding: var(--space-8);
            background: var(--white);
            position: relative;
        }

        .form-label {
            margin-bottom: var(--space-2);
            font-weight: 600;
            font-size: 15px;
            color: var(--gray-700);
            display: block;
            position: relative;
            padding-left: var(--space-4);
        }

        .form-label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 16px;
            background: var(--accent-gradient);
            border-radius: var(--radius-full);
        }

        .form-control, .form-select {
            font-size: 15px;
            height: 56px;
            padding: var(--space-4) var(--space-5);
            border-radius: var(--radius-xl);
            border: 2px solid var(--gray-200);
            background: var(--white);
            transition: var(--transition-bounce);
            font-family: inherit;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .form-control:focus, .form-select:focus {
            border-color: transparent;
            outline: 0;
            box-shadow: var(--shadow-glow), var(--shadow-lg);
            transform: translateY(-2px) scale(1.02);
            background: linear-gradient(135deg, var(--white) 0%, #f8faff 100%);
        }

        .form-control:hover, .form-select:hover {
            border-color: var(--accent-color);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .btn {
            font-size: 15px;
            padding: var(--space-4) var(--space-6);
            border-radius: var(--radius-xl);
            font-weight: 600;
            transition: var(--transition-bounce);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            min-height: 48px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition-fast);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn i {
            margin-right: var(--space-2);
            font-size: 16px;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--white);
            box-shadow: var(--shadow-lg);
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: var(--shadow-2xl);
            color: var(--white);
        }

        .btn-outline-secondary {
            background: var(--white);
            color: var(--gray-600);
            border: 2px solid var(--gray-300);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline-secondary:hover {
            background: var(--gray-50);
            color: var(--gray-800);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--gray-400);
        }

        .btn-success {
            background: var(--success-gradient);
            color: var(--white);
            box-shadow: var(--shadow-lg);
        }

        .btn-danger {
            background: var(--danger-gradient);
            color: var(--white);
            box-shadow: var(--shadow-lg);
        }

        .btn-sm {
            padding: var(--space-2) var(--space-4);
            font-size: 14px;
            min-height: 36px;
        }

        .input-group-text {
            font-size: 15px;
            padding: var(--space-4) var(--space-5);
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-right: none;
            height: 56px;
            font-weight: 600;
            color: var(--gray-600);
            border-radius: var(--radius-xl) 0 0 var(--radius-xl);
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 var(--radius-xl) var(--radius-xl) 0;
        }

        .table {
            margin-bottom: 0;
            font-size: 15px;
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: none;
        }

        .table th, .table td {
            padding: var(--space-5);
            vertical-align: middle;
            border-color: var(--gray-100);
            font-weight: 500;
        }

        .table thead th {
            background: var(--primary-gradient);
            color: var(--white);
            font-weight: 700;
            border: none;
            font-size: 16px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            position: relative;
        }

        .table tbody tr {
            transition: var(--transition-fast);
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
        }

        .table tfoot td {
            background: var(--gray-50);
            font-weight: 700;
            color: var(--gray-800);
            border-top: 3px solid var(--accent-color);
        }

        .total-row {
            background: linear-gradient(135deg, var(--success-gradient));
            color: var(--white) !important;
        }

        .total-row td {
            font-weight: 800;
            font-size: 18px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .required-field::after {
            content: " *";
            color: var(--danger-color);
            font-weight: 800;
            font-size: 18px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .is-invalid {
            border-color: var(--danger-color);
            background: linear-gradient(135deg, rgba(245, 101, 101, 0.05) 0%, rgba(255, 255, 255, 1) 100%);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .is-invalid:focus {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 4px rgba(245, 101, 101, 0.2);
        }

        .form-text {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: var(--space-2);
            font-style: italic;
            font-weight: 400;
        }

        .table-responsive {
            border: none;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
        }

        /* Card-style sections with modern effects */
        .form-section {
            margin-bottom: var(--space-6);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--gray-100);
            transition: var(--transition-normal);
        }

        .form-section:hover {
            box-shadow: var(--shadow-xl);
            transform: translateY(-2px);
        }

        /* Modern checkboxes and radios */
        .form-check {
            margin-bottom: var(--space-4);
            padding-left: 0;
            display: flex;
            align-items: center;
            position: relative;
        }

        .form-check-input {
            width: 24px;
            height: 24px;
            margin-top: 0;
            margin-right: var(--space-3);
            border: 3px solid var(--gray-300);
            background: var(--white);
            transition: var(--transition-bounce);
            cursor: pointer;
            position: relative;
        }

        .form-check-input:checked {
            background: var(--accent-gradient);
            border-color: transparent;
            box-shadow: 0 0 15px rgba(66, 153, 225, 0.4);
        }

        .form-check-input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(66, 153, 225, 0.2);
            outline: none;
        }

        .form-check-input:hover {
            transform: scale(1.1);
            border-color: var(--accent-color);
        }

        .form-check-label {
            font-size: 15px;
            font-weight: 500;
            color: var(--gray-700);
            cursor: pointer;
            margin-bottom: 0;
            transition: var(--transition-fast);
        }

        .form-check-label:hover {
            color: var(--accent-color);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            body {
                padding: var(--space-2);
            }
            
            .form-container {
                border-radius: var(--radius-xl);
            }
            
            .form-section-inner {
                padding: var(--space-5);
            }
            
            .form-header {
                padding: var(--space-8) var(--space-5);
            }
            
            .form-header h4 {
                font-size: 24px;
            }
        }

        /* Advanced animations */
        .form-section {
            animation: slideInUp 0.6s ease-out backwards;
        }

        .form-section:nth-child(1) { animation-delay: 0.1s; }
        .form-section:nth-child(2) { animation-delay: 0.2s; }
        .form-section:nth-child(3) { animation-delay: 0.3s; }
        .form-section:nth-child(4) { animation-delay: 0.4s; }
        .form-section:nth-child(5) { animation-delay: 0.5s; }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: var(--radius-full);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-gradient);
            border-radius: var(--radius-full);
            border: 2px solid var(--gray-100);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-gradient);
        }

        /* Loading states */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Glassmorphism effects */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Modern focus indicators */
        *:focus {
            outline: 2px solid var(--accent-color);
            outline-offset: 2px;
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            :root {
                --primary-gradient: linear-gradient(135deg, #000000 0%, #333333 100%);
                --accent-color: #0066cc;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Print styles */
        @media print {
            body {
                background: white;
            }
            
            .form-container {
                box-shadow: none;
                border: 1px solid #000;
            }
        }

        /* Enhanced Dropdown Styling */
        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px 12px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 40px !important;
        }

        .form-select:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%233182ce' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
        }

        /* Hidden Elements Styling */
        .collapse, .collapsing {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            margin-top: var(--space-4);
            overflow: hidden;
        }

        .collapse .card, .collapsing .card {
            border: none;
            box-shadow: none;
            background: transparent;
        }

        .collapse .card-body, .collapsing .card-body {
            padding: var(--space-6);
            background: var(--white);
        }

        /* Side panels and expandable sections */
        .side-panel, .expandable-section {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            margin: var(--space-4) 0;
            overflow: hidden;
            transition: var(--transition-normal);
        }

        .side-panel:hover, .expandable-section:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-1px);
        }

        .side-panel .card-header, .expandable-section .card-header {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border-bottom: 1px solid var(--gray-200);
            padding: var(--space-4) var(--space-6);
            font-weight: 600;
            color: var(--gray-700);
        }

        .side-panel .card-body, .expandable-section .card-body {
            padding: var(--space-6);
            background: var(--white);
        }

        /* Modal and popup styling */
        .modal-content {
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-2xl);
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: var(--white);
            border-bottom: none;
            padding: var(--space-6);
        }

        .modal-body {
            padding: var(--space-6);
        }

        .modal-footer {
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            padding: var(--space-4) var(--space-6);
        }

        /* Accordion styling */
        .accordion-item {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            margin-bottom: var(--space-4);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .accordion-button {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--gray-700);
            font-weight: 600;
            border: none;
            padding: var(--space-5) var(--space-6);
        }

        .accordion-button:not(.collapsed) {
            background: var(--accent-gradient);
            color: var(--white);
            box-shadow: none;
        }

        .accordion-body {
            padding: var(--space-6);
            background: var(--white);
        }

        /* Tab styling */
        .nav-tabs {
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: var(--space-6);
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: var(--space-4) var(--space-6);
            font-weight: 600;
            color: var(--gray-600);
            background: var(--gray-100);
            margin-right: var(--space-2);
            transition: var(--transition-normal);
        }

        .nav-tabs .nav-link:hover {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .nav-tabs .nav-link.active {
            background: var(--accent-gradient);
            color: var(--white);
            border-bottom: 2px solid transparent;
        }

        .tab-content {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-md);
        }

        /* Alert styling */
        .alert {
            border-radius: var(--radius-xl);
            border: none;
            padding: var(--space-5) var(--space-6);
            margin: var(--space-4) 0;
            box-shadow: var(--shadow-sm);
        }

        .alert-primary {
            background: linear-gradient(135deg, rgba(49, 130, 206, 0.1) 0%, rgba(66, 153, 225, 0.1) 100%);
            color: var(--blue-dark);
            border-left: 4px solid var(--accent-color);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(56, 161, 105, 0.1) 0%, rgba(72, 187, 120, 0.1) 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(214, 158, 46, 0.1) 0%, rgba(237, 137, 54, 0.1) 100%);
            color: var(--warning-color);
            border-left: 4px solid var(--warning-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(229, 62, 62, 0.1) 0%, rgba(245, 101, 101, 0.1) 100%);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* Badge styling */
        .badge {
            border-radius: var(--radius-full);
            padding: var(--space-2) var(--space-4);
            font-weight: 600;
            font-size: 12px;
        }

        .badge-primary {
            background: var(--accent-gradient);
            color: var(--white);
        }

        .badge-success {
            background: var(--success-gradient);
            color: var(--white);
        }

        .badge-warning {
            background: var(--warning-gradient);
            color: var(--white);
        }

        .badge-danger {
            background: var(--danger-gradient);
            color: var(--white);
        }

        /* Card styling for dynamic content */
        .card {
            border-radius: var(--radius-xl);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: var(--transition-normal);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border-bottom: 1px solid var(--gray-200);
            padding: var(--space-5) var(--space-6);
            font-weight: 600;
            color: var(--gray-700);
        }

        .card-body {
            padding: var(--space-6);
            background: var(--white);
        }

        .card-footer {
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            padding: var(--space-4) var(--space-6);
        }
        
        /* Price Validation Styling */
        .price-warning {
            border-color: var(--warning-color) !important;
            background: linear-gradient(135deg, rgba(214, 158, 46, 0.05) 0%, rgba(255, 255, 255, 1) 100%) !important;
            box-shadow: 0 0 0 2px rgba(214, 158, 46, 0.2) !important;
        }

        .price-warning:focus {
            border-color: var(--warning-color) !important;
            box-shadow: 0 0 0 4px rgba(214, 158, 46, 0.3) !important;
        }

        .price-suggestion {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border: 2px solid var(--warning-color);
            border-radius: var(--radius-lg);
            padding: var(--space-3);
            margin-top: var(--space-1);
            font-size: 12px;
            color: var(--warning-color);
            font-weight: 600;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .price-suggestion .suggestion-text {
            display: block;
            margin-bottom: var(--space-2);
        }

        .price-suggestion .suggestion-actions {
            display: flex;
            gap: var(--space-2);
        }

        .price-suggestion .btn-accept {
            background: var(--warning-gradient);
            color: var(--white);
            border: none;
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-md);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .price-suggestion .btn-accept:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-sm);
        }

        .price-suggestion .btn-dismiss {
            background: transparent;
            color: var(--gray-500);
            border: 1px solid var(--gray-300);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-md);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .price-suggestion .btn-dismiss:hover {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        /* Accessibility Improvements */
        
        /* Enhanced Radio Button and Checkbox Styling */
        .form-check-input {
            width: 1.2em !important;
            height: 1.2em !important;
            margin-top: 0.1em !important;
            border: 2px solid #495057 !important;
            background-color: #fff !important;
        }
        
        .form-check-input:checked {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }
        
        .form-check-input:focus {
            border-color: #86b7fe !important;
            outline: 0 !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        
        .form-check-input:hover {
            border-color: #0d6efd !important;
            cursor: pointer;
        }
        
        .form-check-label {
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #212529 !important;
            cursor: pointer !important;
            padding-left: 0.5rem !important;
        }
        
        .form-check-label:hover {
            color: #0d6efd !important;
        }
        
        .form-check {
            margin-bottom: 0.75rem !important;
            padding-left: 0 !important;
            display: flex !important;
            align-items: center !important;
        }
        
        /* Enhanced Focus States for All Form Elements */
        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd !important;
            outline: 0 !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        
        /* Better Button Contrast */
        .btn-primary {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            font-weight: 600 !important;
        }
        
        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #0b5ed7 !important;
            border-color: #0a58ca !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        
        .btn-outline-secondary {
            color: #495057 !important;
            border-color: #495057 !important;
            font-weight: 600 !important;
        }
        
        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus {
            background-color: #495057 !important;
            border-color: #495057 !important;
            color: #fff !important;
            box-shadow: 0 0 0 0.25rem rgba(73, 80, 87, 0.25) !important;
        }
        
        /* Enhanced Required Field Indicators */
        .required-field::after {
            content: " *";
            color: #dc3545 !important;
            font-weight: bold !important;
            font-size: 1.1em !important;
        }
        
        /* Better Error States */
        .is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        
        .is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }
        
        /* Enhanced Form Labels */
        .form-label {
            font-weight: 600 !important;
            color: #212529 !important;
            font-size: 13px !important;
        }
        
        /* Better Section Headers */
        .form-section h5 {
            background-color: #e9ecef !important;
            color: #495057 !important;
            font-weight: 700 !important;
            font-size: 14px !important;
        }
        
        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            .form-check-input {
                border-width: 3px !important;
            }
            
            .form-control,
            .form-select {
                border-width: 2px !important;
            }
            
            .btn {
                border-width: 2px !important;
                font-weight: 700 !important;
            }
        }
        
        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Better Table Accessibility */
        .table th {
            background-color: #e9ecef !important;
            color: #495057 !important;
            font-weight: 700 !important;
        }
        
        .table-striped > tbody > tr:nth-of-type(odd) > td {
            background-color: #f8f9fa !important;
        }
        
        /* Enhanced Focus Indicators for Interactive Elements */
        button:focus,
        a:focus,
        input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid #0d6efd !important;
            outline-offset: 2px !important;
        }
        
        /* Better Color Contrast for Text */
        .text-muted {
            color: #6c757d !important;
        }
        
        .form-text {
            color: #6c757d !important;
            font-size: 12px !important;
        }
        
        /* Improved Spacing for Touch Targets */
        .btn {
            min-height: 44px !important;
            min-width: 44px !important;
            padding: 8px 16px !important;
        }
        
        .btn-sm {
            min-height: 36px !important;
            min-width: 36px !important;
            padding: 6px 12px !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h4>Angel Stones - Order/Quote Form</h4>
            </div>
            
            <form id="orderQuoteForm" action="../process_order_quote.php" method="POST" enctype="multipart/form-data">
                <!-- Security Fields -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <!-- Honeypot fields (hidden from users, visible to bots) -->
                <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                    <input type="email" name="url" tabindex="-1" autocomplete="off">
                </div>
                
                <!-- Hidden fields for calculated totals -->
                <input type="hidden" name="subtotal" value="0.00">
                <input type="hidden" name="additional_charges_total" value="0.00">
                <input type="hidden" name="discount_rate" value="0.00">
                <input type="hidden" name="discount_amount" value="0.00">
                <input type="hidden" name="tax_rate" value="0.00">
                <input type="hidden" name="tax_amount" value="0.00">
                <input type="hidden" name="grand_total" value="0.00">
                
                <!-- Sales Rep & Type Section -->
                <div class="form-section">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label for="salesRep" class="form-label required-field">Sales Rep</label>
                            <select class="form-select form-select-sm" id="salesRep" name="sales_person" required>
                                <option value=""></option>
                                <option value="Sandy">Sandy</option>
                                <option value="Martha">Martha</option>
                                <option value="Mike">Mike</option>
                                <option value="Tiffany">Tiffany</option>
                                <option value="Chris">Chris</option>
                                <option value="Kattie">Kattie</option>
                                <option value="Test">Test</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="formType" class="form-label required-field">Type</label>
                            <select class="form-select form-select-sm" id="formType" name="form_type" required>
                                <option value=""></option>
                                <option value="Order">Order</option>
                                <option value="Quote">Quote</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="poNumber" class="form-label">PO#</label>
                            <input type="text" class="form-control form-control-sm" id="poNumber" name="po_number">
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label required-field">Date</label>
                            <input type="date" class="form-control form-control-sm" id="date" name="date" required>
                        </div>
                    </div>
                </div>

                <!-- Customer Information Section -->
                <div class="form-section">
                    <h5>Customer Information</h5>
                    <div class="form-section-inner">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="customerName" class="form-label required-field">Customer Name</label>
                                <input type="text" class="form-control form-control-sm" id="customerName" name="customer_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="customerCompany" class="form-label">Company</label>
                                <input type="text" class="form-control form-control-sm" id="customerCompany" name="customer_company">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-6">
                                <label for="customerEmail" class="form-label required-field">Email</label>
                                <input type="email" class="form-control form-control-sm" id="customerEmail" name="customer_email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="customerPhone" class="form-label required-field">Phone</label>
                                <input type="tel" class="form-control form-control-sm" id="customerPhone" name="customer_phone" required>
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-12">
                                <label for="customerAddress" class="form-label">Address</label>
                                <input type="text" class="form-control form-control-sm" id="customerAddress" name="customer_address">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-4">
                                <label for="customerCity" class="form-label">City</label>
                                <input type="text" class="form-control form-control-sm" id="customerCity" name="customer_city">
                            </div>
                            <div class="col-md-4">
                                <label for="customerState" class="form-label">State</label>
                                <select class="form-select form-select-sm" id="customerState" name="customer_state">
                                    <option value=""></option>
                                    <option value="AL">Alabama</option>
                                    <option value="AK">Alaska</option>
                                    <option value="AZ">Arizona</option>
                                    <option value="AR">Arkansas</option>
                                    <option value="CA">California</option>
                                    <option value="CO">Colorado</option>
                                    <option value="CT">Connecticut</option>
                                    <option value="DE">Delaware</option>
                                    <option value="FL">Florida</option>
                                    <option value="GA">Georgia</option>
                                    <option value="HI">Hawaii</option>
                                    <option value="ID">Idaho</option>
                                    <option value="IL">Illinois</option>
                                    <option value="IN">Indiana</option>
                                    <option value="IA">Iowa</option>
                                    <option value="KS">Kansas</option>
                                    <option value="KY">Kentucky</option>
                                    <option value="LA">Louisiana</option>
                                    <option value="ME">Maine</option>
                                    <option value="MD">Maryland</option>
                                    <option value="MA">Massachusetts</option>
                                    <option value="MI">Michigan</option>
                                    <option value="MN">Minnesota</option>
                                    <option value="MS">Mississippi</option>
                                    <option value="MO">Missouri</option>
                                    <option value="MT">Montana</option>
                                    <option value="NE">Nebraska</option>
                                    <option value="NV">Nevada</option>
                                    <option value="NH">New Hampshire</option>
                                    <option value="NJ">New Jersey</option>
                                    <option value="NM">New Mexico</option>
                                    <option value="NY">New York</option>
                                    <option value="NC">North Carolina</option>
                                    <option value="ND">North Dakota</option>
                                    <option value="OH">Ohio</option>
                                    <option value="OK">Oklahoma</option>
                                    <option value="OR">Oregon</option>
                                    <option value="PA">Pennsylvania</option>
                                    <option value="RI">Rhode Island</option>
                                    <option value="SC">South Carolina</option>
                                    <option value="SD">South Dakota</option>
                                    <option value="TN">Tennessee</option>
                                    <option value="TX">Texas</option>
                                    <option value="UT">Utah</option>
                                    <option value="VT">Vermont</option>
                                    <option value="VA">Virginia</option>
                                    <option value="WA">Washington</option>
                                    <option value="WV">West Virginia</option>
                                    <option value="WI">Wisconsin</option>
                                    <option value="WY">Wyoming</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="customerZip" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control form-control-sm" id="customerZip" name="customer_zip">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Payment and Shipping Information -->
                <div class="form-section">
                    <h5>Payment & Shipping Details</h5>
                    <div class="form-section-inner">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required-field">Payment Terms</label>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="payOnAck" value="Pay on ACK" required>
                                    <label class="form-check-label" for="payOnAck">Pay on ACK</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="fullPayment" value="FULL">
                                    <label class="form-check-label" for="fullPayment">FULL</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="oneThird" value="1/3rd">
                                    <label class="form-check-label" for="oneThird">1/3rd</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="oneHalf" value="1/2">
                                    <label class="form-check-label" for="oneHalf">1/2</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="cod" value="COD">
                                    <label class="form-check-label" for="cod">COD</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="payBeforeShipping" value="Pay before shipping">
                                    <label class="form-check-label" for="payBeforeShipping">Pay before shipping</label>
                                </div>
                                <div class="invalid-feedback">
                                    Please select a payment term.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="firstOrder" name="first_order" value="1">
                                    <label class="form-check-label" for="firstOrder">First Order</label>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="truckerInfo" class="form-label required-field">Trucker Information</label>
                                    <input type="text" class="form-control form-control-sm" id="truckerInfo" name="trucker_info" required>
                                    <div class="invalid-feedback">
                                        Please provide trucker information.
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="termsDetails" class="form-label required-field">Terms</label>
                                    <textarea class="form-control form-control-sm" id="termsDetails" name="terms" rows="2" required></textarea>
                                    <div class="invalid-feedback">
                                        Please provide terms details.
                                    </div>
                                </div>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="sameAsBilling" name="same_as_billing" value="1">
                                    <label class="form-check-label" for="sameAsBilling">Ship to same as billing address</label>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="sealCertificate" name="seal_certificate" value="1">
                                            <label class="form-check-label" for="sealCertificate">Seal & Certificate</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                             <input class="form-check-input" type="checkbox" id="markCrate" name="mark_crate" value="1">
                                            <label class="form-check-label" for="markCrate">Mark Crate</label>
                                        </div>
                                        <!-- Mark Crate Details (initially hidden) -->
                                        <div class="mark-crate-details mt-2" style="display: none;">
                                            <input type="text" class="form-control form-control-sm" id="markCrateDetails" name="mark_crate_details" placeholder="Enter Mark Crate details">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address Section (initially hidden, shows when needed) -->
                <div class="form-section" id="shippingAddressSection" style="display: none;">
                    <h5>Shipping Address</h5>
                    <div class="form-section-inner">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shippingName" class="form-label">Name</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingName" name="shipping_name">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingCompany" class="form-label">Company</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingCompany" name="shipping_company">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingAddress1" class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingAddress1" name="shipping_address1">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingAddress2" class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingAddress2" name="shipping_address2">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shippingCity" class="form-label">City</label>
                                        <input type="text" class="form-control form-control-sm" id="shippingCity" name="shipping_city">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="shippingState" class="form-label">State</label>
                                        <select class="form-select form-select-sm" id="shippingState" name="shipping_state">
                                            <option value="">Select State</option>
                                            <!-- States will be populated by JavaScript -->
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="shippingZip" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingZip" name="shipping_zip">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingPhone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control form-control-sm" id="shippingPhone" name="shipping_phone">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control form-control-sm" id="shippingEmail" name="shipping_email">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Information Section -->
                <div class="form-section">
                    <h5>Product Information</h5>
                    <div class="form-section-inner">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="productsTable">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">#</th>
                                        <th width="40%">Product Name/Code</th>
                                        <th width="15%" class="text-center">Quantity</th>
                                        <th width="20%" class="text-end">Price</th>
                                        <th width="15%" class="text-end">Total</th>
                                        <th width="5%" class="text-center">Remove</th>
                                    </tr>
                                </thead>
                                <tbody id="productContainer">
                                    <tr class="product-row">
                                <td class="text-center align-middle" data-label="#">1</td>
                                <td data-label="Product">
                                    <input type="text" class="form-control form-control-sm product-name mb-1" name="products[0][name]" placeholder="Product Name" required>
                                    
                                    <!-- Enhanced Granite Color Dropdown -->
                                    <div class="mb-1">
                                        <label class="form-label small mb-1 required-field">Granite Color</label>
                                        <select class="form-select form-select-sm granite-color" name="products[0][color]" required>
                                            <option value="">Select Granite Color</option>
                                            <!-- Dynamic colors will be populated here -->
                                            <option value="other">Other (Specify)</option>
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1 d-none" name="products[0][custom_color]" placeholder="Enter custom color">
                                    </div>
                                    
                                    <!-- Product Type Checkboxes -->
                                    <div class="mb-2">
                                        <label class="form-label small mb-1 required-field">Product Type (Select at least one)</label>
                                        <div class="row g-2">
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_tablet_1" value="Tablet">
                                                    <label class="form-check-label small" for="product_type_tablet_1">Tablet</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_slant_1" value="Slant Base">
                                                    <label class="form-check-label small" for="product_type_slant_1">Slant Base</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_grass_1" value="Grass Marker">
                                                    <label class="form-check-label small" for="product_type_grass_1">Grass Marker</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_hickey_1" value="Hickey">
                                                    <label class="form-check-label small" for="product_type_hickey_1">Hickey</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_bench_1" value="Bench">
                                                    <label class="form-check-label small" for="product_type_bench_1">Bench</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_vase_1" value="Vase">
                                                    <label class="form-check-label small" for="product_type_vase_1">Vase</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_other_1" value="Other">
                                                    <label class="form-check-label small" for="product_type_other_1">Other</label>
                                                </div>
                                                <!-- Text box for Other product type (initially hidden) -->
                                                <div class="other-product-text mt-1" style="display: none;">
                                                    <input type="text" class="form-control form-control-sm" name="products[0][other_product_name]" placeholder="Enter product name/code">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please select at least one product type.
                                        </div>
                                    </div>

                                    <!-- Manufacturing Type -->
                                    <div class="mb-1">
                                        <label class="form-label small mb-1 required-field">Manufacturing Type</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input manufacturing-type" type="radio" name="products[0][manufacturing_type]" id="manufactured_1" value="manufactured" required>
                                            <label class="form-check-label" for="manufactured_1">Manufactured</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input manufacturing-type" type="radio" name="products[0][manufacturing_type]" id="prefinished_1" value="prefinished">
                                            <label class="form-check-label" for="prefinished_1">Prefinished</label>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please select a manufacturing type.
                                        </div>
                                        
                                        <!-- Manufacturing Options (initially hidden) -->
                                        <div class="manufacturing-options mt-1" style="display: none;">
                                            <select class="form-select form-select-sm" name="products[0][manufacturing_option]">
                                                <!-- Options will be populated by JavaScript -->
                                            </select>
                                        </div>
                                        
                                        <!-- Manufacturing Text Box -->
                                        <div class="manufacturing-text-box mt-2">
                                            <label class="form-label small mb-1">Manufacturing Details</label>
                                            <input type="text" class="form-control form-control-sm" name="products[0][manufacturing_details]" placeholder="Enter manufacturing details">
                                        </div>
                                        
                                        <!-- Side Section -->
                                        <div class="mt-3 border-top pt-2">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="small fw-bold mb-0">SIDES</h6>
                                                <span class="badge bg-secondary side-count">0 Sides</span>
                                            </div>
                                            <div class="sides-container" data-product-index="0">
                                                <!-- Sides will be added here dynamically -->
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-side" data-product-index="0">
                                                <i class="bi bi-plus"></i> Add Side
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center align-middle" data-label="Quantity">
                                    <input type="number" class="form-control form-control-sm quantity text-center" name="products[0][quantity]" min="1" value="1" required>
                                </td>
                                <td class="align-middle" data-label="Price">
                                    <div class="input-group input-group-sm">
                                        <span class="input-text">$</span>
                                        <input type="number" step="0.01" class="form-control form-control-sm price text-end" name="products[0][price]">
                                    </div>
                                </td>
                                <td class="align-middle" data-label="Total">
                                    <div class="input-group input-group-sm">
                                        <span class="input-text">$</span>
                                        <input type="text" class="form-control form-control-sm total text-end" name="products[0][total]" value="0.00" readonly>
                                    </div>
                                </td>
                                <td class="text-center align-middle" data-label="Remove">
                                    <button type="button" class="btn btn-link btn-sm text-danger remove-product p-0" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="addProduct">
                                                <i class="bi bi-plus-lg"></i> Add Product
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="total-row">
                                        <td colspan="4" class="text-end">Subtotal:</td>
                                        <td class="text-end"><span id="subtotal">$0.00</span></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Discount:</td>
                                        <td class="text-end">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-end" id="discountRate" value="0.00" placeholder="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td class="text-end"><span id="discount">-$0.00</span></td>
                                        <td></td>
                                    </tr>
                                    <tr class="total-row">
                                        <td colspan="4" class="text-end">Additional Charges:</td>
                                        <td class="text-end"><span id="additionalChargesTotal">$0.00</span></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Tax:</td>
                                        <td class="text-end">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-end" id="taxRate" value="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td class="text-end"><span id="tax">$0.00</span></td>
                                        <td></td>
                                    </tr>
                                    <tr class="total-row fw-bold">
                                        <td colspan="4" class="text-end">Total:</td>
                                        <td class="text-end"><span id="grandTotal">$0.00</span></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Notes Section -->
                <div class="form-section">
                    <h5>Additional Notes</h5>
                    <div class="form-section-inner">
                        <div class="mb-2">
                            <label for="notes" class="form-label">Special Instructions or Notes</label>
                            <textarea class="form-control form-control-sm" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <!-- File Upload Section -->
                <div class="form-section">
                    <h5>File Attachments</h5>
                    <div class="form-section-inner">
                        <div class="mb-3">
                            <label for="fileUploads" class="form-label">Upload Files (Images, PDFs, Excel files - Max 10MB total)</label>
                            <input class="form-control form-control-sm" type="file" id="fileUploads" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.xls,.xlsx">
                            <div class="form-text">You can select multiple files. Allowed types: JPG, PNG, GIF, PDF, XLS, XLSX</div>
                        </div>
                        <div id="uploadPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                    <button type="button" id="saveDraftBtn" class="btn btn-secondary me-2">
    <i class="bi bi-file-earmark-arrow-down"></i> Save as Draft
</button>
                    </div>
                    <div>
                        <button type="reset" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-x-lg"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send"></i> <span id="submitBtnText">Submit</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="submitSpinner"></span>
                        </button>
                    </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Color Selector JS -->
    <script src="js/color-selector.js"></script>
    
    <!-- Mobile Table Enhancements JS -->
    <script src="js/mobile-table-enhancements.js"></script>
    
    <script>
        // US States array for shipping state dropdown
        console.log('Script loaded successfully!');
        const usStates = [
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
            'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
            'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
            'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
            'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
        ];

        $(document).ready(function() {
            // Fix dropdown closing issue
            $(document).on('change', '.form-select', function() {
                // Force blur to close dropdown properly
                const $this = $(this);
                setTimeout(() => {
                    $this.blur();
                }, 50);
            });
            
            // Handle dropdown focus states properly
            $(document).on('focus', '.form-select', function() {
                $(this).addClass('dropdown-focused');
            });
            
            $(document).on('blur', '.form-select', function() {
                $(this).removeClass('dropdown-focused');
            });
            
            // Handle discount rate changes
            $(document).on('input change', '#discountRate', function() {
                calculateOrderTotals();
            });
            
            // Populate shipping state dropdown
            const $shippingState = $('#shippingState');
            usStates.forEach(state => {
                $shippingState.append(`<option value="${state}">${state}</option>`);
            });

            // Function to update shipping address visibility and copy data if needed
            function updateShippingAddressVisibility() {
                const $shippingSection = $('#shippingAddressSection');
                const isSameAsBilling = $('#sameAsBilling').is(':checked');
                
                if (isSameAsBilling) {
                    $shippingSection.hide();
                    // Copy billing address to shipping address
                    $('#shippingName').val($('#customerName').val());
                    $('#shippingCompany').val($('#customerCompany').val());
                    $('#shippingAddress1').val($('#customerAddress').val());
                    $('#shippingAddress2').val('');
                    $('#shippingCity').val($('#customerCity').val());
                    $('#shippingState').val($('#customerState').val());
                    $('#shippingZip').val($('#customerZip').val());
                    $('#shippingPhone').val($('#customerPhone').val());
                    $('#shippingEmail').val($('#customerEmail').val());
                } else {
                    $shippingSection.show();
                }
            }

            // Initialize shipping address visibility on page load
            updateShippingAddressVisibility();
            
            // Handle same as billing address checkbox changes
            $('#sameAsBilling').change(updateShippingAddressVisibility);

            // Dynamic Color Loading Functionality
            let colorCache = null;
            let fallbackColors = [
                'Absolute Black',
                'Alaska White', 
                'Black Galaxy',
                'Blue Pearl',
                'Colonial White',
                'Costa Esmeralda'
            ];

            // Load colors from service
            async function loadColors() {
                try {
                    console.log('Loading colors from service...');
                    const response = await fetch('get_color_images.php');
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.colors && Array.isArray(data.colors)) {
                        console.log(`Successfully loaded ${data.colors.length} colors from service`);
                        colorCache = data.colors;
                        return data.colors;
                    } else {
                        throw new Error(data.error || 'Invalid response format');
                    }
                } catch (error) {
                    console.error('Error loading colors from service:', error);
                    console.log('Falling back to hardcoded colors');
                    
                    // Return fallback colors in the expected format
                    colorCache = fallbackColors.map(name => ({ name: name }));
                    return colorCache;
                }
            }

            // Populate color dropdown with dynamic colors
            function populateColorDropdown($select, colors) {
                if (!$select || !colors) return;
                
                // Store current value to preserve selection
                const currentValue = $select.val();
                
                // Clear existing options except placeholder and "Other"
                $select.find('option').not(':first').not('[value="other"]').remove();
                
                // Add dynamic colors before "Other" option
                const $otherOption = $select.find('option[value="other"]');
                
                colors.forEach(color => {
                    const $option = $('<option></option>')
                        .attr('value', color.name)
                        .text(color.name);
                    
                    if ($otherOption.length > 0) {
                        $option.insertBefore($otherOption);
                    } else {
                        $select.append($option);
                    }
                });
                
                // If "Other" option doesn't exist, add it
                if ($otherOption.length === 0) {
                    $select.append('<option value="other">Other (Specify)</option>');
                }
                
                // Restore previous selection if it still exists
                if (currentValue && $select.find(`option[value="${currentValue}"]`).length > 0) {
                    $select.val(currentValue);
                }
            }

            // Initialize colors for all existing dropdowns
            async function initializeColors() {
                console.log('Initializing dynamic colors...');
                
                // Add loading state to all color dropdowns
                $('.granite-color').each(function() {
                    const $select = $(this);
                    const originalHtml = $select.html();
                    $select.html('<option value="">Loading colors...</option>');
                    $select.data('original-html', originalHtml);
                    $select.addClass('loading');
                });
                
                try {
                    const colors = await loadColors();
                    
                    // Update all existing color dropdowns
                    $('.granite-color').each(function() {
                        populateColorDropdown($(this), colors);
                        $(this).removeClass('loading').addClass('loaded');
                    });
                    
                    console.log('Color initialization complete');
                } catch (error) {
                    console.error('Failed to initialize colors:', error);
                    
                    // Restore original HTML on error
                    $('.granite-color').each(function() {
                        const $select = $(this);
                        const originalHtml = $select.data('original-html');
                        if (originalHtml) {
                            $select.html(originalHtml);
                        }
                        $select.removeClass('loading').addClass('error');
                    });
                }
            }

            // Initialize colors when page loads
            $(document).ready(function() {
                initializeColors();
            });

            // Handle granite color selection (show/hide custom color input)
            $(document).on('change', '.granite-color', function() {
                const $customColorInput = $(this).closest('tr').find('input[name$="[custom_color]"]');
                if ($(this).val() === 'other') {
                    $customColorInput.removeClass('d-none').prop('required', true).addClass('active');
                } else {
                    $customColorInput.addClass('d-none').prop('required', false).val('').removeClass('active');
                }
            });

            // Handle manufacturing type selection
            $(document).on('change', '.manufacturing-type', function() {
                const $row = $(this).closest('tr');
                const $optionsContainer = $row.find('.manufacturing-options');
                const $select = $optionsContainer.find('select');
                
                // Clear existing options
                $select.empty();
                
                // Add options based on selection
                if ($(this).val() === 'manufactured') {
                    $select.append([
                        '<option value="">Select Manufacturing Option</option>',
                        '<option value="inhouse">In-House</option>',
                        '<option value="outsource">Outsource</option>',
                        '<option value="inventory">Inventory</option>'
                    ].join(''));
                } else if ($(this).val() === 'prefinished') {
                    $select.append([
                        '<option value="">Select Prefinished Option</option>',
                        '<option value="import">Import</option>',
                        '<option value="inventory">Inventory</option>',
                        '<option value="outsource">Outsource</option>'
                    ].join(''));
                }
                
                // Show/hide the options
                $optionsContainer.toggle(!!$(this).val());
                $select.prop('required', !!$(this).val());
            });
            
            // Initialize the first row's manufacturing type if needed
            $('.manufacturing-type:checked').trigger('change');

            // Handle product type validation
            $(document).on('change', '.product-type', function() {
                const $row = $(this).closest('tr');
                const $productTypeContainer = $row.find('.product-type').closest('.mb-2');
                
                // Check if at least one product type is selected
                const anyChecked = $row.find('.product-type:checked').length > 0;
                $productTypeContainer.toggleClass('was-validated', !anyChecked);
                
                // Remove validation classes when at least one is checked
                if (anyChecked) {
                    $productTypeContainer.removeClass('is-invalid');
                }
            });
            
            // Handle manufacturing type validation
            $(document).on('change', '.manufacturing-type', function() {
                const $row = $(this).closest('tr');
                const $manufacturingContainer = $row.find('.manufacturing-type').closest('.mb-1');
                
                // Remove validation class when a manufacturing type is selected
                $manufacturingContainer.removeClass('is-invalid');
            });
            
            // Form validation
            function validateProductRow($row) {
                let isValid = true;
                
                // Validate product types
                const $productTypeContainer = $row.find('.product-type').closest('.mb-2');
                const hasProductType = $row.find('.product-type:checked').length > 0;
                
                if (!hasProductType) {
                    $productTypeContainer.addClass('is-invalid');
                    isValid = false;
                } else {
                    $productTypeContainer.removeClass('is-invalid');
                }
                
                // Validate manufacturing type
                const $manufacturingContainer = $row.find('.manufacturing-type').closest('.mb-1');
                const hasManufacturingType = $row.find('.manufacturing-type:checked').length > 0;
                
                if (!hasManufacturingType) {
                    $manufacturingContainer.addClass('is-invalid');
                    isValid = false;
                } else {
                    $manufacturingContainer.removeClass('is-invalid');
                }
                
                return isValid;
            }

            // Comprehensive Form Validation
            $('#orderQuoteForm').on('submit', function(e) {
                e.preventDefault();
                
                let isValid = true;
                let errors = [];
                
                // Clear previous validation states
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                // Validate customer name
                const customerName = $('input[name="customer_name"]').val().trim();
                if (!customerName || customerName.length < 2 || customerName.length > 100) {
                    addValidationError('input[name="customer_name"]', 'Customer name is required (2-100 characters)');
                    errors.push('Customer name is required');
                    isValid = false;
                }
                
                // Validate email
                const customerEmail = $('input[name="customer_email"]').val().trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!customerEmail || !emailRegex.test(customerEmail)) {
                    addValidationError('input[name="customer_email"]', 'Valid email address is required');
                    errors.push('Valid email address is required');
                    isValid = false;
                }
                
                // Validate phone if provided
                const customerPhone = $('input[name="customer_phone"]').val().trim();
                if (customerPhone) {
                    const phoneRegex = /^[\d\s\-\+\(\)\.]{10,20}$/;
                    if (!phoneRegex.test(customerPhone)) {
                        addValidationError('input[name="customer_phone"]', 'Valid phone number format required');
                        errors.push('Valid phone number format required');
                        isValid = false;
                    }
                }
                
                // Validate sales person selection
                const salesPerson = $('select[name="sales_person"]').val();
                if (!salesPerson) {
                    addValidationError('select[name="sales_person"]', 'Please select a sales representative');
                    errors.push('Sales representative selection is required');
                    isValid = false;
                }
                
                // Validate form type
                const formType = $('select[name="form_type"]').val();
                if (!formType) {
                    addValidationError('select[name="form_type"]', 'Please select Quote or Order');
                    errors.push('Form type selection is required');
                    isValid = false;
                }
                
                // Validate file uploads
                const fileInput = document.getElementById('fileUploads');
                if (fileInput && fileInput.files.length > 0) {
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                    const maxFileSize = 10 * 1024 * 1024; // 10MB
                    let totalSize = 0;
                    
                    for (let i = 0; i < fileInput.files.length; i++) {
                        const file = fileInput.files[i];
                        totalSize += file.size;
                        
                        if (file.size > maxFileSize) {
                            addValidationError('#fileUploads', `File "${file.name}" exceeds 10MB limit`);
                            errors.push(`File "${file.name}" is too large`);
                            isValid = false;
                        }
                        
                        if (!allowedTypes.includes(file.type)) {
                            addValidationError('#fileUploads', `File "${file.name}" has invalid file type`);
                            errors.push(`File "${file.name}" has invalid type`);
                            isValid = false;
                        }
                    }
                    
                    if (totalSize > maxFileSize) {
                        addValidationError('#fileUploads', 'Total file size exceeds 10MB limit');
                        errors.push('Total file size is too large');
                        isValid = false;
                    }
                }
                
                // Show validation summary if errors exist
                if (!isValid) {
                    showValidationSummary(errors);
                    // Scroll to first error
                    const firstError = $('.is-invalid').first();
                    if (firstError.length) {
                        $('html, body').animate({
                            scrollTop: firstError.offset().top - 100
                        }, 500);
                    }
                    return false;
                }
                
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="bi bi-hourglass-split me-2"></i>Processing...').prop('disabled', true);
                
                // Submit form if validation passes
                this.submit();
            });
            
            // Helper function to add validation error
            function addValidationError(selector, message) {
                const element = $(selector);
                element.addClass('is-invalid');
                element.after(`<div class="invalid-feedback">${message}</div>`);
            }
            
            // Helper function to show validation summary
            function showValidationSummary(errors) {
                // Remove existing summary
                $('.validation-summary').remove();
                
                // Create validation summary
                const summary = $(`
                    <div class="validation-summary alert alert-danger" role="alert">
                        <h6><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</h6>
                        <ul class="mb-0">
                            ${errors.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                `);
                
                // Insert at top of form
                $('#orderQuoteForm').prepend(summary);
            }
            
            // Remove invalid class when user interacts with required fields
            $('.payment-term').on('change', function() {
                $('.payment-term').removeClass('is-invalid');
            });
            
            $('#truckerInfo').on('input', function() {
                $(this).removeClass('is-invalid');
            });
            
            $('#termsDetails').on('input', function() {
                $(this).removeClass('is-invalid');
            });
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            $('#date').val(today);
            
            // Force recalculation of all rows and order totals on page load
            $('.product-row').each(function() {
                updateRowTotal($(this));
            });
            
            let productCount = 1;
            
            // Add new product row
            $('#addProduct').click(function() {
                productCount++;
                const newRow = `
                    <tr class="product-row">
                        <td class="text-center align-middle" data-label="#">${productCount}</td>
                        <td data-label="Product">
                            <input type="text" class="form-control form-control-sm product-name mb-1" name="products[${productCount-1}][name]" placeholder="Product Name" required>
                            
                            <!-- Enhanced Granite Color Dropdown -->
                            <div class="mb-1">
                                <label class="form-label small mb-1 required-field">Granite Color</label>
                                <select class="form-select form-select-sm granite-color" name="products[${productCount-1}][color]" required>
                                    <option value="">Select Granite Color</option>
                                    <!-- Dynamic colors will be populated here -->
                                    <option value="other">Other (Specify)</option>
                                </select>
                                <input type="text" class="form-control form-control-sm mt-1 d-none" name="products[${productCount-1}][custom_color]" placeholder="Enter custom color">
                            </div>
                            
                            <!-- Product Type Checkboxes -->
                            <div class="mb-2 product-type-container">
                                <label class="form-label small mb-1 required-field">Product Type (Select at least one)</label>
                                <div class="row g-2">
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_tablet_${productCount}" value="Tablet">
                                            <label class="form-check-label small" for="product_type_tablet_${productCount}">Tablet</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_slant_${productCount}" value="Slant Base">
                                            <label class="form-check-label small" for="product_type_slant_${productCount}">Slant Base</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_grass_${productCount}" value="Grass Marker">
                                            <label class="form-check-label small" for="product_type_grass_${productCount}">Grass Marker</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_hickey_${productCount}" value="Hickey">
                                            <label class="form-check-label small" for="product_type_hickey_${productCount}">Hickey</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_bench_${productCount}" value="Bench">
                                            <label class="form-check-label small" for="product_type_bench_${productCount}">Bench</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_vase_${productCount}" value="Vase">
                                            <label class="form-check-label small" for="product_type_vase_${productCount}">Vase</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_other_${productCount}" value="Other">
                                            <label class="form-check-label small" for="product_type_other_${productCount}">Other</label>
                                        </div>
                                        <!-- Text box for Other product type (initially hidden) -->
                                        <div class="other-product-text mt-1" style="display: none;">
                                            <input type="text" class="form-control form-control-sm" name="products[${productCount-1}][other_product_name]" placeholder="Enter product name/code">
                                        </div>
                                    </div>
                                </div>
                                <div class="invalid-feedback">
                                    Please select at least one product type.
                                </div>
                            </div>

                            <!-- Manufacturing Type -->
                            <div class="mb-1 manufacturing-type-container">
                                <label class="form-label small mb-1 required-field">Manufacturing Type</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input manufacturing-type" type="radio" name="products[${productCount-1}][manufacturing_type]" id="manufactured_${productCount}" value="manufactured" required>
                                    <label class="form-check-label" for="manufactured_${productCount}">Manufactured</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input manufacturing-type" type="radio" name="products[${productCount-1}][manufacturing_type]" id="prefinished_${productCount}" value="prefinished">
                                    <label class="form-check-label" for="prefinished_${productCount}">Prefinished</label>
                                </div>
                                <div class="invalid-feedback">
                                    Please select a manufacturing type.
                                </div>
                                
                                <!-- Manufacturing Options (initially hidden) -->
                                <div class="manufacturing-options mt-1" style="display: none;">
                                    <select class="form-select form-select-sm" name="products[${productCount-1}][manufacturing_option]" required>
                                        <option value="">Select an option</option>
                                    </select>
                                </div>
                                
                                <!-- Manufacturing Text Box -->
                                <div class="manufacturing-text-box mt-2">
                                    <label class="form-label small mb-1">Manufacturing Details</label>
                                    <input type="text" class="form-control form-control-sm" name="products[${productCount-1}][manufacturing_details]" placeholder="Enter manufacturing details">
                                </div>
                                
                                <!-- Total Charges -->
<div class="mt-3 border-top pt-2">
    <div class="row">
        <div class="col-6">
            <label class="form-label fw-medium">Additional Charges:</label>
        </div>
        <div class="col-6 text-end">
            <span class="fw-bold">$<span class="additional-charges">0.00</span></span>
        </div>
    </div>
    <div class="row mt-1">
        <div class="col-6">
            <label class="form-label fw-medium">Product Total:</label>
        </div>
        <div class="col-6 text-end">
            <span class="fw-bold">$<span class="product-total">0.00</span></span>
        </div>
    </div>
    <div class="row mt-1">
        <div class="col-6">
            <label class="form-label fw-medium">Grand Total:</label>
        </div>
        <div class="col-6 text-end">
            <span class="fw-bold fs-5">$<span class="grand-total">0.00</span></span>
        </div>
    </div>
</div>
                                <!-- Side Section -->
                                <div class="mt-3 border-top pt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="small fw-bold mb-0">SIDES</h6>
                                        <span class="badge bg-secondary side-count">0 Sides</span>
                                    </div>
                                    <div class="sides-container" data-product-index="${productCount-1}">
                                        <!-- Sides will be added here dynamically -->
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-side" data-product-index="${productCount-1}">
                                        <i class="bi bi-plus"></i> Add Side
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class="text-center align-middle" data-label="Quantity">
                            <input type="number" class="form-control form-control-sm quantity text-center" name="products[${productCount-1}][quantity]" min="1" value="1" required>
                        </td>
                        <td class="align-middle" data-label="Price">
                            <div class="input-group input-group-sm">
                                <span class="input-text">$</span>
                                <input type="number" step="0.01" class="form-control form-control-sm price text-end" name="products[${productCount-1}][price]" required>
                            </div>
                        </td>
                        <td class="align-middle" data-label="Total">
                            <div class="input-group input-group-sm">
                                <span class="input-text">$</span>
                                <input type="text" class="form-control form-control-sm total text-end" name="products[${productCount-1}][total]" value="0.00" readonly>
                            </div>
                        </td>
                        <td class="text-center align-middle" data-label="Remove">
                            <button type="button" class="btn btn-link btn-sm text-danger remove-product p-0" title="Remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                
                const $newRow = $(newRow);
                $('#productContainer').append($newRow);
                
                // Add event listeners to the new row
                $newRow.find('.quantity, .price').on('input', calculateRowTotal);
                
                // Populate colors for the new row
                if (colorCache) {
                    populateColorDropdown($newRow.find('.granite-color'), colorCache);
                }
                
                // Initialize granite color change handler
                $newRow.find('.granite-color').on('change', function() {
                    const $customColorInput = $(this).closest('tr').find('input[name$="[custom_color]"]');
                    if ($(this).val() === 'other') {
                        $customColorInput.removeClass('d-none').prop('required', true).addClass('active');
                    } else {
                        $customColorInput.addClass('d-none').prop('required', false).val('').removeClass('active');
                    }
                });
                
                // Initialize manufacturing type change handler
                $newRow.find('.manufacturing-type').on('change', function() {
                    const $row = $(this).closest('tr');
                    const $optionsContainer = $row.find('.manufacturing-options');
                    const $select = $optionsContainer.find('select');
                    
                    // Clear existing options
                    $select.empty().append('<option value="">Select an option</option>');
                    
                    // Add options based on selection
                    if ($(this).val() === 'manufactured') {
                        $select.append([
                            '<option value="inhouse">In-House</option>',
                            '<option value="outsource">Outsource</option>',
                            '<option value="inventory">Inventory</option>'
                        ].join(''));
                    } else if ($(this).val() === 'prefinished') {
                        $select.append([
                            '<option value="import">Import</option>',
                            '<option value="inventory">Inventory</option>',
                            '<option value="outsource">Outsource</option>'
                        ].join(''));
                    }
                    
                    // Show/hide the options
                    $optionsContainer.toggle(!!$(this).val());
                });
                
                // Initialize product type validation
                $newRow.find('.product-type').on('change', function() {
                    const $container = $(this).closest('.product-type-container');
                    const anyChecked = $container.find('.product-type:checked').length > 0;
                    
                    if (anyChecked) {
                        $container.removeClass('is-invalid');
                    }
                });
                
                // Initialize manufacturing type validation
                $newRow.find('.manufacturing-type').on('change', function() {
                    $(this).closest('.manufacturing-type-container').removeClass('is-invalid');
                    
                    // Trigger change to update manufacturing options
                    $(this).trigger('change');
                });
                
                // Initialize manufacturing options validation
                $newRow.find('.manufacturing-options select').on('change', function() {
                    if ($(this).val()) {
                        $(this).removeClass('is-invalid');
                    }
                });
            });
            
            // Update row numbers when rows are removed
            function updateRowNumbers() {
                $('.product-row').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                    // Update the array indices in the name attributes
                    $(this).find('input, select').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                            $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                        }
                    });
                });
                productCount = $('.product-row').length;
            }
            
            // Calculate row total when quantity or price changes
            $(document).on('input', '.quantity, .price', function() {
                calculateRowTotal.call(this);
                
                // Validate price if this is a price input
                if ($(this).hasClass('price')) {
                    validatePrice($(this));
                }
            });
            
            function calculateRowTotal() {
                const row = $(this).closest('tr');
                const quantity = parseFloat(row.find('.quantity').val()) || 0;
                const price = parseFloat(row.find('.price').val()) || 0;
                const total = (quantity * price).toFixed(2);
                row.find('.total').val(total);
                calculateOrderTotals();
            }
            
            // Price validation function with soft warnings
            function validatePrice($priceInput) {
                const price = parseFloat($priceInput.val()) || 0;
                const row = $priceInput.closest('tr');
                const quantity = parseFloat(row.find('.quantity').val()) || 1;
                
                // Remove existing validation states and suggestions
                $priceInput.removeClass('price-warning');
                row.find('.price-suggestion').remove();
                
                // Skip validation if price is 0 or empty
                if (price <= 0) {
                    return;
                }
                
                // Get product type information for context-aware validation
                const productTypes = [];
                row.find('.product-type:checked').each(function() {
                    productTypes.push($(this).val());
                });
                
                const manufacturingTypes = [];
                row.find('.manufacturing-type:checked').each(function() {
                    manufacturingTypes.push($(this).val());
                });
                
                // Define price ranges based on product and manufacturing types
                const priceRanges = getPriceRanges(productTypes, manufacturingTypes);
                
                // Check if price falls outside expected ranges
                let suggestion = null;
                let warningType = null;
                
                if (price < priceRanges.min) {
                    warningType = 'low';
                    suggestion = {
                        message: `Price seems low for this product type. Suggested minimum: $${priceRanges.min}`,
                        suggestedPrice: priceRanges.min
                    };
                } else if (price > priceRanges.max) {
                    warningType = 'high';
                    suggestion = {
                        message: `Price seems high for this product type. Suggested maximum: $${priceRanges.max}`,
                        suggestedPrice: priceRanges.max
                    };
                } else if (price < priceRanges.typical.min || price > priceRanges.typical.max) {
                    warningType = 'atypical';
                    const suggestedPrice = price < priceRanges.typical.min ? priceRanges.typical.min : priceRanges.typical.max;
                    suggestion = {
                        message: `Price is outside typical range ($${priceRanges.typical.min}-$${priceRanges.typical.max}). Consider: $${suggestedPrice}`,
                        suggestedPrice: suggestedPrice
                    };
                }
                
                // Show warning and suggestion if needed
                if (suggestion) {
                    showPriceWarning($priceInput, suggestion, warningType);
                }
            }
            
            // Get price ranges based on product and manufacturing types
            function getPriceRanges(productTypes, manufacturingTypes) {
                // Default ranges
                let ranges = {
                    min: 50,
                    max: 5000,
                    typical: { min: 100, max: 1500 }
                };
                
                // Adjust ranges based on product types
                if (productTypes.includes('monument')) {
                    ranges = {
                        min: 200,
                        max: 8000,
                        typical: { min: 500, max: 3000 }
                    };
                } else if (productTypes.includes('headstone')) {
                    ranges = {
                        min: 150,
                        max: 4000,
                        typical: { min: 300, max: 1500 }
                    };
                } else if (productTypes.includes('marker')) {
                    ranges = {
                        min: 75,
                        max: 2000,
                        typical: { min: 150, max: 800 }
                    };
                } else if (productTypes.includes('memorial')) {
                    ranges = {
                        min: 100,
                        max: 6000,
                        typical: { min: 250, max: 2000 }
                    };
                }
                
                // Adjust for manufacturing complexity
                if (manufacturingTypes.includes('custom') || manufacturingTypes.includes('engraving')) {
                    ranges.min *= 1.2;
                    ranges.max *= 1.5;
                    ranges.typical.min *= 1.3;
                    ranges.typical.max *= 1.4;
                }
                
                // Round to nearest 25
                ranges.min = Math.round(ranges.min / 25) * 25;
                ranges.max = Math.round(ranges.max / 25) * 25;
                ranges.typical.min = Math.round(ranges.typical.min / 25) * 25;
                ranges.typical.max = Math.round(ranges.typical.max / 25) * 25;
                
                return ranges;
            }
            
            // Show price warning with suggestion
            function showPriceWarning($priceInput, suggestion, warningType) {
                // Add warning styling to input
                $priceInput.addClass('price-warning');
                
                // Create suggestion popup
                const $inputGroup = $priceInput.closest('.input-group');
                $inputGroup.css('position', 'relative');
                
                const $suggestion = $(`
                    <div class="price-suggestion">
                        <span class="suggestion-text">${suggestion.message}</span>
                        <div class="suggestion-actions">
                            <button type="button" class="btn-accept" data-price="${suggestion.suggestedPrice}">
                                Use $${suggestion.suggestedPrice}
                            </button>
                            <button type="button" class="btn-dismiss">
                                Keep Current
                            </button>
                        </div>
                    </div>
                `);
                
                $inputGroup.append($suggestion);
                
                // Handle suggestion actions
                $suggestion.find('.btn-accept').on('click', function() {
                    const suggestedPrice = $(this).data('price');
                    $priceInput.val(suggestedPrice).trigger('input');
                    $suggestion.remove();
                    $priceInput.removeClass('price-warning');
                });
                
                $suggestion.find('.btn-dismiss').on('click', function() {
                    $suggestion.remove();
                    $priceInput.removeClass('price-warning');
                });
                
                // Auto-hide after 10 seconds
                setTimeout(function() {
                    if ($suggestion.length) {
                        $suggestion.fadeOut(300, function() {
                            $(this).remove();
                            $priceInput.removeClass('price-warning');
                        });
                    }
                }, 10000);
            }
            
            function calculateTotals() {
                // This is now just a wrapper for calculateOrderTotals for backwards compatibility
                calculateOrderTotals();
            }
            // Special etching charge calculator to ensure we get the right value
function calculateEtchingCharge($side) {
    // Try every possible selector for etching charge inputs
    let etchingCharge = 0;
    
    // First check standalone section
    if ($side.find('.side-etching-toggle:checked').length) {
        // Try multiple selector patterns to find the charge input
        const selectors = [
            '.side-etching-options input.side-etching-charge',
            '.side-etching-options input[type="number"]',
            'input[name*="[etching][charge]"]',
            '.side-etching-charge'
        ];
        
        for (const selector of selectors) {
            const input = $side.find(selector);
            if (input.length) {
                const val = parseFloat(input.val()) || 0;
                if (val > 0) {
                    etchingCharge += val;
                    console.log('Found etching charge with selector:', selector, val);
                }
            }
        }
    }
    
    // Then check sandblast & etching section
    if ($side.find('input[name*="sandblast_etching"][name*="etching"]:checked').length) {
        const selectors = [
            'input[name*="sandblast_etching"][name*="etching_charge"]',
            '.side-etching-charge',
            'input[type="number"][name*="etching_charge"]'
        ];
        
        for (const selector of selectors) {
            const input = $side.find(selector);
            if (input.length) {
                const val = parseFloat(input.val()) || 0;
                if (val > 0) {
                    etchingCharge += val;
                    console.log('Found sandblast etching charge with selector:', selector, val);
                }
            }
        }
    }
    
    return etchingCharge;
}

// Calculate additional charges for a row
function calculateAdditionalCharges($row) {
    let total = 0;
    
    // Add S/B CARVING charge if enabled
    if ($row.find('.sb-carving-toggle:checked').length) {
        total += parseFloat($row.find('.sb-carving-charge .form-control').val()) || 0;
    }
    
    // Add ETCHING charge if enabled
    if ($row.find('.etching-toggle:checked').length) {
        total += parseFloat($row.find('.etching-charge').val()) || 0;
    }
    
    // Add DEDO charge if enabled
    if ($row.find('.dedo-toggle:checked').length) {
        total += parseFloat($row.find('.dedo-charge .form-control').val()) || 0;
    }
    
    // Add DOMESTIC ADD ON charge if enabled
    if ($row.find('.domestic-addon-toggle:checked').length) {
        total += parseFloat($row.find('.domestic-addon-charge .form-control').val()) || 0;
    }
    
    // Add DIGITIZATION charge if enabled
    if ($row.find('.digitization-toggle:checked').length) {
        total += parseFloat($row.find('.digitization-charge .form-control').val()) || 0;
    }
    
    // Add all charges from sides
    $row.find('.side-card').each(function() {
        const $side = $(this);
        
        // Add BLANK STONE QTY. PRICE (the main price for this side)
        total += parseFloat($side.find('.side-qty-price').val()) || 0;
        
        // Add all SIDE CHARGES from the summary section at bottom
        total += parseFloat($side.find('.side-sb-charge-display').val()) || 0;
        total += parseFloat($side.find('.side-dedo-charge-display').val()) || 0;
        total += parseFloat($side.find('.side-etching-charge-display').val()) || 0;
        total += parseFloat($side.find('.side-domestic-charge-display').val()) || 0;
        total += parseFloat($side.find('.side-digitization-charge-display').val()) || 0;
        total += parseFloat($side.find('.side-misc-charge').val()) || 0;
    });
    
    return parseFloat(total.toFixed(2));
}

// Update row totals when any input changes
function updateRowTotal($row) {
    const quantity = parseFloat($row.find('.quantity').val()) || 0;
    const price = parseFloat($row.find('.price').val()) || 0;
    const additionalCharges = calculateAdditionalCharges($row);
    
    const subtotal = price * quantity;
    const grandTotal = subtotal + additionalCharges;
    
    // Update the total input field (displayed in the table)
    $row.find('.total').val(subtotal.toFixed(2));
    
    $row.find('.subtotal').text(subtotal.toFixed(2));
    $row.find('.additional-charges').text(additionalCharges.toFixed(2));
    $row.find('.product-total').text(subtotal.toFixed(2));
    $row.find('.grand-total').text(grandTotal.toFixed(2));
    
    // Update hidden input for form submission
    $row.find('input[name$="[total]"], input[name$="[total_amount]"]').val(grandTotal.toFixed(2));
    
    // Recalculate order totals
    calculateOrderTotals();
}

// Calculate order totals
function calculateOrderTotals() {
    let subtotal = 0;
    let additionalCharges = 0;
    
    // Debug info
    console.log('Calculating order totals for ' + $('.product-row').length + ' product rows');
    
    $('.product-row').each(function() {
        const $row = $(this);
        const quantity = parseFloat($row.find('.quantity').val()) || 0;
        const price = parseFloat($row.find('.price').val()) || 0;
        const rowSubtotal = quantity * price;
        
        // Debug info for each row
        console.log('Row calculation - Quantity:', quantity, 'Price:', price, 'Subtotal:', rowSubtotal);
        
        subtotal += rowSubtotal;
        additionalCharges += calculateAdditionalCharges($row); // Recalculate to ensure accuracy
    });
    
    // Debug total subtotal
    console.log('Final subtotal:', subtotal, 'Additional charges:', additionalCharges);
    
    const taxRate = parseFloat($('#taxRate').val()) || 0;
    const tax = (subtotal + additionalCharges) * (taxRate / 100);
    const grandTotal = subtotal + additionalCharges + tax;
    
    // Calculate discount
    const discountRate = parseFloat($('#discountRate').val()) || 0;
    const discountAmount = subtotal * (discountRate / 100);
    const subtotalAfterDiscount = subtotal - discountAmount;
    
    // Recalculate tax and grand total with discount applied
    const taxAfterDiscount = (subtotalAfterDiscount + additionalCharges) * (taxRate / 100);
    const grandTotalAfterDiscount = subtotalAfterDiscount + additionalCharges + taxAfterDiscount;
    
    // Update the display values
    $('#subtotal').text('$' + subtotal.toFixed(2));
    $('#discount').text('-$' + discountAmount.toFixed(2));
    $('#additionalChargesTotal').text('$' + additionalCharges.toFixed(2));
    $('#tax').text('$' + taxAfterDiscount.toFixed(2));
    $('#grandTotal').text('$' + grandTotalAfterDiscount.toFixed(2));
    
    // Also update any hidden fields for form submission
    $('input[name="subtotal"]').val(subtotal.toFixed(2));
    $('input[name="additional_charges_total"]').val(additionalCharges.toFixed(2));
    $('input[name="discount_rate"]').val(discountRate.toFixed(2));
    $('input[name="discount_amount"]').val(discountAmount.toFixed(2));
    $('input[name="tax_rate"]').val(taxRate.toFixed(2));
    $('input[name="tax_amount"]').val(taxAfterDiscount.toFixed(2));
    $('input[name="grand_total"]').val(grandTotalAfterDiscount.toFixed(2));
}

// Update totals when any charge or price changes
$(document).on('input', '.sb-charge, .etching-charge, .dedo-charge, .domestic-charge, .digitization-charge, .side-charge, .side-etching-charge, .side-sb-charge, .side-domestic-charge, .side-misc-charge, .side-unit-price, .side-qty, .side-qty-price, .side-sb-charge-display, .side-dedo-charge-display, .side-etching-charge-display, .side-domestic-charge-display, .side-digitization-charge-display, .quantity, .price', function() {
    updateRowTotal($(this).closest('tr'));
});

// Immediate update when etching charge is entered (both standalone and in card section)
$(document).on('change input', 'input[name$="[etching][charge]"], input[name$="[sandblast_etching][etching_charge]"]', function() {
    updateRowTotal($(this).closest('tr'));
});

// Recalculate when radio buttons or checkboxes in etching sections are changed
$(document).on('change', 'input[name*="etching"][type="radio"], input[name*="etching"][type="checkbox"]', function() {
    updateRowTotal($(this).closest('tr'));
});

// Recalculate totals when tax rate changes
$(document).on('input', '#taxRate', function() {
    // Ensure tax rate is between 0 and 100
    let taxRate = parseFloat($(this).val());
    if (isNaN(taxRate) || taxRate < 0) {
        $(this).val('0.00');
    } else if (taxRate > 100) {
        $(this).val('100.00');
    }
    calculateOrderTotals();
});

// Update toggle handlers to show/hide charge fields
$(document).on('change', '.dedo-toggle', function() {
    const $field = $(this).closest('.form-check').find('.dedo-charge');
    $field.toggle(this.checked);
    if (!this.checked) {
        $field.find('input[type="text"]').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

$(document).on('change', '.domestic-addon-toggle', function() {
    const $formCheck = $(this).closest('.form-check');
    const $fields = $formCheck.find('.domestic-addon-fields');
    $fields.toggle(this.checked);
    if (!this.checked) {
        $fields.find('input[type="text"], .domestic-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

$(document).on('change', '.digitization-toggle', function() {
    const $formCheck = $(this).closest('.form-check');
    const $chargeField = $formCheck.find('.digitization-charge');
    const $textField = $formCheck.find('.digitization-field');
    $chargeField.toggle(this.checked);
    $textField.toggle(this.checked);
    if (!this.checked) {
        $chargeField.val('');
        $textField.find('input').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

// Initialize all toggles on page load
$(document).ready(function() {
    // Initialize DEDO toggles
    $('.dedo-toggle').each(function() {
        const $row = $(this).closest('tr');
        const $chargeField = $row.find('.dedo-charge');
        $chargeField.toggle($(this).is(':checked'));
    });
    
    // Initialize DOMESTIC ADD ON toggles
    $('.domestic-addon-toggle').each(function() {
        const $formCheck = $(this).closest('.form-check');
        $formCheck.find('.domestic-addon-fields').toggle($(this).is(':checked'));
    });
    
    // Initialize DIGITIZATION toggles
    $('.digitization-toggle').each(function() {
        const $formCheck = $(this).closest('.form-check');
        const isChecked = $(this).is(':checked');
        $formCheck.find('.digitization-charge, .digitization-field').toggle(isChecked);
    });

    // Initialize manufacturing text box
    $('.manufacturing-text-box').each(function() {
        const $row = $(this).closest('tr');
        const $manufacturingType = $row.find('.manufacturing-type:checked').val();
        if ($manufacturingType) {
            $(this).show();
        }
    });
});

$(document).on('change', '.etching-toggle', function() {
    const $options = $(this).closest('.form-check').find('.etching-options');
    $options.toggle(this.checked);
    if (!this.checked) {
        $options.find('input[type="radio"]').prop('checked', false);
        $options.find('.etching-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

// Update existing toggle handlers
$(document).on('change', '.sb-carving-toggle', function() {
    const $options = $(this).closest('.form-check').find('.sb-carving-options');
    $options.toggle(this.checked);
    if (!this.checked) {
        $options.find('input[type="radio"]').prop('checked', false);
        $options.find('.sb-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

$(document).on('change', '.etching-toggle', function() {
    const $options = $(this).closest('.form-check').find('.etching-options');
    $options.toggle(this.checked);
    if (!this.checked) {
        $options.find('input[type="radio"]').prop('checked', false);
        $options.find('.etching-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

$(document).on('change', '.domestic-addon-toggle', function() {
    const $fields = $(this).closest('.form-check').find('.domestic-addon-fields');
    $fields.toggle(this.checked);
    if (!this.checked) {
        $fields.find('input[type="text"], .domestic-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});
            
            // Update submit button text based on form type
            $('#formType').change(function() {
                const type = $(this).val();
                const submitText = type === 'Order' ? 'Place Order' : 'Request Quote';
                $('#submitBtnText').text(submitText);
            });
            
            // Initialize form type
            $('#formType').trigger('change');
            
            // Save as draft
            $('#saveDraft').click(function() {
                // Validate form
                if (validateForm()) {
                    // Show saving state
                    const $btn = $(this);
                    const originalText = $btn.html();
                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
                    
                    // Simulate save delay
                    setTimeout(function() {
                        // Reset button
                        $btn.prop('disabled', false).html(originalText);
                        
                        // Show success message
                        const alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                    '<i class="bi bi-check-circle-fill me-2"></i> Draft saved successfully!' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                    '</div>';
                        
                        $('.form-container').prepend(alert);
                        
                        // Auto-hide alert after 3 seconds
                        setTimeout(function() {
                            $('.alert').alert('close');
                        }, 3000);
                    }, 1000);
                }
            });
            
            // Form submission
            $('#orderQuoteForm').on('submit', function(e) {
                if (validateForm()) {
                    // Show loading state
                    const submitBtn = $(this).find('button[type="submit"]');
                    const submitBtnText = submitBtn.find('#submitBtnText');
                    const spinner = submitBtn.find('#submitSpinner');
                    
                    submitBtn.prop('disabled', true);
                    submitBtnText.addClass('d-none');
                    spinner.removeClass('d-none');
                    
                    // Allow the form to submit normally
                    return true;
                } else {
                    // Prevent submission if validation fails
                    e.preventDefault();
                    return false;
                }
            });
            
            // Form validation function
            function validateForm() {
                let isValid = true;
                $('.is-invalid').removeClass('is-invalid');

                // Validate required fields
                $('[required]').each(function() {
                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        isValid = false;
                    }
                });

                // Validate at least one product
                if ($('.product-row').length === 0) {
                    alert('Please add at least one product');
                    return false;
                }

                // Validate each product row
                $('.product-row').each(function() {
                    const $row = $(this);
                    
                    // Validate product name
                    if (!$row.find('.product-name').val()) {
                        $row.find('.product-name').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate quantity
                    if (!$row.find('.quantity').val() || parseFloat($row.find('.quantity').val()) <= 0) {
                        $row.find('.quantity').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate price
                    if (!$row.find('.price').val() || parseFloat($row.find('.price').val()) < 0) {
                        $row.find('.price').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate granite color
                    if (!$row.find('.granite-color').val()) {
                        $row.find('.granite-color').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate at least one product type is selected
                    if ($row.find('.product-type:checked').length === 0) {
                        $row.find('.product-type-container').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate manufacturing type is selected
                    if (!$row.find('.manufacturing-type:checked').length) {
                        $row.find('.manufacturing-type-container').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate manufacturing option if manufacturing type is selected
                    const $manufacturingType = $row.find('.manufacturing-type:checked').val();
                    if ($manufacturingType && !$row.find('.manufacturing-options select').val()) {
                        $row.find('.manufacturing-options').addClass('is-invalid');
                        isValid = false;
                    }
                });

                return isValid;
            }
            
            // Handle remove button clicks
            $(document).on('click', '.remove-product', function() {
                if ($('.product-row').length > 1) {
                    if (confirm('Are you sure you want to remove this product?')) {
                        $(this).closest('tr').remove();
                        updateRowNumbers();
                        calculateTotals();
                    }
                } else {
                    alert('You need to have at least one product in the order.');
                }
            });
            
            // Initialize calculations
            calculateTotals();
            
            // Handle S/B carving toggle
            $(document).on('change', '.sb-carving-toggle', function() {
                const $sbOptions = $(this).closest('.form-check').find('.sb-carving-options');
                $sbOptions.toggle(this.checked);
                
                // Clear selections if unchecked
                if (!this.checked) {
                    $sbOptions.find('input[type="radio"]').prop('checked', false);
                    $sbOptions.find('.sb-charge').val('');
                    updateRowTotal($(this).closest('tr'));
                }
            });
            
            // Initialize S/B carving toggles on page load
            $('.sb-carving-toggle').each(function() {
                if (this.checked) {
                    $(this).closest('.form-check').find('.sb-carving-options').show();
                }
            });
            
            // Handle etching toggle
            $(document).on('change', '.etching-toggle', function() {
                const $etchingOptions = $(this).closest('.form-check').find('.etching-options');
                $etchingOptions.toggle(this.checked);
                
                // Clear selections if unchecked
                if (!this.checked) {
                    $etchingOptions.find('input[type="radio"]').prop('checked', false);
                    $etchingOptions.find('input[type="checkbox"]').prop('checked', false);
                }
            });
            
            // Initialize etching toggles on page load
            $('.etching-toggle').each(function() {
                if (this.checked) {
                    $(this).closest('.form-check').find('.etching-options').show();
                }
            });
            
            // Handle Add Side button click
            $(document).on('click', '.add-side', function() {
                const productIndex = $(this).data('product-index');
                const $sidesContainer = $(this).siblings('.sides-container');
                const sideIndex = $sidesContainer.children('.side-card').length;
                
                const newSideHtml = getSideTemplate(productIndex, sideIndex);
                $sidesContainer.append(newSideHtml);
                
                // Update side count display
                updateSideCount($sidesContainer);
            });
            
            // Side template function to generate HTML for a new side
            function getSideTemplate(productIndex, sideIndex) {
                return `
                    <div class="side-card card mb-3 shadow-sm" style="border: 1px solid #dee2e6; border-radius: 8px;">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 2px solid #dee2e6;">
                            <span class="fw-bold" style="font-size: 15px; color: #495057;">Side ${sideIndex + 1}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-side" style="padding: 4px 10px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <div class="card-body p-3">
                            
                            <!-- BLANK STONE PRICING ROW -->
                            <div class="mb-3 p-2" style="background: #fff3e0; border-radius: 6px; border: 2px solid #ffb74d;">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold mb-1" style="color: #e65100;">BLANK STONE UNIT PRICE</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-unit-price" name="products[${productIndex}][sides][${sideIndex}][blank_stone_unit_price]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold mb-1" style="color: #e65100;">QUANTITY</label>
                                        <input type="number" class="form-control form-control-sm side-qty" name="products[${productIndex}][sides][${sideIndex}][quantity]" min="1" value="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold mb-1" style="color: #e65100;">BLANK STONE QTY. PRICE</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control side-qty-price" name="products[${productIndex}][sides][${sideIndex}][blank_stone_qty_price]" readonly placeholder="0.00" style="background: #fff8e1;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SIDE NOTES -->
                            <div class="mb-3 p-2" style="background: #e8f5e9; border-radius: 6px; border-left: 4px solid #4caf50;">
                                <label class="form-label small fw-bold mb-1" style="color: #2e7d32;">SIDE NOTES</label>
                                <textarea class="form-control form-control-sm side-notes" name="products[${productIndex}][sides][${sideIndex}][notes]" rows="2" style="border: 1px solid #c8e6c9;"></textarea>
                            </div>
                            
                            <!-- WORK TYPE - Horizontal Checkboxes -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold mb-2" style="color: #1565c0;">WORK TYPE</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <div class="form-check form-check-inline m-0" style="background: #f5f5f5; padding: 8px 12px; border-radius: 6px; border: 1px solid #e0e0e0;">
                                        <input class="form-check-input" type="checkbox" id="side_blank_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][blank]" value="1">
                                        <label class="form-check-label" for="side_blank_${productIndex}_${sideIndex}">BLANK</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0" style="background: #fff3e0; padding: 8px 12px; border-radius: 6px; border: 1px solid #ffe0b2;">
                                        <input class="form-check-input" type="checkbox" id="side_sandblast_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][sandblast]" value="1">
                                        <label class="form-check-label" for="side_sandblast_${productIndex}_${sideIndex}">SANDBLAST</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0" style="background: #e3f2fd; padding: 8px 12px; border-radius: 6px; border: 1px solid #bbdefb;">
                                        <input class="form-check-input side-etching-toggle" type="checkbox" id="side_etching_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][etching][enabled]" value="1">
                                        <label class="form-check-label" for="side_etching_${productIndex}_${sideIndex}">ETCHING</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ETCHING OPTIONS (shown when ETCHING is checked) -->
                            <div class="side-etching-options mb-3 p-2" style="display: none; background: #e3f2fd; border-radius: 6px; border-left: 4px solid #1976d2;">
                                <label class="form-label small fw-bold mb-2" style="color: #1565c0;">ETCHING TYPE</label>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][etching][type]" id="side_etching_bw_${productIndex}_${sideIndex}" value="B&W">
                                        <label class="form-check-label" for="side_etching_bw_${productIndex}_${sideIndex}">B&W</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][etching][type]" id="side_etching_color_${productIndex}_${sideIndex}" value="COLOR">
                                        <label class="form-check-label" for="side_etching_color_${productIndex}_${sideIndex}">COLOR</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][etching][type]" id="side_etching_hand_${productIndex}_${sideIndex}" value="HAND">
                                        <label class="form-check-label" for="side_etching_hand_${productIndex}_${sideIndex}">HAND</label>
                                    </div>
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][etching][type]" id="side_etching_laser_${productIndex}_${sideIndex}" value="LASER">
                                        <label class="form-check-label" for="side_etching_laser_${productIndex}_${sideIndex}">LASER</label>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="side_etching_with_order_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][etching][with_order]" value="1">
                                            <label class="form-check-label" for="side_etching_with_order_${productIndex}_${sideIndex}">WITH ORDER</label>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group input-group-sm" style="width: 120px;">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-charge side-etching-charge" name="products[${productIndex}][sides][${sideIndex}][etching][charge]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DRAFTING SECTION -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold mb-2" style="color: #6a1b9a;">DRAFTING</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="p-2" style="background: #f3e5f5; border-radius: 6px;">
                                            <small class="text-muted d-block mb-1">Shape Drawing</small>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="side_shape_dealer_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][shape_drawing_dealer]" value="1">
                                                <label class="form-check-label small" for="side_shape_dealer_${productIndex}_${sideIndex}">DEALER</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="side_shape_company_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][shape_drawing_company]" value="1">
                                                <label class="form-check-label small" for="side_shape_company_${productIndex}_${sideIndex}">COMPANY</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2" style="background: #fce4ec; border-radius: 6px;">
                                            <small class="text-muted d-block mb-1">Sandblast Drafting</small>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="side_company_drafting_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][company_drafting]" value="1">
                                                <label class="form-check-label small" for="side_company_drafting_${productIndex}_${sideIndex}">COMPANY</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="side_customer_drafting_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][customer_drafting]" value="1">
                                                <label class="form-check-label small" for="side_customer_drafting_${productIndex}_${sideIndex}">CUSTOMER</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <div class="form-check" style="background: #f5f5f5; padding: 8px 12px; border-radius: 6px;">
                                            <input class="form-check-input" type="checkbox" id="side_customer_stencil_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][customer_stencil]" value="1">
                                            <label class="form-check-label small" for="side_customer_stencil_${productIndex}_${sideIndex}">CUSTOMER STENCIL</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check" style="background: #f5f5f5; padding: 8px 12px; border-radius: 6px;">
                                            <input class="form-check-input" type="checkbox" id="side_sandblast_with_order_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][sandblast_with_order]" value="1">
                                            <label class="form-check-label small" for="side_sandblast_with_order_${productIndex}_${sideIndex}">WITH ORDER</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ADDITIONAL OPTIONS - Simplified Grid -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold mb-2" style="color: #00695c;">ADDITIONAL OPTIONS</label>
                                <div class="row g-2">
                                    <!-- S/B CARVING -->
                                    <div class="col-md-6">
                                        <div class="p-2" style="background: #e0f2f1; border-radius: 6px;">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input side-sb-toggle" type="checkbox" id="side_sb_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sb_carving][enabled]">
                                                <label class="form-check-label fw-medium" for="side_sb_${productIndex}_${sideIndex}">S/B CARVING</label>
                                            </div>
                                            <div class="side-sb-options ps-3" style="display: none;">
                                                <div class="d-flex flex-wrap gap-1">
                                                    <span class="badge bg-light text-dark border">
                                                        <input class="form-check-input me-1" type="radio" name="products[${productIndex}][sides][${sideIndex}][sb_carving][type]" id="side_sb_flat_${productIndex}_${sideIndex}" value="FLAT">
                                                        <label class="form-check-label small" for="side_sb_flat_${productIndex}_${sideIndex}">FLAT</label>
                                                    </span>
                                                    <span class="badge bg-light text-dark border">
                                                        <input class="form-check-input me-1" type="radio" name="products[${productIndex}][sides][${sideIndex}][sb_carving][type]" id="side_sb_shaped_${productIndex}_${sideIndex}" value="SHAPED">
                                                        <label class="form-check-label small" for="side_sb_shaped_${productIndex}_${sideIndex}">SHAPED</label>
                                                    </span>
                                                    <span class="badge bg-light text-dark border">
                                                        <input class="form-check-input me-1" type="radio" name="products[${productIndex}][sides][${sideIndex}][sb_carving][option]" id="side_sb_lettering_${productIndex}_${sideIndex}" value="LETTERING">
                                                        <label class="form-check-label small" for="side_sb_lettering_${productIndex}_${sideIndex}">LETTERING</label>
                                                    </span>
                                                    <span class="badge bg-light text-dark border">
                                                        <input class="form-check-input me-1" type="radio" name="products[${productIndex}][sides][${sideIndex}][sb_carving][option]" id="side_sb_rose_${productIndex}_${sideIndex}" value="ROSE">
                                                        <label class="form-check-label small" for="side_sb_rose_${productIndex}_${sideIndex}">ROSE</label>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- DEDO -->
                                    <div class="col-md-6">
                                        <div class="p-2" style="background: #fff8e1; border-radius: 6px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="side_dedo_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][dedo][enabled]">
                                                <label class="form-check-label fw-medium" for="side_dedo_${productIndex}_${sideIndex}">RECESS & MOUNT DEDO</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-2 mt-2">
                                    <!-- DOMESTIC ADD ON -->
                                    <div class="col-md-6">
                                        <div class="p-2" style="background: #e8eaf6; border-radius: 6px;">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input side-domestic-toggle" type="checkbox" id="side_domestic_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][domestic_addon][enabled]">
                                                <label class="form-check-label fw-medium" for="side_domestic_${productIndex}_${sideIndex}">DOMESTIC ADD ON</label>
                                            </div>
                                            <div class="side-domestic-fields ps-3" style="display: none;">
                                                <div class="row g-1">
                                                    <div class="col-6">
                                                        <input type="text" class="form-control form-control-sm" name="products[${productIndex}][sides][${sideIndex}][domestic_addon][field1]" placeholder="(1)">
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="text" class="form-control form-control-sm" name="products[${productIndex}][sides][${sideIndex}][domestic_addon][field2]" placeholder="(2)">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- DIGITIZATION -->
                                    <div class="col-md-6">
                                        <div class="p-2" style="background: #fbe9e7; border-radius: 6px;">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input side-digitization-toggle" type="checkbox" id="side_digitization_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][digitization][enabled]">
                                                <label class="form-check-label fw-medium" for="side_digitization_${productIndex}_${sideIndex}">DIGITIZATION</label>
                                            </div>
                                            <div class="side-digitization-charge ps-3" style="display: none;">
                                                <input type="text" class="form-control form-control-sm" name="products[${productIndex}][sides][${sideIndex}][digitization][details]" placeholder="Details">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SIDE CHARGES SUMMARY -->
                            <div class="p-2" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 6px; border: 2px solid #1976d2;">
                                <label class="form-label small fw-bold mb-2" style="color: #0d47a1;">SIDE CHARGES</label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small mb-0">S/B CHARGES</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-charge side-sb-charge-display" name="products[${productIndex}][sides][${sideIndex}][sb_charges]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-0">RECESS & MOUNT DEDO</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-charge side-dedo-charge-display" name="products[${productIndex}][sides][${sideIndex}][dedo_charges]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-0">ETCHING CHARGES</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-charge side-etching-charge-display" name="products[${productIndex}][sides][${sideIndex}][etching_charges]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-md-4">
                                        <label class="form-label small mb-0">DOMESTIC ADD ON</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-charge side-domestic-charge-display" name="products[${productIndex}][sides][${sideIndex}][domestic_addon_charges]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-0">DIGITIZATION</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-charge side-digitization-charge-display" name="products[${productIndex}][sides][${sideIndex}][digitization_charges]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-0">MISC CHARGES</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-charge side-misc-charge" name="products[${productIndex}][sides][${sideIndex}][misc_charge]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                `;
            }
            
            // Handle remove side button click
            $(document).on('click', '.remove-side', function() {
                const $sideCard = $(this).closest('.side-card');
                const $sidesContainer = $sideCard.closest('.sides-container');
                $sideCard.remove();
                updateSideCount($sidesContainer);
                
                // Renumber remaining sides
                $sidesContainer.find('.side-card').each(function(index) {
                    $(this).find('h6').text(`Side ${index + 1}`);
                });
            });
            
            // Update side count badge
            function updateSideCount($sidesContainer) {
                const count = $sidesContainer.children('.side-card').length;
                $sidesContainer.siblings('.d-flex').find('.side-count')
                    .text(count === 1 ? '1 Side' : `${count} Sides`)
                    .toggleClass('bg-secondary', count === 0)
                    .toggleClass('bg-primary', count > 0);
                
                // Update the product row total when sides are added or removed
                updateRowTotal($sidesContainer.closest('tr'));
            }
            
            // Handle S/B Carving toggle
            $(document).on('change', '.sb-carving-toggle', function() {
                const $options = $(this).closest('.form-check').find('.sb-carving-options');
                $options.toggle(this.checked);
                if (!this.checked) {
                    $options.find('input[type="radio"]').prop('checked', false);
                }
            });

            // Handle ETCHING toggle
            $(document).on('change', '.etching-toggle', function() {
                const $options = $(this).closest('.form-check').find('.etching-options');
                $options.toggle(this.checked);
                if (!this.checked) {
                    $options.find('input[type="radio"]').prop('checked', false);
                }
            });

            // Handle DOMESTIC ADD ON toggle
            $(document).on('change', '.domestic-addon-toggle', function() {
                const $fields = $(this).closest('.form-check').find('.domestic-addon-fields');
                $fields.toggle(this.checked);
                if (!this.checked) {
                    $fields.find('input[type="text"]').val('');
                }
            });
            
            // Handle side-specific toggles
            
            // Side S/B CARVING toggle
            $(document).on('change', '.side-sb-toggle', function() {
                const $options = $(this).closest('.form-check').find('.side-sb-options');
                $options.toggle(this.checked);
                if (!this.checked) {
                    // Clear values when unchecked
                    $options.find('input[type="radio"]').prop('checked', false);
                    $options.find('.side-sb-charge').val('');
                }
                // Update totals when toggling
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side ETCHING toggle - for the new ETCHING section
            $(document).on('change', '.side-etching-toggle', function() {
                const $options = $(this).closest('.form-check').find('.side-etching-options');
                $options.toggle(this.checked);
                if (!this.checked) {
                    // Clear values when unchecked
                    $options.find('input[type="radio"]').prop('checked', false);
                    $options.find('.side-etching-charge').val('');
                }
                // Update totals when toggling
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side DEDO toggle
            $(document).on('change', '.side-dedo-toggle', function() {
                const $options = $(this).closest('.form-check').find('.side-dedo-charge');
                $options.toggle(this.checked);
                if (!this.checked) {
                    $options.find('input').val('');
                }
                // Update totals when toggling
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side ETCHING toggle
            $(document).on('change', '.side-etching-toggle', function() {
                const $chargeField = $(this).closest('.form-check').find('.side-etching-charge');
                $chargeField.toggle(this.checked);
                if (!this.checked) {
                    $chargeField.find('input').val('');
                }
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side DEDO toggle
            $(document).on('change', '.side-dedo-toggle', function() {
                const $chargeField = $(this).closest('.form-check').find('.side-dedo-charge');
                $chargeField.toggle(this.checked);
                if (!this.checked) {
                    $chargeField.find('input').val('');
                }
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side DOMESTIC ADD ON toggle
            $(document).on('change', '.side-domestic-toggle', function() {
                const $fields = $(this).closest('.form-check').find('.side-domestic-fields');
                $fields.toggle(this.checked);
                if (!this.checked) {
                    $fields.find('input[type="text"]').val('');
                    $fields.find('.side-domestic-charge').val('');
                }
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side DIGITIZATION toggle
            $(document).on('change', '.side-digitization-toggle', function() {
                const $fields = $(this).closest('.form-check').find('.side-digitization-fields');
                $fields.toggle(this.checked);
                if (!this.checked) {
                    $fields.find('input[type="text"]').val('');
                    $fields.find('.side-digitization-charge').val('');
                }
                updateRowTotal($(this).closest('tr'));
            });
            
            // Initialize side charge inputs to update totals on change
            $(document).on('input', '.side-charge', function() {
                updateRowTotal($(this).closest('tr'));
            });
            
            // Calculate BLANK STONE QTY. PRICE (unit price × quantity)
            $(document).on('input', '.side-unit-price, .side-qty', function() {
                const $sideCard = $(this).closest('.side-card');
                const unitPrice = parseFloat($sideCard.find('.side-unit-price').val()) || 0;
                const qty = parseInt($sideCard.find('.side-qty').val()) || 1;
                const qtyPrice = (unitPrice * qty).toFixed(2);
                $sideCard.find('.side-qty-price').val(qtyPrice);
                // Update row totals
                updateRowTotal($(this).closest('tr'));
            });

            // Handle Mark Crate checkbox toggle
            $('#markCrate').change(function() {
                console.log('Mark Crate checkbox changed:', this.checked);
                $('.mark-crate-details').toggle(this.checked);
                if (!this.checked) {
                    $('#markCrateDetails').val('');
                }
            });
            
            // Also handle it with direct event binding in case of timing issues
            $(document).on('change', '#markCrate', function() {
                $('.mark-crate-details').toggle(this.checked);
                if (!this.checked) {
                    $('#markCrateDetails').val('');
                }
            });
            
            // Initialize all toggle behaviors
            function initializeToggles() {
                // Initialize Mark Crate details field visibility
                $('.mark-crate-details').toggle($('#markCrate').is(':checked'));
                
                // All product-level charge toggles have been removed in favor of side-level toggles
                
                // Initialize side level toggles
                $('.side-sb-toggle').each(function() {
                    const $options = $(this).closest('.form-check').find('.side-sb-options');
                    $options.toggle(this.checked);
                });
                
                $('.side-etching-toggle').each(function() {
                    const $options = $(this).closest('.form-check').find('.side-etching-options');
                    $options.toggle(this.checked);
                });
                
                $('.side-dedo-toggle').each(function() {
                    const $chargeField = $(this).closest('.form-check').find('.side-dedo-charge');
                    $chargeField.toggle(this.checked);
                });
                
                $('.side-domestic-toggle').each(function() {
                    const $fields = $(this).closest('.form-check').find('.side-domestic-fields');
                    $fields.toggle(this.checked);
                });
                
                $('.side-digitization-toggle').each(function() {
                    const $chargeField = $(this).closest('.form-check').find('.side-digitization-charge');
                    const $textField = $(this).closest('.form-check').find('.side-digitization-text');
                    $chargeField.toggle(this.checked);
                    $textField.toggle(this.checked);
                });
                
                // Initialize manufacturing options display
                $('.manufacturing-type:checked').each(function() {
                    const $row = $(this).closest('tr');
                    handleManufacturingSelection($row, this.value);
                });
                
                // Initialize other product type text fields
                $('.product-type[value="Other"]:checked').each(function() {
                    $(this).closest('.form-check').siblings('.other-product-text').show();
                });
            }

            // Initialize toggles and calculate totals on page load
            $(document).ready(function() {
                // Force recalculate all totals on page load
                $('.product-row').each(function() {
                    updateRowTotal($(this));
                });
                calculateOrderTotals();
                
                // Set up special binding for etching charge inputs to ensure they update totals
                $(document).on('input change keyup', '.side-etching-charge', function() {
                    console.log('Etching charge updated to: ' + $(this).val());
                    updateRowTotal($(this).closest('tr'));
                });
                
                // File upload preview and validation
                $('#fileUploads').on('change', function(e) {
                    const files = e.target.files;
                    const $preview = $('#uploadPreview');
                    $preview.empty();
                    
                    // Check total file size (max 10MB)
                    let totalSize = 0;
                    let invalidFiles = [];
                    const allowedTypes = [
                        'image/jpeg', 'image/png', 'image/gif', 
                        'application/pdf', 
                        'application/vnd.ms-excel', 
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ];
                    
                    // Validate files
                    Array.from(files).forEach(file => {
                        totalSize += file.size;
                        if (!allowedTypes.includes(file.type)) {
                            invalidFiles.push(file.name);
                        }
                    });
                    
                    // Show warnings if needed
                    if (totalSize > 10 * 1024 * 1024) {
                        alert('Total file size exceeds 10MB limit. Please reduce the number or size of files.');
                        $(this).val(''); // Clear the input
                        return;
                    }
                    
                    if (invalidFiles.length > 0) {
                        alert('The following files have invalid types: ' + invalidFiles.join(', ') + '\nOnly JPG, PNG, GIF, PDF, XLS and XLSX files are allowed.');
                        $(this).val(''); // Clear the input
                        return;
                    }
                    
                    // Show previews for valid files
                    Array.from(files).forEach(file => {
                        const $item = $('<div class="border rounded p-2 text-center" style="width: 100px;"></div>');
                        
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                $item.append(`<img src="${e.target.result}" class="img-fluid mb-1" style="max-height: 60px;">`)
                                     .append(`<div class="small text-truncate">${file.name}</div>`);
                            };
                            reader.readAsDataURL(file);
                        } else {
                            let icon = 'bi-file-earmark';
                            if (file.type === 'application/pdf') icon = 'bi-file-earmark-pdf';
                            if (file.type.includes('excel') || file.type.includes('spreadsheet')) icon = 'bi-file-earmark-excel';
                            
                            $item.append(`<i class="bi ${icon} fs-2"></i>`)
                                 .append(`<div class="small text-truncate">${file.name}</div>`);
                        }
                        
                        $preview.append($item);
                    });
                });
                
                // Initialize all toggle behaviors
                initializeToggles();
            });

            // Handle "Other" product type selection
            $(document).on('change', '.product-type[value="Other"]', function() {
                const $textField = $(this).closest('.form-check').siblings('.other-product-text');
                $textField.toggle(this.checked);
                if (!this.checked) {
                    $textField.find('input').val('');
                }
            });

            // Handle digitization toggle for showing/hiding both charge and text fields
            $(document).on('change', '.side-digitization-toggle', function() {
                const $formCheck = $(this).closest('.form-check');
                const $chargeField = $formCheck.find('.side-digitization-charge');
                const $textField = $formCheck.find('.side-digitization-text');
                $chargeField.toggle(this.checked);
                $textField.toggle(this.checked);
                if (!this.checked) {
                    $chargeField.find('input').val('');
                    $textField.find('input').val('');
                }
                // Update totals when digitization is toggled
                updateRowTotal($(this).closest('tr'));
            });
        });
    </script>
    <script>
$(document).ready(function() {
    // Save as Draft functionality
    $('#saveDraftBtn').click(function() {
        // Create a hidden form to submit the data
        var $form = $('<form>', {
            'action': 'forms/order-draft-generator.php', // Path relative to current directory
            'method': 'post',
            'target': '_blank'
        });
        
        // Debug message to console
        console.log('Cloning form data for PDF generation');
            
        // Clone the current form data
        $('#orderQuoteForm').find('input, select, textarea').each(function() {
            var $input = $(this);
            var name = $input.attr('name');
            var value = $input.val();
            
            // Skip inputs without a name
            if (!name) return;
            
            console.log('Processing form field: ' + name);
            
            // Handle checkboxes and radio buttons
            if ($input.is(':checkbox') || $input.is(':radio')) {
                if ($input.is(':checked')) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: name,
                        value: value
                    }).appendTo($form);
                    console.log('Added checked field: ' + name + ' = ' + value);
                }
            } else {
                $('<input>').attr({
                    type: 'hidden',
                    name: name,
                    value: value
                }).appendTo($form);
                console.log('Added regular field: ' + name + ' = ' + value);
            }
            
            // Special handling for payment_terms radio buttons
            if (name === 'payment_terms' && $input.is(':checked')) {
                console.log('Payment term selected: ' + value);
            }
        });
        
        // Special handling for sides information - needs to capture ALL details
        $('.product-row').each(function(productIndex) {
            // First add the product name/code
            var productName = $(this).find('.product-name').val() || '';
            $('<input>').attr({
                type: 'hidden',
                name: 'products[' + productIndex + '][name]',
                value: productName
            }).appendTo($form);
            console.log('Added product name: ' + productName);
            
            // Add manufacturing options
            var manufacturingType = $(this).find('.manufacturing-type:checked').val() || '';
            $('<input>').attr({
                type: 'hidden',
                name: 'products[' + productIndex + '][manufacturing_details]',
                value: manufacturingType
            }).appendTo($form);
            
            // Add in-house, outsource, inventory options
            var inHouse = $(this).find('input[name^="products[' + productIndex + '][in_house]"]').is(':checked') ? '1' : '0';
            var outsource = $(this).find('input[name^="products[' + productIndex + '][outsource]"]').is(':checked') ? '1' : '0';
            var inventory = $(this).find('input[name^="products[' + productIndex + '][inventory]"]').is(':checked') ? '1' : '0';
            
            $('<input>').attr({
                type: 'hidden',
                name: 'products[' + productIndex + '][in_house]',
                value: inHouse
            }).appendTo($form);
            
            $('<input>').attr({
                type: 'hidden',
                name: 'products[' + productIndex + '][outsource]',
                value: outsource
            }).appendTo($form);
            
            $('<input>').attr({
                type: 'hidden',
                name: 'products[' + productIndex + '][inventory]',
                value: inventory
            }).appendTo($form);
            
            // Process each product's sides with all details
            var $product = $(this);
            $product.find('.side-card').each(function(sideIndex) {
                var $side = $(this);
                var sideData = {};
                
                // Get side notes
                sideData.notes = $side.find('.side-notes').val() || '';
                
                // Get all checked options for this side
                $side.find('input[type="checkbox"]:checked').each(function() {
                    var optionId = $(this).attr('id');
                    sideData[optionId] = '1';
                    
                    // Check for associated charge fields
                    var $parent = $(this).closest('.form-check');
                    
                    // S/B CARVING charges
                    if (optionId.includes('side_sb_')) {
                        sideData.sb_carving_charge = $parent.find('input[type="number"]').val() || '0';
                    }
                    
                    // ETCHING charges
                    if (optionId.includes('side_etching_')) {
                        sideData.etching_charge = $parent.find('input[type="number"]').val() || '0';
                    }
                    
                    // DEDO charges
                    if (optionId.includes('side_dedo_')) {
                        sideData.dedo_charge = $parent.find('input[type="number"]').val() || '0';
                    }
                    
                    // Digitization charges and details
                    if (optionId.includes('side_digitization_')) {
                        sideData.digitization_charge = $parent.find('input[type="number"]').val() || '0';
                        sideData.digitization_details = $parent.find('input[type="text"]').val() || '';
                    }
                });
                
                // Add all side data as JSON to preserve structure
                $('<input>').attr({
                    type: 'hidden',
                    name: 'products[' + productIndex + '][sides][' + sideIndex + '][data]',
                    value: JSON.stringify(sideData)
                }).appendTo($form);
                
                console.log('Added complete side data for side ' + (sideIndex+1) + ' of product ' + (productIndex+1));
            });
        });
        
        // Add special instructions/notes
        var specialInstructions = $('#specialInstructions').val() || '';
        $('<input>').attr({
            type: 'hidden',
            name: 'special_instructions',
            value: specialInstructions
        }).appendTo($form);
        
        // Make sure sales person is properly included
        var salesPerson = $('#salesRep').val() || 'N/A';
        $('<input>').attr({
            type: 'hidden',
            name: 'salesperson',  // Using the field name expected by the PDF generator
            value: salesPerson
        }).appendTo($form);
        console.log('Added sales person: ' + salesPerson);
        
        // Ensure we have the subtotal, additional charges total, tax, and grand total
        // Calculate or get these values from the form
        var subtotal = $('#subtotal').val() || $('#subtotalDisplay').text().replace('$', '');
        var additionalChargesTotal = $('#additionalChargesTotal').val() || $('#additionalChargesTotalDisplay').text().replace('$', '');
        var tax = $('#tax').val() || $('#taxDisplay').text().replace('$', '');
        var grandTotal = $('#grandTotal').val() || $('#grandTotalDisplay').text().replace('$', '');
        
        // Add these totals to the form submission
        $('<input>').attr({
            type: 'hidden',
            name: 'subtotal',
            value: subtotal
        }).appendTo($form);
        
        $('<input>').attr({
            type: 'hidden',
            name: 'additional_charges_total',
            value: additionalChargesTotal
        }).appendTo($form);
        
        $('<input>').attr({
            type: 'hidden',
            name: 'tax',
            value: tax
        }).appendTo($form);
        
        $('<input>').attr({
            type: 'hidden',
            name: 'grand_total',
            value: grandTotal
        }).appendTo($form);
        
        console.log('Added totals:', subtotal, additionalChargesTotal, tax, grandTotal);
        
        // Add the submit button to the form
        $form.append($('<input>').attr({
            type: 'submit',
            value: 'Generate PDF'
        }));
        
        // Append the form to the body and submit it
        $form.appendTo('body').submit();
        
        // Clean up the form after submission
        $form.remove();
    });
});
</script>
</body>
</html>
