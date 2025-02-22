<?php
session_start();
if (!isset($_SESSION['registration_success']) || !isset($_SESSION['recipient_email'])) {
    header("Location: recipient_registration.php");
    exit();
}

// Get the ODML ID from the database
require_once '../backend/php/connection.php';
try {
    $stmt = $conn->prepare("SELECT odml_id FROM recipient_registration WHERE email = ?");
    $stmt->execute([$_SESSION['recipient_email']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $odml_id = $result['odml_id'];
} catch(PDOException $e) {
    $odml_id = "Error retrieving ODML ID";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Success</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .important-note {
            color: #dc3545;
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #dc3545;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <h1>Registration Successful!</h1>
        <p>Thank you for registering with LifeLink. Your registration is currently under review.</p>
        
        <div class="important-note">
            <h3>Important!</h3>
            <p>Please check your email for your ODML ID which you will need for logging in.</p>
        </div>

        <p>Once your registration is approved, you can log in using:</p>
        <ul style="text-align: left; list-style: none;">
            <li>✉️ Your email address</li>
            <li>🔑 Your ODML ID (sent to your email)</li>
            <li>🔒 Your password</li>
        </ul>

        <p>We will notify you by email once your registration has been reviewed.</p>
        
        <a href="recipient_login.php" class="btn">Go to Login</a>
    </div>
</body>
</html>
<?php
// Clear the session variables
unset($_SESSION['registration_success']);
unset($_SESSION['recipient_email']);
?>
