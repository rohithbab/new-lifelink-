<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/organ_matches.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Debug: Check database connection
try {
    $conn->query("SELECT 1");
    error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
}

// Get matches
$matches = getAllOrganMatches($conn);

// Debug: Log the number of matches
error_log("Number of matches in page: " . count($matches));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organ Matches - Admin Dashboard</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/notification-bell.css">
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
                    <a href="admin_dashboard.php" class="nav-link">
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
                    <a href="organ_match_info_for_admin.php" class="nav-link active">
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
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <main>
            <div class="dashboard-header">
                <h1>Organ Matches</h1>
            </div>
            
            <div class="container">
                <h2>Organ Match History</h2>
                
                <!-- Search Bar -->
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search...">
                    <button onclick="search()">Search</button>
                </div>

                <!-- Matches Table -->
                <div class="table-container">
                    <table class="matches-table">
                        <thead>
                            <tr>
                                <th>Match ID</th>
                                <th>Match Made By</th>
                                <th>Donor ID</th>
                                <th>Donor Name</th>
                                <th>Donor Hospital ID</th>
                                <th>Donor Hospital</th>
                                <th>Recipient ID</th>
                                <th>Recipient Name</th>
                                <th>Recipient Hospital ID</th>
                                <th>Recipient Hospital</th>
                                <th>Organ Type</th>
                                <th>Blood Group</th>
                                <th>Match Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (!empty($matches)): 
                                foreach ($matches as $match): 
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($match['match_id']); ?></td>
                                    <td><?php echo htmlspecialchars($match['match_made_by']); ?></td>
                                    <td><?php echo htmlspecialchars($match['donor_id']); ?></td>
                                    <td><?php echo htmlspecialchars($match['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($match['donor_hospital_id']); ?></td>
                                    <td><?php echo htmlspecialchars($match['donor_hospital_name']); ?></td>
                                    <td><?php echo htmlspecialchars($match['recipient_id']); ?></td>
                                    <td><?php echo htmlspecialchars($match['recipient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($match['recipient_hospital_id']); ?></td>
                                    <td><?php echo htmlspecialchars($match['recipient_hospital_name']); ?></td>
                                    <td><?php echo htmlspecialchars($match['organ_type']); ?></td>
                                    <td><?php echo htmlspecialchars($match['blood_group']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($match['match_date'])); ?></td>
                                </tr>
                            <?php 
                                endforeach; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="13" style="text-align: center;">No matches found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <style>
                /* Prevent horizontal scroll on body and main container */
                body {
                    overflow-x: hidden;
                }

                .sidebar {
                    z-index: 1000;
                    position: fixed;
                }

                main {
                    margin-left: 250px;
                    padding: 20px;
                    min-height: 100vh;
                    background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(33, 150, 243, 0.1));
                    overflow-x: hidden;
                    width: calc(100vw - 250px); /* Account for sidebar */
                    box-sizing: border-box;
                }

                .container {
                    width: 100%;
                    overflow-x: hidden;
                }

                .dashboard-header, .search-bar {
                    width: calc(100% - 40px);
                    position: relative;
                    background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(33, 150, 243, 0.1));
                }

                .search-bar {
                    margin-bottom: 20px;
                    display: flex;
                    gap: 10px;
                    max-width: 600px;
                }

                .search-bar input {
                    padding: 12px;
                    border: 1px solid #4CAF50;
                    border-radius: 6px;
                    width: 100%;
                    font-size: 14px;
                }

                .search-bar button {
                    padding: 12px 20px;
                    background: linear-gradient(135deg, #4CAF50, #2196F3);
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: bold;
                    white-space: nowrap;
                }

                .table-container {
                    overflow-x: auto;
                    margin: 20px -20px;
                    padding: 0 20px;
                    width: calc(100% + 40px);
                }

                .matches-table {
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }

                .matches-table th {
                    background: linear-gradient(135deg, #4CAF50, #2196F3);
                    color: white;
                    font-weight: 600;
                    padding: 15px;
                    text-align: left;
                    white-space: nowrap;
                    position: sticky;
                    top: 0;
                }

                .matches-table td {
                    padding: 15px;
                    text-align: left;
                    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                    white-space: nowrap;
                }

                @media screen and (max-width: 1024px) {
                    main {
                        margin-left: 0;
                    }
                }
            </style>
        </main>
    </div>

    <script>
        function search() {
            const searchTerm = document.getElementById('searchInput').value;
            window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
        }

        // Enable search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                search();
            }
        });
    </script>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/notifications.js"></script>
</body>
</html>
