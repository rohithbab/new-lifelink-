<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];

// Fetch hospital's recipients
try {
    $stmt = $conn->prepare("
        SELECT 
            id,
            full_name,
            blood_type,
            organ_required,
            request_status as approval_status
        FROM recipient_registration 
        WHERE request_status = 'accepted'
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE recipient_id = id
        )
        ORDER BY full_name ASC
    ");
    
    $stmt->execute();
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error fetching recipients: " . $e->getMessage());
    $recipients = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Recipients - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .switch-list-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .recipients-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .recipients-table th,
        .recipients-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .recipients-table th {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            font-weight: 500;
        }

        .select-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .select-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .empty-state h2 {
            margin-bottom: 0.5rem;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Choose Recipients</h1>
                </div>
                <div class="header-right">
                    <a href="choose_donors_for_matches.php" class="switch-list-btn">
                        <i class="fas fa-user-plus"></i>
                        Donors List
                    </a>
                </div>
            </div>

            <?php if (empty($recipients)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-plus"></i>
                    <h2>No Approved Recipients Found</h2>
                    <p>There are no approved recipients available for matching at the moment.</p>
                </div>
            <?php else: ?>
                <table class="recipients-table">
                    <thead>
                        <tr>
                            <th>Recipient Name</th>
                            <th>Blood Group</th>
                            <th>Required Organ</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipients as $recipient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($recipient['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['blood_type']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['organ_required']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['approval_status']); ?></td>
                                <td>
                                    <button class="select-btn" onclick="selectRecipient(<?php echo $recipient['id']; ?>)">
                                        Select for Match
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function selectRecipient(recipientId) {
            // Get recipient details from the row
            const row = event.target.closest('tr');
            const recipientName = row.cells[0].textContent.trim();
            const bloodGroup = row.cells[1].textContent.trim();
            const organType = row.cells[2].textContent.trim();

            // Here you can add the logic to handle the recipient selection
            // For example, redirect to a matching page or show a modal
            console.log('Selected recipient:', {
                id: recipientId,
                name: recipientName,
                bloodGroup: bloodGroup,
                organType: organType
            });
        }
    </script>
</body>
</html>
