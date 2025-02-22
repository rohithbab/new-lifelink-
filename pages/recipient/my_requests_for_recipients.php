<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in as recipient
if (!isset($_SESSION['recipient_id'])) {
    header("Location: ../recipient_login.php");
    exit();
}

$recipient_id = $_SESSION['recipient_id'];

// Fetch recipient details
try {
    $stmt = $conn->prepare("SELECT full_name, blood_type FROM recipient_registration WHERE id = ?");
    $stmt->execute([$recipient_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        die("Recipient not found");
    }
} catch(PDOException $e) {
    error_log("Error fetching recipient details: " . $e->getMessage());
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
        .priority-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            text-transform: capitalize;
        }
        .priority-high {
            background-color: #f8d7da;
            color: #721c24;
        }
        .priority-medium {
            background-color: #fff3cd;
            color: #856404;
        }
        .priority-low {
            background-color: #d4edda;
            color: #155724;
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
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #20bf55, #01baef);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #2C3E50;
        }
        .empty-state p {
            margin: 0;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php require_once 'includes/sidebar_for_recipients_dashboard.php'; ?>

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
                                    <th>Recipient Name</th>
                                    <th>Blood Type</th>
                                    <th>Required Organ</th>
                                    <th>Priority Level</th>
                                    <th>Hospital Name</th>
                                    <th>Hospital Email</th>
                                    <th>Hospital Address</th>
                                    <th>Hospital Number</th>
                                    <th>Status</th>
                                    <?php if (isset($_GET['show_reason'])): ?>
                                    <th>Reason</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch approved and rejected requests
                                try {
                                    $stmt = $conn->prepare("
                                        SELECT hra.*, h.name as hospital_name, h.email as hospital_email, 
                                               h.address as hospital_address, h.phone as hospital_phone,
                                               r.full_name, r.blood_type, r.organ_required
                                        FROM hospital_recipient_approvals hra
                                        JOIN hospitals h ON hra.hospital_id = h.hospital_id
                                        JOIN recipient_registration r ON hra.recipient_id = r.id
                                        WHERE hra.recipient_id = ? 
                                        AND hra.status IN ('Approved', 'Rejected')
                                        ORDER BY hra.request_date DESC");
                                    
                                    $stmt->execute([$recipient_id]);
                                    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (empty($requests)) {
                                        echo '<tr><td colspan="9">';
                                        echo '<div class="empty-state">';
                                        echo '<i class="fas fa-inbox"></i>';
                                        echo '<h3>No Requests Found</h3>';
                                        echo '<p>You don\'t have any approved or rejected requests yet.</p>';
                                        echo '</div>';
                                        echo '</td></tr>';
                                    } else {
                                        foreach ($requests as $request) {
                                            $status_class = strtolower($request['status']);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                                <td>
                                                    <span class="blood-type"><?php echo htmlspecialchars($request['blood_type']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['organ_required']); ?></td>
                                                <td>
                                                    <span class="priority-badge priority-<?php echo strtolower($request['priority_level']); ?>">
                                                        <?php echo htmlspecialchars($request['priority_level']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['hospital_name']); ?></td>
                                                <td><?php echo htmlspecialchars($request['hospital_email']); ?></td>
                                                <td><?php echo htmlspecialchars($request['hospital_address']); ?></td>
                                                <td><?php echo htmlspecialchars($request['hospital_phone']); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($request['status']); ?>
                                                    </span>
                                                </td>
                                                <?php if (isset($_GET['show_reason'])): ?>
                                                <td><?php echo htmlspecialchars($request['reason'] ?? '-'); ?></td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php
                                        }
                                    }
                                } catch(PDOException $e) {
                                    error_log("Error fetching requests: " . $e->getMessage());
                                    echo '<tr><td colspan="9">An error occurred while fetching your requests.</td></tr>';
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
