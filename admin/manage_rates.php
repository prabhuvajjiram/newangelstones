<?php
require_once 'includes/config.php';
requireAdmin();

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_stone'])) {
        $id = $_POST['stone_id'];
        $price = $_POST['price_per_sqft'];
        $polish_rate = $_POST['width_polish_rate'];
        $base_price = $_POST['base_price'];
        $slant_price = $_POST['slant_price'];
        
        $stmt = $conn->prepare("UPDATE stone_color_rates SET price_per_sqft = ?, width_polish_rate = ?, base_price = ?, slant_price = ? WHERE id = ?");
        $stmt->bind_param("ddiddi", $price, $polish_rate, $base_price, $slant_price, $id);
        
        if ($stmt->execute()) {
            $message = "Stone price updated successfully!";
        } else {
            $message = "Error updating stone price: " . $conn->error;
        }
    }
    
    if (isset($_POST['update_commission'])) {
        $id = $_POST['commission_id'];
        $percentage = $_POST['percentage'];
        
        $stmt = $conn->prepare("UPDATE commission_rates SET percentage = ? WHERE id = ?");
        $stmt->bind_param("di", $percentage, $id);
        
        if ($stmt->execute()) {
            $message = "Commission rate updated successfully!";
        } else {
            $message = "Error updating commission rate: " . $conn->error;
        }
    }
}

// Fetch stone colors
$stones = [];
$result = $conn->query("SELECT * FROM stone_color_rates ORDER BY color_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stones[] = $row;
    }
}

// Fetch commission rates
$commission_rates = [];
$result = $conn->query("SELECT * FROM commission_rates ORDER BY rate_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $commission_rates[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rates - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Angel Stones Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_rates.php">Manage Rates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quote.php">Generate Quote</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Stone Colors and Prices -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Stone Colors and Prices</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="stonesTable">
                                <thead>
                                    <tr>
                                        <th>Color</th>
                                        <th>Price per sq ft (₹)</th>
                                        <th>Width Polish Rate (₹)</th>
                                        <th>SERTOP Base (₹)</th>
                                        <th>SERTOP Slant (₹)</th>
                                        <th>Last Updated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stones as $stone): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stone['color_name']); ?></td>
                                        <td>₹<?php echo number_format($stone['price_per_sqft'], 2); ?></td>
                                        <td>₹<?php echo number_format($stone['width_polish_rate'], 2); ?></td>
                                        <td>₹<?php echo number_format($stone['base_price'], 2); ?></td>
                                        <td>₹<?php echo number_format($stone['slant_price'], 2); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($stone['updated_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editStoneModal"
                                                    data-id="<?php echo $stone['id']; ?>"
                                                    data-color="<?php echo htmlspecialchars($stone['color_name']); ?>"
                                                    data-price="<?php echo $stone['price_per_sqft']; ?>"
                                                    data-polish="<?php echo $stone['width_polish_rate']; ?>"
                                                    data-base="<?php echo $stone['base_price']; ?>"
                                                    data-slant="<?php echo $stone['slant_price']; ?>">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commission Rates -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Commission Rates</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="commissionTable">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Rate (%)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($commission_rates as $rate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rate['rate_name']); ?></td>
                                        <td><?php echo number_format($rate['percentage'], 2); ?>%</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editCommissionModal"
                                                    data-id="<?php echo $rate['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($rate['rate_name']); ?>"
                                                    data-percentage="<?php echo $rate['percentage']; ?>">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Stone Modal -->
    <div class="modal fade" id="editStoneModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Stone Price</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="stone_id" id="editStoneId">
                        <div class="mb-3">
                            <label class="form-label">Color</label>
                            <input type="text" class="form-control" id="editStoneColor" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per sq ft (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="price_per_sqft" id="editStonePrice" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Width Polish Rate (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="width_polish_rate" id="editStonePolish" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SERTOP Base Price (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="base_price" id="editStoneBase" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SERTOP Slant Price (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="slant_price" id="editStoneSlant" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_stone" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Commission Modal -->
    <div class="modal fade" id="editCommissionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Commission Rate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="commission_id" id="editCommissionId">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <input type="text" class="form-control" id="editCommissionName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="percentage" id="editCommissionPercentage" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_commission" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#stonesTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 10
            });
            
            $('#commissionTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 10
            });

            // Handle Stone Edit Modal
            $('#editStoneModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var color = button.data('color');
                var price = button.data('price');
                var polish = button.data('polish');
                var base = button.data('base');
                var slant = button.data('slant');
                
                var modal = $(this);
                modal.find('#editStoneId').val(id);
                modal.find('#editStoneColor').val(color);
                modal.find('#editStonePrice').val(price);
                modal.find('#editStonePolish').val(polish);
                modal.find('#editStoneBase').val(base);
                modal.find('#editStoneSlant').val(slant);
            });

            // Handle Commission Edit Modal
            $('#editCommissionModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var name = button.data('name');
                var percentage = button.data('percentage');
                
                var modal = $(this);
                modal.find('#editCommissionId').val(id);
                modal.find('#editCommissionName').val(name);
                modal.find('#editCommissionPercentage').val(percentage);
            });
        });
    </script>
</body>
</html>
