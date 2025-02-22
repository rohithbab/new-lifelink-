 <?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in as recipient
if (!isset($_SESSION['is_recipient']) || !$_SESSION['is_recipient']) {
    header("Location: ../recipient_login.php");
    exit();
}

// Get recipient info from session
$recipient_id = $_SESSION['recipient_id'];

// Fetch recipient details from database
try {
    $stmt = $conn->prepare("SELECT id, full_name, date_of_birth, gender, phone_number, email, address, 
                           medical_condition, blood_type, organ_required, urgency_level, request_status 
                           FROM recipient_registration WHERE id = ?");
    $stmt->execute([$recipient_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        die("Recipient not found");
    }
} catch(PDOException $e) {
    error_log("Error fetching recipient details: " . $e->getMessage());
    die("An error occurred while fetching your details");
}

// Then, get hospital requests - only pending ones for dashboard
$stmt = $conn->prepare("
    SELECT 
        r.full_name,
        r.blood_type,
        r.organ_required,
        h.name AS hospital_name,
        h.email AS hospital_email,
        h.address AS hospital_address,
        h.phone AS hospital_number,
        hra.status,
        hra.request_date,
        hra.approval_id AS request_id,
        hra.medical_reports,
        hra.id_document
    FROM hospital_recipient_approvals hra
    JOIN hospitals h ON hra.hospital_id = h.hospital_id
    JOIN recipient_registration r ON hra.recipient_id = r.id
    WHERE hra.recipient_id = ? AND hra.status = 'pending'
    ORDER BY hra.request_date DESC
");
$stmt->execute([$recipient_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Dashboard - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/recipient-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: #f4f6f9;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .table-container {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .table-header h2 {
            color: #333;
            font-size: 1.5rem;
            margin: 0;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .modern-table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }

        .modern-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            color: #6c757d;
        }

        .modern-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-approved {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85em;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .reject-btn {
            background: #dc3545;
            color: white;
        }

        .reject-btn:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .empty-state p {
            color: #6c757d;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 4px;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }

        .search-box input {
            border: none;
            background: none;
            padding: 5px;
            outline: none;
            color: #495057;
        }

        .search-box i {
            color: #6c757d;
            margin-right: 8px;
        }

        /* Notification Dropdown Styles */
        .notification-icon {
            position: relative;
            cursor: pointer;
            margin-right: 20px;
            display: flex;
            align-items: center;
        }

        .notification-icon i {
            font-size: 20px;
            color: #666;
            transition: color 0.3s ease;
        }

        .notification-icon:hover i {
            color: #2196F3;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 12px;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .notification-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dropdown-header h3 {
            margin: 0;
            color: #333;
            font-size: 16px;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.unread {
            background-color: #e3f2fd;
        }

        .notification-item.unread:hover {
            background-color: #bbdefb;
        }

        .notification-icon-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(33, 150, 243, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2196F3;
            font-size: 14px;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            margin: 0 0 4px 0;
            font-size: 14px;
            color: #333;
            line-height: 1.4;
        }

        .notification-time {
            font-size: 12px;
            color: #666;
        }

        .dropdown-footer {
            padding: 12px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .view-all-link {
            color: #2196F3;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .view-all-link:hover {
            color: #1976D2;
        }

        /* Custom Scrollbar */
        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .notification-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Update header-right styles */
        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Add gradient text styles */
        .gradient-text {
            background: linear-gradient(120deg, #28a745, #2196F3);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            font-family: 'Poppins', sans-serif;
        }

        .header-left h1 {
            font-size: 28px;
            margin: 0;
            padding: 10px 0;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .table-header h2 {
            font-size: 24px;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.3px;
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
                        <h1 class="gradient-text">Welcome, <?php echo htmlspecialchars($recipient['full_name']); ?>!</h1>
                    </div>
                    <div class="header-right">
                        <div class="notification-icon" id="notificationIcon">
                            <i class="fas fa-bell"></i>
                            <?php
                            // Get unread count
                            $stmt = $conn->prepare("
                                SELECT COUNT(*) as unread_count 
                                FROM recipient_notifications 
                                WHERE recipient_id = ? AND is_read = 0
                            ");
                            $stmt->execute([$_SESSION['recipient_id']]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            $unread_count = $result['unread_count'];
                            
                            if ($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>

                            <!-- Notification Dropdown -->
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="dropdown-header">
                                    <h3>Notifications</h3>
                                </div>
                                <div class="notification-list">
                                    <?php
                                    // Get recent notifications
                                    $stmt = $conn->prepare("
                                        SELECT * FROM recipient_notifications 
                                        WHERE recipient_id = ? 
                                        ORDER BY created_at DESC 
                                        LIMIT 3
                                    ");
                                    $stmt->execute([$_SESSION['recipient_id']]);
                                    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (!empty($notifications)):
                                        foreach ($notifications as $notification): ?>
                                            <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                                <div class="notification-icon-small">
                                                    <i class="fas <?php echo $notification['type'] === 'request_status' ? 'fa-file-medical' : 'fa-handshake'; ?>"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    <span class="notification-time">
                                                        <?php 
                                                        $date = new DateTime($notification['created_at']);
                                                        echo $date->format('M d, h:i A'); 
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach;
                                    else: ?>
                                        <div class="notification-item">
                                            <p class="notification-message" style="text-align: center; width: 100%;">No notifications</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown-footer">
                                    <a href="recipients_notifications.php" class="view-all-link">View All Notifications</a>
                                </div>
                            </div>
                        </div>
                        <div class="profile-section">
                            <button class="profile-trigger" onclick="toggleProfile()">
                                <div class="profile-icon">
                                    <?php if($recipient['gender'] === 'Male'): ?>
                                        <i class="fas fa-male"></i>
                                    <?php else: ?>
                                        <i class="fas fa-female"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="profile-name"><?php echo htmlspecialchars($recipient['full_name']); ?></span>
                            </button>
                            
                            <div class="profile-card modern">
                                <div class="profile-header">
                                    <div class="header-overlay"></div>
                                    <div class="profile-avatar">
                                        <?php if($recipient['gender'] === 'Male'): ?>
                                            <i class="fas fa-male"></i>
                                        <?php else: ?>
                                            <i class="fas fa-female"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h2><?php echo htmlspecialchars($recipient['full_name']); ?></h2>
                                </div>
                                
                                <div class="profile-content">
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <div class="info-text">
                                                <label>Email</label>
                                                <span><?php echo htmlspecialchars($recipient['email']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="fas fa-phone"></i>
                                            </div>
                                            <div class="info-text">
                                                <label>Phone</label>
                                                <span><?php echo htmlspecialchars($recipient['phone_number']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="fas fa-tint"></i>
                                            </div>
                                            <div class="info-text">
                                                <label>Blood Type</label>
                                                <span><?php echo htmlspecialchars($recipient['blood_type']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="fas fa-heartbeat"></i>
                                            </div>
                                            <div class="info-text">
                                                <label>Organ Required</label>
                                                <span><?php echo htmlspecialchars($recipient['organ_required']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Modern Table Section -->
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="gradient-text"><i class="fas fa-clock"></i>Pending Requests</h2>
                        <div class="table-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search requests...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <?php if (empty($requests)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No Pending Requests</h3>
                                <p>You don't have any pending requests at the moment.</p>
                            </div>
                        <?php else: ?>
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Recipient Name</th>
                                        <th>Blood Type</th>
                                        <th>Organ Type</th>
                                        <th>Hospital Name</th>
                                        <th>Hospital Email</th>
                                        <th>Hospital Address</th>
                                        <th>Hospital Number</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['blood_type']); ?></td>
                                            <td><?php echo htmlspecialchars($request['organ_required']); ?></td>
                                            <td><?php echo htmlspecialchars($request['hospital_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['hospital_email']); ?></td>
                                            <td><?php echo htmlspecialchars($request['hospital_address']); ?></td>
                                            <td><?php echo htmlspecialchars($request['hospital_number']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                                    <?php echo htmlspecialchars($request['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="action-btn reject-btn" onclick="rejectRequest(<?php echo $request['request_id']; ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleProfile() {
            const profileCard = document.querySelector('.profile-card');
            profileCard.classList.toggle('show');

            // Close profile card when clicking outside
            document.addEventListener('click', function(event) {
                const profileSection = document.querySelector('.profile-section');
                const isClickInside = profileSection.contains(event.target);
                
                if (!isClickInside && profileCard.classList.contains('show')) {
                    profileCard.classList.remove('show');
                }
            });
        }

        // Reject request
        function rejectRequest(requestId) {
            if (confirm('Are you sure you want to reject this request? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="request_id" value="${requestId}">
                    <input type="hidden" name="status" value="rejected">
                    <input type="hidden" name="update_status" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationDropdown = document.getElementById('notificationDropdown');

            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationIcon.contains(e.target)) {
                    notificationDropdown.classList.remove('show');
                }
            });

            // Prevent dropdown from closing when clicking inside it
            notificationDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>