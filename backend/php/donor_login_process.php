<?php
session_start();
require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get and sanitize input
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $odml_id = filter_var($_POST['odml_id'], FILTER_SANITIZE_STRING);
        $password = $_POST['password'];

        // Validate input
        if (empty($email) || empty($odml_id) || empty($password)) {
            throw new Exception("All fields are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if donor exists and is approved
        $stmt = $conn->prepare("SELECT donor_id, name, password, status FROM donor WHERE email = ? AND odml_id = ?");
        $stmt->execute([$email, $odml_id]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donor) {
            throw new Exception("Invalid email, ODML ID, or password.");
        }

        // Check if donor is approved
        if ($donor['status'] !== 'Approved') {
            throw new Exception("Your account is pending approval or has been rejected. Please contact the admin.");
        }

        // Verify password
        if (!password_verify($password, $donor['password'])) {
            throw new Exception("Invalid email, ODML ID, or password.");
        }

        // Set session variables
        $_SESSION['donor_id'] = $donor['donor_id'];
        $_SESSION['donor_name'] = $donor['name'];
        $_SESSION['is_donor'] = true;

        // Redirect to donor dashboard
        header("Location: ../../pages/donor/donor_dashboard.php");
        exit();

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../../pages/donor_login.php");
        exit();
    }
} else {
    header("Location: ../../pages/donor_login.php");
    exit();
}
?>
