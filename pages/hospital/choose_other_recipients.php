<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get the selected hospital's ID from URL
$selected_hospital_id = isset($_GET['hospital_id']) ? $_GET['hospital_id'] : null;

if (!$selected_hospital_id) {
    header("Location: make_matches.php");
    exit();
}

// Fetch selected hospital's information
try {
    $stmt = $conn->prepare("
        SELECT name, phone, address 
        FROM hospitals 
        WHERE hospital_id = ?
    ");
    $stmt->execute([$selected_hospital_id]);
    $hospital_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hospital_info) {
        header("Location: make_matches.php");
        exit();
    }

    // Fetch recipients from the selected hospital
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            ha.required_organ,
            ha.blood_group,
            ha.priority_level,
            CASE 
                WHEN rr.status IS NOT NULL THEN rr.status
                ELSE 'Not Requested'
            END as request_status
        FROM recipient_registration r
        JOIN hospital_recipient_approvals ha ON r.id = ha.recipient_id
        LEFT JOIN recipient_requests rr ON r.id = rr.recipient_id 
            AND rr.requesting_hospital_id = ? 
            AND rr.recipient_hospital_id = ?
            AND rr.status IN ('Pending', 'Approved')
        WHERE ha.hospital_id = ?
        AND ha.status = 'Approved'
        ORDER BY ha.priority_level DESC, r.full_name ASC
    ");
    $stmt->execute([$hospital_id, $selected_hospital_id, $selected_hospital_id]);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error fetching hospital data: " . $e->getMessage());
    header("Location: make_matches.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Recipients - <?php echo htmlspecialchars($hospital_info['name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hospital-info {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 2rem;
        }

        .hospital-details {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            align-items: center;
        }

        .hospital-contact {
            display: flex;
            gap: 1rem;
            color: #666;
        }

        .recipients-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 2rem;
        }

        .recipients-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
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

        .priority-high {
            color: #dc3545;
            font-weight: bold;
        }

        .priority-medium {
            color: #ffc107;
            font-weight: bold;
        }

        .priority-low {
            color: #28a745;
            font-weight: bold;
        }

        .request-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .request-btn.not-requested {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
        }

        .request-btn.pending {
            background: #ffc107;
            color: #000;
            cursor: not-allowed;
        }

        .request-btn.approved {
            background: #28a745;
            color: white;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="hospital-info">
                <h2><?php echo htmlspecialchars($hospital_info['name']); ?></h2>
                <div class="hospital-contact">
                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($hospital_info['phone']); ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hospital_info['address']); ?></span>
                </div>
            </div>

            <div class="recipients-section">
                <h3>Available Recipients</h3>
                <?php if (empty($recipients)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash fa-3x"></i>
                        <h4>No Recipients Available</h4>
                        <p>This hospital currently has no approved recipients.</p>
                    </div>
                <?php else: ?>
                    <table class="recipients-table">
                        <thead>
                            <tr>
                                <th>Recipient Name</th>
                                <th>Blood Group</th>
                                <th>Required Organ</th>
                                <th>Priority Level</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recipients as $recipient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($recipient['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['blood_group']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['required_organ']); ?></td>
                                    <td class="priority-<?php echo strtolower($recipient['priority_level']); ?>">
                                        <?php echo htmlspecialchars($recipient['priority_level']); ?>
                                    </td>
                                    <td>
                                        <?php if ($recipient['request_status'] === 'Not Requested'): ?>
                                            <button onclick="requestRecipient(<?php echo $recipient['id']; ?>)" 
                                                    class="request-btn not-requested">
                                                Request Recipient
                                            </button>
                                        <?php elseif ($recipient['request_status'] === 'Pending'): ?>
                                            <button class="request-btn pending" disabled>
                                                Request Pending
                                            </button>
                                        <?php else: ?>
                                            <button class="request-btn approved" disabled>
                                                Request Approved
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function requestRecipient(recipientId) {
            if (!confirm('Are you sure you want to request this recipient?')) {
                return;
            }

            fetch('../../ajax/send_recipient_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    recipient_id: recipientId,
                    recipient_hospital_id: <?php echo $selected_hospital_id; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Request sent successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to send request. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>
