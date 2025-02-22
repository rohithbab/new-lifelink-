<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Fetch statistics from database
$stats = array(
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
);

// Get total count
$total_query = "SELECT COUNT(*) as total FROM hospitals";
$result = $conn->query($total_query);
if ($result) {
    $stats['total'] = $result->fetch_assoc()['total'];
}

// Get count by status
$status_query = "SELECT status, COUNT(*) as count FROM hospitals GROUP BY status";
$result = $conn->query($status_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
    }
}

// Convert to JSON for JavaScript
$stats_json = json_encode($stats);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Analytics - LifeLink Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-5px);
        }

        .chart-container {
            position: relative;
            height: 200px;
            margin: 10px 0;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 10px;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <main class="admin-main">
            <a href="manage_hospitals.php" class="back-button">
                <i class="fas fa-arrow-left"></i>&nbsp; Back to Hospitals
            </a>
            
            <h1 class="page-title">Hospital Analytics</h1>
            
            <div class="analytics-grid">
                <div class="chart-card">
                    <div class="stat-label">Total Hospitals</div>
                    <div class="chart-container">
                        <canvas id="totalHospitalsChart"></canvas>
                    </div>
                    <div class="stat-value" id="totalHospitals">0</div>
                </div>
                
                <div class="chart-card">
                    <div class="stat-label">Pending Approvals</div>
                    <div class="chart-container">
                        <canvas id="pendingHospitalsChart"></canvas>
                    </div>
                    <div class="stat-value" id="pendingHospitals">0</div>
                </div>
                
                <div class="chart-card">
                    <div class="stat-label">Approved Hospitals</div>
                    <div class="chart-container">
                        <canvas id="approvedHospitalsChart"></canvas>
                    </div>
                    <div class="stat-value" id="approvedHospitals">0</div>
                </div>
                
                <div class="chart-card">
                    <div class="stat-label">Rejected Hospitals</div>
                    <div class="chart-container">
                        <canvas id="rejectedHospitalsChart"></canvas>
                    </div>
                    <div class="stat-value" id="rejectedHospitals">0</div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Get statistics from PHP
        const stats = <?php echo $stats_json; ?>;
        
        function createRingChart(canvasId, color, hoverColor) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [0, 100],
                        backgroundColor: [color, '#f0f0f0'],
                        hoverBackgroundColor: [hoverColor, '#f0f0f0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '75%',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000,
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

        // Initialize charts with gradient colors
        const charts = {
            total: createRingChart('totalHospitalsChart', '#4285f4', '#1a73e8'),    // Google Blue
            pending: createRingChart('pendingHospitalsChart', '#fbbc05', '#f9a825'), // Google Yellow
            approved: createRingChart('approvedHospitalsChart', '#34a853', '#2e7d32'), // Google Green
            rejected: createRingChart('rejectedHospitalsChart', '#ea4335', '#c62828')  // Google Red
        };

        // Update stat values
        document.getElementById('totalHospitals').textContent = stats.total;
        document.getElementById('pendingHospitals').textContent = stats.pending;
        document.getElementById('approvedHospitals').textContent = stats.approved;
        document.getElementById('rejectedHospitals').textContent = stats.rejected;

        // Calculate percentages for charts
        const maxValue = Math.max(stats.total, 100); // Use at least 100 for scale

        // Animate charts
        setTimeout(() => {
            charts.total.data.datasets[0].data = [stats.total, Math.max(0, maxValue - stats.total)];
            charts.pending.data.datasets[0].data = [stats.pending, stats.total - stats.pending];
            charts.approved.data.datasets[0].data = [stats.approved, stats.total - stats.approved];
            charts.rejected.data.datasets[0].data = [stats.rejected, stats.total - stats.rejected];
            
            Object.values(charts).forEach(chart => chart.update());
        }, 500);

        // Auto-refresh every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
