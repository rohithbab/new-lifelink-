<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get analytics data
$donorStats = getAnalyticsDonorStats($conn);
$recipientStats = getAnalyticsRecipientStats($conn);
$hospitalStats = getAnalyticsHospitalStats($conn);
$organMatchStats = getAnalyticsOrganMatchStats($conn);
$totalUsersStats = getAnalyticsTotalUsersStats($conn);
$rejectionStats = getAnalyticsRejectionStats($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-container {
            width: 100%;
            min-height: 100vh;
            background: #f5f5f5;
            position: relative;
            padding: 0;
            margin: 0;
        }
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 100%;
            margin: 0;
            justify-content: start;
        }
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
        }
        .chart-card h2 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2rem;
            text-align: center;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 0;
        }
        .chart-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .stat-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        .stat-value {
            font-weight: bold;
            color: #333;
        }
        .back-button {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            transition: transform 0.2s;
            z-index: 100;
        }
        .back-button:hover {
            transform: translateY(-2px);
        }
        .main-content {
            padding-top: 60px;
            width: 100%;
            max-width: none;
            margin: 0;
            padding-left: 0;
        }
        @media (max-width: 1200px) {
            .analytics-grid {
                grid-template-columns: repeat(2, minmax(300px, 1fr));
            }
        }
        @media (max-width: 768px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0;">
    <div class="admin-container">
        <!-- Back to Dashboard Button -->
        <a href="admin_dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- Main Content -->
        <div class="main-content">
            <div class="analytics-grid">
                <!-- Donors Chart -->
                <div class="chart-card">
                    <h2>Donor Statistics</h2>
                    <div class="chart-container">
                        <canvas id="donorsChart"></canvas>
                    </div>
                    <div class="chart-stats">
                        <div class="stat-item">
                            <div class="stat-color" style="background: #4CAF50;"></div>
                            <span class="stat-label">Approved:</span>
                            <span class="stat-value"><?php echo $donorStats['approved']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #F44336;"></div>
                            <span class="stat-label">Rejected:</span>
                            <span class="stat-value"><?php echo $donorStats['rejected']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #FFC107;"></div>
                            <span class="stat-label">Pending:</span>
                            <span class="stat-value"><?php echo $donorStats['pending']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recipients Chart -->
                <div class="chart-card">
                    <h2>Recipient Statistics</h2>
                    <div class="chart-container">
                        <canvas id="recipientsChart"></canvas>
                    </div>
                    <div class="chart-stats">
                        <div class="stat-item">
                            <div class="stat-color" style="background: #4CAF50;"></div>
                            <span class="stat-label">Approved:</span>
                            <span class="stat-value"><?php echo $recipientStats['approved']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #F44336;"></div>
                            <span class="stat-label">Rejected:</span>
                            <span class="stat-value"><?php echo $recipientStats['rejected']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #FFC107;"></div>
                            <span class="stat-label">Pending:</span>
                            <span class="stat-value"><?php echo $recipientStats['pending']; ?></span>
                        </div>  
                    </div>
                </div>

                <!-- Hospitals Chart -->
                <div class="chart-card">
                    <h2>Hospital Statistics</h2>
                    <div class="chart-container">
                        <canvas id="hospitalsChart"></canvas>
                    </div>
                    <div class="chart-stats">
                        <div class="stat-item">
                            <div class="stat-color" style="background: #4CAF50;"></div>
                            <span class="stat-label">Approved:</span>
                            <span class="stat-value"><?php echo $hospitalStats['approved']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #F44336;"></div>
                            <span class="stat-label">Rejected:</span>
                            <span class="stat-value"><?php echo $hospitalStats['rejected']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #FFC107;"></div>
                            <span class="stat-label">Pending:</span>
                            <span class="stat-value"><?php echo $hospitalStats['pending']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Organ Matches Chart -->
                <div class="chart-card">
                    <h2>Organ Match Statistics</h2>
                    <div class="chart-container">
                        <canvas id="matchesChart"></canvas>
                    </div>
                    <div class="chart-stats">
                        <div class="stat-item">
                            <div class="stat-color" style="background: #4CAF50;"></div>
                            <span class="stat-label">Successful:</span>
                            <span class="stat-value"><?php echo $organMatchStats['approved']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #F44336;"></div>
                            <span class="stat-label">Failed:</span>
                            <span class="stat-value"><?php echo $organMatchStats['rejected']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #FFC107;"></div>
                            <span class="stat-label">Pending:</span>
                            <span class="stat-value"><?php echo $organMatchStats['pending']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Total Users Chart -->
                <div class="chart-card">
                    <h2>Total Users Distribution</h2>
                    <div class="chart-container">
                        <canvas id="usersChart"></canvas>
                    </div>
                    <div class="chart-stats">
                        <div class="stat-item">
                            <div class="stat-color" style="background: #2196F3;"></div>
                            <span class="stat-label">Donors:</span>
                            <span class="stat-value"><?php echo $totalUsersStats['donors']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #9C27B0;"></div>
                            <span class="stat-label">Recipients:</span>
                            <span class="stat-value"><?php echo $totalUsersStats['recipients']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #FF9800;"></div>
                            <span class="stat-label">Hospitals:</span>
                            <span class="stat-value"><?php echo $totalUsersStats['hospitals']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Rejections Chart -->
                <div class="chart-card">
                    <h2>Rejection Statistics</h2>
                    <div class="chart-container">
                        <canvas id="rejectionsChart"></canvas>
                    </div>
                    <div class="chart-stats">
                        <div class="stat-item">
                            <div class="stat-color" style="background: #2196F3;"></div>
                            <span class="stat-label">Donor Rejections:</span>
                            <span class="stat-value"><?php echo $rejectionStats['donor_rejections']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #9C27B0;"></div>
                            <span class="stat-label">Recipient Rejections:</span>
                            <span class="stat-value"><?php echo $rejectionStats['recipient_rejections']; ?></span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-color" style="background: #FF9800;"></div>
                            <span class="stat-label">Hospital Rejections:</span>
                            <span class="stat-value"><?php echo $rejectionStats['hospital_rejections']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chartColors = {
            approved: '#4CAF50',
            rejected: '#F44336',
            pending: '#FFC107',
            donors: '#2196F3',
            recipients: '#9C27B0',
            hospitals: '#FF9800'
        };

        // Function to create doughnut chart with center text
        function createDoughnutChart(ctx, data, labels, colors, title, total) {
            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                },
                plugins: [{
                    id: 'centerText',
                    beforeDraw: function(chart) {
                        const width = chart.width;
                        const height = chart.height;
                        const ctx = chart.ctx;
                        
                        ctx.restore();
                        ctx.font = '16px Arial';
                        ctx.textBaseline = 'middle';
                        ctx.textAlign = 'center';
                        
                        // Draw title
                        ctx.font = 'bold 14px Arial';
                        ctx.fillStyle = '#666';
                        ctx.fillText(title, width / 2, height / 2 - 15);
                        
                        // Draw total
                        ctx.font = 'bold 24px Arial';
                        ctx.fillStyle = '#333';
                        ctx.fillText(total, width / 2, height / 2 + 15);
                        
                        ctx.save();
                    }
                }]
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Donors Chart
            const donorsData = [
                <?php echo $donorStats['approved']; ?>,
                <?php echo $donorStats['rejected']; ?>,
                <?php echo $donorStats['pending']; ?>
            ];
            const donorsTotal = donorsData.reduce((a, b) => a + b, 0);
            createDoughnutChart(
                document.getElementById('donorsChart'),
                donorsData,
                ['Approved', 'Rejected', 'Pending'],
                [chartColors.approved, chartColors.rejected, chartColors.pending],
                'Total Donors',
                donorsTotal
            );

            // Recipients Chart
            const recipientsData = [
                <?php echo $recipientStats['approved']; ?>,
                <?php echo $recipientStats['rejected']; ?>,
                <?php echo $recipientStats['pending']; ?>
            ];
            const recipientsTotal = recipientsData.reduce((a, b) => a + b, 0);
            createDoughnutChart(
                document.getElementById('recipientsChart'),
                recipientsData,
                ['Approved', 'Rejected', 'Pending'],
                [chartColors.approved, chartColors.rejected, chartColors.pending],
                'Total Recipients',
                recipientsTotal
            );

            // Hospitals Chart
            const hospitalsData = [
                <?php echo $hospitalStats['approved']; ?>,
                <?php echo $hospitalStats['rejected']; ?>,
                <?php echo $hospitalStats['pending']; ?>
            ];
            const hospitalsTotal = hospitalsData.reduce((a, b) => a + b, 0);
            createDoughnutChart(
                document.getElementById('hospitalsChart'),
                hospitalsData,
                ['Approved', 'Rejected', 'Pending'],
                [chartColors.approved, chartColors.rejected, chartColors.pending],
                'Total Hospitals',
                hospitalsTotal
            );

            // Organ Matches Chart
            const matchesData = [
                <?php echo $organMatchStats['approved']; ?>,
                <?php echo $organMatchStats['rejected']; ?>,
                <?php echo $organMatchStats['pending']; ?>
            ];
            const matchesTotal = matchesData.reduce((a, b) => a + b, 0);
            createDoughnutChart(
                document.getElementById('matchesChart'),
                matchesData,
                ['Successful', 'Failed', 'Pending'],
                [chartColors.approved, chartColors.rejected, chartColors.pending],
                'Total Matches',
                matchesTotal
            );

            // Total Users Chart
            const usersData = [
                <?php echo $totalUsersStats['donors']; ?>,
                <?php echo $totalUsersStats['recipients']; ?>,
                <?php echo $totalUsersStats['hospitals']; ?>
            ];
            const usersTotal = usersData.reduce((a, b) => a + b, 0);
            createDoughnutChart(
                document.getElementById('usersChart'),
                usersData,
                ['Donors', 'Recipients', 'Hospitals'],
                [chartColors.donors, chartColors.recipients, chartColors.hospitals],
                'Total Users',
                usersTotal
            );

            // Rejections Chart
            const rejectionsData = [
                <?php echo $rejectionStats['donor_rejections']; ?>,
                <?php echo $rejectionStats['recipient_rejections']; ?>,
                <?php echo $rejectionStats['hospital_rejections']; ?>
            ];
            const rejectionsTotal = rejectionsData.reduce((a, b) => a + b, 0);
            createDoughnutChart(
                document.getElementById('rejectionsChart'),
                rejectionsData,
                ['Donor Rejections', 'Recipient Rejections', 'Hospital Rejections'],
                [chartColors.donors, chartColors.recipients, chartColors.hospitals],
                'Total Rejections',
                rejectionsTotal
            );
        });
    </script>
</body>
</html>
