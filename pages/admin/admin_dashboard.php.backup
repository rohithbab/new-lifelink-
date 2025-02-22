<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$stats = getDashboardStats($conn);
$notifications = getAdminNotifications($conn, 5);
$pendingHospitals = getPendingHospitals($conn);
$urgentRecipients = getUrgentRecipients($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .logo-text {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .logo-gradient {
            background: linear-gradient(45deg, var(--primary-green), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        .nav-links a {
            position: relative;
            text-decoration: none;
            color: var(--dark-gray);
            transition: color 0.3s ease;
        }

        .nav-links a:not(.btn)::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            transition: width 0.3s ease;
        }

        .nav-links a:not(.btn):hover::after {
            width: 100%;
        }

        .nav-links a:not(.btn):hover {
            color: var(--primary-blue);
        }

        .logout-btn {
            background: var(--primary-blue) !important;
            color: white !important;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none !important;
            font-size: 0.9rem;
            border: none;
        }

        .logout-btn:hover {
            background: var(--primary-blue) !important;
            color: white !important;
            text-decoration: none !important;
        }

        .dashboard-container {
            padding: 2rem;
        }

        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }

        .recent-activity {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .activity-list {
            list-style: none;
            padding: 0;
        }

        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .pending-hospitals,
        .urgent-recipients {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .pending-list {
            list-style: none;
            padding: 0;
        }

        .pending-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pending-item:last-child {
            border-bottom: none;
        }

        .pending-item .hospital-info {
            flex: 1;
        }

        .pending-item .hospital-name {
            font-weight: bold;
            color: var(--primary-blue);
        }

        .pending-item .hospital-details {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.3rem;
        }

        .pending-item .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .approve-btn, .reject-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: opacity 0.3s;
        }

        .approve-btn {
            background: var(--primary-green);
            color: white;
        }

        .reject-btn {
            background: #dc3545;
            color: white;
        }

        .urgent-list {
            list-style: none;
            padding: 0;
        }

        .urgent-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .urgent-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <span class="logo-text"><span class="logo-gradient">LifeLink</span> Admin</span>
            </div>
            <div class="nav-links">
                <a href="view-hospitals.php">Hospitals</a>
                <a href="view-donors.php">Donors</a>
                <a href="view-recipients.php">Recipients</a>
                <a href="analytics.php">Analytics</a>
                <a href="notifications.php">Notifications</a>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>Welcome, Admin!</h1>
            <p>Manage and monitor the organ donation system</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Hospitals</h3>
                <p class="stat-number"><?php echo $stats['total_hospitals']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Donors</h3>
                <p class="stat-number"><?php echo $stats['total_donors']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Recipients</h3>
                <p class="stat-number"><?php echo $stats['total_recipients']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Approvals</h3>
                <p class="stat-number"><?php echo $stats['pending_hospitals']; ?></p>
            </div>
        </div>

        <div class="recent-activity">
            <h2>Recent Activities</h2>
            <div class="activity-list">
                <?php foreach ($notifications as $notification): ?>
                <div class="activity-item">
                    <span class="activity-type"><?php echo htmlspecialchars($notification['type']); ?></span>
                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                    <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="pending-hospitals">
                <h2>Pending Hospital Approvals</h2>
                <div class="pending-list">
                    <?php foreach ($pendingHospitals as $hospital): ?>
                    <div class="pending-item">
                        <div class="hospital-info">
                            <div class="hospital-name"><?php echo htmlspecialchars($hospital['name']); ?></div>
                            <div class="hospital-details">
                                Email: <?php echo htmlspecialchars($hospital['email']); ?><br>
                                Address: <?php echo htmlspecialchars($hospital['address']); ?>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="approve-btn" data-id="<?php echo $hospital['id']; ?>">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="reject-btn" data-id="<?php echo $hospital['id']; ?>">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="urgent-recipients">
                <h2>Urgent Recipients</h2>
                <div class="urgent-list">
                    <?php foreach ($urgentRecipients as $recipient): ?>
                    <div class="urgent-item">
                        <h4><?php echo htmlspecialchars($recipient['name']); ?></h4>
                        <p>Blood Type: <?php echo htmlspecialchars($recipient['blood_type']); ?></p>
                        <p>Organ Needed: <?php echo htmlspecialchars($recipient['needed_organ']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="dashboard-section">
            <h2><i class="fas fa-hospital"></i> Pending Hospital Registrations</h2>
            <div class="pending-hospitals">
                <?php if (empty($pendingHospitals)): ?>
                    <p>No pending hospital registrations.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hospital Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registration Date</th>
                                    <th>License</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingHospitals as $hospital): ?>
                                    <tr class="<?php echo $hospital['is_new'] ? 'new-registration' : ''; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($hospital['hospital_name']); ?>
                                            <?php if ($hospital['is_new']): ?>
                                                <span class="new-badge">New</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($hospital['email']); ?></td>
                                        <td><?php echo htmlspecialchars($hospital['phone']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($hospital['created_at'])); ?></td>
                                        <td>
                                            <a href="../view_license.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-file-medical"></i> View License
                                            </a>
                                        </td>
                                        <td>
                                            <button onclick="approveHospital(<?php echo $hospital['hospital_id']; ?>)" 
                                                    class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button onclick="rejectHospital(<?php echo $hospital['hospital_id']; ?>)" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2><i class="fas fa-hospital"></i> Hospital Management</h2>
                <a href="manage_hospitals.php" class="btn btn-primary">View All</a>
            </div>
            
            <?php
            // Get pending hospitals count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM hospitals WHERE status = 'pending'");
            $stmt->execute();
            $result = $stmt->get_result();
            $pending_count = $result->fetch_assoc()['count'];
            
            // Get recent registrations
            $stmt = $conn->prepare("
                SELECT hospital_id, hospital_name, email, created_at, status,
                CASE 
                    WHEN created_at > NOW() - INTERVAL 24 HOUR THEN 1 
                    ELSE 0 
                END as is_new
                FROM hospitals 
                WHERE status = 'pending'
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $recent_hospitals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>
            
            <div class="quick-stats">
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending Approvals</h3>
                        <p class="stat-number"><?php echo $pending_count; ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($recent_hospitals)): ?>
                <div class="recent-registrations">
                    <h3>Recent Hospital Registrations</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hospital Name</th>
                                    <th>Email</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_hospitals as $hospital): ?>
                                    <tr class="<?php echo $hospital['is_new'] ? 'new-registration' : ''; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($hospital['hospital_name']); ?>
                                            <?php if ($hospital['is_new']): ?>
                                                <span class="new-badge">New</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($hospital['email']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($hospital['created_at'])); ?></td>
                                        <td>
                                            <a href="../view_license.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-file-medical"></i> View License
                                            </a>
                                            <a href="manage_hospitals.php" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-external-link-alt"></i> Review
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="no-data">No pending hospital registrations</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../assets/js/admin.js"></script>
    <script>
    function approveHospital(hospitalId) {
        if (confirm('Are you sure you want to approve this hospital?')) {
            updateHospitalStatus(hospitalId, 'approved');
        }
    }

    function rejectHospital(hospitalId) {
        if (confirm('Are you sure you want to reject this hospital?')) {
            updateHospitalStatus(hospitalId, 'rejected');
        }
    }

    function updateHospitalStatus(hospitalId, status) {
        fetch('../../backend/php/update_hospital_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                hospital_id: hospitalId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the hospital status');
        });
    }
    </script>
</body>
</html>
