<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in as donor
if (!isset($_SESSION['donor_id'])) {
    header("Location: ../donor_login.php");
    exit();
}

$donor_id = $_SESSION['donor_id'];

// Fetch donor details
try {
    $stmt = $conn->prepare("SELECT name, blood_group FROM donor WHERE donor_id = ?");
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        die("Donor not found");
    }
} catch(PDOException $e) {
    error_log("Error fetching donor details: " . $e->getMessage());
    die("An error occurred");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/donor-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-badge.approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-badge.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .blood-type {
            background-color: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        .table-responsive {
            margin: 20px 0;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }
        .modern-table th, .modern-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .modern-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .modern-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .no-data i {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
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
                        <a href="my_requests_for_donors.php" class="active">
                            <i class="fas fa-list"></i>
                            <span>My Requests</span>
                        </a>
                    </li>
                    <li>
                    <a href="donors_notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'donors_notifications.php' ? 'active' : ''; ?>">
                     
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                            <span class="notification-badge">2</span>
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
                <header class="dashboard-header">
                    <div class="header-left">
                        <h1>My Requests</h1>
                    </div>
                </header>

                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-clipboard-list"></i> Approved & Rejected Requests</h2>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Donor Name</th>
                                    <th>Blood Type</th>
                                    <th>Organ Type</th>
                                    <th>Hospital Name</th>
                                    <th>Hospital Email</th>
                                    <th>Hospital Address</th>
                                    <th>Hospital Number</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch approved and rejected requests
                                $stmt = $conn->prepare("
                                    SELECT hda.*, h.name as hospital_name, h.email as hospital_email, 
                                           h.address as hospital_address, h.phone as hospital_phone,
                                           d.name as donor_name, d.blood_group
                                    FROM hospital_donor_approvals hda
                                    JOIN hospitals h ON hda.hospital_id = h.hospital_id
                                    JOIN donor d ON hda.donor_id = d.donor_id
                                    WHERE hda.donor_id = ? 
                                    AND hda.status IN ('Approved', 'Rejected')
                                    ORDER BY hda.request_date DESC");
                                
                                $stmt->execute([$donor_id]);
                                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($requests) > 0) {
                                    foreach ($requests as $request) {
                                        $status_class = strtolower($request['status']);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['donor_name']); ?></td>
                                            <td>
                                                <span class="blood-type"><?php echo htmlspecialchars($request['blood_group']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['organ_type']); ?></td>
                                            <td><?php echo htmlspecialchars($request['hospital_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['hospital_email']); ?></td>
                                            <td><?php echo htmlspecialchars($request['hospital_address']); ?></td>
                                            <td><?php echo htmlspecialchars($request['hospital_phone']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($request['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="8" class="no-data">
                                            <i class="fas fa-info-circle"></i>
                                            <p>No approved or rejected requests found</p>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
