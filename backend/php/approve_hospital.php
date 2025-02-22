<?php
session_start();
require_once '../../config/connection.php';
require '../../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    $hospitalId = $_POST['hospitalId'];
    $odmlId = $_POST['odmlId'];

    // Start transaction
    $conn->begin_transaction();

    // Get hospital details
    $stmt = $conn->prepare("SELECT email, name FROM hospitals WHERE id = ?");
    $stmt->bind_param("i", $hospitalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospital = $result->fetch_assoc();

    if (!$hospital) {
        throw new Exception("Hospital not found");
    }

    // Update hospital status
    $stmt = $conn->prepare("UPDATE hospitals SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $hospitalId);
    $stmt->execute();

    // Create login credentials
    $stmt = $conn->prepare("INSERT INTO hospital_login (hospital_id, email, odml_id, password) VALUES (?, ?, ?, ?)");
    $tempPassword = bin2hex(random_bytes(8)); // Generate temporary password
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
    $stmt->bind_param("isss", $hospitalId, $hospital['email'], $odmlId, $hashedPassword);
    $stmt->execute();

    // Send email with credentials
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Update with your SMTP host
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com'; // Update with your email
    $mail->Password = 'your-app-password'; // Update with your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('your-email@gmail.com', 'LifeLink Admin');
    $mail->addAddress($hospital['email'], $hospital['name']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'LifeLink Hospital Registration Approved';
    $mail->Body = "
        <h2>Welcome to LifeLink!</h2>
        <p>Dear {$hospital['name']},</p>
        <p>Your hospital registration has been approved. Here are your login credentials:</p>
        <p><strong>ODML ID:</strong> {$odmlId}</p>
        <p><strong>Temporary Password:</strong> {$tempPassword}</p>
        <p>Please login and change your password immediately.</p>
        <p>Login URL: <a href='http://your-domain.com/hospital-login.php'>Click here to login</a></p>
        <p>Best regards,<br>LifeLink Team</p>
    ";

    $mail->send();
    
    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Hospital approved successfully and credentials sent'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
