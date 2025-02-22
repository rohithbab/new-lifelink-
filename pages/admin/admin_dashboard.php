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
$pendingDonors = getPendingDonors($conn);
$pendingRecipients = getPendingRecipients($conn);
$urgentRecipients = getUrgentRecipients($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LifeLink</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/notification-bell.css">
    
    <!-- Custom Styles -->
    <style>
        .odml-input {
            width: 150px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            transition: border-color 0.3s ease;
            font-size: 14px;
            margin-right: 8px;
        }

        .odml-input:focus {
            border-color: #1a73e8;
            outline: none;
            box-shadow: 0 0 5px rgba(26, 115, 232, 0.3);
        }

        /* Button Styles */
        .update-btn {
            padding: 6px 12px;
            background: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .update-btn:hover {
            background: #1557b0;
        }

        .reject-btn {
            padding: 6px 12px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .reject-btn:hover {
            background: #c82333;
        }

        .view-btn {
            padding: 6px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .view-btn:hover {
            background: #388E3C;
            text-decoration: none;
            color: white;
        }

        .action-cell {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .approve-btn, .reject-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            color: white;
        }

        .approve-btn {
            background: #28a745;
        }

        .approve-btn:hover {
            background: #218838;
        }

        .reject-btn {
            background: #dc3545;
        }

        .reject-btn:hover {
            background: #c82333;
        }
        
        /* Update gradient header for pending hospitals table */
        .table-container h2 {
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
            padding: 15px;
            border-radius: 5px 5px 0 0;
            margin: 0;
        }
        
        #pending-hospitals-table thead tr {
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
        }
        
        #pending-hospitals-table thead th {
            padding: 12px;
            font-weight: 600;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .stat-card i {
            font-size: 2.5rem;
        }

        .stat-card:nth-child(1) i { /* Total Hospitals */
            color: #2196F3; /* Blue */
        }
        
        .stat-card:nth-child(2) i { /* Total Donors */
            color: #4CAF50; /* Green */
        }
        
        .stat-card:nth-child(3) i { /* Total Recipients */
            color: #9C27B0; /* Purple */
        }
        
        .stat-card:nth-child(4) i { /* Pending Hospitals */
            color: #FF9800; /* Orange */
        }
        
        .stat-card:nth-child(5) i { /* Successful Matches */
            color: #00BCD4; /* Cyan */
        }
        
        .stat-card:nth-child(6) i { /* Pending Matches */
            color: #F44336; /* Red */
        }
        
        .stat-card:nth-child(7) i { /* Urgent Recipients */
            color: #E91E63; /* Pink */
        }

        .stat-card:nth-child(1):hover { border-left: 4px solid #2196F3; }
        .stat-card:nth-child(2):hover { border-left: 4px solid #4CAF50; }
        .stat-card:nth-child(3):hover { border-left: 4px solid #9C27B0; }
        .stat-card:nth-child(4):hover { border-left: 4px solid #FF9800; }
        .stat-card:nth-child(5):hover { border-left: 4px solid #00BCD4; }
        .stat-card:nth-child(6):hover { border-left: 4px solid #F44336; }
        .stat-card:nth-child(7):hover { border-left: 4px solid #E91E63; }

        .stat-info h3 {
            margin: 0;
            font-size: 1rem;
            color: #666;
        }

        .stat-info p {
            margin: 5px 0 0;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }
        
        /* Added styles for smaller buttons */
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .btn-sm .fas {
            font-size: 1rem;
        }

        .btn-approve {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .btn-reject {
            background: linear-gradient(135deg, #f44336, #e53935);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #e53935, #d32f2f);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .odml-input {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 120px;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .odml-input:focus {
            border-color: #2196F3;
            box-shadow: 0 0 5px rgba(33, 150, 243, 0.3);
            outline: none;
        }

        .update-odml-btn {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .update-odml-btn:hover {
            background: linear-gradient(135deg, #1976D2, #1565C0);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .update-odml-btn i {
            margin-right: 4px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f0fe 100%);
            margin: 20px auto;
            padding: 25px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            position: sticky;
            top: 0;
            background: inherit;
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e3e8f0;
            z-index: 1;
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }

        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #666;
        }

        .close {
            position: absolute;
            right: 25px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            color: #666;
            cursor: pointer;
            transition: 0.3s;
            z-index: 2;
        }

        .close:hover {
            color: #333;
        }

        .modal h2 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0;
            padding-right: 40px;
        }

        .match-details {
            display: grid;
            gap: 20px;
            padding: 10px 0;
        }

        .detail-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e3e8f0;
        }

        .detail-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-section h3 i {
            color: #3498db;
            font-size: 20px;
        }

        .detail-section.match-info {
            border-left: 4px solid #3498db;
        }

        .detail-section.donor-info {
            border-left: 4px solid #2ecc71;
        }

        .detail-section.recipient-info {
            border-left: 4px solid #9b59b6;
        }

        .detail-section p {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            padding: 8px 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .detail-section p:last-child {
            border-bottom: none;
        }

        .detail-section p strong {
            color: #34495e;
            min-width: 140px;
        }

        .detail-section p span {
            color: #666;
            flex: 1;
            text-align: right;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* ... other styles ... */
        
        /* Notification Bell Styles */
        .notification-bell-container {
            position: relative;
            display: inline-block;
        }

        .notification-bell {
            cursor: pointer;
            font-size: 24px;
            color: #666;
            transition: color 0.3s ease;
        }

        .notification-bell:hover {
            color: #333;
        }

        .notification-count {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 12px;
            background: #f44336;
            color: white;
            padding: 2px 5px;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        .notification-dropdown {
            position: absolute;
            top: 30px;
            right: 0;
            background: white;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: none;
        }

        .notification-dropdown.show {
            display: block;
        }

        .notification-header {
            margin-bottom: 10px;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #f0f2f5;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #f8f9fa;
        }

        .notification-badge {
            font-size: 12px;
            padding: 2px 5px;
            border-radius: 4px;
            color: white;
            margin-right: 5px;
        }

        .notification-badge.type-match {
            background: #2196F3;
        }

        .notification-badge.type-donor {
            background: #4CAF50;
        }

        .notification-badge.type-recipient {
            background: #9C27B0;
        }

        .notification-content {
            font-size: 14px;
            color: #666;
        }

        .notification-time {
            font-size: 12px;
            color: #999;
        }

        .no-notifications {
            padding: 10px;
            text-align: center;
            color: #666;
        }

        .notification-footer {
            padding: 10px;
            text-align: center;
            border-top: 1px solid #f0f2f5;
        }

        .notification-footer a {
            color: #2196F3;
            text-decoration: none;
        }

        .notification-footer a:hover {
            text-decoration: underline;
        }
        
        /* ODML Update Modal Styles */
        .odml-input-group {
            margin: 20px 0;
        }

        .odml-input-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .notification-info, .status-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }

        .notification-info {
            background: rgba(37, 211, 102, 0.1);
        }

        .status-info {
            background: rgba(52, 152, 219, 0.1);
        }

        .notification-info i {
            color: #25d366;
        }

        .status-info i {
            color: #3498db;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e3e8f0;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-1px);
        }
        
        /* Button Styles */
        .update-btn {
            padding: 6px 12px;
            background: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .update-btn:hover {
            background: #1557b0;
        }

        .reject-btn {
            padding: 6px 12px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .reject-btn:hover {
            background: #c82333;
        }

        .view-btn {
            padding: 6px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .view-btn:hover {
            background: #388E3C;
            text-decoration: none;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }

        .modal h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body p {
            margin-bottom: 15px;
        }

        .modal-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .modal-info i {
            color: #1a73e8;
        }

        .odml-input {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            padding: 8px 16px;
            background: #f1f3f4;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: #5f6368;
        }

        .btn-approve {
            padding: 8px 16px;
            background: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-approve:hover {
            background: #1557b0;
        }
    </style>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <a href="admin_dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_hospitals.php" class="nav-link">
                        <i class="fas fa-hospital"></i>
                        Manage Hospitals
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_donors.php" class="nav-link">
                        <i class="fas fa-hand-holding-heart"></i>
                        Manage Donors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_recipients.php" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        Manage Recipients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="organ_match_info_for_admin.php" class="nav-link">
                        <i class="fas fa-handshake-angle"></i>
                        Organ Matches
                    </a>
                </li>
                <li class="nav-item">
                    <a href="analytics.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a href="notifications.php" class="nav-link">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../admin_dashboard_logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <!-- Notification Bell -->
                <div class="notification-bell-container">
                    <div class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count">0</span>
                    </div>
                    <div class="notification-dropdown">
                        <div class="notification-header">
                            <h3>Recent Notifications</h3>
                        </div>
                        <div class="notification-list">
                            <!-- Notifications will be dynamically inserted here -->
                        </div>
                        <div class="notification-footer">
                            <a href="notifications.php">View All Notifications</a>
                        </div>
                    </div>
                </div>
                
                <!-- Rest of top bar content -->
                <div class="top-bar-content">
                    <!-- Add any other top bar content here -->
                </div>
            </div>
            <!-- Stats Cards Section -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-hospital"></i>
                    <div class="stat-info">
                        <h3>Total Hospitals</h3>
                        <p><?php echo $stats['total_hospitals']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-plus"></i>
                    <div class="stat-info">
                        <h3>Total Donors</h3>
                        <p><?php echo $stats['total_donors']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-procedures"></i>
                    <div class="stat-info">
                        <h3>Total Recipients</h3>
                        <p><?php echo $stats['total_recipients']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-info">
                        <h3>Pending Hospitals</h3>
                        <p><?php echo $stats['pending_hospitals']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-info">
                        <h3>Successful Matches</h3>
                        <p><?php echo $stats['successful_matches']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hourglass-half"></i>
                    <div class="stat-info">
                        <h3>Pending Matches</h3>
                        <p><?php echo $stats['pending_matches']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="stat-info">
                        <h3>Urgent Recipients</h3>
                        <p><?php echo $stats['urgent_recipients']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Hospitals Section -->
            <div class="table-container">
                <h2>Pending Hospital Approvals (<?php echo count($pendingHospitals); ?>)</h2>
                <div class="table-responsive">
                    <table class="table" id="pending-hospitals-table">
                        <thead>
                            <tr>
                                <th>Hospital Name</th>
                                <th>Email</th>
                                <th>Update ODML ID</th>
                                <th>Actions</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingHospitals as $hospital): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hospital['hospital_name']); ?></td>
                                <td><?php echo htmlspecialchars($hospital['email']); ?></td>
                                <td>
                                    <button class="update-btn" onclick="openOdmlModal('hospital', '<?php echo $hospital['hospital_id']; ?>', '<?php echo htmlspecialchars($hospital['hospital_name']); ?>', '<?php echo htmlspecialchars($hospital['email']); ?>')">
                                        <i class="fas fa-edit"></i> Update ODML ID
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <button class="reject-btn">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                                <td>
                                    <a href="view_hospital_details.php?id=<?php echo $hospital['hospital_id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Donors Table -->
            <div class="table-container">
                <h2>Pending Donor Approvals (<?php echo count($pendingDonors); ?>)</h2>
                <div class="table-responsive">
                    <table class="table" id="pending-donors-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Blood Type</th>
                                <th>Update ODML ID</th>
                                <th>Actions</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingDonors as $donor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donor['name']); ?></td>
                                <td><?php echo htmlspecialchars($donor['email']); ?></td>
                                <td><?php echo htmlspecialchars($donor['blood_type']); ?></td>
                                <td>
                                    <button class="update-btn" onclick="openOdmlModal('donor', '<?php echo $donor['donor_id']; ?>', '<?php echo htmlspecialchars($donor['name']); ?>', '<?php echo htmlspecialchars($donor['email']); ?>')">
                                        <i class="fas fa-edit"></i> Update ODML ID
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <button class="reject-btn">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                                <td>
                                    <a href="view_donor_details.php?id=<?php echo $donor['donor_id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Recipients Table -->
            <div class="table-container">
                <h2>Pending Recipient Approvals (<?php echo count($pendingRecipients); ?>)</h2>
                <div class="table-responsive">
                    <table class="table" id="pending-recipients-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Blood Type</th>
                                <th>Update ODML ID</th>
                                <th>Actions</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRecipients as $recipient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($recipient['name']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['email']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['blood_type']); ?></td>
                                <td>
                                    <button class="update-btn" onclick="openOdmlModal('recipient', '<?php echo $recipient['id']; ?>', '<?php echo htmlspecialchars($recipient['name']); ?>', '<?php echo htmlspecialchars($recipient['email']); ?>')">
                                        <i class="fas fa-edit"></i> Update ODML ID
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <button class="reject-btn">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                                <td>
                                    <a href="view_recipient_details_in_pending.php?id=<?php echo $recipient['id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Matches Table -->
            <div class="table-container">
                <h2>Recent Organ Match Activities</h2>
                <div class="table-responsive">
                    <table class="table" id="recent-matches-table">
                        <thead>
                            <tr>
                                <th>Match ID</th>
                                <th>Hospital</th>
                                <th>Donor Name</th>
                                <th>Recipient Name</th>
                                <th>Match Date</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            require_once '../../backend/php/organ_matches.php';
                            $recent_matches = getRecentOrganMatches($conn);
                            
                            if (empty($recent_matches)) {
                                echo '<tr><td colspan="6" class="text-center">No recent matches found</td></tr>';
                            } else {
                                foreach ($recent_matches as $match) {
                                    $match_date = date('M d, Y', strtotime($match['match_date']));
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($match['match_id']); ?></td>
                                        <td><?php echo htmlspecialchars($match['match_made_by_hospital_name']); ?></td>
                                        <td><?php echo htmlspecialchars($match['donor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($match['recipient_name']); ?></td>
                                        <td><?php echo $match_date; ?></td>
                                        <td>
                                            <button class="view-btn" onclick="viewMatchDetails(<?php echo $match['match_id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Match Details Modal -->
            <div id="matchDetailsModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Match Details</h2>
                    </div>
                    <div class="modal-body" id="matchDetailsContent"></div>
                    <span class="close">&times;</span>
                </div>
            </div>

            <!-- ODML Update Modal -->
            <div id="odmlModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3>Update ODML ID</h3>
                    <div class="modal-body">
                        <p>You are about to update the ODML ID for: <strong id="entityName"></strong></p>
                        <input type="text" id="modalOdmlId" class="odml-input" placeholder="Enter ODML ID">
                        <div class="modal-info">
                            <i class="fas fa-envelope"></i>
                            <span>An email notification will be sent to <strong id="entityEmail"></strong></span>
                        </div>
                        <div class="modal-info">
                            <i class="fas fa-check-circle"></i>
                            <span>This action will approve the hospital's registration.</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeOdmlModal()">Cancel</button>
                        <button class="btn-approve" onclick="approveAndUpdate()">
                            <i class="fas fa-check"></i> Approve & Update
                        </button>
                    </div>
                </div>
            </div>

            <script>
            $(document).ready(function() {
                // Initialize notification system
                updateNotifications();
                
                // Toggle notification dropdown
                $('#notificationBell').click(function(e) {
                    e.stopPropagation();
                    $('.notification-dropdown').toggleClass('show');
                });
                
                // Close dropdown when clicking outside
                $(document).click(function() {
                    $('.notification-dropdown').removeClass('show');
                });
                
                // Update notifications every 30 seconds
                setInterval(updateNotifications, 30000);
            });
            
            function updateNotifications() {
                $.ajax({
                    url: '../../backend/php/get_recent_notifications.php',
                    method: 'GET',
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            // Update notification count
                            $('#notificationCount').text(data.unread_count);
                            
                            // Clear existing notifications
                            const notificationList = $('#notificationList');
                            notificationList.empty();
                            
                            // Add new notifications
                            data.notifications.forEach(function(notification) {
                                const notificationItem = $('<div>').addClass('notification-item');
                                
                                // Add type badge
                                const badge = $('<span>')
                                    .addClass('notification-badge')
                                    .addClass('type-' + notification.type)
                                    .text(notification.type.replace('_', ' '));
                                
                                // Add message and time
                                const content = $('<div>').addClass('notification-content');
                                content.append($('<p>').addClass('notification-message').text(notification.message));
                                content.append($('<span>').addClass('notification-time').text(notification.time_ago));
                                
                                // Add link if available
                                if (notification.link_url) {
                                    notificationItem.css('cursor', 'pointer');
                                    notificationItem.click(function() {
                                        window.location.href = notification.link_url;
                                    });
                                }
                                
                                notificationItem.append(badge).append(content);
                                notificationList.append(notificationItem);
                            });
                            
                            // Show "No notifications" message if empty
                            if (data.notifications.length === 0) {
                                notificationList.append(
                                    $('<div>')
                                        .addClass('no-notifications')
                                        .text('No unread notifications')
                                );
                            }
                        } catch (e) {
                            console.error('Error parsing notifications:', e);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching notifications:', error);
                    }
                });
            }

            // Close modal when clicking the close button or outside
            $('.close').click(function() {
                $('#matchDetailsModal').fadeOut(300);
            });

            $(window).click(function(event) {
                if ($(event.target).is('#matchDetailsModal')) {
                    $('#matchDetailsModal').fadeOut(300);
                }
            });

            $(document).ready(function() {
                // Add event listeners for all dynamic elements
                $('.update-btn').on('click', function(e) {
                    e.preventDefault();
                    const hospitalId = $(this).data('hospital-id');
                    updateHospitalODMLID(hospitalId);
                });
            });

            function updateHospitalODMLID(hospitalId) {
                const odmlId = $(`#odml_id_${hospitalId}`).val();
                
                if (!odmlId) {
                    alert('Please enter an ODML ID');
                    return;
                }
                
                if (confirm('Are you sure you want to update the ODML ID?')) {
                    $.ajax({
                        url: '../../backend/php/update_odml_id.php',
                        method: 'POST',
                        data: {
                            type: 'hospital',
                            id: hospitalId,
                            odml_id: odmlId
                        },
                        success: function(response) {
                            try {
                                const data = typeof response === 'string' ? JSON.parse(response) : response;
                                if (data.success) {
                                    alert('ODML ID updated successfully');
                                } else {
                                    alert('Failed to update ODML ID: ' + (data.message || 'Unknown error'));
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                alert('Error updating ODML ID');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert('Error updating ODML ID');
                        }
                    });
                }
            }

            function updateHospitalStatus(hospitalId, status) {
                if (confirm(`Are you sure you want to ${status.toLowerCase()} this hospital?`)) {
                    $.ajax({
                        url: '../../backend/php/update_hospital_status.php',
                        method: 'POST',
                        data: {
                            hospital_id: hospitalId,
                            status: status
                        },
                        success: function(response) {
                            try {
                                const data = typeof response === 'string' ? JSON.parse(response) : response;
                                if (data.success) {
                                    alert(`Hospital ${status.toLowerCase()} successfully`);
                                    $(`#hospital_row_${hospitalId}`).fadeOut(400, function() {
                                        $(this).remove();
                                        const counter = $('#pending-hospitals-count');
                                        counter.text(parseInt(counter.text()) - 1);
                                    });
                                } else {
                                    alert('Failed to update status: ' + (data.message || 'Unknown error'));
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                alert('Error updating hospital status');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert('Error updating hospital status');
                        }
                    });
                }
            }
            
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
                                location.reload();
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

            function updateDonorODMLID(donorId, button) {
                const odmlId = $(button).prev('input').val();
                
                if (!odmlId) {
                    alert('Please enter an ODML ID');
                    return;
                }
                
                if (confirm('Are you sure you want to update the ODML ID?')) {
                    $.ajax({
                        url: '../../backend/php/update_odml_id.php',
                        method: 'POST',
                        data: {
                            type: 'donor',
                            id: donorId,
                            odml_id: odmlId
                        },
                        success: function(response) {
                            try {
                                const data = typeof response === 'string' ? JSON.parse(response) : response;
                                if (data.success) {
                                    alert('ODML ID updated successfully');
                                    $(button).prev('input').prop('disabled', true);
                                    $(button).prop('disabled', true);
                                } else {
                                    alert('Failed to update ODML ID: ' + (data.message || 'Unknown error'));
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                alert('Error updating ODML ID');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert('Error updating ODML ID');
                        }
                    });
                }
            }

            function updateRecipientStatus(recipientId, status) {
                // Capitalize first letter of status
                status = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
                
                if (confirm(`Are you sure you want to ${status.toLowerCase()} this recipient?`)) {
                    $.ajax({
                        url: '../../backend/php/update_recipient_status.php',
                        method: 'POST',
                        data: JSON.stringify({
                            recipient_id: recipientId,
                            status: status
                        }),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function(response) {
                            console.log('Response:', response);
                            if (response.success) {
                                alert(`Recipient ${status.toLowerCase()} successfully`);
                                location.reload();
                            } else {
                                alert('Failed to update status: ' + (response.message || 'Unknown error'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            console.error('Response:', xhr.responseText);
                            alert('Error updating recipient status. Please check the console for details.');
                        }
                    });
                }
            }

            function updateRecipientODML(recipientId) {
                const odmlId = document.getElementById(`odml-${recipientId}`).value.trim();
                
                if (!odmlId) {
                    alert('Please enter an ODML ID');
                    return;
                }

                if (confirm('Are you sure you want to update the ODML ID?')) {
                    $.ajax({
                        url: '../../backend/php/update_recipient_odml.php',
                        method: 'POST',
                        data: JSON.stringify({
                            recipient_id: recipientId,
                            odml_id: odmlId
                        }),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function(response) {
                            console.log('Response:', response);
                            if (response.success) {
                                alert('ODML ID updated successfully');
                            } else {
                                alert('Failed to update ODML ID: ' + (response.message || 'Unknown error'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            console.error('Response:', xhr.responseText);
                            alert('Error updating ODML ID. Please check the console for details.');
                        }
                    });
                }
            }
        </script>
        <script>
            // Global variables for modal
            let currentEntityId = null;
            let currentEntityType = null;

            // Open ODML modal
            function openOdmlModal(type, id, name, email) {
                currentEntityId = id;
                currentEntityType = type;
                
                document.getElementById('entityName').textContent = name;
                document.getElementById('entityEmail').textContent = email;
                document.getElementById('modalOdmlId').value = '';
                
                document.getElementById('odmlModal').style.display = 'block';
            }

            // Close ODML modal
            function closeOdmlModal() {
                document.getElementById('odmlModal').style.display = 'none';
                currentEntityId = null;
                currentEntityType = null;
            }

            // Approve and update ODML ID
            function approveAndUpdate() {
                const odmlId = document.getElementById('modalOdmlId').value.trim();
                
                if (!odmlId) {
                    return;
                }

                // Update ODML ID and approve
                $.ajax({
                    url: '../backend/php/update_odml.php',
                    type: 'POST',
                    data: {
                        id: currentEntityId,
                        type: currentEntityType,
                        odmlId: odmlId,
                        action: 'approve'
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function() {
                        location.reload();
                    }
                });

                closeOdmlModal();
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('odmlModal');
                if (event.target == modal) {
                    closeOdmlModal();
                }
            }

            // Close modal when clicking X
            document.querySelector('.close').onclick = function() {
                closeOdmlModal();
            }
        </script>
        <script src="../../assets/js/notifications.js"></script>
    </body>
</html>
