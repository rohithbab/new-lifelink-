<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];

// Fetch requests from other hospitals
try {
    $stmt = $conn->prepare("
        SELECT r.*, 
               h.hospital_name as requesting_hospital_name,
               d.name as donor_name,
               rec.full_name as recipient_name,
               r.blood_type,
               r.organ_type,
               r.request_date
        FROM donor_and_recipient_requests r
        LEFT JOIN hospitals h ON r.requesting_hospital_id = h.hospital_id
        LEFT JOIN donor d ON r.donor_id = d.donor_id
        LEFT JOIN recipient_registration rec ON r.recipient_id = rec.id
        WHERE r.requested_hospital_id = ? AND r.status = 'pending'
        ORDER BY r.request_date DESC
    ");
    $stmt->execute([$hospital_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching requests: " . $e->getMessage());
    $requests = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Requests - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .requests-container {
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 2rem;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
        }

        .modern-table th,
        .modern-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .modern-table th {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            font-weight: 500;
        }

        .modern-table tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-approve,
        .btn-reject {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }

        .btn-approve {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
        }

        .btn-reject {
            background: #dc3545;
        }

        .btn-approve:hover,
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 class="logo-text">LifeLink</h2>
                <div class="sub-text">HospitalHub</div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="hospital_dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="hospitals_handles_donors_status.php">
                            <i class="fas fa-users"></i>
                            <span>Manage Donors</span>
                        </a>
                    </li>
                    <li>
                        <a href="hospitals_handles_recipients_status.php">
                            <i class="fas fa-procedures"></i>
                            <span>Manage Recipients</span>
                        </a>
                    </li>
                    <li>
                        <a href="make_matches.php">
                            <i class="fas fa-link"></i>
                            <span>Make Matches</span>
                        </a>
                    </li>
                    <li>
                        <a href="check_requests.php" class="active">
                            <i class="fas fa-bell"></i>
                            <span>Check Requests</span>
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

        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Check Requests</h1>
                </div>
            </div>

            <div class="requests-container">
                <?php if (empty($requests)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h2>No Pending Requests</h2>
                        <p>You don't have any pending requests from other hospitals at the moment.</p>
                    </div>
                <?php else: ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Requesting Hospital</th>
                                <th>Donor/Recipient</th>
                                <th>Blood Group</th>
                                <th>Organ Type</th>
                                <th>Requested Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['requesting_hospital_name']); ?></td>
                                    <td>
                                        <?php 
                                        if ($request['donor_name']) {
                                            echo "Donor: " . htmlspecialchars($request['donor_name']);
                                        } else {
                                            echo "Recipient: " . htmlspecialchars($request['recipient_name']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['blood_type']); ?></td>
                                    <td><?php echo htmlspecialchars($request['organ_type']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($request['request_date'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn-approve" onclick="handleRequest(<?php echo $request['request_id']; ?>, 'approve')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn-reject" onclick="handleRequest(<?php echo $request['request_id']; ?>, 'reject')">
                                            <i class="fas fa-times"></i> Reject
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
        function handleRequest(requestId, action) {
            if (!confirm(`Are you sure you want to ${action} this request?`)) {
                return;
            }

            fetch('../../ajax/handle_match_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    request_id: requestId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Request ${action}ed successfully`);
                    location.reload();
                } else {
                    alert('Error processing request: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing the request');
            });
        }
    </script>
</body>
</html>
