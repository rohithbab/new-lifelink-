<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_id = isset($_GET['hospital_id']) ? (int)$_GET['hospital_id'] : 0;
$logged_in_hospital = $_SESSION['hospital_id'];

// Check if trying to view own hospital
if ($hospital_id === $logged_in_hospital) {
    header("Location: choose_donors_for_matches.php");
    exit();
}

if (!$hospital_id) {
    header("Location: choose_donors_for_matches.php");
    exit();
}

// Get hospital details and its donors
try {
    // Get hospital info
    $stmt = $conn->prepare("
        SELECT name, phone, address 
        FROM hospitals 
        WHERE hospital_id = ? AND hospital_id != ?
    ");
    $stmt->execute([$hospital_id, $logged_in_hospital]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hospital) {
        header("Location: choose_donors_for_matches.php");
        exit();
    }

    // Get donors from this hospital only (not from logged in hospital)
    $stmt = $conn->prepare("
        SELECT 
            d.donor_id,
            d.name as donor_name,
            d.blood_group,
            ha.organ_type,
            d.medical_conditions
        FROM donor d
        JOIN hospital_donor_approvals ha ON d.donor_id = ha.donor_id
        WHERE ha.hospital_id = ? 
        AND ha.hospital_id != ?
        AND ha.status = 'Approved'
        ORDER BY d.name ASC
    ");
    $stmt->execute([$hospital_id, $logged_in_hospital]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header("Location: choose_donors_for_matches.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Donors - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .donor-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 2rem;
            padding: 2rem;
        }

        .hospital-info {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .hospital-info h2 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            color: #666;
        }

        .donor-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .donor-table th {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            padding: 1rem;
            text-align: left;
        }

        .donor-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .donor-table tr:hover {
            background: #f8f9fa;
        }

        .request-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .request-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .request-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .back-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            background: #f8f9fa;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .back-btn:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Only show back button and selected hospital's donors -->
            <div class="donor-list">
                <a href="choose_donors_for_matches.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Search
                </a>

                <div class="hospital-info">
                    <h2><?php echo htmlspecialchars($hospital['name']); ?></h2>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <?php echo htmlspecialchars($hospital['phone']); ?>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($hospital['address']); ?>
                    </div>
                </div>

                <?php if (empty($donors)): ?>
                    <div class="text-center">
                        <p>No donors available from this hospital.</p>
                    </div>
                <?php else: ?>
                    <table class="donor-table">
                        <thead>
                            <tr>
                                <th>Donor Name</th>
                                <th>Blood Group</th>
                                <th>Organ Type</th>
                                <th>Medical Conditions</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donors as $donor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($donor['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($donor['blood_group']); ?></td>
                                    <td><?php echo htmlspecialchars($donor['organ_type']); ?></td>
                                    <td><?php echo $donor['medical_conditions'] ? htmlspecialchars($donor['medical_conditions']) : 'None'; ?></td>
                                    <td>
                                        <button class="request-btn" onclick="sendRequest(<?php echo $donor['donor_id']; ?>)">
                                            Request Donor
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function sendRequest(donorId) {
            if (!confirm('Are you sure you want to send a request for this donor?')) {
                return;
            }

            fetch('../../ajax/send_donor_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    donor_id: donorId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Request sent successfully!');
                    location.reload();
                } else {
                    alert('Error sending request: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending the request');
            });
        }
    </script>
</body>
</html>
