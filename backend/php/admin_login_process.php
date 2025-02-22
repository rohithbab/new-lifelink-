<?php
session_start();
require_once 'connection.php';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: ../../pages/admin_login.php");
        exit();
    }

    try {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() == 1) {
            $admin = $stmt->fetch();
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Password is correct, create session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_name'] = $admin['name'];
                
                // Redirect to admin dashboard
                header("Location: ../../pages/admin/admin_dashboard.php");
                exit();
            } else {
                // Invalid password
                $_SESSION['error'] = "Invalid email or password";
                header("Location: ../../pages/admin_login.php");
                exit();
            }
        } else {
            // No admin found with that email
            $_SESSION['error'] = "Invalid email or password";
            header("Location: ../../pages/admin_login.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "An error occurred. Please try again later.";
        header("Location: ../../pages/admin_login.php");
        exit();
    }
} else {
    // If someone tries to access this file directly
    header("Location: ../../pages/admin_login.php");
    exit();
}
?>
