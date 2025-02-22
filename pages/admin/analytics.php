<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get analytics data
$monthlyStats = getMonthlyStats($conn);
$organTypeStats = getOrganTypeStats($conn);
$bloodTypeStats = getBloodTypeStats($conn);
$successfulMatches = getSuccessfulMatches($conn);
$regionalStats = getRegionalStats($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - LifeLink Admin</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="logo">
                <span class="logo-life">Life</span>Link Admin
            </a>
            <div class="nav-links">
                <a href="view-hospitals.php">Hospitals</a>
                <a href="view-donors.php">Donors</a>
                <a href="view-recipients.php">Recipients</a>
                <a href="analytics.php" class="active">Analytics</a>
                <a href="notifications.php">Notifications</a>
                <a href="../logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="analytics-header">
            <h1>Analytics Dashboard</h1>
            <div class="date-filter">
                <select id="timeRange">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 3 months</option>
                    <option value="365">Last year</option>
                </select>
            </div>
        </div>

        <div class="analytics-grid">
            <!-- Registration Trends -->
            <div class="card chart-card">
                <h2>Registration Trends</h2>
                <canvas id="registrationChart"></canvas>
            </div>

            <!-- Organ Type Distribution -->
            <div class="card chart-card">
                <h2>Organ Type Distribution</h2>
                <canvas id="organTypeChart"></canvas>
            </div>

            <!-- Blood Type Distribution -->
            <div class="card chart-card">
                <h2>Blood Type Distribution</h2>
                <canvas id="bloodTypeChart"></canvas>
            </div>

            <!-- Success Rate -->
            <div class="card chart-card">
                <h2>Successful Matches</h2>
                <canvas id="successRateChart"></canvas>
            </div>

            <!-- Regional Distribution -->
            <div class="card chart-card full-width">
                <h2>Regional Distribution</h2>
                <canvas id="regionalChart"></canvas>
            </div>

            <!-- Key Metrics -->
            <div class="card metrics-card">
                <h2>Key Metrics</h2>
                <div class="metrics-grid">
                    <div class="metric">
                        <h3>Average Match Time</h3>
                        <p><?php echo $monthlyStats['avg_match_time']; ?> days</p>
                    </div>
                    <div class="metric">
                        <h3>Success Rate</h3>
                        <p><?php echo $monthlyStats['success_rate']; ?>%</p>
                    </div>
                    <div class="metric">
                        <h3>Active Donors</h3>
                        <p><?php echo $monthlyStats['active_donors']; ?></p>
                    </div>
                    <div class="metric">
                        <h3>Urgent Cases</h3>
                        <p><?php echo $monthlyStats['urgent_cases']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/analytics.js"></script>
</body>
</html>
