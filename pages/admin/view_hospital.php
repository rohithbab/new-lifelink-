<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Get hospital ID from URL
$hospital_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$hospital_id) {
    header("Location: manage_hospitals.php");
    exit();
}

// Fetch hospital details
$query = "SELECT * FROM hospitals WHERE hospital_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$result = $stmt->get_result();
$hospital = $result->fetch_assoc();

// Get rejection details if available
$rejection_details = isset($_SESSION['rejection_details'][$hospital_id]) ? $_SESSION['rejection_details'][$hospital_id] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Hospital - LifeLink Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 20px;
        }
        
        .hospital-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .detail-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            color: #1a73e8;
            margin-bottom: 15px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .pending { background-color: #ffd700; color: #000; }
        .approved { background-color: #34a853; color: white; }
        .rejected { background-color: #dc3545; color: white; }
        
        .license-preview {
            max-width: 100%;
            margin-top: 10px;
        }
        
        .rejection-details {
            background-color: #fff3f3;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="manage_hospitals.php" class="back-button">
            <i class="fas fa-arrow-left"></i>&nbsp; Back to Hospitals
        </a>
        
        <div class="hospital-details">
            <div class="detail-section">
                <h2 class="section-title">Hospital Information</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Hospital Name</div>
                        <div><?php echo htmlspecialchars($hospital['name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div>
                            <span class="status-badge <?php echo $hospital['status']; ?>">
                                <?php echo ucfirst($hospital['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div><?php echo htmlspecialchars($hospital['email']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div><?php echo htmlspecialchars($hospital['phone']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Address</div>
                        <div><?php echo htmlspecialchars($hospital['address']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Registration Date</div>
                        <div><?php echo date('F j, Y', strtotime($hospital['created_at'])); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h2 class="section-title">License Information</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">License Number</div>
                        <div><?php echo htmlspecialchars($hospital['license_number']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">License Document</div>
                        <div>
                            <?php if (!empty($hospital['license_file'])): ?>
                                <a href="../../uploads/hospitals/license_file/<?php echo htmlspecialchars($hospital['license_file']); ?>" 
                                   target="_blank" class="view-btn">
                                    <i class="fas fa-file-alt"></i> View License Document
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No license document uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($hospital['status'] === 'rejected' && $rejection_details): ?>
            <div class="detail-section">
                <h2 class="section-title">Rejection Details</h2>
                <div class="rejection-details">
                    <div class="detail-item">
                        <div class="detail-label">Rejection Date</div>
                        <div><?php echo date('F j, Y', strtotime($rejection_details['date'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Reason for Rejection</div>
                        <div><?php echo htmlspecialchars($rejection_details['reason']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email Notification</div>
                        <div>
                            <?php echo $rejection_details['email_sent'] ? 
                                '<i class="fas fa-check-circle" style="color: #34a853;"></i> Sent' : 
                                '<i class="fas fa-times-circle" style="color: #dc3545;"></i> Not Sent'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
