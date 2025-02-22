<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Handle hospital rejection
if (isset($_POST['reject_hospital'])) {
    $hospital_id = $_POST['hospital_id'];
    $rejection_reason = $_POST['rejection_reason'];
    
    // Update hospital status
    $update_query = "UPDATE hospitals SET status = 'rejected' WHERE hospital_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    
    // Store rejection details in session
    $_SESSION['rejection_details'][$hospital_id] = [
        'reason' => $rejection_reason,
        'date' => date('Y-m-d H:i:s'),
        'email_sent' => false
    ];
    
    // Send email notification
    $get_hospital = "SELECT name, email FROM hospitals WHERE hospital_id = ?";
    $stmt = $conn->prepare($get_hospital);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospital = $result->fetch_assoc();
    
    $to = $hospital['email'];
    $subject = "Hospital Registration Status Update - LifeLink";
    $message = "Dear " . $hospital['name'] . ",\n\n";
    $message .= "Your hospital registration with LifeLink has been reviewed by the admin.\n\n";
    $message .= "Status: REJECTED\n";
    $message .= "Reason: " . $rejection_reason . "\n\n";
    $message .= "If you wish to reapply, please ensure to address the above concerns.\n\n";
    $message .= "Best Regards,\nLifeLink Admin Team";
    
    mail($to, $subject, $message);
    $_SESSION['rejection_details'][$hospital_id]['email_sent'] = true;
}

// Fetch all hospitals with their status
$query = "
    SELECT 
        hospital_id,
        name,
        email,
        phone,
        address,
        license_number,
        license_file,
        status,
        created_at,
        CASE 
            WHEN status = 'pending' AND created_at > NOW() - INTERVAL 24 HOUR 
            THEN 1 ELSE 0 
        END as is_new
    FROM hospitals 
    ORDER BY 
        CASE status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
        END,
        created_at DESC
";

