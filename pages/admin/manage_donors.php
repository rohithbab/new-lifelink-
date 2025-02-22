<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Get status filter from URL parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the SQL query based on status filter
$query = "
    SELECT 
        d.*,
        d.donor_id,
        d.name,
        d.email,
        d.gender,
        d.blood_group,
        d.organs_to_donate,
        d.status,
        d.created_at,
        d.id_proof_path,
        d.medical_reports_path,
        d.guardian_id_proof_path
    FROM donor d";

// Add WHERE clause for status filtering
if ($status_filter !== 'all') {
    $query .= " WHERE d.status = '" . $conn->real_escape_string($status_filter) . "'";
}

// Add ORDER BY clause
$query .= " ORDER BY 
    CASE d.status
        WHEN 'Approved' THEN 1
        WHEN 'Rejected' THEN 2
        WHEN 'Pending' THEN 3
    END,
    d.created_at DESC";

$result = $conn->query($query);
$donors = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donors[] = $row;
    }
}

// Count donors by status
$status_counts = [
    'all' => 0,
    'Pending' => 0,
    'Approved' => 0,
    'Rejected' => 0
];

$count_query = "SELECT status, COUNT(*) as count FROM donor GROUP BY status";
$count_result = $conn->query($count_query);
if ($count_result) {
    while ($row = $count_result->fetch_assoc()) {
        $status_counts[$row['status']] = $row['count'];
    }
}
$status_counts['all'] = array_sum([$status_counts['Pending'], $status_counts['Approved'], $status_counts['Rejected']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donors - LifeLink Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Hamburger Menu & Sidebar Styles */
        .hamburger-menu {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            cursor: pointer;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .hamburger-menu:hover {
            transform: scale(1.1);
        }

        .hamburger-menu .bar {
            width: 25px;
            height: 3px;
            background: white;
            margin: 5px 0;
            transition: transform 0.3s ease-in-out, opacity 0.2s ease-in-out;
        }

        .hamburger-menu.active .bar:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }

        .hamburger-menu.active .bar:nth-child(2) {
            opacity: 0;
        }

        .hamburger-menu.active .bar:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
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
            padding-top: 20px;
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px;
            border-bottom: 2px solid rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 2.5em;
            margin: 0 0 10px 0;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        .sidebar-header h3 {
            font-size: 1.2em;
            color: #333;
            margin: 0;
            font-weight: 500;
        }

        .content-wrapper {
            transition: all 0.3s ease;
            margin-left: 0;
            padding: 20px;
            padding-top: 80px; /* Added to prevent content from going under hamburger */
        }

        .content-wrapper.shifted {
            margin-left: 300px;
        }

        .page-title {
            font-size: 2.5em;
            margin: 40px 0 30px;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
            text-align: center;
        }

        .donor-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .donor-table th {
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .donor-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #333;
            font-weight: 500;
        }

        .donor-table tr.status-approved {
            background-color: #d4edda;
            transition: background-color 0.3s ease;
        }

        .donor-table tr.status-approved:hover {
            background-color: #c3e6cb;
        }

        .donor-table tr.status-rejected {
            background-color: #f8d7da;
            transition: background-color 0.3s ease;
        }

        .donor-table tr.status-rejected:hover {
            background-color: #f5c6cb;
        }

        .donor-table tr.status-pending {
            background-color: #fff3cd;
            transition: background-color 0.3s ease;
        }

        .donor-table tr.status-pending:hover {
            background-color: #ffeeba;
        }

        /* Status Buttons */
        .status-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-width: 110px;
            cursor: default;
            transition: all 0.3s ease;
        }

        .status-btn.pending {
            background: linear-gradient(135deg, #ffc107, #ffdb4d);
            color: #000;
        }

        .status-btn.approved {
            background: linear-gradient(135deg, #28a745, #34ce57);
            color: white;
        }

        .status-btn.rejected {
            background: linear-gradient(135deg, #dc3545, #ff4444);
            color: white;
        }

        .status-btn i {
            font-size: 1em;
        }

        .view-btn {
            padding: 6px 12px;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .filter-buttons {
            margin: 20px 0;
            text-align: center;
        }

        .filter-btn {
            padding: 8px 15px;
            margin-right: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            background-color: #f0f0f0;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .filter-btn:hover {
            background-color: #e0e0e0;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
        }
        
        .status-count {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.9em;
            margin-left: 5px;
        }

        .active .status-count {
            background: rgba(255, 255, 255, 0.3);
        }

        .back-button {
            position: absolute;
            top: 20px;
            right: 20px;
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .back-button:hover {
            transform: translateY(-2px);
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 10px;
        }

        .sidebar-nav a {
            display: block;
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-nav a:hover {
            background: #f0f0f0;
        }

        .sidebar-nav a.active {
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            display: none;
        }

        /* Reject Button Style */
        .reject-btn {
            padding: 6px 12px;
            background: linear-gradient(135deg, #dc3545, #ff4444);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .reject-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .confirm-btn {
            background: linear-gradient(135deg, #dc3545, #ff4444);
            color: white;
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Hamburger Menu -->
    <div class="hamburger-menu" id="hamburgerMenu">
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>LifeLink</h2>
            <h3>Admin Donor Management</h3>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        All Donors
                        <span class="status-count"><?php echo $status_counts['all']; ?></span>
                    </a>
                </li>
                <li>
                    <a href="?status=Pending" class="filter-btn <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>">
                        Pending Donors
                        <span class="status-count"><?php echo $status_counts['Pending']; ?></span>
                    </a>
                </li>
                <li>
                    <a href="?status=Approved" class="filter-btn <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>">
                        Approved Donors
                        <span class="status-count"><?php echo $status_counts['Approved']; ?></span>
                    </a>
                </li>
                <li>
                    <a href="?status=Rejected" class="filter-btn <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>">
                        Rejected Donors
                        <span class="status-count"><?php echo $status_counts['Rejected']; ?></span>
                    </a>
                </li>
                <li>
                    <a href="donor_analytics.php" class="filter-btn">
                        <i class="fas fa-chart-pie"></i> Donor Analytics
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper" id="contentWrapper">
        <div class="admin-container">
            <main class="admin-main">
                <a href="admin_dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>&nbsp; Back to Dashboard
                </a>

                <h1 class="page-title">Manage Donors</h1>

                <!-- Top Filter Buttons -->
                <div class="filter-buttons">
                    <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        All Donors <span class="status-count"><?php echo $status_counts['all']; ?></span>
                    </a>
                    <a href="?status=Pending" class="filter-btn <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>">
                        Pending Donors <span class="status-count"><?php echo $status_counts['Pending']; ?></span>
                    </a>
                    <a href="?status=Approved" class="filter-btn <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>">
                        Approved Donors <span class="status-count"><?php echo $status_counts['Approved']; ?></span>
                    </a>
                    <a href="?status=Rejected" class="filter-btn <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>">
                        Rejected Donors <span class="status-count"><?php echo $status_counts['Rejected']; ?></span>
                    </a>
                </div>

                <table class="donor-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th>Blood Group</th>
                            <th>Organs to Donate</th>
                            <th>Status</th>
                            <th>ID Proof</th>
                            <th>Medical Reports</th>
                            <th>Guardian ID</th>
                            <th>Actions</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donors as $donor): ?>
                        <tr class="status-<?php echo strtolower($donor['status']); ?>">
                            <td><?php echo htmlspecialchars($donor['name']); ?></td>
                            <td><?php echo htmlspecialchars($donor['email']); ?></td>
                            <td><?php echo htmlspecialchars($donor['gender']); ?></td>
                            <td><?php echo htmlspecialchars($donor['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($donor['organs_to_donate']); ?></td>
                            <td>
                                <?php 
                                $statusIcon = '';
                                $statusClass = '';
                                
                                switch($donor['status']) {
                                    case 'Approved':
                                        $statusIcon = '<i class="fas fa-check-circle"></i>';
                                        $statusClass = 'approved';
                                        break;
                                    case 'Rejected':
                                        $statusIcon = '<i class="fas fa-times-circle"></i>';
                                        $statusClass = 'rejected';
                                        break;
                                    case 'Pending':
                                        $statusIcon = '<i class="fas fa-clock"></i>';
                                        $statusClass = 'pending';
                                        break;
                                    default:
                                        $statusIcon = '<i class="fas fa-clock"></i>';
                                        $statusClass = 'pending';
                                }
                                ?>
                                <button class="status-btn <?php echo $statusClass; ?>" style="background: <?php 
                                    switch($statusClass) {
                                        case 'approved':
                                            echo 'linear-gradient(135deg, #28a745, #34ce57)';
                                            break;
                                        case 'rejected':
                                            echo 'linear-gradient(135deg, #dc3545, #ff4444)';
                                            break;
                                        default:
                                            echo 'linear-gradient(135deg, #ffc107, #ffdb4d)';
                                    }
                                ?>; color: <?php echo $statusClass === 'pending' ? '#000' : '#fff'; ?>;">
                                    <?php echo $statusIcon . ' ' . $donor['status']; ?>
                                </button>
                            </td>
                            <td>
                                <?php if (!empty($donor['id_proof_path'])): ?>
                                    <a href="view_id_proof.php?donor_id=<?php echo $donor['donor_id']; ?>&type=donor" 
                                       target="_blank" class="view-button">
                                        <i class="fas fa-eye"></i> View ID
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No ID proof</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($donor['medical_reports_path'])): ?>
                                    <a href="view_medical_report.php?type=donor&id=<?php echo $donor['donor_id']; ?>" 
                                       target="_blank" class="view-button">
                                        <i class="fas fa-file-medical"></i> View Reports
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No reports</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($donor['guardian_id_proof_path'])): ?>
                                    <a href="view_id_proof.php?donor_id=<?php echo $donor['donor_id']; ?>&type=guardian" 
                                       target="_blank" class="view-button">
                                        <i class="fas fa-id-card"></i> View Guardian ID
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No guardian ID</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="reject-btn" onclick="showRejectModal(<?php echo $donor['donor_id']; ?>)">
                                    <i class="fas fa-times-circle"></i> Reject
                                </button>
                            </td>
                            <td>
                                <button class="view-btn" onclick="viewDonor(<?php echo $donor['donor_id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Donor</h3>
            </div>
            <div class="modal-body">
                <p>Please provide a reason for rejection:</p>
                <textarea id="rejectionReason" placeholder="Enter rejection reason..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-btn" onclick="hideRejectModal()">Cancel</button>
                <button class="modal-btn confirm-btn" onclick="confirmReject()">Confirm Rejection</button>
            </div>
        </div>
    </div>

    <script>
        // Hamburger Menu Functionality
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const contentWrapper = document.getElementById('contentWrapper');
        const sidebar = document.getElementById('sidebar');

        // Check if sidebar state is stored in localStorage
        const sidebarState = localStorage.getItem('sidebarState');
        if (sidebarState === 'open') {
            sidebar.classList.add('active');
            contentWrapper.classList.add('shifted');
            hamburgerMenu.classList.add('active');
        }

        hamburgerMenu.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            contentWrapper.classList.toggle('shifted');
            hamburgerMenu.classList.toggle('active');
            
            // Store sidebar state in localStorage
            if (sidebar.classList.contains('active')) {
                localStorage.setItem('sidebarState', 'open');
            } else {
                localStorage.setItem('sidebarState', 'closed');
            }
        });

        // Prevent sidebar from closing when clicking links
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', (e) => {
                // Don't prevent default - let the link work normally
                // Just make sure we keep the sidebar state
                if (sidebar.classList.contains('active')) {
                    localStorage.setItem('sidebarState', 'open');
                }
            });
        });

        function viewDonor(donorId) {
            window.location.href = `view_donor_details.php?id=${donorId}`;
        }

        let currentDonorId = null;

        function showRejectModal(donorId) {
            currentDonorId = donorId;
            document.getElementById('rejectModal').style.display = 'block';
            document.getElementById('rejectionReason').value = '';
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            currentDonorId = null;
        }

        function confirmReject() {
            const reason = document.getElementById('rejectionReason').value.trim();
            if (!reason) {
                alert('Please provide a reason for rejection');
                return;
            }

            // Send rejection request to server
            fetch('update_donor_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    donor_id: currentDonorId,
                    status: 'rejected',
                    rejection_reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Donor has been rejected successfully');
                    location.reload(); // Refresh the page to show updated status
                } else {
                    alert('Error: ' + (data.message || 'Failed to reject donor'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while rejecting the donor');
            })
            .finally(() => {
                hideRejectModal();
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rejectModal');
            if (event.target === modal) {
                hideRejectModal();
            }
        }
    </script>
</body>
</html>
