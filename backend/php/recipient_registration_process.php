<?php
session_start();
require_once 'connection.php';
require_once 'queries.php';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to handle file upload
function handle_file_upload($file, $target_dir) {
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Check if file was actually uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        throw new Exception("No file uploaded.");
    }
    
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    // Generate unique filename
    $filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $filename;
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "pdf") {
        throw new Exception("Only JPG, JPEG, PNG & PDF files are allowed.");
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        chmod($target_file, 0666); // Set proper permissions
        return $filename;
    } else {
        throw new Exception("Error uploading file: " . error_get_last()['message']);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get and sanitize input
        $full_name = sanitize_input($_POST['fullName']);
        $date_of_birth = sanitize_input($_POST['dob']);
        $gender = sanitize_input($_POST['gender']);
        $phone_number = sanitize_input($_POST['phone']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $address = sanitize_input($_POST['address']);
        $medical_condition = sanitize_input($_POST['medicalCondition']);
        $blood_type = sanitize_input($_POST['bloodType']);
        $organ_required = sanitize_input($_POST['organRequired']);
        $organ_reason = sanitize_input($_POST['organReason']);
        $urgency_level = sanitize_input($_POST['urgencyLevel']); // Added urgency level
        $id_proof_type = sanitize_input($_POST['idType']);
        $id_proof_number = sanitize_input($_POST['idNumber']);
        
        // Handle file uploads with new directory structure
        $base_upload_dir = __DIR__ . '/../../uploads/recipient_registration/';
        
        // Handle medical reports
        $recipient_medical_reports = null;
        if (isset($_FILES['medical_reports'])) {
            $recipient_medical_reports = handle_file_upload(
                $_FILES['medical_reports'], 
                $base_upload_dir . 'recipient_medical_reports/'
            );
        }
        
        // Handle ID document
        $id_document = null;
        if (isset($_FILES['id_document'])) {
            $id_document = handle_file_upload(
                $_FILES['id_document'], 
                $base_upload_dir . 'id_document/'
            );
        }

        // Generate username from email (part before @)
        $username = explode('@', $email)[0];
        // Add random number if username exists
        $stmt = $conn->prepare("SELECT id FROM recipient_registration WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $username .= rand(100, 999);
        }

        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM recipient_registration WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Email already registered.");
        }

        // Begin transaction
        $conn->beginTransaction();

        // Insert into recipient_registration table
        $sql = "INSERT INTO recipient_registration (
            username, password, full_name, date_of_birth, gender, phone_number, 
            email, address, medical_condition, blood_type, organ_required, 
            organ_reason, urgency_level, id_proof_type, id_proof_number, 
            id_document, recipient_medical_reports
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $username, $password, $full_name, $date_of_birth, $gender, $phone_number,
            $email, $address, $medical_condition, $blood_type, $organ_required,
            $organ_reason, $urgency_level, $id_proof_type, $id_proof_number,
            $id_document, $recipient_medical_reports
        ]);

        if ($stmt->rowCount() > 0) {
            // Get the new recipient's ID
            $recipient_id = $conn->lastInsertId();
            
            // Create notification
            createNotification(
                $conn,
                'recipient',
                'registered',
                $recipient_id,
                "New recipient registration: $full_name"
            );

            $conn->commit();
            $_SESSION['registration_success'] = true;
            $_SESSION['recipient_email'] = $email;
            header("Location: ../../pages/recipient/recipient_registration_success.php");
            exit();
        }

    } catch(Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../../pages/recipient_registration.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: ../../pages/recipient_registration.php");
    exit();
}
