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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approval_id']) && isset($_POST['status'])) {
    try {
        if ($_POST['status'] === 'rejected' && !empty($_POST['reason'])) {
            $stmt = $conn->prepare("UPDATE hospital_recipient_approvals SET status = ?, rejection_reason = ?, approval_date = NOW() WHERE approval_id = ? AND hospital_id = ?");
            $stmt->execute([$_POST['status'], $_POST['reason'], $_POST['approval_id'], $hospital_id]);
        } else {
            $stmt = $conn->prepare("UPDATE hospital_recipient_approvals SET status = ?, approval_date = NOW() WHERE approval_id = ? AND hospital_id = ?");
            $stmt->execute([$_POST['status'], $_POST['approval_id'], $hospital_id]);
        }
        
        header("Location: hospitals_handles_recipients_status.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Fetch only approved recipient requests
try {
    $stmt = $conn->prepare("
        SELECT 
            hra.*,
            r.full_name,
            r.blood_type,
            r.organ_required,
            r.email,
            r.phone_number
        FROM hospital_recipient_approvals hra
        JOIN recipient_registration r ON hra.recipient_id = r.id
        WHERE hra.hospital_id = ? AND hra.status = 'Approved'
        ORDER BY hra.request_date DESC
    ");
    $stmt->execute([$hospital_id]);
    $recipient_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error_message = "An error occurred while fetching the data.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Recipients - <?php echo htmlspecialchars($hospital['name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Modern Table Styling */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin: 20px 0;
        }

        .table-responsive {
            margin-top: 20px;
            border-radius: 12px;
            overflow: hidden;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
        }

        .modern-table th {
            background: linear-gradient(45deg, #20bf55, #01baef);
            color: white;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .modern-table th:first-child {
            border-top-left-radius: 10px;
        }

        .modern-table th:last-child {
            border-top-right-radius: 10px;
        }

        .modern-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            color: #2C3E50;
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .modern-table tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Status and Priority Badges */
        .status-badge, .priority-badge {
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            text-transform: capitalize;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .status-pending {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
            color: white;
        }

        .status-approved {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            color: white;
        }

        .status-rejected {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .priority-high {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .priority-medium {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
            color: white;
        }

        .priority-low {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #20bf55, #01baef);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .empty-state h3 {
            color: #2C3E50;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #6c757d;
            font-size: 1rem;
            margin: 0;
        }

        /* Card Header */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 20px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #2C3E50;
            font-weight: 600;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
        }

        .modal textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-reject {
            padding: 8px 16px;
            background: linear-gradient(45deg, #ff416c, #ff4b2b);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
        }

        .btn-reject i {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        <main class="main-content">
            <div class="container mt-4">
                <div class="table-container">
                    <div class="card-header">
                        <h2>Recipient Requests Status</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Recipient Name</th>
                                    <th>Required Organ</th>
                                    <th>Blood Group</th>
                                    <th>Priority Level</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recipient_requests)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <h3>No Requests Found</h3>
                                                <p>There are no recipient requests at this time.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recipient_requests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['organ_required']); ?></td>
                                            <td>
                                                <span class="status-badge">
                                                    <?php echo htmlspecialchars($request['blood_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="priority-badge priority-<?php echo strtolower($request['priority_level']); ?>">
                                                    <?php echo htmlspecialchars($request['priority_level']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                                    <?php echo htmlspecialchars($request['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn-reject" onclick="openRejectModal(<?php echo $request['approval_id']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Rejection Modal for Recipients -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h3>Reject Recipient</h3>
            <p>Please provide a reason for rejection:</p>
            <textarea id="rejectionReason" rows="4" placeholder="Enter rejection reason..."></textarea>
            <div class="modal-buttons">
                <button onclick="submitRejection()" class="btn-confirm">Submit</button>
                <button onclick="closeRejectModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let currentApprovalId = null;

        function openRejectModal(approvalId) {
            currentApprovalId = approvalId;
            document.getElementById('rejectModal').style.display = 'flex';
            document.getElementById('rejectionReason').value = '';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        function submitRejection() {
            const reason = document.getElementById('rejectionReason').value.trim();
            if (!reason) {
                alert('Please provide a reason for rejection');
                return;
            }

            // Send AJAX request to update status
            $.ajax({
                url: 'update_recipient_status.php',
                method: 'POST',
                data: {
                    approval_id: currentApprovalId,
                    status: 'Rejected',
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while processing your request');
                }
            });

            closeRejectModal();
        }
    </script>
</body>
</html>
