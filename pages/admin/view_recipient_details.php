<?php
session_start();
require_once '../../backend/php/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get recipient ID from URL
if (!isset($_GET['id'])) {
    header('Location: manage_recipients.php');
    exit();
}

// Get recipient details
try {
    $stmt = $conn->prepare("
        SELECT * FROM recipient_registration 
        WHERE id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        header('Location: manage_recipients.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching recipient details: " . $e->getMessage());
    header('Location: manage_recipients.php');
    exit();
}

// Get medical reports from the recipient_medical_reports column
$medical_reports = [];
if (!empty($recipient['recipient_medical_reports'])) {
    $medical_reports = explode(',', $recipient['recipient_medical_reports']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Details - LifeLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            position: relative;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .back-btn i {
            margin-right: 8px;
        }

        .details-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 60px;
        }

        .page-title {
            color: #2196F3;
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 600;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid;
            border-image: linear-gradient(to bottom, #4CAF50, #2196F3) 1;
        }

        .section-title {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 6px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            width: 200px;
            flex-shrink: 0;
        }

        .info-value {
            color: #333;
            flex-grow: 1;
        }

        .document-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .document-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .document-link i {
            margin-right: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .medical-reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .report-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .report-card a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #333;
        }

        .report-card i {
            font-size: 2rem;
            color: #1565C0;
            margin-bottom: 0.5rem;
        }

        .report-name {
            text-align: center;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="manage_recipients.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Recipients
        </a>

        <div class="details-container">
            <h1 class="page-title">Recipient Details</h1>

            <!-- Personal Information -->
            <div class="section">
                <h2 class="section-title">Personal Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['full_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['date_of_birth']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['gender']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['phone_number']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['address']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Medical Information -->
            <div class="section">
                <h2 class="section-title">Medical Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Medical Condition</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['medical_condition']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Blood Type</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['blood_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Required Organ</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['organ_required']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Organ Requirement Reason</div>
                        <div class="info-value"><?php echo htmlspecialchars($recipient['organ_reason']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Urgency Level</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo strtolower($recipient['urgency_level']); ?>">
                                <?php echo htmlspecialchars($recipient['urgency_level']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo strtolower($recipient['request_status']); ?>">
                                <?php echo htmlspecialchars($recipient['request_status'] === 'accepted' ? 'Approved' : ucfirst($recipient['request_status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="section">
                <h2 class="section-title">Documents</h2>
                
                <!-- ID Proof -->
                <div class="info-item">
                    <h3>ID Proof</h3>
                    <?php if (!empty($recipient['id_document'])): ?>
                        <a href="../../uploads/recipient_registration/id_document/<?php echo htmlspecialchars($recipient['id_document']); ?>" 
                           target="_blank" class="document-link">
                            <i class="fas fa-id-card"></i> View ID Document
                        </a>
                    <?php else: ?>
                        <p>No ID proof uploaded</p>
                    <?php endif; ?>
                </div>

                <!-- Medical Reports -->
                <div class="info-item">
                    <div class="info-label">Medical Reports</div>
                    <div class="medical-reports-grid">
                        <?php if (!empty($medical_reports)): ?>
                            <?php foreach ($medical_reports as $report): 
                                $report = trim($report);
                                if (!empty($report)):
                            ?>
                                <div class="report-card">
                                    <a href="../../uploads/recipient_registration/recipient_medical_reports/<?php echo htmlspecialchars($report); ?>" 
                                       target="_blank">
                                        <i class="fas fa-file-medical"></i>
                                        <span class="report-name"><?php echo htmlspecialchars($report); ?></span>
                                    </a>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        <?php else: ?>
                            <div class="info-value">No medical reports uploaded</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
