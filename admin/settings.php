<?php
require_once 'includes/config.php';
require_once 'session_check.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_rates'])) {
        $stmt = $pdo->prepare("UPDATE stone_color_rates SET color_name = ?, price_increase_percentage = ? WHERE id = ?");
        $stmt->execute([$_POST['color_name'], $_POST['color_rate'], $_POST['color_id']]);
        $success_message = "Stone color rate updated successfully!";
    } elseif (isset($_POST['add_color'])) {
        $stmt = $pdo->prepare("INSERT INTO stone_color_rates (color_name, price_increase_percentage) VALUES (?, ?)");
        $stmt->execute([$_POST['new_color_name'], $_POST['new_color_rate']]);
        $success_message = "New stone color added successfully!";
    } elseif (isset($_POST['update_commission'])) {
        $stmt = $pdo->prepare("UPDATE commission_rates SET rate_name = ?, percentage = ? WHERE id = ?");
        $stmt->execute([$_POST['commission_name'], $_POST['commission_rate'], $_POST['commission_id']]);
        $success_message = "Commission rate updated successfully!";
    } elseif (isset($_POST['add_commission'])) {
        $stmt = $pdo->prepare("INSERT INTO commission_rates (rate_name, percentage) VALUES (?, ?)");
        $stmt->execute([$_POST['new_commission_name'], $_POST['new_commission_rate']]);
        $success_message = "New commission rate added successfully!";
    } elseif (isset($_POST['update_components'])) {
        $stmt = $pdo->prepare("UPDATE price_components SET component_name = ?, base_rate = ?, description = ? WHERE id = ?");
        $stmt->execute([$_POST['component_name'], $_POST['component_rate'], $_POST['component_desc'], $_POST['component_id']]);
        $success_message = "Price component updated successfully!";
    } elseif (isset($_POST['add_component'])) {
        $stmt = $pdo->prepare("INSERT INTO price_components (component_name, base_rate, description) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['new_component_name'], $_POST['new_component_rate'], $_POST['new_component_desc']]);
        $success_message = "New price component added successfully!";
    } elseif (isset($_POST['delete_color'])) {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Delete the color
            $stmt = $pdo->prepare("DELETE FROM stone_color_rates WHERE id = ?");
            $stmt->execute([$_POST['delete_id']]);

            // Commit transaction
            $pdo->commit();
            $success_message = "Stone color deleted successfully!";
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error_message = "Cannot delete this color as it is being used in existing quotes. Please update or delete the related quotes first.";
        }
    } elseif (isset($_POST['delete_commission'])) {
        $stmt = $pdo->prepare("DELETE FROM commission_rates WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $success_message = "Commission rate deleted successfully!";
    } elseif (isset($_POST['delete_component'])) {
        $stmt = $pdo->prepare("DELETE FROM price_components WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $success_message = "Price component deleted successfully!";
    }
}

// Get current settings
$color_rates = [];
$result = $pdo->query("SELECT * FROM stone_color_rates ORDER BY color_name");
$color_rates = $result->fetchAll(PDO::FETCH_ASSOC);

$commission_rates = [];
$result = $pdo->query("SELECT * FROM commission_rates ORDER BY rate_name");
$commission_rates = $result->fetchAll(PDO::FETCH_ASSOC);

$price_components = [];
$result = $pdo->query("SELECT * FROM price_components ORDER BY component_name");
$price_components = $result->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .new-item-form {
            display: none;
        }
        .table-sm td, .table-sm th {
            padding: 0.3rem;
            font-size: 0.9rem;
        }
        .table-responsive {
            max-height: 300px;
            overflow-y: auto;
        }
        .delete-form {
            margin: 0;
            padding: 0;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">System Settings</h2>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Stone Color Rates -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Stone Color Rates</h5>
                    </div>
                    <div class="card-body">
                        <!-- Current Colors List -->
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Color Name</th>
                                        <th>Price Increase</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($color_rates as $rate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rate['color_name']); ?></td>
                                        <td><?php echo $rate['price_increase_percentage']; ?>%</td>
                                        <td class="text-center" style="width: 100px;">
                                            <form method="post" class="delete-form d-inline">
                                                <input type="hidden" name="delete_id" value="<?php echo $rate['id']; ?>">
                                                <button type="submit" name="delete_color" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this color?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <hr class="mb-4">
                        <form method="post" id="colorForm">
                            <div class="mb-3">
                                <label class="form-label">Select Color</label>
                                <select class="form-select" id="colorSelect" name="color_id">
                                    <option value="">Select Color</option>
                                    <?php foreach ($color_rates as $rate): ?>
                                    <option value="<?php echo $rate['id']; ?>" 
                                            data-rate="<?php echo $rate['price_increase_percentage']; ?>">
                                        <?php echo htmlspecialchars($rate['color_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <option value="new">+ Add New Color</option>
                                </select>
                            </div>
                             
                            <!-- Existing Color Form -->
                            <div id="existingColorForm">
                                <div class="mb-3">
                                    <label class="form-label">Color Name</label>
                                    <input type="text" class="form-control" name="color_name" id="colorName">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price Increase</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="color_rate" id="colorRate">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <button type="submit" name="update_rates" class="btn btn-primary" id="updateColorBtn">Update Color</button>
                            </div>

                            <!-- New Color Form -->
                            <div id="newColorForm" class="new-item-form">
                                <div class="mb-3">
                                    <label class="form-label">Color Name</label>
                                    <input type="text" class="form-control" name="new_color_name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price Increase</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="new_color_rate">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <button type="submit" name="add_color" class="btn btn-success">Add Color</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Commission Rates -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Commission Rates</h5>
                    </div>
                    <div class="card-body">
                        <!-- Current Commission Rates List -->
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Rate Name</th>
                                        <th>Percentage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($commission_rates as $rate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rate['rate_name']); ?></td>
                                        <td><?php echo $rate['percentage']; ?>%</td>
                                        <td class="text-center" style="width: 100px;">
                                            <form method="post" class="delete-form d-inline">
                                                <input type="hidden" name="delete_id" value="<?php echo $rate['id']; ?>">
                                                <button type="submit" name="delete_commission" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this commission rate?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <hr class="mb-4">
                        <form method="post" id="commissionForm">
                            <div class="mb-3">
                                <label class="form-label">Select Rate</label>
                                <select class="form-select" id="commissionSelect" name="commission_id">
                                    <option value="">Select Rate</option>
                                    <?php foreach ($commission_rates as $rate): ?>
                                    <option value="<?php echo $rate['id']; ?>" 
                                            data-rate="<?php echo $rate['percentage']; ?>">
                                        <?php echo htmlspecialchars($rate['rate_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <option value="new">+ Add New Rate</option>
                                </select>
                            </div>

                            <!-- Existing Commission Form -->
                            <div id="existingCommissionForm">
                                <div class="mb-3">
                                    <label class="form-label">Rate Name</label>
                                    <input type="text" class="form-control" name="commission_name" id="commissionName">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Percentage</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="commission_rate" id="commissionRate">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <button type="submit" name="update_commission" class="btn btn-primary" id="updateCommissionBtn">Update Rate</button>
                            </div>

                            <!-- New Commission Form -->
                            <div id="newCommissionForm" class="new-item-form">
                                <div class="mb-3">
                                    <label class="form-label">Rate Name</label>
                                    <input type="text" class="form-control" name="new_commission_name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Percentage</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="new_commission_rate">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <button type="submit" name="add_commission" class="btn btn-success">Add Rate</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Price Components -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Price Components</h5>
                    </div>
                    <div class="card-body">
                        <!-- Current Price Components List -->
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Component Name</th>
                                        <th>Base Rate</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($price_components as $component): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($component['component_name']); ?></td>
                                        <td>$<?php echo number_format($component['base_rate'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($component['description']); ?></td>
                                        <td class="text-center" style="width: 100px;">
                                            <form method="post" class="delete-form d-inline">
                                                <input type="hidden" name="delete_id" value="<?php echo $component['id']; ?>">
                                                <button type="submit" name="delete_component" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this price component?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <hr class="mb-4">
                        <form method="post" id="componentForm">
                            <div class="mb-3">
                                <label class="form-label">Select Component</label>
                                <select class="form-select" id="componentSelect" name="component_id">
                                    <option value="">Select Component</option>
                                    <?php foreach ($price_components as $component): ?>
                                    <option value="<?php echo $component['id']; ?>" 
                                            data-rate="<?php echo $component['base_rate']; ?>"
                                            data-description="<?php echo htmlspecialchars($component['description']); ?>">
                                        <?php echo htmlspecialchars($component['component_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <option value="new">+ Add New Component</option>
                                </select>
                            </div>

                            <!-- Existing Component Form -->
                            <div id="existingComponentForm">
                                <div class="mb-3">
                                    <label class="form-label">Component Name</label>
                                    <input type="text" class="form-control" name="component_name" id="componentName">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Base Rate</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control" name="component_rate" id="componentRate">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="component_desc" id="componentDesc" rows="2"></textarea>
                                </div>
                                <button type="submit" name="update_components" class="btn btn-primary" id="updateComponentBtn">Update Component</button>
                            </div>

                            <!-- New Component Form -->
                            <div id="newComponentForm" class="new-item-form">
                                <div class="mb-3">
                                    <label class="form-label">Component Name</label>
                                    <input type="text" class="form-control" name="new_component_name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Base Rate</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control" name="new_component_rate">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="new_component_desc" rows="2"></textarea>
                                </div>
                                <button type="submit" name="add_component" class="btn btn-success">Add Component</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
            <!-- Email Settings -->
            <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Email Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Gmail Integration Status</h6>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT setting_value FROM email_settings WHERE setting_name = 'access_token'");
                                    $stmt->execute();
                                    $access_token = $stmt->fetchColumn();
                                    
                                    if ($access_token) {
                                        echo '<p class="text-success"><i class="fas fa-check-circle"></i> Gmail integration is configured</p>';
                                        echo '<button class="btn btn-warning" onclick="window.location.href=\'gmail_auth.php\'">Reconfigure Gmail</button>';
                                    } else {
                                        echo '<p class="text-warning"><i class="fas fa-exclamation-circle"></i> Gmail integration not configured</p>';
                                        echo '<button class="btn btn-primary" onclick="window.location.href=\'gmail_auth.php\'">Configure Gmail</button>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error checking Gmail configuration</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Stone Color Rates
            $('#colorSelect').change(function() {
                const selected = $(this).val();
                if (selected === 'new') {
                    $('#existingColorForm').hide();
                    $('#newColorForm').show();
                } else if (selected) {
                    const rate = $('option:selected', this).data('rate');
                    const name = $('option:selected', this).text().trim();
                    $('#colorRate').val(rate);
                    $('#colorName').val(name);
                    $('#existingColorForm').show();
                    $('#newColorForm').hide();
                } else {
                    $('#existingColorForm').hide();
                    $('#newColorForm').hide();
                }
            });

            // Commission Rates
            $('#commissionSelect').change(function() {
                const selected = $(this).val();
                if (selected === 'new') {
                    $('#existingCommissionForm').hide();
                    $('#newCommissionForm').show();
                } else if (selected) {
                    const rate = $('option:selected', this).data('rate');
                    const name = $('option:selected', this).text().trim();
                    $('#commissionRate').val(rate);
                    $('#commissionName').val(name);
                    $('#existingCommissionForm').show();
                    $('#newCommissionForm').hide();
                } else {
                    $('#existingCommissionForm').hide();
                    $('#newCommissionForm').hide();
                }
            });

            // Price Components
            $('#componentSelect').change(function() {
                const selected = $(this).val();
                if (selected === 'new') {
                    $('#existingComponentForm').hide();
                    $('#newComponentForm').show();
                } else if (selected) {
                    const rate = $('option:selected', this).data('rate');
                    const name = $('option:selected', this).text().trim();
                    const description = $('option:selected', this).data('description');
                    $('#componentRate').val(rate);
                    $('#componentName').val(name);
                    $('#componentDesc').val(description);
                    $('#existingComponentForm').show();
                    $('#newComponentForm').hide();
                } else {
                    $('#existingComponentForm').hide();
                    $('#newComponentForm').hide();
                }
            });

            // Form submission handlers
            $('#colorForm, #commissionForm, #componentForm').on('submit', function(e) {
                if (!$(this).find('select').val()) {
                    e.preventDefault();
                    alert('Please select an item to update or choose "Add New"');
                    return false;
                }
            });

            // Initial state
            $('#existingColorForm, #newColorForm').hide();
            $('#existingCommissionForm, #newCommissionForm').hide();
            $('#existingComponentForm, #newComponentForm').hide();
        });
    </script>
</body>
</html>
