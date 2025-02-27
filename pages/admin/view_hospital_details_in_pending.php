<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get hospital ID from URL
$hospital_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$hospital_id) {
    header('Location: admin_dashboard.php');
    exit();
}

// Get hospital details
try {
    $query = "SELECT * FROM hospitals WHERE hospital_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$hospital_id]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching hospital details: " . $e->getMessage());
    $hospital = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Details - LifeLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #4CAF50;
            --text-color: #2c3e50;
            --light-gray: #f5f5f5;
            --border-color: #e0e0e0;
            --shadow-color: rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: var(--light-gray);
            color: var(--text-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .back-btn {
            text-decoration: none;
            color: white;
            font-size: 15px;
            display: flex;
            align-items: center;
            margin-right: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transition: opacity 0.3s;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .back-btn:hover {
            opacity: 0.9;
        }

        .back-btn i {
            margin-right: 8px;
        }

        h1 {
            margin: 0;
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 600;
        }

        .hospital-details {
            padding: 20px 0;
        }

        .detail-section h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            padding: 20px;
        }

        .detail-item {
            padding: 20px;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 4px var(--shadow-color);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px var(--shadow-color);
        }

        .detail-item label {
            display: block;
            color: var(--primary-color);
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .detail-item span {
            color: var(--text-color);
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
        }

        .error-message {
            color: #dc3545;
            text-align: center;
            padding: 30px;
            font-size: 18px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1>Hospital Details</h1>
        </div>
        
        <div class="hospital-details">
            <?php if ($hospital): ?>
                <div class="detail-section">
                    <h2><?php echo htmlspecialchars($hospital['name']); ?></h2>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Email</label>
                            <span><?php echo htmlspecialchars($hospital['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Phone</label>
                            <span><?php echo htmlspecialchars($hospital['phone']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Address</label>
                            <span><?php echo htmlspecialchars($hospital['address']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>License Number</label>
                            <span><?php echo htmlspecialchars($hospital['license_number'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>License File</label>
                            <span>
                                <?php if (!empty($hospital['license_file'])): ?>
                                    <a href="../../uploads/hospitals/license_file/<?php echo htmlspecialchars($hospital['license_file']); ?>" 
                                       target="_blank" 
                                       style="color: var(--primary-color); text-decoration: none;">
                                        <i class="fas fa-file-pdf"></i> View License
                                    </a>
                                <?php else: ?>
                                    Not uploaded
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <label>Registration Date</label>
                            <span><?php echo date('F j, Y', strtotime($hospital['registration_date'])); ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Hospital not found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
