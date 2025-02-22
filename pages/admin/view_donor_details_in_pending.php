<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get donor ID from URL
$donor_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$donor_id) {
    header('Location: admin_dashboard.php');
    exit();
}

// Get donor details
try {
    $stmt = $conn->prepare("SELECT * FROM donor WHERE donor_id = ? AND status = 'pending'");
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        header('Location: admin_dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching donor details: " . $e->getMessage());
    header('Location: admin_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Details - LifeLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <style>
        .details-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary-blue);
        }
        .detail-item h3 {
            color: var(--primary-blue);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .detail-item p {
            color: #333;
            margin: 0;
        }
        .view-document {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-blue);
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .view-document:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
        .view-document i {
            font-size: 0.9rem;
        }
        .action-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .approve-button {
            background-color: #28a745;
            color: white;
        }
        .reject-button {
            background-color: #dc3545;
            color: white;
        }
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            transition: all 0.3s ease;
            margin-right: 15px;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .back-btn i {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><span class="logo-gradient">LifeLink</span> Admin</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <!-- Other sidebar items -->
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="details-container">
                <div class="details-header">
                    <h2>Donor Details</h2>
                    <div class="action-buttons">
                        <a href="admin_dashboard.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <button class="action-button approve-button" onclick="updateDonorStatus('<?php echo htmlspecialchars($donor['donor_id']); ?>', 'Approved')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="action-button reject-button" onclick="updateDonorStatus('<?php echo htmlspecialchars($donor['donor_id']); ?>', 'Rejected')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <h3>Full Name</h3>
                        <p><?php echo htmlspecialchars($donor['name']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Email</h3>
                        <p><?php echo htmlspecialchars($donor['email']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Blood Type</h3>
                        <p><?php echo htmlspecialchars($donor['blood_group'] ?? 'Not specified'); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Phone</h3>
                        <p><?php echo htmlspecialchars($donor['phone']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Gender</h3>
                        <p><?php echo htmlspecialchars($donor['gender']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Date of Birth</h3>
                        <p><?php echo htmlspecialchars($donor['dob']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Address</h3>
                        <p><?php echo htmlspecialchars($donor['address']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Organs to Donate</h3>
                        <p><?php echo htmlspecialchars($donor['organs_to_donate']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Reason for Donation</h3>
                        <p><?php echo htmlspecialchars($donor['reason_for_donation'] ?? 'Not specified'); ?></p>
                    </div>
                    <?php if (!empty($donor['medical_conditions'])): ?>
                    <div class="detail-item">
                        <h3>Medical Conditions</h3>
                        <p><?php echo htmlspecialchars($donor['medical_conditions']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="detail-item">
                        <h3>ID Proof</h3>
                        <p>
                            <a href="../../uploads/donors/id_proof_path/<?php echo htmlspecialchars($donor['id_proof_path']); ?>" 
                               target="_blank" class="view-document">
                                View ID Proof <i class="fas fa-external-link-alt"></i>
                            </a>
                        </p>
                    </div>
                    <?php if (!empty($donor['medical_reports_path'])): ?>
                    <div class="detail-item">
                        <h3>Medical Reports</h3>
                        <p>
                            <a href="../../uploads/donors/medical_reports_path/<?php echo htmlspecialchars($donor['medical_reports_path']); ?>" 
                               target="_blank" class="view-document">
                                View Medical Reports <i class="fas fa-external-link-alt"></i>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($donor['guardian_name'])): ?>
                    <div class="detail-item">
                        <h3>Guardian Name</h3>
                        <p><?php echo htmlspecialchars($donor['guardian_name']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Guardian Email</h3>
                        <p><?php echo htmlspecialchars($donor['guardian_email']); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Guardian Phone</h3>
                        <p><?php echo htmlspecialchars($donor['guardian_phone']); ?></p>
                    </div>
                    <?php if (!empty($donor['guardian_id_proof_path'])): ?>
                    <div class="detail-item">
                        <h3>Guardian ID Proof</h3>
                        <p>
                            <a href="../../uploads/donors/guardian_id_proof_path/<?php echo htmlspecialchars($donor['guardian_id_proof_path']); ?>" 
                               target="_blank" class="view-document">
                                View Guardian ID Proof <i class="fas fa-external-link-alt"></i>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <div class="detail-item">
                        <h3>ODML ID</h3>
                        <p><?php echo htmlspecialchars($donor['odml_id'] ?? 'Not assigned'); ?></p>
                    </div>
                    <div class="detail-item">
                        <h3>Registration Date</h3>
                        <p><?php echo htmlspecialchars($donor['created_at']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function updateDonorStatus(donorId, status) {
            // Capitalize first letter of status
            status = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
            
            if (confirm(`Are you sure you want to ${status.toLowerCase()} this donor?`)) {
                $.ajax({
                    url: '../../backend/php/update_donor_status.php',
                    method: 'POST',
                    data: JSON.stringify({
                        donor_id: donorId,
                        status: status
                    }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Response:', response); // Debug log
                        if (response.success) {
                            alert(`Donor ${status.toLowerCase()} successfully`);
                            window.location.href = 'admin_dashboard.php';
                        } else {
                            alert('Failed to update status: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText); // Debug log
                        alert('Error updating donor status. Please check the console for details.');
                    }
                });
            }
        }
    </script>
</body>
</html>
