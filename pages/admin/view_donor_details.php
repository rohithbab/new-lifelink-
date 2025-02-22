<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Get donor ID from URL
$donor_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$donor_id) {
    header("Location: manage_donors.php");
    exit();
}

// Fetch donor details
$query = "SELECT * FROM donor WHERE donor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();

if (!$donor) {
    header("Location: manage_donors.php");
    exit();
}

// Debug: Print donor data
error_log("Donor data: " . print_r($donor, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Details - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Add your CSS styles here */
        .details-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .back-btn i {
            font-size: 1.1em;
        }
        .document-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 5px;
        }
        .document-link:hover {
            background: #0056b3;
        }
        .document-link i {
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="details-container">
        <a href="manage_donors.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Donors List
        </a>
        
        <div class="section">
            <h2>Personal Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gender:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['gender']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date of Birth:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['dob']); ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Medical Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Blood Group:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['blood_group']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Organs to Donate:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['organs_to_donate']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Medical History:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['medical_history'] ?? 'Not provided'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Current Medications:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['current_medications'] ?? 'None'); ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Documents</h2>
            <div class="info-grid">
                <?php if (!empty($donor['medical_reports_path'])): ?>
                <div class="info-item">
                    <span class="info-label">Medical Reports:</span>
                    <?php
                    error_log("Medical Reports Path: " . $donor['medical_reports_path']);
                    ?>
                    <a href="view_medical_report.php?type=donor&id=<?php echo htmlspecialchars($donor['donor_id']); ?>" class="document-link" target="_blank">
                        <i class="fas fa-file-medical"></i> View Medical Reports
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($donor['id_proof_path'])): ?>
                <div class="info-item">
                    <span class="info-label">ID Proof:</span>
                    <?php
                    error_log("ID Proof Path: " . $donor['id_proof_path']);
                    ?>
                    <a href="view_id_proof.php?donor_id=<?php echo htmlspecialchars($donor['donor_id']); ?>&type=donor" class="document-link" target="_blank">
                        <i class="fas fa-id-card"></i> View ID Proof
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($donor['guardian_id_proof_path'])): ?>
                <div class="info-item">
                    <span class="info-label">Guardian ID Proof:</span>
                    <?php
                    error_log("Guardian ID Proof Path: " . $donor['guardian_id_proof_path']);
                    ?>
                    <a href="view_id_proof.php?donor_id=<?php echo htmlspecialchars($donor['donor_id']); ?>&type=guardian" class="document-link" target="_blank">
                        <i class="fas fa-id-card"></i> View Guardian ID Proof
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Contact Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['phone']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['address']); ?></span>
                </div>
                <?php if (!empty($donor['city'])): ?>
                <div class="info-item">
                    <span class="info-label">City:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['city']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($donor['state'])): ?>
                <div class="info-item">
                    <span class="info-label">State:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['state']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Status Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="status-badge status-<?php echo strtolower($donor['status']); ?>">
                        <?php echo htmlspecialchars($donor['status']); ?>
                    </span>
                </div>
                <?php if ($donor['status'] === 'Rejected' && isset($donor['rejection_reason'])): ?>
                <div class="info-item">
                    <span class="info-label">Rejection Reason:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['rejection_reason']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Registration Date:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['created_at']); ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
