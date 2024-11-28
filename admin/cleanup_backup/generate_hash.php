<?php
$password = 'P@ssword1';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash for 'P@ssword1': " . $hash . "\n";
?>
