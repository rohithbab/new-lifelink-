<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in as donor
if (!isset($_SESSION['is_donor']) || !$_SESSION['is_donor']) {
    header("Location: ../donor_login.php");
    exit();
}

// Get donor info
$donor_id = $_SESSION['donor_id'];
$stmt = $conn->prepare("SELECT * FROM donor WHERE donor_id = ?");
$stmt->execute([$donor_id]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

// Get hospital info
if (!isset($_GET['hospital_id'])) {
    header("Location: search_hospitals_for_donors.php");
    exit();
}

$hospital_id = $_GET['hospital_id'];
$stmt = $conn->prepare("SELECT * FROM hospitals WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    header("Location: search_hospitals_for_donors.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $organ_type = $_POST['organ_type'];
        
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
            $target_path = "../../uploads/hospitals_donors/medical_reports/" . $filename;
            
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
            $target_path = "../../uploads/hospitals_donors/id_proof/" . $filename;
            
            if (!move_uploaded_file($id_proof['tmp_name'], $target_path)) {
                throw new Exception("Failed to upload ID proof.");
            }
            
            $id_proof_path = $filename;
        }
        
        // Insert request into database
        error_log("Submitting request - Donor ID: " . $donor_id . ", Hospital ID: " . $hospital_id);
        
        $stmt = $conn->prepare("
            INSERT INTO hospital_donor_approvals 
            (donor_id, hospital_id, organ_type, blood_group, medical_reports, id_proof, status, request_date)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        
        $stmt->execute([
            $donor_id,
            $hospital_id,
            $organ_type,
            $donor['blood_group'],
            $medical_reports_path,
            $id_proof_path
        ]);
        
        error_log("Request submitted successfully");
        
        $conn->commit();
        
        // Redirect to dashboard
        header("Location: donor_dashboard.php?request_sent=1");
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
    <link rel="stylesheet" href="../../assets/css/donor-dashboard.css">
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

        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group select:focus,
        .form-group input[type="file"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-input-button {
            display: block;
            padding: 10px;
            background: rgba(var(--primary-color-rgb), 0.1);
            color: var(--primary-color);
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-button:hover {
            background: rgba(var(--primary-color-rgb), 0.2);
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .error-message {
            background: #ffe5e5;
            color: #d63031;
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
                        <a href="donor_dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="donor_personal_details.php">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="search_hospitals_for_donors.php">
                            <i class="fas fa-search"></i>
                            <span>Search Hospitals</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" id="myRequestsBtn">
                            <i class="fas fa-clipboard-list"></i>
                            <span>My Requests</span>
                            <span class="notification-badge">2</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" id="notificationsBtn">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                            <span class="notification-badge">3</span>
                        </a>
                    </li>
                    <li>
                        <a href="../donor_login.php">
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
                    <p>Please fill out the request form below to proceed with your organ donation request.</p>
                </div>

                <div class="hospital-details">
                    <h3>Hospital Details</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <i class="fas fa-hospital"></i>
                            <span><?php echo htmlspecialchars($hospital['name']); ?></span>
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
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($hospital['address']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map"></i>
                            <span><?php echo htmlspecialchars($hospital['region']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-id-card"></i>
                            <span>License: <?php echo htmlspecialchars($hospital['license_number']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="request-form">
                    <h3>Donor Request Form</h3>
                    <form action="" method="POST" enctype="multipart/form-data" onsubmit="return confirmSubmission()">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="donor_name">Donor Name</label>
                                <input type="text" id="donor_name" value="<?php echo htmlspecialchars($donor['name']); ?>" disabled class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="blood_group">Blood Group</label>
                                <input type="text" id="blood_group" value="<?php echo htmlspecialchars($donor['blood_group']); ?>" disabled class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="organ_type">Organ to Donate</label>
                            <select name="organ_type" id="organ_type" required>
                                <option value="">Select Organ</option>
                                <option value="Kidney">Kidney</option>
                                <option value="Liver">Liver</option>
                                <option value="Heart">Heart</option>
                                <option value="Lungs">Lungs</option>
                                <option value="Pancreas">Pancreas</option>
                                <option value="Intestines">Intestines</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="medical_reports">Medical Reports (PDF, JPEG, PNG - Max 5MB)</label>
                            <div class="file-input-wrapper">
                                <div class="file-input-button">
                                    <i class="fas fa-upload"></i> Choose Medical Reports
                                </div>
                                <input type="file" name="medical_reports" id="medical_reports" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <small id="medical_reports_name"></small>
                        </div>

                        <div class="form-group">
                            <label for="id_proof">ID Proof (PDF, JPEG, PNG - Max 5MB)</label>
                            <div class="file-input-wrapper">
                                <div class="file-input-button">
                                    <i class="fas fa-upload"></i> Choose ID Proof
                                </div>
                                <input type="file" name="id_proof" id="id_proof" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <small id="id_proof_name"></small>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Show selected file names
        document.getElementById('medical_reports').addEventListener('change', function(e) {
            document.getElementById('medical_reports_name').textContent = e.target.files[0]?.name || '';
        });

        document.getElementById('id_proof').addEventListener('change', function(e) {
            document.getElementById('id_proof_name').textContent = e.target.files[0]?.name || '';
        });

        // Confirm form submission
        function confirmSubmission() {
            return confirm('Are you sure you want to submit this organ donation request? This action cannot be undone.');
        }
    </script>
</body>
</html>
