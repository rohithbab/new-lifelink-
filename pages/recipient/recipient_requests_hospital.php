<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in as recipient
if (!isset($_SESSION['is_recipient']) || !$_SESSION['is_recipient']) {
    header("Location: ../recipient_login.php");
    exit();
}

// Get recipient info
$recipient_id = $_SESSION['recipient_id'];
$stmt = $conn->prepare("SELECT * FROM recipient_registration WHERE id = ?");
$stmt->execute([$recipient_id]);
$recipient = $stmt->fetch(PDO::FETCH_ASSOC);

// Get hospital info
if (!isset($_GET['hospital_id'])) {
    header("Location: search_hospitals_for_recipient.php");
    exit();
}

$hospital_id = $_GET['hospital_id'];
$stmt = $conn->prepare("SELECT * FROM hospitals WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    header("Location: search_hospitals_for_recipient.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Upload medical reports
        $medical_reports_path = '';
        if (isset($_FILES['medical_reports']) && $_FILES['medical_reports']['error'] === 0) {
            $medical_reports = $_FILES['medical_reports'];
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
            
            if (!in_array($medical_reports['type'], $allowed_types)) {
                throw new Exception("Invalid file type for medical reports. Only PDF, JPEG, and PNG are allowed.");
            }
            
            if ($medical_reports['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception("Medical reports file size must be less than 5MB.");
            }
            
            $filename = uniqid() . '_' . basename($medical_reports['name']);
            $target_path = "../../uploads/hospitals_recipients/medical_reports/" . $filename;
            
            if (!move_uploaded_file($medical_reports['tmp_name'], $target_path)) {
                throw new Exception("Failed to upload medical reports.");
            }
            
            $medical_reports_path = $filename;
        }
        
        // Upload ID proof
        $id_proof_path = '';
        if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === 0) {
            $id_proof = $_FILES['id_proof'];
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
            
            if (!in_array($id_proof['type'], $allowed_types)) {
                throw new Exception("Invalid file type for ID proof. Only PDF, JPEG, and PNG are allowed.");
            }
            
            if ($id_proof['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception("ID proof file size must be less than 5MB.");
            }
            
            $filename = uniqid() . '_' . basename($id_proof['name']);
            $target_path = "../../uploads/hospitals_recipients/id_proof/" . $filename;
            
            if (!move_uploaded_file($id_proof['tmp_name'], $target_path)) {
                throw new Exception("Failed to upload ID proof.");
            }
            
            $id_proof_path = $filename;
        }
        
        // Insert request into database
        $stmt = $conn->prepare("
            INSERT INTO hospital_recipient_approvals 
            (recipient_id, hospital_id, status, request_date, required_organ, blood_group, 
             priority_level, location, medical_reports, id_document)
            VALUES (?, ?, 'Pending', NOW(), ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $recipient_id,
            $hospital_id,
            $recipient['organ_required'],
            $recipient['blood_type'],
            $recipient['urgency_level'],
            $recipient['address'],
            $medical_reports_path,
            $id_proof_path
        ]);
        
        $conn->commit();
        
        // Redirect to dashboard
        header("Location: recipient_dashboard.php?request_sent=1");
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Hospital - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/recipient-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .thank-you-board {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }

        .thank-you-board h2 {
            margin: 0;
            font-size: 24px;
        }

        .thank-you-board p {
            margin: 10px 0 0;
            opacity: 0.9;
        }

        .hospital-details {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .hospital-details h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-item i {
            color: var(--primary-color);
            font-size: 20px;
            width: 24px;
        }

        .request-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .request-form h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background: white;
        }

        .form-info {
            font-size: 0.9em;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            background: #fee;
            color: #e44;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 20px;
        }

        .selected-file {
            margin-top: 8px;
            padding: 8px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            font-size: 0.9em;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-heartbeat"></i>
                <span>LifeLink</span>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="recipient_dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="recipient_personal_details.php">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="search_hospitals_for_recipient.php">
                            <i class="fas fa-search"></i>
                            <span>Search Hospitals</span>
                        </a>
                    </li>
                    <li>
                        <a href="my_requests_for_recipients.php">
                            <i class="fas fa-list"></i>
                            <span>My Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="main-section">
                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="thank-you-board">
                    <h2>Thank You for Choosing <?php echo htmlspecialchars($hospital['name']); ?></h2>
                    <p>Please complete the request form below. We will process your request as soon as possible.</p>
                </div>

                <div class="hospital-details">
                    <h3>Hospital Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <i class="fas fa-hospital"></i>
                            <span><?php echo htmlspecialchars($hospital['name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($hospital['address']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($hospital['phone']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($hospital['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-id-card"></i>
                            <span>License: <?php echo htmlspecialchars($hospital['license_number']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="request-form">
                    <h3>Recipient Request Form</h3>
                    <form action="" method="POST" enctype="multipart/form-data" onsubmit="return confirmSubmission()">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="medical_reports">Upload Medical Reports</label>
                                <input type="file" id="medical_reports" name="medical_reports" accept=".pdf,.jpg,.jpeg,.png" required>
                                <div id="medical_reports_name" class="selected-file"></div>
                                <div class="form-info">Accepted formats: PDF, JPEG, PNG (Max size: 5MB)</div>
                            </div>

                            <div class="form-group">
                                <label for="id_proof">Upload ID Proof</label>
                                <input type="file" id="id_proof" name="id_proof" accept=".pdf,.jpg,.jpeg,.png" required>
                                <div id="id_proof_name" class="selected-file"></div>
                                <div class="form-info">Accepted formats: PDF, JPEG, PNG (Max size: 5MB)</div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-paper-plane"></i>
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Show selected file names
        document.getElementById('medical_reports').addEventListener('change', function(e) {
            document.getElementById('medical_reports_name').textContent = 
                e.target.files[0] ? 'Selected file: ' + e.target.files[0].name : '';
        });

        document.getElementById('id_proof').addEventListener('change', function(e) {
            document.getElementById('id_proof_name').textContent = 
                e.target.files[0] ? 'Selected file: ' + e.target.files[0].name : '';
        });

        // Confirm submission
        function confirmSubmission() {
            return confirm('Are you sure you want to submit this request? Please ensure all documents are correct before proceeding.');
        }
    </script>
</body>
</html>
