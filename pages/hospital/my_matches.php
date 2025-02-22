<?php
session_start();
require_once '../../config/db_connect.php';

if (!isset($_SESSION['hospital_id'])) {
    header("Location: ../hospital_login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get hospital info
try {
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE hospital_id = ?");
    $stmt->execute([$hospital_id]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching hospital details: " . $e->getMessage());
}

// Fetch matches made by this hospital
try {
    $query = "SELECT * FROM made_matches_by_hospitals 
              WHERE match_made_by = :hospital_id 
              ORDER BY match_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':hospital_id', $hospital_id);
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching matches: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Matches - <?php echo htmlspecialchars($hospital['name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Table Container Styling */
        .table-container {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
        }

        /* Table Styling */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }

        .empty-state i {
            font-size: 48px;
            color: #adb5bd;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            color: #495057;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #6c757d;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Status Badge Styling */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Action Button Styling */
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background-color: #007bff;
            color: white;
        }

        .action-btn:hover {
            background-color: #0056b3;
        }

        .action-btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="container">
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">My Matches</h2>
                    </div>

                    <?php if (count($matches) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Match ID</th>
                                    <th>Donor Name</th>
                                    <th>Donor Hospital</th>
                                    <th>Recipient Name</th>
                                    <th>Recipient Hospital</th>
                                    <th>Organ Type</th>
                                    <th>Blood Group</th>
                                    <th>Match Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matches as $match): ?>
                                    <tr>
                                        <td>#<?php echo $match['match_id']; ?></td>
                                        <td><?php echo htmlspecialchars($match['donor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($match['donor_hospital_name']); ?></td>
                                        <td><?php echo htmlspecialchars($match['recipient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($match['recipient_hospital_name']); ?></td>
                                        <td><?php echo htmlspecialchars($match['organ_type']); ?></td>
                                        <td><?php echo htmlspecialchars($match['blood_group']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($match['match_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-handshake"></i>
                            <h3>No Matches Yet</h3>
                            <p>You haven't made any matches yet. Go to the Make Matches page to create your first donor-recipient match.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function viewMatchDetails(matchId) {
            // TODO: Implement match details view
            console.log('Viewing match:', matchId);
        }
    </script>
</body>
</html>
