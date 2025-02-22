<?php
session_start();
require_once 'connection.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$email = $_POST['email'] ?? '';
$odml_id = $_POST['odml_id'] ?? '';
$password = $_POST['password'] ?? '';

try {
    // Validate all fields are present
    if (empty($email) || empty($odml_id) || empty($password)) {
        throw new Exception("All fields are required");
    }

    // First check if the hospital exists and get its status
    $stmt = $conn->prepare("
        SELECT * FROM hospitals 
        WHERE email = ? AND odml_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }

    $stmt->execute([$email, $odml_id]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hospital) {
        throw new Exception("Invalid email or ODML ID");
    }

    // Check hospital status
    if ($hospital['status'] === 'pending') {
        throw new Exception("Your registration is still pending approval");
    }

    if ($hospital['status'] === 'rejected') {
        throw new Exception("Your registration has been rejected");
    }

    // Verify password
    if (!password_verify($password, $hospital['password'])) {
        throw new Exception("Invalid password");
    }

    // Set session variables
    $_SESSION['hospital_id'] = $hospital['hospital_id'];
    $_SESSION['hospital_name'] = $hospital['name'];
    $_SESSION['hospital_email'] = $hospital['email'];
    $_SESSION['odml_id'] = $hospital['odml_id'];
    $_SESSION['hospital_logged_in'] = true;

    // For debugging
    error_log("Logged in hospital ID: " . $hospital['hospital_id']);

    // Redirect to hospital dashboard
    header("Location: ../../pages/hospital/hospital_dashboard.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../../pages/hospital_login.php");
    exit();
}
?>
