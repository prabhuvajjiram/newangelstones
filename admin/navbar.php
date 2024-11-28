<?php
if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', '');
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo ADMIN_PATH; ?>index.php">Angel Stones</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'quote.php' ? 'active' : ''; ?>" 
                       href="<?php echo ADMIN_PATH; ?>quote.php">
                        <i class="bi bi-file-earmark-text"></i> New Quote
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>" 
                       href="<?php echo ADMIN_PATH; ?>customers.php">
                        <i class="bi bi-people"></i> Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'quotes.php' ? 'active' : ''; ?>" 
                       href="<?php echo ADMIN_PATH; ?>quotes.php">
                        <i class="bi bi-files"></i> All Quotes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" 
                       href="<?php echo ADMIN_PATH; ?>settings.php">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" 
                       href="<?php echo ADMIN_PATH; ?>products.php">
                        <i class="bi bi-box-seam"></i> Products
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
                    <a class="nav-link" href="<?php echo ADMIN_PATH; ?>logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
