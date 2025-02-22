<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Get donor counts
$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM donor";

$result = $conn->query($query);
$counts = $result->fetch_assoc();

// Calculate percentages
$total = $counts['total'] > 0 ? $counts['total'] : 1; // Prevent division by zero
$total_percent = round(($total / 1000) * 100); // Calculate percentage out of 1000
$approved_percent = round(($counts['approved'] / $total) * 100);
$pending_percent = round(($counts['pending'] / $total) * 100);
$rejected_percent = round(($counts['rejected'] / $total) * 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Analytics - LifeLink Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .analytics-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .analytics-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .chart-container {
            width: 200px;
            height: 200px;
            margin: 0 auto 30px;
            position: relative;
        }

        .chart-ring {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(transparent 0%, transparent var(--percent), #f0f0f0 var(--percent), #f0f0f0 100%);
            transform: rotate(-90deg);
            position: relative;
        }

        .chart-ring::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 160px;
            height: 160px;
            background: white;
            border-radius: 50%;
        }

        .chart-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .chart-label {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-top: 15px;
        }

        .total-count {
            font-size: 16px;
            color: #666;
            margin-top: 10px;
        }

        .total-donors .chart-ring { --color: #1a73e8; }
        .approved-donors .chart-ring { --color: #34a853; }
        .pending-donors .chart-ring { --color: #fbbc05; }
        .rejected-donors .chart-ring { --color: #ea4335; }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #1a73e8, #34a853);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .page-title {
            text-align: center;
            font-size: 28px;
            color: #333;
            margin: 20px 0;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .analytics-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a href="manage_donors.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Donor List
    </a>

    <h1 class="page-title">Donor Analytics Dashboard</h1>

    <div class="analytics-container">
        <div class="analytics-card total-donors">
            <div class="chart-container">
                <div class="chart-ring" style="--percent: 0%"></div>
                <div class="chart-value">0%</div>
            </div>
            <div class="chart-label">Total Donors</div>
            <div class="total-count">
                <?php echo $counts['total']; ?> out of 1000 potential donors
            </div>
        </div>

        <div class="analytics-card approved-donors">
            <div class="chart-container">
                <div class="chart-ring" style="--percent: 0%"></div>
                <div class="chart-value">0%</div>
            </div>
            <div class="chart-label">Approved Donors</div>
            <div class="total-count"><?php echo $counts['approved']; ?> donors</div>
        </div>

        <div class="analytics-card pending-donors">
            <div class="chart-container">
                <div class="chart-ring" style="--percent: 0%"></div>
                <div class="chart-value">0%</div>
            </div>
            <div class="chart-label">Pending Donors</div>
            <div class="total-count"><?php echo $counts['pending']; ?> donors</div>
        </div>

        <div class="analytics-card rejected-donors">
            <div class="chart-container">
                <div class="chart-ring" style="--percent: 0%"></div>
                <div class="chart-value">0%</div>
            </div>
            <div class="chart-label">Rejected Donors</div>
            <div class="total-count"><?php echo $counts['rejected']; ?> donors</div>
        </div>
    </div>

    <script>
        function animateChart(element, targetPercent, color) {
            let current = 0;
            const duration = 1500;
            const interval = 16;
            const steps = duration / interval;
            const increment = targetPercent / steps;

            const ring = element.querySelector('.chart-ring');
            const value = element.querySelector('.chart-value');

            const animation = setInterval(() => {
                current += increment;
                if (current >= targetPercent) {
                    current = targetPercent;
                    clearInterval(animation);
                }

                ring.style.background = `conic-gradient(${color} 0%, ${color} ${current}%, #f0f0f0 ${current}%, #f0f0f0 100%)`;
                value.textContent = Math.round(current) + '%';
            }, interval);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Animate total donors (percentage of 1000)
            animateChart(document.querySelector('.total-donors'), <?php echo $total_percent; ?>, '#1a73e8');
            
            // Animate status percentages
            animateChart(document.querySelector('.approved-donors'), <?php echo $approved_percent; ?>, '#34a853');
            animateChart(document.querySelector('.pending-donors'), <?php echo $pending_percent; ?>, '#fbbc05');
            animateChart(document.querySelector('.rejected-donors'), <?php echo $rejected_percent; ?>, '#ea4335');
        });
    </script>
</body>
</html>
