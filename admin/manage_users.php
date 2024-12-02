<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/auth_config.php';
require_once 'session_check.php';

// Check for admin access
requireAdmin();

// Get all users with their roles
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name, r.id as role_id
    FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    ORDER BY u.email
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available roles
$stmt = $pdo->query("SELECT * FROM roles ORDER BY name");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_role':
                $user_id = $_POST['user_id'];
                $role_id = $_POST['role_id'];
                
                // Update user's role
                $stmt = $pdo->prepare("
                    UPDATE user_roles 
                    SET role_id = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([$role_id, $user_id]);
                
                $success_message = "User role updated successfully!";
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'];
                
                // Delete user's roles first
                $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Then delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $success_message = "User deleted successfully!";
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Angel Stones Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Manage Users</h2>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Name</th>
                                <th>Current Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role_id" class="form-select form-select-sm d-inline-block w-auto me-2">
                                            <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['id']; ?>" <?php echo ($role['id'] == $user['role_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="action" value="update_role">
                                        <button type="submit" class="btn btn-primary btn-sm">Update Role</button>
                                    </form>
                                    
                                    <form method="post" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>