$result = $conn->query($query);
$hospitals = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hospitals[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hospitals - LifeLink Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Existing styles remain... */
        
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
            transition: all 0.3s ease;
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

        .content-wrapper {
            transition: all 0.3s ease;
            margin-left: 0;
        }

        .content-wrapper.shifted {
            margin-left: 300px;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 20px;
        }

        .sidebar-header h2 {
            font-size: 2.2em;
            font-weight: bold;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            padding: 0;
        }

        .sidebar-header h3 {
            color: #333;
            margin-top: 10px;
            font-size: 1.2em;
        }

        .sidebar-nav {
            padding: 20px;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 15px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-nav i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 998;
        }

        @media (max-width: 768px) {
            .content-wrapper.shifted {
                margin-left: 0;
                transform: translateX(300px);
            }
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-main {
            flex: 1;
            padding: 20px;
            position: relative;
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
        
        .page-title {
            font-size: 2.5em;
            margin: 40px 0 30px;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
            text-align: center;
        }
        
        .hospital-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .hospital-table th {
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .hospital-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #333;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .pending { background-color: #ffd700; color: #000; }
        .approved { background-color: #34a853; color: white; }
        .rejected { background-color: #dc3545; color: white; }
        
        .action-buttons button {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
            font-weight: 500;
        }
        
        .view-btn {
            background-color: #1a73e8;
            color: white;
        }
        
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            width: 50%;
            border-radius: 5px;
        }
        
        .close {
            float: right;
            cursor: pointer;
            font-size: 24px;
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
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
        }

        .new-badge {
            background: #ff6b6b;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7em;
            margin-left: 5px;
            font-weight: bold;
        }
        
        .analytics-container {
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            margin: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .analytics-title {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .analytics-title i {
            margin-right: 10px;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 150px;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            text-align: center;
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
            <h3>Admin Hospital Management</h3>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="#all" class="active">
                        <i class="fas fa-hospital"></i>
                        All Hospitals
                    </a>
                </li>
                <li>
                    <a href="#pending">
                        <i class="fas fa-clock"></i>
                        Pending Approvals
                    </a>
                </li>
                <li>
                    <a href="#approved">
                        <i class="fas fa-check-circle"></i>
                        Approved Hospitals
                    </a>
                </li>
                <li>
                    <a href="#rejected">
                        <i class="fas fa-times-circle"></i>
                        Rejected Hospitals
                    </a>
                </li>
                <li>
                    <a href="manage_hospitals_analytics.php" onclick="window.location.href='manage_hospitals_analytics.php'; return false;">
                        <i class="fas fa-chart-pie"></i>
                        Hospital Analytics
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Analytics Section -->
        <div class="analytics-container" id="analyticsContainer" style="display: none;">
            <div class="analytics-title">
                <i class="fas fa-chart-pie"></i>
                Hospital Statistics
            </div>
            <div class="chart-container">
                <canvas id="totalHospitalsChart"></canvas>
                <div class="stat-label">Total Hospitals</div>
                <div class="stat-value" id="totalHospitals">0</div>
            </div>
            <div class="chart-container">
                <canvas id="pendingHospitalsChart"></canvas>
                <div class="stat-label">Pending Hospitals</div>
                <div class="stat-value" id="pendingHospitals">0</div>
            </div>
            <div class="chart-container">
                <canvas id="approvedHospitalsChart"></canvas>
                <div class="stat-label">Approved Hospitals</div>
                <div class="stat-value" id="approvedHospitals">0</div>
            </div>
            <div class="chart-container">
                <canvas id="rejectedHospitalsChart"></canvas>
                <div class="stat-label">Rejected Hospitals</div>
                <div class="stat-value" id="rejectedHospitals">0</div>
            </div>
        </div>
    </div>

    <!-- Dark Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Main Content -->
    <div class="content-wrapper" id="contentWrapper">
        <div class="admin-container">
            <main class="admin-main">
                <a href="admin_dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>&nbsp; Back to Dashboard
                </a>
                
                <h1 class="page-title">Manage Hospitals</h1>
                
                <div class="filter-buttons">
                    <button class="filter-btn active" data-status="all">All</button>
                    <button class="filter-btn" data-status="pending">Pending</button>
                    <button class="filter-btn" data-status="approved">Approved</button>
                    <button class="filter-btn" data-status="rejected">Rejected</button>
                </div>
                
                <table class="hospital-table">
                    <thead>
                        <tr>
                            <th>Hospital Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>License Number</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hospitals as $row): ?>
                            <tr class="hospital-row <?php echo $row['status']; ?>">
                                <td>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                    <?php if ($row['is_new']): ?>
                                        <span class="new-badge">New</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['license_number']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <?php if ($row['status'] !== 'rejected'): ?>
                                        <button class="reject-btn" onclick="showRejectModal(<?php echo $row['hospital_id']; ?>)">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="view-btn" onclick="viewHospital(<?php echo $row['hospital_id']; ?>)">
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

    <!-- Reject Hospital Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Reject Hospital</h3>
            <form id="rejectForm" method="POST">
                <input type="hidden" name="hospital_id" id="reject_hospital_id">
                <div>
                    <label for="rejection_reason">Reason for Rejection:</label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="4" required></textarea>
                </div>
                <button type="submit" name="reject_hospital" class="reject-btn">
                    Confirm Rejection
                </button>
            </form>
        </div>
    </div>

    <script>
        // Existing script content...

        // Hamburger Menu Functionality
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const sidebar = document.getElementById('sidebar');
        const contentWrapper = document.getElementById('contentWrapper');
        const overlay = document.getElementById('overlay');

        hamburgerMenu.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            contentWrapper.classList.toggle('shifted');
            overlay.style.display = overlay.style.display === 'block' ? 'none' : 'block';
            
            // Animate hamburger to X
            const bars = hamburgerMenu.getElementsByClassName('bar');
            hamburgerMenu.classList.toggle('active');
            if (hamburgerMenu.classList.contains('active')) {
                bars[0].style.transform = 'rotate(-45deg) translate(-5px, 6px)';
                bars[1].style.opacity = '0';
                bars[2].style.transform = 'rotate(45deg) translate(-5px, -6px)';
            } else {
                bars[0].style.transform = 'none';
                bars[1].style.opacity = '1';
                bars[2].style.transform = 'none';
            }
        }

        // Handle sidebar links
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const status = this.getAttribute('href').replace('#', '');
                
                // Update active link
                document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
                this.classList.add('active');
                
                // Filter table
                if (status !== 'reports') {
                    const filterBtn = document.querySelector(`.filter-btn[data-status="${status}"]`);
                    if (filterBtn) {
                        filterBtn.click();
                    }
                }
                
                // Close sidebar on mobile
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Filter table rows
                const status = this.dataset.status;
                document.querySelectorAll('.hospital-row').forEach(row => {
                    if (status === 'all' || row.classList.contains(status)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Modal functionality
        const modal = document.getElementById('rejectModal');
        const span = document.getElementsByClassName('close')[0];

        function showRejectModal(hospitalId) {
            document.getElementById('reject_hospital_id').value = hospitalId;
            modal.style.display = 'block';
        }

        span.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function viewHospital(hospitalId) {
            // Implement view functionality
            // This will show detailed hospital information
            window.location.href = `view_hospital.php?id=${hospitalId}`;
        }

        // Analytics functionality
        const analyticsLink = document.getElementById('analyticsLink');
        const analyticsContainer = document.getElementById('analyticsContainer');
        let charts = {};

        function createRingChart(canvasId, color) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [0, 100],
                        backgroundColor: [color, '#f0f0f0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '80%',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    }
                }
            });
        }

        function updateCharts(hospitals) {
            const total = hospitals.length;
            const pending = hospitals.filter(h => h.status === 'pending').length;
            const approved = hospitals.filter(h => h.status === 'approved').length;
            const rejected = hospitals.filter(h => h.status === 'rejected').length;

            // Update values
            document.getElementById('totalHospitals').textContent = total;
            document.getElementById('pendingHospitals').textContent = pending;
            document.getElementById('approvedHospitals').textContent = approved;
            document.getElementById('rejectedHospitals').textContent = rejected;

            // Update charts
            charts.total.data.datasets[0].data = [total, Math.max(0, 100 - total)];
            charts.pending.data.datasets[0].data = [pending, total - pending];
            charts.approved.data.datasets[0].data = [approved, total - approved];
            charts.rejected.data.datasets[0].data = [rejected, total - rejected];

            Object.values(charts).forEach(chart => chart.update());
        }

        analyticsLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Toggle analytics container
            const isHidden = analyticsContainer.style.display === 'none';
            analyticsContainer.style.display = isHidden ? 'block' : 'none';
            
            if (isHidden) {
                // Initialize charts if not already done
                if (!charts.total) {
                    charts.total = createRingChart('totalHospitalsChart', '#1a73e8');
                    charts.pending = createRingChart('pendingHospitalsChart', '#ffd700');
                    charts.approved = createRingChart('approvedHospitalsChart', '#34a853');
                    charts.rejected = createRingChart('rejectedHospitalsChart', '#dc3545');
                }
                
                // Update charts with current data
                updateCharts(<?php echo json_encode($hospitals); ?>);
            }
        });
    </script>
</body>
</html>
