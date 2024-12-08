<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Debug information
error_log("Debug - Session Data: " . print_r($_SESSION, true));
error_log("Debug - User Roles: " . print_r($_SESSION['roles'] ?? [], true));

$current_page = basename($_SERVER['PHP_SELF']);
require_once 'session_check.php';
?>
<div class="sidebar bg-dark text-white" style="width: 250px; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto;">
    <div class="d-flex flex-column h-100">
        <!-- Logo/Brand -->
        <div class="p-3 border-bottom">
            <a class="text-decoration-none text-white fs-4" href="<?php echo getUrl('index.php'); ?>">
                <img src="../images/favicon.png" alt="Angel Stones" style="width: 24px; height: 24px; margin-right: 8px;">
                Angel Stones
            </a>
        </div>

        <!-- Navigation Items -->
        <div class="nav flex-column py-3">
            <a class="nav-link text-white <?php echo $current_page == 'crm_dashboard.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('crm_dashboard.php'); ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>

            <a class="nav-link text-white <?php echo $current_page == 'tasks.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('tasks.php'); ?>">
                <i class="bi bi-list-check me-2"></i> Tasks
            </a>

            <!-- Contacts & Companies Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" href="#" id="contactsDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-people me-2"></i> Contacts & Companies
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" style="position: relative; width: 100%; margin: 0; border-radius: 0;">
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>" 
                           href="<?php echo getUrl('customers.php'); ?>">
                            <i class="bi bi-person me-2"></i> Contacts
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'companies.php' ? 'active' : ''; ?>" 
                           href="<?php echo getUrl('companies.php'); ?>">
                            <i class="bi bi-building me-2"></i> Companies
                        </a>
                    </li>
                </ul>
            </div>

            <a class="nav-link text-white <?php echo $current_page == 'quote.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('quote.php'); ?>">
                <i class="bi bi-file-earmark-text me-2"></i> New Quote
            </a>

            <a class="nav-link text-white <?php echo $current_page == 'quotes.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('quotes.php'); ?>">
                <i class="bi bi-files me-2"></i> All Quotes
            </a>

            <?php if (isAdmin()): ?>
            <div class="border-top my-3"></div>
            
            <a class="nav-link text-white <?php echo $current_page == 'products.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('products.php'); ?>">
                <i class="bi bi-box-seam me-2"></i> Products
            </a>

            <a class="nav-link text-white <?php echo $current_page == 'settings.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('settings.php'); ?>">
                <i class="bi bi-gear me-2"></i> Settings
            </a>

            <a class="nav-link text-white <?php echo $current_page == 'manage_users.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('manage_users.php'); ?>">
                <i class="bi bi-people-fill me-2"></i> Users
            </a>
            <?php endif; ?>
        </div>

        <!-- User Info and Logout at Bottom -->
        <div class="mt-auto p-3 border-top">
            <?php if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])): ?>
            <div class="d-flex flex-column">
                <span class="text-white mb-2">
                    <i class="bi bi-person-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                </span>
                <a class="nav-link text-white" href="<?php echo getUrl('logout.php'); ?>">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Content Wrapper with Left Margin -->
<div style="margin-left: 250px;">
    <div class="container-fluid py-4">
        <!-- Your page content will go here -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        new bootstrap.Dropdown(dropdown);
    });
});
</script>

<style>
.sidebar .nav-link {
    padding: 0.5rem 1rem;
    transition: all 0.3s;
    white-space: nowrap;
}

.sidebar .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-link.active {
    background-color: var(--bs-primary);
}

.sidebar .dropdown-menu {
    background-color: #2c3034;
    border: none;
    padding: 0;
    box-shadow: none;
    margin-top: 0 !important;
    position: static !important;
    transform: none !important;
    width: 100%;
}

.sidebar .dropdown-item {
    padding: 0.5rem 1rem;
    color: white;
}

.sidebar .dropdown-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar .dropdown-item.active {
    background-color: var(--bs-primary);
}

.sidebar .dropdown-toggle::after {
    float: right;
    margin-top: 10px;
}

.sidebar .dropdown-toggle[aria-expanded="true"]::after {
    transform: rotate(180deg);
}
</style>