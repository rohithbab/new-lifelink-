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
    // Fetch recipient details
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            hra.status,
            hra.request_date,
            hra.approval_id,
            hra.priority_level,
            hra.id_document,
            hra.medical_reports
        FROM hospital_recipient_approvals hra
        JOIN recipient_registration r ON hra.recipient_id = r.id
        WHERE hra.approval_id = ? AND hra.hospital_id = ?
    ");
    $stmt->execute([$approval_id, $hospital_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        header('Location: hospital_dashboard.php');
        exit();
    }
} catch(PDOException $e) {
    error_log("Error fetching recipient details: " . $e->getMessage());
    header('Location: hospital_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Profile - Hospital Dashboard</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: var(--primary-gradient);
            color: white;
            border-radius: 10px;
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

        .priority-badge {
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            color: white;
        }

        .priority-high {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }

        .priority-medium {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
        }

        .priority-low {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
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

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: white;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .back-button i {
            margin-right: 0.5rem;
        }

        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            color: #20bf55;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Recipient Profile</h1>
            <a href="hospital_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="profile-content">
            <div class="info-group">
                <h2>Personal Information</h2>
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($recipient['full_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($recipient['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact Number:</span>
                    <span class="info-value"><?php echo htmlspecialchars($recipient['phone_number'] ?? 'Not provided'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Location:</span>
                    <span class="info-value"><?php echo htmlspecialchars($recipient['address']); ?></span>
                </div>
            </div>

            <div class="info-group">
                <h2>Medical Information</h2>
                <div class="info-item">
                    <span class="info-label">Blood Group:</span>
                    <span class="info-value"><?php echo htmlspecialchars($recipient['blood_type']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Required Organ:</span>
                    <span class="info-value"><?php echo htmlspecialchars($recipient['organ_required']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Medical Condition:</span>
                    <span class="info-value"><?php echo htmlspecialchars($recipient['medical_condition'] ?? 'Not provided'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Priority Level:</span>
                    <span class="info-value">
                        <span class="priority-badge priority-<?php echo strtolower($recipient['priority_level']); ?>">
                            <?php echo htmlspecialchars($recipient['priority_level']); ?>
                        </span>
                    </span>
                </div>
            </div>

            <div class="info-group">
                <h2>Documents</h2>
                <?php if (!empty($recipient['medical_reports'])): ?>
                <div class="info-item">
                    <span class="info-label">Medical Reports:</span>
                    <a href="../../uploads/hospitals_recipients/medical_reports/<?php echo htmlspecialchars($recipient['medical_reports']); ?>" 
                       target="_blank" 
                       class="document-link">
                        <i class="fas fa-file-medical"></i> View Medical Reports
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($recipient['id_document'])): ?>
                <div class="info-item">
                    <span class="info-label">ID Document:</span>
                    <a href="../../uploads/hospitals_recipients/id_proof/<?php echo htmlspecialchars($recipient['id_document']); ?>" 
                       target="_blank" 
                       class="document-link">
                        <i class="fas fa-id-card"></i> View ID Document
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($recipient['status'] === 'pending'): ?>
                <div class="action-buttons">
                    <button class="btn-action btn-approve" onclick="approveRecipient(<?php echo $recipient['approval_id']; ?>)">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn-action btn-reject" onclick="rejectRecipient(<?php echo $recipient['approval_id']; ?>)">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function approveRecipient(approvalId) {
            if (confirm('Are you sure you want to approve this recipient?')) {
                $.post('approve_recipient_request.php', {
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

        function rejectRecipient(approvalId) {
            const reason = prompt('Please enter a reason for rejection:');
            if (reason) {
                $.post('reject_recipient_request.php', {
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
