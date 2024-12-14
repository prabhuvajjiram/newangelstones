<?php
require_once 'includes/config.php';
require_once 'session_check.php';

// Check for admin access
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_monument'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO special_monument (sp_name, sp_value) VALUES (?, ?)");
            $stmt->execute([
                $_POST['sp_name'],
                $_POST['sp_value']
            ]);
            $success_message = "New monument rate added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding monument rate: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_monument'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM special_monument WHERE id = ?");
            $stmt->execute([$_POST['monument_id']]);
            $success_message = "Monument rate deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting monument rate: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_monument'])) {
        try {
            $stmt = $pdo->prepare("UPDATE special_monument SET sp_name = ?, sp_value = ? WHERE id = ?");
            $stmt->execute([
                $_POST['sp_name'],
                $_POST['sp_value'],
                $_POST['monument_id']
            ]);
            
            if ($stmt->rowCount() > 0) {
                $success_message = "Monument rate updated successfully!";
            } else {
                $error_message = "No changes were made to monument.";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating monument rate: " . $e->getMessage();
        }
    } elseif (isset($_POST['add_color'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO stone_color_rates (color_name, price_increase_percentage) VALUES (?, ?)");
            $stmt->execute([
                $_POST['color_name'],
                $_POST['color_rate']
            ]);
            $success_message = "New color rate added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding color rate: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_color'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM stone_color_rates WHERE id = ?");
            $stmt->execute([$_POST['color_id']]);
            $success_message = "Color rate deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting color rate: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_color'])) {
        try {
            $stmt = $pdo->prepare("UPDATE stone_color_rates SET color_name = ?, price_increase_percentage = ? WHERE id = ?");
            $stmt->execute([
                $_POST['color_name'],
                $_POST['color_rate'],
                $_POST['color_id']
            ]);
            
            if ($stmt->rowCount() > 0) {
                $success_message = "Stone color rate updated successfully!";
            } else {
                $error_message = "No changes were made to color rate.";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating color rate: " . $e->getMessage();
        }
    }
}

// Get monument rates
try {
    $monument_rates = [];
    $stmt = $pdo->query("SELECT * FROM special_monument ORDER BY id");
    $monument_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching monument rates: " . $e->getMessage();
}

// Get color rates
try {
    $color_rates = [];
    $stmt = $pdo->query("SELECT * FROM stone_color_rates ORDER BY id");
    $color_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching color rates: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Angel Stones Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <h2 class="mb-4">Settings</h2>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Monument Rates Section -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Monument Rates</h5>
                        <button type="button" class="btn btn-success btn-sm add-monument-btn" 
                                data-bs-toggle="tooltip" 
                                title="Add a new monument type and its rate">
                            <i class="bi bi-plus-circle"></i> Add New
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Add Monument Form -->
                        <div id="monumentAddForm" style="display: none;" class="mb-4">
                            <h5 class="mb-3">Add New Monument Rate</h5>
                            <form method="post" id="monumentAddForm">
                                <div class="mb-3">
                                    <label for="new_sp_name" class="form-label">Monument Name</label>
                                    <input type="text" class="form-control" id="new_sp_name" name="sp_name" required
                                           data-bs-toggle="tooltip" 
                                           title="Enter the name of the monument type">
                                </div>
                                <div class="mb-3">
                                    <label for="new_sp_value" class="form-label">Rate (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="new_sp_value" name="sp_value" required
                                           data-bs-toggle="tooltip" 
                                           title="Enter the rate percentage for this monument type">
                                </div>
                                <div class="mb-3">
                                    <button type="submit" name="add_monument" class="btn btn-success">Add Monument</button>
                                    <button type="button" class="btn btn-secondary cancel-add-monument">Cancel</button>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Monument Type</th>
                                        <th>Rate (%)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monument_rates as $rate): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rate['id']); ?></td>
                                            <td><?php echo htmlspecialchars($rate['sp_name']); ?></td>
                                            <td><?php echo number_format($rate['sp_value'], 2); ?>%</td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-primary btn-sm edit-monument-btn me-1"
                                                        data-bs-toggle="tooltip"
                                                        title="Edit this monument type's details"
                                                        data-id="<?php echo $rate['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($rate['sp_name']); ?>"
                                                        data-value="<?php echo $rate['sp_value']; ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm delete-monument-btn"
                                                        data-bs-toggle="tooltip"
                                                        title="Delete this monument type"
                                                        data-id="<?php echo $rate['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($rate['sp_name']); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                                <form method="post" class="d-none delete-monument-form">
                                                    <input type="hidden" name="monument_id" value="<?php echo $rate['id']; ?>">
                                                    <input type="hidden" name="delete_monument" value="1">
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Monument Edit Form -->
                        <div id="monumentEditForm" style="display: none;">
                            <h5 class="mb-3">Edit Monument Rate</h5>
                            <form method="post" id="monumentForm">
                                <input type="hidden" id="monument_id" name="monument_id">
                                <div class="mb-3">
                                    <label for="sp_name" class="form-label">Monument Name</label>
                                    <input type="text" class="form-control" id="sp_name" name="sp_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="sp_value" class="form-label">Rate (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="sp_value" name="sp_value" required>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" name="update_monument" class="btn btn-primary">Update Monument</button>
                                    <button type="button" class="btn btn-secondary cancel-monument">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stone Color Rates Section -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Stone Color Rates</h5>
                        <button type="button" class="btn btn-success btn-sm add-color-btn">
                            <i class="bi bi-plus-circle"></i> Add New
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Add Color Form -->
                        <div id="colorAddForm" style="display: none;" class="mb-4">
                            <h5 class="mb-3">Add New Color Rate</h5>
                            <form method="post" id="colorAddForm">
                                <div class="mb-3">
                                    <label for="new_color_name" class="form-label">Color Name</label>
                                    <input type="text" class="form-control" id="new_color_name" name="color_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_color_rate" class="form-label">Rate (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="new_color_rate" name="color_rate" required>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" name="add_color" class="btn btn-success">Add Color</button>
                                    <button type="button" class="btn btn-secondary cancel-add-color">Cancel</button>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Color</th>
                                        <th>Rate (%)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($color_rates as $rate): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rate['id']); ?></td>
                                            <td><?php echo htmlspecialchars($rate['color_name']); ?></td>
                                            <td><?php echo number_format($rate['price_increase_percentage'], 2); ?>%</td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-primary btn-sm edit-color-btn me-1"
                                                        data-bs-toggle="tooltip"
                                                        title="Edit this color's details"
                                                        data-id="<?php echo $rate['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($rate['color_name']); ?>"
                                                        data-rate="<?php echo $rate['price_increase_percentage']; ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm delete-color-btn"
                                                        data-bs-toggle="tooltip"
                                                        title="Delete this color"
                                                        data-id="<?php echo $rate['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($rate['color_name']); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                                <form method="post" class="d-none delete-color-form">
                                                    <input type="hidden" name="color_id" value="<?php echo $rate['id']; ?>">
                                                    <input type="hidden" name="delete_color" value="1">
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Color Edit Form -->
                        <div id="colorEditForm" style="display: none;">
                            <h5 class="mb-3">Edit Color Rate</h5>
                            <form method="post" id="colorForm">
                                <input type="hidden" id="color_id" name="color_id">
                                <div class="mb-3">
                                    <label for="color_name" class="form-label">Color Name</label>
                                    <input type="text" class="form-control" id="color_name" name="color_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="color_rate" class="form-label">Rate (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="color_rate" name="color_rate" required>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" name="update_color" class="btn btn-primary">Update Color</button>
                                    <button type="button" class="btn btn-secondary cancel-color">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Monument Rate Functions
            $('.add-monument-btn').click(function() {
                $('#monumentAddForm').slideDown();
                $('html, body').animate({
                    scrollTop: $('#monumentAddForm').offset().top - 100
                }, 500);
            });
            
            $('.cancel-add-monument').click(function() {
                $('#monumentAddForm').slideUp();
                $('#monumentAddForm form')[0].reset();
            });

            $('.delete-monument-btn').click(function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                if (confirm(`Are you sure you want to delete the monument rate for "${name}"?`)) {
                    $(this).siblings('.delete-monument-form').submit();
                }
            });

            $('.edit-monument-btn').click(function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const value = $(this).data('value');
                
                $('#monument_id').val(id);
                $('#sp_name').val(name);
                $('#sp_value').val(value);
                
                $('#monumentEditForm').slideDown();
                $('html, body').animate({
                    scrollTop: $('#monumentEditForm').offset().top - 100
                }, 500);
            });
            
            $('.cancel-monument').click(function() {
                $('#monumentEditForm').slideUp();
                $('#monumentForm')[0].reset();
            });

            // Color Rate Functions
            $('.add-color-btn').click(function() {
                $('#colorAddForm').slideDown();
                $('html, body').animate({
                    scrollTop: $('#colorAddForm').offset().top - 100
                }, 500);
            });
            
            $('.cancel-add-color').click(function() {
                $('#colorAddForm').slideUp();
                $('#colorAddForm form')[0].reset();
            });

            $('.delete-color-btn').click(function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                if (confirm(`Are you sure you want to delete the color rate for "${name}"?`)) {
                    $(this).siblings('.delete-color-form').submit();
                }
            });

            $('.edit-color-btn').click(function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const rate = $(this).data('rate');
                
                $('#color_id').val(id);
                $('#color_name').val(name);
                $('#color_rate').val(rate);
                
                $('#colorEditForm').slideDown();
                $('html, body').animate({
                    scrollTop: $('#colorEditForm').offset().top - 100
                }, 500);
            });
            
            $('.cancel-color').click(function() {
                $('#colorEditForm').slideUp();
                $('#colorForm')[0].reset();
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
              return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
</body>
</html>