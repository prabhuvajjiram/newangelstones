<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);
require_once 'session_check.php';
?>
<!-- Mobile Toggle Button -->
<button class="btn btn-dark mobile-toggle">
    <i class="bi bi-list"></i>
</button>

<div class="sidebar bg-dark text-white" style="width: 250px; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto; transition: all 0.3s;">
    <div class="d-flex flex-column h-100">
        <!-- Logo/Brand -->
        <div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <a class="text-decoration-none text-white fs-4" href="<?php echo getUrl('index.php'); ?>">
                    <img src="<?php echo getUrl('../images/favicon.png'); ?>" alt="Angel Stones" style="width: 24px; height: 24px; margin-right: 8px;">
                    <span class="menu-text">Angel Stones</span>
                </a>
                <button class="btn btn-link text-white p-0 d-none d-md-block" id="sidebarToggle">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>
        </div>

        <!-- Navigation Items -->
        <div class="nav flex-column py-3">
            <a class="nav-link text-white <?php echo $current_page == 'crm_dashboard.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('crm_dashboard.php'); ?>">
                <i class="bi bi-speedometer2 me-2"></i> <span class="menu-text">Dashboard</span>
            </a>

            <a class="nav-link text-white <?php echo $current_page == 'tasks.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('tasks.php'); ?>">
                <i class="bi bi-list-check me-2"></i> <span class="menu-text">Tasks</span>
            </a>

            <!-- Contacts & Companies Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" href="#" id="contactsDropdown" role="button">
                    <i class="bi bi-people me-2"></i> <span class="menu-text">Contacts & Companies</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" style="position: relative; width: 100%; margin: 0; border-radius: 0;">
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>" 
                           href="<?php echo getUrl('customers.php'); ?>">
                            <i class="bi bi-person me-2"></i> <span class="menu-text">Contacts</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'companies.php' ? 'active' : ''; ?>" 
                           href="<?php echo getUrl('companies.php'); ?>">
                            <i class="bi bi-building me-2"></i> <span class="menu-text">Companies</span>
                        </a>
                    </li>
                </ul>
            </div>

            <a class="nav-link text-white <?php echo $current_page == 'quote.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('quote.php'); ?>">
                <i class="bi bi-file-earmark-text me-2"></i> <span class="menu-text">New Quote</span>
            </a>

            <a class="nav-link text-white <?php echo $current_page == 'quotes.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('quotes.php'); ?>">
                <i class="bi bi-files me-2"></i> <span class="menu-text">All Quotes</span>
            </a>
            <!-- Inventory Management Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" href="#" id="inventoryDropdown" role="button">
                    <i class="bi bi-box-seam me-2"></i> <span class="menu-text">Inventory</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" style="position: relative; width: 100%; margin: 0; border-radius: 0;">
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'raw_materials.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('raw_materials.php'); ?>">
                            <i class="bi bi-boxes me-2"></i> <span class="menu-text">Raw Materials</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'finished_products.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('finished_products.php'); ?>">
                            <i class="bi bi-box me-2"></i> <span class="menu-text">Finished Products</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'product_movements.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('product_movements.php'); ?>">
                            <i class="bi bi-arrows-move me-2"></i> <span class="menu-text">Product Movements</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'batch_operations.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('batch_operations.php'); ?>">
                            <i class="bi bi-box-arrow-in-right me-2"></i> <span class="menu-text">Batch Operations</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" href="#" id="orderProcessing" role="button">
                    <i class="bi bi-cart3 me-2"></i> <span class="menu-text">Order Processing</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" style="position: relative; width: 100%; margin: 0; border-radius: 0;">
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('orders.php'); ?>">
                            <i class="bi bi-cart-check me-2"></i> <span class="menu-text">Purchase Management</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('orders.php'); ?>">
                            <i class="bi bi-gear-wide-connected me-2"></i> <span class="menu-text">Production Orders</span>
                        </a>
                    </li>
                </ul>
            </div>
            <?php if (isAdmin()): ?>
            <div class="border-top my-3"></div>
            
             <!-- Pricing -->
             <div class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" href="#" id="pricingDropdown" role="button">
                    <i class="bi bi-currency-dollar me-2"></i> <span class="menu-text">Pricing</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" style="position: relative; width: 100%; margin: 0; border-radius: 0;">
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'products.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('products.php'); ?>">
                            <i class="bi bi-clipboard-data me-2"></i> <span class="menu-text">Product Pricing</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'inventory_pricing.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('inventory_pricing.php'); ?>">
                            <i class="bi bi-tags me-2"></i> <span class="menu-text">Inventory Pricing</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <a class="nav-link text-white <?php echo $current_page == 'warehouses.php' ? 'active bg-primary' : ''; ?>" 
                href="<?php echo getUrl('warehouses.php'); ?>">
                <i class="bi bi-building me-2"></i> <span class="menu-text">Warehouse Management</span>
            </a>
            
            <!-- Reports & Analytics Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" href="#" id="reportsDropdown" role="button">
                    <i class="bi bi-bar-chart-line me-2"></i> <span class="menu-text">Reports & Analytics</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" style="position: relative; width: 100%; margin: 0; border-radius: 0;">
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'inventory_reports.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('inventory_reports.php'); ?>">
                            <i class="bi bi-clipboard-data me-2"></i> <span class="menu-text">Inventory Reports</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'financial_reports.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('financial_reports.php'); ?>">
                            <i class="bi bi-cash-stack me-2"></i> <span class="menu-text">Financial Reports</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'email_analytics.php' ? 'active' : ''; ?>" 
                        href="<?php echo getUrl('email_analytics.php'); ?>">
                            <i class="bi bi-envelope-fill me-1"></i> <span class="menu-text">Email Analytics</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <a class="nav-link text-white <?php echo $current_page == 'settings.php' ? 'active bg-primary' : ''; ?>" 
               href="<?php echo getUrl('settings.php'); ?>">
                <i class="bi bi-gear me-2"></i> <span class="menu-text">Settings</span>
            </a>

            <div class="nav-item dropdown">
                <a class="nav-link text-white dropdown-toggle" href="#" id="adminDropdown" role="button">
                    <i class="bi bi-gear me-2"></i> <span class="menu-text">Admin</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" style="position: relative; width: 100%; margin: 0; border-radius: 0;">
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'supplier_management.php' ? 'active' : ''; ?>" 
                           href="<?php echo getUrl('supplier/supplier_management.php'); ?>">
                            <i class="bi bi-building-add me-2"></i> <span class="menu-text">Manage Suppliers</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'supplier_invoice.php' ? 'active' : ''; ?>" 
                           href="<?php echo getUrl('supplier/supplier_invoice.php'); ?>">
                            <i class="bi bi-receipt me-2"></i> <span class="menu-text">Supplier Invoices</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ps-4 <?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>" 
                           href="<?php echo getUrl('manage_users.php'); ?>">
                            <i class="bi bi-people-fill me-2"></i> <span class="menu-text">Users</span>
                        </a>
                    </li>
                </ul>
            </div>
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
<div style="margin-left: 250px; transition: margin-left 0.3s;">
    <div class="container-fluid py-4">
        <!-- Page content will go here -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('[style*="margin-left: 250px"]');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const toggleIcon = sidebarToggle.querySelector('i');
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const mobileToggle = document.querySelector('.mobile-toggle');
    
    // Load saved state
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        sidebar.style.width = '60px';
        mainContent.style.marginLeft = '60px';
        toggleIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
        document.querySelectorAll('.menu-text').forEach(el => {
            el.style.display = 'none';
        });
    }

    // Handle dropdowns
    dropdownToggles.forEach(toggle => {
        toggle.removeAttribute('data-bs-toggle');
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdownMenu = this.nextElementSibling;
            
            dropdownToggles.forEach(otherToggle => {
                if (otherToggle !== this) {
                    otherToggle.classList.remove('show');
                    otherToggle.setAttribute('aria-expanded', 'false');
                    const otherMenu = otherToggle.nextElementSibling;
                    if (otherMenu) {
                        otherMenu.classList.remove('show');
                    }
                }
            });
            
            this.classList.toggle('show');
            dropdownMenu.classList.toggle('show');
            this.setAttribute('aria-expanded', dropdownMenu.classList.contains('show'));
        });
    });

    // Handle mobile toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('show');
            document.querySelector('.mobile-overlay').classList.toggle('show');
        });

        // Close sidebar when clicking overlay
        document.querySelector('.mobile-overlay').addEventListener('click', function() {
            sidebar.classList.remove('show');
            this.classList.remove('show');
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            dropdownToggles.forEach(toggle => {
                toggle.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
                const menu = toggle.nextElementSibling;
                if (menu) {
                    menu.classList.remove('show');
                }
            });
        }
    });

    // Desktop collapse toggle
    sidebarToggle.addEventListener('click', function() {
        const isExpanded = sidebar.style.width !== '60px';
        sidebar.style.width = isExpanded ? '60px' : '250px';
        mainContent.style.marginLeft = isExpanded ? '60px' : '250px';
        toggleIcon.classList.toggle('bi-chevron-left');
        toggleIcon.classList.toggle('bi-chevron-right');
        document.querySelectorAll('.menu-text').forEach(el => {
            el.style.display = isExpanded ? 'none' : 'inline';
        });
        localStorage.setItem('sidebarCollapsed', isExpanded);
    });

    // Handle responsive behavior
    function checkWidth() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('show');
            document.querySelector('.mobile-overlay').classList.remove('show');
            mainContent.style.marginLeft = '0';
        } else {
            sidebar.classList.remove('show');
            document.querySelector('.mobile-overlay').classList.remove('show');
            mainContent.style.marginLeft = isCollapsed ? '60px' : '250px';
        }
    }

    window.addEventListener('resize', checkWidth);
    checkWidth();
});
</script>

<style>
.dropdown-menu {
    display: none;
}

.dropdown-menu.show {
    display: block;
}

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

/* Collapsed sidebar styles */
.sidebar[style*="width: 60px"] .dropdown-toggle::after {
    display: none;
}

.sidebar[style*="width: 60px"] .nav-link {
    padding: 0.5rem;
    text-align: center;
}

.sidebar[style*="width: 60px"] .nav-link i {
    margin: 0;
    font-size: 1.2rem;
}

/* Mobile menu button */
.mobile-toggle {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1046;
    padding: 0.5rem;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .mobile-toggle {
        display: block !important;
    }
    
    .sidebar {
        transform: translateX(-100%);
        z-index: 1040;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0 !important;
        padding-top: 60px;
    }
}

/* Base sidebar styles */
.sidebar {
    z-index: 1045 !important;
}

/* Mobile overlay */
@media (max-width: 768px) {
    .mobile-overlay {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        display: none;
    }

    .mobile-overlay.show {
        display: block;
    }

    .sidebar {
        transform: translateX(-100%);
        z-index: 1045;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
}
</style>

<!-- Add this right after the mobile toggle button -->
<div class="mobile-overlay"></div>