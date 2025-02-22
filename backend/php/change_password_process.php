<?php
session_start();
require_once '../../config/connection.php';

if (!isset($_SESSION['hospital_id']) || !isset($_SESSION['odml_id'])) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../pages/change_password.php");
    exit();
}

try {
    $hospital_id = $_SESSION['hospital_id'];
    $odml_id = $_SESSION['odml_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password requirements
    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/';
    
    if (!preg_match($password_pattern, $new_password)) {
        header("Location: ../../pages/change_password.php?error=requirements");
        exit();
    }

    if ($new_password !== $confirm_password) {
        header("Location: ../../pages/change_password.php?error=match");
        exit();
    }

    // If not first login, verify current password
    if (!isset($_GET['first'])) {
        $current_password = $_POST['current_password'];
        
        // Get current password hash
        $stmt = $conn->prepare("SELECT password FROM hospital_login WHERE odml_id = ?");
        $stmt->bind_param("s", $odml_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current = $result->fetch_assoc();

        if (!password_verify($current_password, $current['password'])) {
            header("Location: ../../pages/change_password.php?error=current");
            exit();
        }
    }

    // Update password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE hospital_login SET password = ? WHERE odml_id = ?");
    $stmt->bind_param("ss", $new_password_hash, $odml_id);
    $stmt->execute();

    // Clear temporary password flag if it exists
    if (isset($_SESSION['temp_password'])) {
        unset($_SESSION['temp_password']);
    }

    // Redirect with success message
    header("Location: ../../pages/change_password.php?success=1");
    exit();

} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    header("Location: ../../pages/change_password.php?error=system");
    exit();
}
?>
