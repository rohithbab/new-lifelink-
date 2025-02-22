<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get recipient counts
function getRecipientCounts($conn) {
    $counts = [
        'all' => 0,
        'pending' => 0,
        'accepted' => 0,
        'rejected' => 0
    ];
    
    try {
        // Get total count
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM recipient_registration");
        if ($row = mysqli_fetch_assoc($result)) {
            $counts['all'] = $row['total'];
        }

        // Get counts by status
        $result = mysqli_query($conn, "SELECT request_status, COUNT(*) as count FROM recipient_registration GROUP BY request_status");
        while ($row = mysqli_fetch_assoc($result)) {
            $status = strtolower($row['request_status']);
            if (isset($counts[$status])) {
                $counts[$status] = $row['count'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting recipient counts: " . $e->getMessage());
    }
    
    return $counts;
}

// Get filter from URL parameter and map 'approved' to 'accepted'
$filter = isset($_GET['filter']) ? strtolower($_GET['filter']) : 'all';
if ($filter === 'approved') {
    $filter = 'accepted';
}
$counts = getRecipientCounts($conn);

// Build SQL query based on filter
$sql = "
    SELECT 
        id,
        full_name,
        email,
        gender,
        blood_type,
        organ_required,
        urgency_level,
        request_status,
        recipient_medical_reports as medical_reports,
        id_document,
        medical_condition,
        phone_number,
        date_of_birth,
        address
    FROM recipient_registration
";

if ($filter !== 'all') {
    $sql .= " WHERE LOWER(request_status) = '" . mysqli_real_escape_string($conn, $filter) . "'";
}

$sql .= " ORDER BY 
    CASE 
        WHEN LOWER(urgency_level) = 'high' THEN 1
        WHEN LOWER(urgency_level) = 'medium' THEN 2
        WHEN LOWER(urgency_level) = 'low' THEN 3
        ELSE 4
    END,
    id DESC";

try {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }
    
    $recipients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recipients[] = $row;
    }
    
    // Debug information
    error_log("Recipients found: " . count($recipients));
    error_log("SQL Query: " . $sql);
} catch (Exception $e) {
    error_log("Error fetching recipients: " . $e->getMessage());
    $recipients = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Recipients - LifeLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 20px;
            z-index: 999;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn i {
            margin-right: 8px;
        }

        .hamburger {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            cursor: pointer;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            padding: 8px;
            border-radius: 4px;
            width: 35px;
            height: 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hamburger span {
            display: block;
            width: 18px;
            height: 2px;
            background: white;
            margin: 2px 0;
            transition: all 0.3s ease;
            transform-origin: center;
        }

        .hamburger.active span:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }

        .sidebar {
            position: fixed;
            left: -300px;
            top: 0;
            width: 300px;
            height: 100vh;
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            z-index: 999;
            padding-top: 80px;
        }

        .sidebar.active {
            left: 0;
        }

        .main-content {
            transition: all 0.3s ease;
            margin-left: 0;
            padding-top: 70px;
        }

        .main-content.shifted {
            margin-left: 300px;
        }

        .sidebar-header {
            background: white;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .sidebar-title {
            font-size: 2.2rem;
            font-weight: bold;
            margin: 0;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
        }

        .sidebar-subtitle {
            font-size: 1.3rem;
            color: #333;
            margin-top: 10px;
            font-weight: 600;
        }

        .sidebar-nav {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-button {
            padding: 12px 20px;
            background: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: #333;
            font-size: 1rem;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .filter-button:hover {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(33, 150, 243, 0.1));
            color: #4CAF50;
        }

        .filter-button.active {
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
        }

        .filter-button i {
            width: 20px;
            text-align: center;
        }

        .page-title {
            text-align: center;
            margin: 1rem 0 2rem 0;
            padding: 1rem;
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 1rem 0 2rem 0;
            padding: 0 2rem;
        }

        .filter-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            color: #333;
            transition: all 0.3s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            border-color: #1565C0;
            color: #1565C0;
            background: #f8f9fa;
        }

        .filter-btn i {
            margin-right: 8px;
        }

        .count-badge {
            background: #f5f5f5;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }

        .data-table thead {
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
        }

        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: bold;
            font-size: 1rem;
            text-transform: uppercase;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .data-table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .status-pending { background: #e3f2fd; color: #1565c0; }
        .status-accepted { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        
        .view-btn, .reject-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .view-btn {
            background: #2196F3;
            color: white;
        }

        .view-btn:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .reject-btn {
            background: #f44336;
            color: white;
        }

        .reject-btn:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1002;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .reject-reason {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1rem;
            resize: vertical;
            min-height: 100px;
        }

        .btn-confirm-reject {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-cancel {
            background: #9e9e9e;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Fixed Header -->
    <div class="header-fixed">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Hamburger Menu -->
    <div class="hamburger" id="hamburgerMenu">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>LifeLink</h2>
            <h3>Admin Recipient Management</h3>
        </div>
        <div class="sidebar-nav">
            <a href="?filter=all" class="filter-button <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                All Recipients
                <span class="count-badge"><?php echo $counts['all']; ?></span>
            </a>
            <a href="?filter=pending" class="filter-button <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                Pending
                <span class="count-badge"><?php echo $counts['pending']; ?></span>
            </a>
            <a href="?filter=accepted" class="filter-button <?php echo $filter === 'accepted' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i>
                Accepted
                <span class="count-badge"><?php echo $counts['accepted']; ?></span>
            </a>
            <a href="?filter=rejected" class="filter-button <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                <i class="fas fa-times-circle"></i>
                Rejected
                <span class="count-badge"><?php echo $counts['rejected']; ?></span>
            </a>
            <a href="admin_recipient_analytics.php" class="filter-button">
                <i class="fas fa-chart-pie"></i>
                Analytics
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 class="page-title">Manage Recipients</h1>
        
        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?filter=all'">
                <i class="fas fa-users"></i> All (<?php echo $counts['all']; ?>)
            </button>
            <button class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>"
                    onclick="window.location.href='?filter=pending'">
                <i class="fas fa-clock"></i> Pending (<?php echo $counts['pending']; ?>)
            </button>
            <button class="filter-btn <?php echo $filter === 'accepted' ? 'active' : ''; ?>"
                    onclick="window.location.href='?filter=approved'">
                <i class="fas fa-check-circle"></i> Approved (<?php echo $counts['accepted']; ?>)
            </button>
            <button class="filter-btn <?php echo $filter === 'rejected' ? 'active' : ''; ?>"
                    onclick="window.location.href='?filter=rejected'">
                <i class="fas fa-times-circle"></i> Rejected (<?php echo $counts['rejected']; ?>)
            </button>
        </div>

        <!-- Recipients Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Blood Type</th>
                        <th>Required Organ</th>
                        <th>Urgency Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Debug output
                    error_log("Recipients count: " . count($recipients));
                    error_log("SQL Query: " . $sql);
                    
                    if (empty($recipients)): 
                    ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px; color: #666;">
                                <i class="fas fa-info-circle" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                                No recipients found for the selected filter
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recipients as $recipient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($recipient['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['email']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['gender']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['blood_type']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['organ_required']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($recipient['urgency_level']); ?>">
                                        <?php echo htmlspecialchars($recipient['urgency_level']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($recipient['request_status']); ?>">
                                        <?php 
                                        // Display "Approved" for "accepted" status
                                        echo htmlspecialchars($recipient['request_status'] === 'accepted' ? 'Approved' : ucfirst($recipient['request_status'])); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($recipient['request_status'] !== 'rejected'): ?>
                                        <button class="reject-btn" onclick="showRejectModal(<?php echo $recipient['id']; ?>)">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="view-btn" onclick="viewRecipient(<?php echo $recipient['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Recipient</h3>
            </div>
            <div class="modal-body">
                <p>Please provide a reason for rejection:</p>
                <textarea class="reject-reason" id="rejectReason" placeholder="Enter rejection reason..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="hideRejectModal()">Cancel</button>
                <button class="btn-confirm-reject" onclick="confirmReject()">Confirm Reject</button>
            </div>
        </div>
    </div>

    <script>
        // Store sidebar state in session storage
        if (sessionStorage.getItem('sidebarOpen') === 'true') {
            document.getElementById('sidebar').classList.add('active');
            document.getElementById('mainContent').classList.add('shifted');
            document.getElementById('hamburgerMenu').classList.add('active');
        }

        // Hamburger Menu Functionality
        const hamburger = document.getElementById('hamburgerMenu');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
            
            // Store sidebar state
            sessionStorage.setItem('sidebarOpen', sidebar.classList.contains('active'));
        });

        // Don't add any click handlers to sidebar links - let them work normally

        function viewRecipient(id) {
            window.location.href = `view_recipient_details.php?id=${id}`;
        }

        function showRejectModal(recipientId) {
            currentRecipientId = recipientId;
            document.getElementById('rejectModal').style.display = 'block';
            document.getElementById('rejectReason').value = '';
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            currentRecipientId = null;
        }

        function confirmReject() {
            const reason = document.getElementById('rejectReason').value.trim();
            
            if (!reason) {
                alert('Please provide a reason for rejection');
                return;
            }

            // Send rejection request to server
            fetch('../../backend/php/reject_recipient.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    recipient_id: currentRecipientId,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Recipient rejected successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while rejecting the recipient');
            })
            .finally(() => {
                hideRejectModal();
            });
        }
    </script>
</body>
</html>
