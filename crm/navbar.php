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
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo getUrl('index.php'); ?>">Angel Stones</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'crm_dashboard.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('crm_dashboard.php'); ?>">
                        <i class="bi bi-speedometer2"></i> CRM Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('tasks.php'); ?>">
                        <i class="bi bi-list-check"></i> Tasks
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'quote.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('quote.php'); ?>">
                        <i class="bi bi-file-earmark-text"></i> New Quote
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'quotes.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('quotes.php'); ?>">
                        <i class="bi bi-files"></i> All Quotes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('customers.php'); ?>">
                        <i class="bi bi-people"></i> Customers
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('products.php'); ?>">
                        <i class="bi bi-box-seam"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('settings.php'); ?>">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('manage_users.php'); ?>">
                        <i class="bi bi-people-fill"></i> Users
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])): ?>
                <li class="nav-item">
                    <span class="nav-link">
                        <i class="bi bi-person"></i> 
                        <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getUrl('logout.php'); ?>">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
