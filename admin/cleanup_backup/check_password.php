<?php
$password = 'admin123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Checking password 'admin123' against stored hash...\n";
if (password_verify($password, $hash)) {
    echo "Password is valid!\n";
} else {
    echo "Password is NOT valid!\n";
    echo "Generating new hash for 'admin123': " . password_hash('admin123', PASSWORD_DEFAULT) . "\n";
}
?>
