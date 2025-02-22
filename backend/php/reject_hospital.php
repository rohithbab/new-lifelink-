<?php
session_start();
require_once '../../config/connection.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $hospitalId = $data['hospitalId'];

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
    $stmt = $conn->prepare("UPDATE hospitals SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $hospitalId);
    $stmt->execute();

    // Send rejection email
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
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
    $mail->Subject = 'LifeLink Hospital Registration Status';
    $mail->Body = "
        <p>Dear {$hospital['name']},</p>
        <p>We regret to inform you that your hospital registration application for LifeLink has been rejected.</p>
        <p>If you believe this is an error or would like to submit a new application with updated information, 
        please contact our support team.</p>
        <p>Best regards,<br>LifeLink Team</p>
    ";

    $mail->send();
    
    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Hospital rejected successfully'
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
