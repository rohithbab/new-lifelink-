<?php
require_once 'connection.php';

$email = 'me@lifelink.com';
$password = 'me123';
$username = 'admin';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Update the admin credentials
    $stmt = $conn->prepare("UPDATE admins SET email = ?, password = ? WHERE username = ?");
    $stmt->execute([$email, $hashed_password, $username]);
    
    echo "Admin credentials updated successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
