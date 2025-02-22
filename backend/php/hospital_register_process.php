<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log file for debugging
$log_file = __DIR__ . '/debug.log';
function debug_log($message) {
    global $log_file;
    $log_message = date('Y-m-d H:i:s') . ': ' . (is_array($message) ? print_r($message, true) : $message) . "\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

debug_log('Registration process started');
debug_log('POST data: ' . print_r($_POST, true));
debug_log('FILES data: ' . print_r($_FILES, true));

require_once '../../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    debug_log('Not a POST request');
    header("Location: ../../pages/hospital_registration.php");
    exit();
}

try {
    // Validate database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    debug_log('Database connection successful');

    // Get and sanitize input
    $name = trim(filter_var($_POST['hospital_name'] ?? '', FILTER_SANITIZE_STRING));
    $email = trim(filter_var($_POST['hospital_email'] ?? '', FILTER_SANITIZE_EMAIL));
    $phone = trim(filter_var($_POST['hospital_phone'] ?? '', FILTER_SANITIZE_STRING));
    $license_number = trim(filter_var($_POST['license_number'] ?? '', FILTER_SANITIZE_STRING));
    $password = $_POST['password'] ?? '';
    
    // Combine address fields
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    $address = implode(', ', array_filter([$street, $city, $state, $postal_code, $country]));
    $region = $state;

    debug_log("Sanitized inputs: name=$name, email=$email, phone=$phone");
    debug_log("Address: $address");
    debug_log("Region: $region");

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($address) || 
        empty($license_number) || empty($password)) {
        throw new Exception("All fields are required");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT hospital_id FROM hospitals WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("This email is already registered");
    }
    $stmt->close();

    // Check if license number already exists
    $stmt = $conn->prepare("SELECT hospital_id FROM hospitals WHERE license_number = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $license_number);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("This license number is already registered");
    }
    $stmt->close();

    // Handle file upload
    if (!isset($_FILES['license_document']) || $_FILES['license_document']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception("Please upload your license document");
    }

    $upload_dir = __DIR__ . '/../../uploads/hospitals/license_file/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['license_document'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file extension
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception("Only PDF, JPG, JPEG, and PNG files are allowed");
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception("Failed to upload license document");
    }
    
    chmod($target_path, 0666); // Set proper permissions

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Start transaction
    $conn->begin_transaction();
    debug_log("Transaction started");

    try {
        // Insert into hospitals table
        $sql = "INSERT INTO hospitals (
            name, email, phone, address, region,
            license_number, license_file, password,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        debug_log("SQL prepared: $sql");

        $stmt->bind_param("ssssssss",
            $name,
            $email,
            $phone,
            $address,
            $region,
            $license_number,
            $filename,
            $hashed_password
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to register hospital: " . $stmt->error);
        }

        debug_log("SQL executed successfully");

        $hospital_id = $conn->insert_id;
        debug_log('Hospital data inserted successfully. ID: ' . $hospital_id);

        // Create notification for new hospital registration
        $sql = "INSERT INTO notifications (
            type, action, entity_id, message, is_read, created_at, link_url
        ) VALUES (
            'hospital', 'registered', ?, ?, 0, NOW(), ?
        )";

        $message = sprintf(
            "New hospital registration: %s",
            $name
        );

        $link_url = "manage_hospitals.php?id=" . $hospital_id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss",
            $hospital_id,
            $message,
            $link_url
        );
        $stmt->execute();

        debug_log('Notification created successfully');

        // Commit transaction
        $conn->commit();
        debug_log("Transaction committed");

        // Set success session variables
        $_SESSION['registration_success'] = true;
        $_SESSION['hospital_name'] = $name;

        // Redirect to success page
        header("Location: ../../pages/hospital_registration_success.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        debug_log("Transaction rolled back: " . $e->getMessage());
        
        // Delete uploaded file if exists
        if (isset($target_path) && file_exists($target_path)) {
            unlink($target_path);
            debug_log("Uploaded file deleted due to error");
        }
        
        throw $e;
    }

} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../../pages/hospital_registration.php");
    exit();
}
?>
