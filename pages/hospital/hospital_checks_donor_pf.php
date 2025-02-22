<?php
session_start();
require_once '../../config/db_connect.php';

if (!isset($_SESSION['hospital_id']) || !isset($_GET['id'])) {
    header('Location: hospital_dashboard.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$approval_id = $_GET['id'];

try {
    // Fetch donor details
    $stmt = $conn->prepare("
        SELECT 
            d.*,
            hda.status,
            hda.request_date,
            hda.approval_id,
            hda.organ_type,
            hda.medical_reports,
            hda.id_proof
        FROM hospital_donor_approvals hda
        JOIN donor d ON hda.donor_id = d.donor_id
        WHERE hda.approval_id = ? AND hda.hospital_id = ?
    ");
    $stmt->execute([$approval_id, $hospital_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        header('Location: hospital_dashboard.php');
        exit();
    }
} catch(PDOException $e) {
    error_log("Error fetching donor details: " . $e->getMessage());
    header('Location: hospital_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Profile - Hospital Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(45deg, #20bf55, #01baef);
        }

        body {
            background-color: #f8f9fa;
        }

        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-header {
            background: var(--primary-gradient);
            padding: 30px;
            color: white;
            text-align: center;
            position: relative;
        }

        .profile-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .profile-content {
            padding: 30px;
        }

        .info-group {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .info-group h2 {
            color: #2C3E50;
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .info-item {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .info-label {
            font-weight: 600;
            color: #2C3E50;
            width: 150px;
            min-width: 150px;
        }

        .info-value {
            color: #2C3E50;
            flex: 1;
        }

        .document-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .document-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-approve {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
        }

        .btn-reject {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Back Button */
        .back-button {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Donor Profile</h1>
            <a href="hospital_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="profile-content">
            <div class="info-group">
                <h2>Personal Information</h2>
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['phone']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Location:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['address']); ?></span>
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

            <div class="info-group">
                <h2>Medical Information</h2>
                <div class="info-item">
                    <span class="info-label">Blood Group:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['blood_group']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Organ Type:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['organ_type']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Medical Conditions:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['medical_conditions'] ?? 'None'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Reason for Donation:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['reason_for_donation']); ?></span>
                </div>
            </div>

            <div class="info-group">
                <h2>Guardian Information</h2>
                <div class="info-item">
                    <span class="info-label">Guardian Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['guardian_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Guardian Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['guardian_email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Guardian Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($donor['guardian_phone']); ?></span>
                </div>
            </div>

            <div class="info-group">
                <h2>Documents</h2>
                <?php if (!empty($donor['medical_reports'])): ?>
                <div class="info-item">
                    <span class="info-label">Medical Reports:</span>
                    <a href="../../uploads/hospitals_donors/medical_reports/<?php echo htmlspecialchars($donor['medical_reports']); ?>" 
                       target="_blank" 
                       class="document-link">
                        <i class="fas fa-file-medical"></i> View Medical Reports
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($donor['id_proof'])): ?>
                <div class="info-item">
                    <span class="info-label">ID Proof:</span>
                    <a href="../../uploads/hospitals_donors/id_proof/<?php echo htmlspecialchars($donor['id_proof']); ?>" 
                       target="_blank" 
                       class="document-link">
                        <i class="fas fa-id-card"></i> View ID Proof
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($donor['status'] === 'pending'): ?>
            <div class="action-buttons">
                <button class="btn-action btn-approve" onclick="approveDonor(<?php echo $donor['approval_id']; ?>)">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button class="btn-action btn-reject" onclick="rejectDonor(<?php echo $donor['approval_id']; ?>)">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function approveDonor(approvalId) {
            if (confirm('Are you sure you want to approve this donor?')) {
                $.post('approve_donor_request.php', {
                    approval_id: approvalId,
                    action: 'approve'
                }, function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                });
            }
        }

        function rejectDonor(approvalId) {
            const reason = prompt('Please enter a reason for rejection:');
            if (reason) {
                $.post('reject_donor_request.php', {
                    approval_id: approvalId,
                    reason: reason,
                    action: 'reject'
                }, function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
