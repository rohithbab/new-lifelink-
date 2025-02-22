<?php
session_start();
require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $odml_id = trim(filter_var($_POST['odml_id'], FILTER_SANITIZE_STRING));
    $password = $_POST['password'];

    try {
        // Check if the email and ODML ID exist and match
        $stmt = $conn->prepare("SELECT * FROM recipient_registration WHERE email = ? AND odml_id = ?");
        $stmt->execute([$email, $odml_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if the recipient is approved
                if ($user['request_status'] === 'accepted') {
                    // Set session variables
                    $_SESSION['recipient_id'] = $user['id'];
                    $_SESSION['recipient_email'] = $email;
                    $_SESSION['recipient_name'] = $user['name'];
                    $_SESSION['is_recipient'] = true;
                    
                    // Redirect to dashboard
                    header("Location: ../../pages/recipient/recipient_dashboard.php");
                    exit();
                } else if ($user['request_status'] === 'pending') {
                    $_SESSION['error'] = "Your account is still under review. Please wait for admin approval.";
                } else if ($user['request_status'] === 'rejected') {
                    $_SESSION['error'] = "Your registration has been rejected. Please contact support.";
                }
            } else {
                $_SESSION['error'] = "Invalid password. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Invalid email or ODML ID. Please check your credentials.";
        }
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred during login. Please try again.";
    }
    
    // If we get here, there was an error
    header("Location: ../../pages/recipient_login.php");
    exit();
}

// If not POST request, redirect to login page
header("Location: ../../pages/recipient_login.php");
exit();
?>
