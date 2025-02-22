<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

try {
    // Get total count
    $sql_total = "SELECT COUNT(*) as total FROM recipient_registration";
    $result_total = $conn->query($sql_total);
    $total = $result_total->fetch_assoc()['total'];

    // Get pending count
    $sql_pending = "SELECT COUNT(*) as pending FROM recipient_registration WHERE request_status='pending'";
    $result_pending = $conn->query($sql_pending);
    $pending = $result_pending->fetch_assoc()['pending'];

    // Get accepted count
    $sql_accepted = "SELECT COUNT(*) as accepted FROM recipient_registration WHERE request_status='accepted'";
    $result_accepted = $conn->query($sql_accepted);
    $accepted = $result_accepted->fetch_assoc()['accepted'];

    // Get rejected count
    $sql_rejected = "SELECT COUNT(*) as rejected FROM recipient_registration WHERE request_status='rejected'";
    $result_rejected = $conn->query($sql_rejected);
    $rejected = $result_rejected->fetch_assoc()['rejected'];

} catch (Exception $e) {
    error_log("Error in analytics: " . $e->getMessage());
    // Set default values if query fails
    $total = $pending = $accepted = $rejected = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f6f9;
        }

        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: bold;
            text-align: center;
            margin: 2rem 0;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .analytics-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .analytics-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .analytics-card:hover {
            transform: translateY(-5px);
        }

        .chart-container {
            position: relative;
            height: 200px;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.2rem;
            color: #666;
            margin: 1rem 0;
        }

        .card-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="manage_recipients.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Recipients
        </a>
    </div>

    <h1 class="page-title">Recipient Analytics</h1>

    <div class="analytics-container">
        <div class="analytics-grid">
            <div class="analytics-card">
                <div class="chart-container">
                    <canvas id="totalChart"></canvas>
                </div>
                <div class="card-title">Total Recipients</div>
                <div class="card-value"><?php echo $total; ?></div>
            </div>
            <div class="analytics-card">
                <div class="chart-container">
                    <canvas id="pendingChart"></canvas>
                </div>
                <div class="card-title">Pending Recipients</div>
                <div class="card-value"><?php echo $pending; ?></div>
            </div>
            <div class="analytics-card">
                <div class="chart-container">
                    <canvas id="acceptedChart"></canvas>
                </div>
                <div class="card-title">Accepted Recipients</div>
                <div class="card-value"><?php echo $accepted; ?></div>
            </div>
            <div class="analytics-card">
                <div class="chart-container">
                    <canvas id="rejectedChart"></canvas>
                </div>
                <div class="card-title">Rejected Recipients</div>
                <div class="card-value"><?php echo $rejected; ?></div>
            </div>
        </div>
    </div>

    <script>
        function createPieChart(elementId, value, total, color) {
            const ctx = document.getElementById(elementId).getContext('2d');
            const percentage = ((value / total) * 100) || 0;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [percentage, 100 - percentage],
                        backgroundColor: [color, '#f0f0f0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '70%',
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${value} recipients (${percentage.toFixed(1)}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize charts
        const total = <?php echo $total; ?>;
        const pending = <?php echo $pending; ?>;
        const accepted = <?php echo $accepted; ?>;
        const rejected = <?php echo $rejected; ?>;

        createPieChart('totalChart', total, total, '#4CAF50');
        createPieChart('pendingChart', pending, total, '#2196F3');
        createPieChart('acceptedChart', accepted, total, '#4CAF50');
        createPieChart('rejectedChart', rejected, total, '#f44336');
    </script>
</body>
</html>
