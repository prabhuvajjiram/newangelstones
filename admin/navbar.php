<?php
require_once 'includes/config.php';
$current_page = basename($_SERVER['PHP_SELF']);
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
                    <a class="nav-link <?php echo $current_page == 'quote.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('quote.php'); ?>">
                        <i class="bi bi-file-earmark-text"></i> New Quote
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('customers.php'); ?>">
                        <i class="bi bi-people"></i> Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'quotes.php' ? 'active' : ''; ?>" 
                       href="<?php echo getUrl('quotes.php'); ?>">
                        <i class="bi bi-files"></i> All Quotes
                    </a>
                </li>
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
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['username'])): ?>
                <li class="nav-item">
                    <span class="nav-link">
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
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
