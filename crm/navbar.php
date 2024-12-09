<?php
require_once 'includes/config.php';
require_once 'session_check.php';

$current_page = basename($_SERVER['PHP_SELF']);
$base_url = '/crm/';
?>

<nav class="sidebar">
    <div class="d-flex flex-column h-100">
        <!-- Logo -->
        <div class="p-3 border-bottom">
            <a class="d-flex align-items-center text-decoration-none text-white" href="<?php echo $base_url; ?>index.php">
                <img src="../images/favicon.png" alt="Angel Stones" class="me-2" style="width: 30px; height: 30px;">
                <span class="fs-4">Angel Stones</span>
            </a>
        </div>

        <!-- Navigation -->
        <ul class="nav flex-column py-3">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" 
                   href="<?php echo $base_url; ?>index.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            <!-- Tasks -->
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>" 
                   href="<?php echo $base_url; ?>tasks.php">
                    <i class="bi bi-list-check me-2"></i> Tasks
                </a>
            </li>

            <!-- Contacts & Companies -->
            <li class="nav-item">
                <a class="nav-link dropdown-toggle" href="#contactsSubmenu" data-bs-toggle="collapse" role="button">
                    <i class="bi bi-people me-2"></i> Contacts & Companies
                </a>
                <div class="collapse" id="contactsSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>" 
                               href="<?php echo $base_url; ?>customers.php">
                                <i class="bi bi-person me-2"></i> Contacts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'companies.php' ? 'active' : ''; ?>" 
                               href="<?php echo $base_url; ?>companies.php">
                                <i class="bi bi-building me-2"></i> Companies
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Warehouse Management -->
            <li class="nav-item">
                <a class="nav-link dropdown-toggle" href="#warehouseSubmenu" data-bs-toggle="collapse" role="button">
                    <i class="bi bi-box-seam me-2"></i> Warehouse
                </a>
                <div class="collapse" id="warehouseSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'warehouses.php' ? 'active' : ''; ?>" 
                               href="<?php echo $base_url; ?>warehouses.php">
                                <i class="bi bi-building me-2"></i> Warehouses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'raw_materials.php' ? 'active' : ''; ?>" 
                               href="<?php echo $base_url; ?>raw_materials.php">
                                <i class="bi bi-box me-2"></i> Raw Materials
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <!-- Admin Section -->
            <li class="nav-item">
                <a class="nav-link dropdown-toggle" href="#adminSubmenu" data-bs-toggle="collapse" role="button">
                    <i class="bi bi-gear me-2"></i> Admin
                </a>
                <div class="collapse" id="adminSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" 
                               href="<?php echo $base_url; ?>users.php">
                                <i class="bi bi-people me-2"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" 
                               href="<?php echo $base_url; ?>settings.php">
                                <i class="bi bi-sliders me-2"></i> Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
        </ul>

        <!-- User Profile -->
        <div class="mt-auto p-3 border-top">
            <?php if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])): ?>
            <div class="d-flex align-items-center mb-2 text-white">
                <i class="bi bi-person-circle me-2"></i>
                <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
            </div>
            <a href="<?php echo $base_url; ?>logout.php" class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Auto-expand current section
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = '<?php echo $current_page; ?>';
    const links = document.querySelectorAll('.nav-link');
    
    links.forEach(link => {
        if (link.getAttribute('href') && link.getAttribute('href').includes(currentPage)) {
            const submenu = link.closest('.collapse');
            if (submenu) {
                submenu.classList.add('show');
            }
        }
    });
});
</script>