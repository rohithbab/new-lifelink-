<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get the type and ID from URL
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Function to get hospital details
function getHospitalDetails($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE hospital_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get donor details
function getDonorDetails($conn, $id) {
    $stmt = $conn->prepare("SELECT d.*, h.hospital_name FROM donor d 
                           LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id 
                           WHERE d.donor_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get recipient details
function getRecipientDetails($conn, $id) {
    $stmt = $conn->prepare("SELECT r.*, h.hospital_name FROM recipient_registration r 
                           LEFT JOIN hospitals h ON r.hospital_id = h.hospital_id 
                           WHERE r.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get details based on type
$details = null;
$title = '';
switch($type) {
    case 'hospital':
        $details = getHospitalDetails($conn, $id);
        $title = 'Hospital Details';
        break;
    case 'donor':
        $details = getDonorDetails($conn, $id);
        $title = 'Donor Details';
        break;
    case 'recipient':
        $details = getRecipientDetails($conn, $id);
        $title = 'Recipient Details';
        break;
    default:
        header('Location: admin_dashboard.php');
        exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - LifeLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }
        .details-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .details-header h1 {
            color: #2c3e50;
            margin: 0;
        }
        .back-button {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #2980b9;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .detail-item {
            margin-bottom: 15px;
        }
        .detail-label {
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .detail-value {
            color: #2c3e50;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        @media (max-width: 600px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="details-container">
        <div class="details-header">
            <h1><?php echo $title; ?></h1>
            <a href="admin_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="details-grid">
            <?php if ($details): ?>
                <?php foreach ($details as $key => $value): ?>
                    <?php if ($key !== 'password' && $key !== 'hospital_id' && $key !== 'donor_id' && $key !== 'id'): ?>
                        <div class="detail-item">
                            <div class="detail-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?></div>
                            <div class="detail-value">
                                <?php 
                                if ($value === null || $value === '') {
                                    echo 'N/A';
                                } else if (is_string($value) && (strpos($value, '.jpg') !== false || 
                                         strpos($value, '.png') !== false || 
                                         strpos($value, '.pdf') !== false)) {
                                    echo '<a href="../../uploads/' . $value . '" target="_blank">View File</a>';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <p>No details found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
