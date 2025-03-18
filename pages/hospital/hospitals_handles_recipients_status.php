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
    // First, let's check if we have any records at all
    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM hospital_recipient_approvals 
        WHERE hospital_id = ?
    ");
    $check_stmt->execute([$hospital_id]);
    $total_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Now get the approved recipients with correct table structure
    $stmt = $conn->prepare("
        SELECT 
            hra.*,
            r.full_name,
            r.email,
            r.phone_number,
            r.organ_required
        FROM hospital_recipient_approvals hra
        JOIN recipient_registration r ON hra.recipient_id = r.id
        WHERE hra.hospital_id = ? 
        AND hra.status = 'approved'
        ORDER BY hra.approval_date DESC
    ");
    $stmt->execute([$hospital_id]);
    $recipient_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For debugging
    error_log("Total records for hospital " . $hospital_id . ": " . $total_count);
    error_log("Approved recipients found: " . count($recipient_requests));
    
} catch(PDOException $e) {
    error_log("Error in hospitals_handles_recipients_status.php: " . $e->getMessage());
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
        /* Table Container Styling */
        .table-container {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin: 20px 0;
        }

        .section-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .section-header h2 {
            color: #2C3E50;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            background: linear-gradient(45deg, #28a745, #4a90e2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Modern Table Styling */
        .table-responsive {
            margin: 20px 0;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
        }

        .modern-table th {
            background: linear-gradient(45deg, #28a745, #4a90e2);
            color: white;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
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
            font-size: 0.9rem;
        }

        .modern-table tr:last-child td {
            border-bottom: none;
        }

        .modern-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badge Styling */
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
        }

        .status-approved {
            background-color: #e8f5e9;
            color: #28a745;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .status-rejected {
            background-color: #ffebee;
            color: #dc3545;
        }

        /* Organ Badge Styling */
        .organ-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            background: linear-gradient(45deg, #20c997, #0dcaf0);
            color: white;
            box-shadow: 0 2px 10px rgba(32, 201, 151, 0.2);
            display: inline-block;
        }

        /* Button Styling */
        .btn-reject {
            padding: 8px 16px;
            background: linear-gradient(45deg, #dc3545, #ff4b2b);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        .btn-reject i {
            font-size: 0.8rem;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        /* Empty State Styling */
        .no-records {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-records p {
            font-size: 1.1rem;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        <main class="main-content">
            <div class="container">
                <div class="section-header">
                    <h2>Manage Recipients</h2>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Status updated successfully!
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-responsive">
                        <?php if (empty($recipient_requests)): ?>
                            <div class="no-records">
                                <?php if ($total_count == 0): ?>
                                    <p><i class="fas fa-info-circle"></i> No recipient requests found for your hospital.</p>
                                <?php else: ?>
                                    <p><i class="fas fa-info-circle"></i> No approved recipients found. Total requests: <?php echo $total_count; ?></p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Recipient Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Organ Required</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recipient_requests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                                            <td><?php echo htmlspecialchars($request['phone_number']); ?></td>
                                            <td>
                                                <span class="organ-badge">
                                                    <?php echo htmlspecialchars($request['organ_required']); ?>
                                                </span>
                                            </td>
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
                                </tbody>
                            </table>
                        <?php endif; ?>
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
