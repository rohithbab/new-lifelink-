<?php
session_start();

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    header("Location: ../hospital_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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
            color: #007bff;
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
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .action-button {
            background: #007bff;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .action-button:hover {
            background: #0056b3;
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
        .logout-button {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .logout-button:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <a href="../logout.php" class="logout-button">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['hospital_name']); ?>!</h1>
            <p>ODML ID: <?php echo htmlspecialchars($_SESSION['odml_id']); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-user-plus"></i>
                <div class="stat-number">0</div>
                <div class="stat-label">Registered Donors</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-procedures"></i>
                <div class="stat-number">0</div>
                <div class="stat-label">Active Recipients</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-handshake"></i>
                <div class="stat-number">0</div>
                <div class="stat-label">Successful Matches</div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="register_donor.php" class="action-button">
                <i class="fas fa-user-plus"></i> Register New Donor
            </a>
            <a href="manage_recipients.php" class="action-button">
                <i class="fas fa-users"></i> Manage Recipients
            </a>
            <a href="view_matches.php" class="action-button">
                <i class="fas fa-handshake"></i> View Matches
            </a>
            <a href="update_profile.php" class="action-button">
                <i class="fas fa-hospital-user"></i> Update Hospital Profile
            </a>
        </div>

        <div class="recent-activity">
            <h2>Recent Activity</h2>
            <ul class="activity-list">
                <li class="activity-item">
                    <i class="fas fa-info-circle"></i> Welcome to your dashboard! Start by registering donors or managing recipients.
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // You can add dynamic functionality here
        });
    </script>
</body>
</html>
