<?php
session_start();
require_once '../../config/db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];
$hospital_email = $_SESSION['hospital_email'];
$odml_id = $_SESSION['odml_id'];

// Debug session information
error_log("DEBUG: Starting hospital dashboard for hospital_id: " . $hospital_id);
error_log("Session Info - Hospital ID: " . $hospital_id . ", Name: " . $hospital_name);

// Fetch recipient requests
try {
    $query = "
        SELECT 
            hra.*,
            r.full_name,
            r.blood_type,
            r.organ_required,
            r.urgency_level
        FROM hospital_recipient_approvals hra
        JOIN recipient_registration r ON r.id = hra.recipient_id
        WHERE hra.hospital_id = :hospital_id 
        AND LOWER(hra.status) = 'pending'
        ORDER BY hra.approval_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':hospital_id', $hospital_id, PDO::PARAM_INT);
    $stmt->execute();
    $recipient_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("DEBUG: Found " . count($recipient_requests) . " pending recipient requests");
} catch(PDOException $e) {
    error_log("ERROR: " . $e->getMessage());
    $recipient_requests = array();
}

// Fetch donor requests
try {
    $stmt = $conn->prepare("
        SELECT 
            hda.*, 
            d.name as donor_name,
            d.blood_group,
            d.organs_to_donate as organ_type
        FROM hospital_donor_approvals hda
        JOIN donor d ON d.donor_id = hda.donor_id
        WHERE hda.hospital_id = ? AND LOWER(hda.status) = 'pending'
        ORDER BY hda.approval_date DESC
    ");
    $stmt->execute([$hospital_id]);
    $donor_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("ERROR: " . $e->getMessage());
    $donor_requests = array();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard</title>
    <!-- Reset default styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .modal-content {
            font-family: 'Poppins', sans-serif;
        }

        .modal-header h3 {
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .responsibility-message {
            background: linear-gradient(135deg, #f6f9fc, #eef2f7);
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.6;
        }

        .responsibility-message strong {
            color: #2c3e50;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 15px;
            display: block;
            margin-bottom: 8px;
        }

        .responsibility-message p {
            color: #34495e;
            margin: 0;
        }

        .status-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
            min-width: 120px;
        }

        .status-select option {
            padding: 8px;
        }

        .status-select option[value="Pending"] {
            color: #f0ad4e;
        }

        .status-select option[value="Approved"] {
            color: #5cb85c;
        }

        .status-select option[value="Rejected"] {
            color: #d9534f;
        }
        
        .btn-approve, .btn-reject {
            padding: 6px 12px;
            margin: 0 4px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .btn-approve:hover {
            background-color: #218838;
        }

        .btn-reject:hover {
            background-color: #c82333;
        }

        .actions {
            white-space: nowrap;
        }
        
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

        .priority-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .priority-low {
            background-color: #28a745;
            color: white;
        }
        
        .priority-medium {
            background-color: #ffc107;
            color: black;
        }
        
        .priority-high {
            background-color: #dc3545;
            color: white;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }

        .btn-action {
            padding: 8px 15px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-approve {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }

        .btn-reject {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-action i {
            font-size: 0.9rem;
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
            display: none; /* Initial state is hidden */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            position: relative;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            padding: 30px;
            width: 90%;
            max-width: 600px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.4s ease;
            margin: 0;
            transform: translateY(0);
        }

        .modal-header {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 25px;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .modal-header i {
            font-size: 28px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .modal-header h3 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .responsibility-message {
            background: linear-gradient(135deg, #EBF5FB, #D6EAF8);
            border-left: 4px solid #3498db;
            padding: 20px;
            margin: 20px 0;
            border-radius: 12px;
            font-size: 14.5px;
            line-height: 1.7;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .responsibility-message strong {
            color: #2c3e50;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 16px;
            display: block;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .responsibility-message p {
            color: #34495e;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .responsibility-message p i {
            color: #3498db;
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .reason-textarea {
            width: 100%;
            min-height: 140px;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            margin: 15px 0;
            font-size: 14.5px;
            resize: vertical;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .reason-textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-btn i {
            font-size: 16px;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .modal-btn-reject {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .modal-btn-cancel {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }

        .modal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 15px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Welcome, <?php echo htmlspecialchars($hospital_name); ?></h1>
                </div>
                <div class="header-right">
                    <button onclick="window.location.href='hospital_profile.php'" class="profile-button">
                        <i class="fas fa-user-circle"></i>
                        <span>My Profile</span>
                    </button>
                </div>
            </div>

            <!-- Pending Donor Approvals -->
            <div class="table-container">
                <div class="card-header">
                    <h2>Pending Donor Approvals</h2>
                </div>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Donor Name</th>
                                <th>Organ Type</th>
                                <th>Blood Group</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($donor_requests)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">No pending donor approvals</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($donor_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['donor_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($request['organ_type'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="blood-badge">
                                                <?php echo htmlspecialchars($request['blood_group'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($request['approval_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($request['status']); ?>">
                                                <?php echo htmlspecialchars($request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="hospital_checks_donor_pf.php?id=<?php echo $request['approval_id']; ?>" class="btn-action btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                        <td class="actions">
                                            <button onclick="showApprovalModal(<?php echo $request['approval_id']; ?>, 'donor', '<?php echo htmlspecialchars($request['donor_name']); ?>')" class="btn-approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button onclick="showRejectionModal(<?php echo $request['approval_id']; ?>, 'donor', '<?php echo htmlspecialchars($request['donor_name']); ?>')" class="btn-reject">
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

            <!-- Pending Recipient Approvals -->
            <div class="table-container">
                <div class="card-header">
                    <h2>Pending Recipient Approvals</h2>
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
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recipient_requests)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <h3>No Pending Requests</h3>
                                            <p>There are no pending recipient requests at this time.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recipient_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['full_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($request['organ_required'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($request['blood_type'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="priority-badge priority-<?php echo strtolower($request['urgency_level'] ?? 'normal'); ?>">
                                                <?php echo htmlspecialchars($request['urgency_level'] ?? 'Normal'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($request['approval_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-pending">
                                                <?php echo htmlspecialchars($request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="hospital_checks_recipient_pf.php?id=<?php echo $request['approval_id']; ?>" class="btn-action btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                        <td class="actions">
                                            <button onclick="showApprovalModal(<?php echo $request['approval_id']; ?>, 'recipient', '<?php echo htmlspecialchars($request['full_name']); ?>')" class="btn-approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button onclick="showRejectionModal(<?php echo $request['approval_id']; ?>, 'recipient', '<?php echo htmlspecialchars($request['full_name']); ?>')" class="btn-reject">
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

            <!-- Approval Modal -->
            <div id="approvalModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <i class="fas fa-check-circle"></i>
                        <h3>Approve Request</h3>
                    </div>
                    <div class="modal-body">
                        <p>You are about to approve the request from <strong id="approvalName"></strong></p>
                        
                        <div class="responsibility-message">
                            <strong><i class="fas fa-hospital-user"></i> HOSPITAL RESPONSIBILITY NOTICE</strong>
                            <p><i class="fas fa-user-check"></i> The person will be registered under YOUR hospital's care</p>
                            <p><i class="fas fa-hand-holding-medical"></i> You will provide necessary medical support and guidance</p>
                            <p><i class="fas fa-comments-alt-medical"></i> You will maintain regular communication and updates</p>
                            <p><i class="fas fa-file-medical-alt"></i> You will ensure proper documentation and follow-up</p>
                            <p><i class="fas fa-heart"></i> This is a significant responsibility. Please proceed only if you are fully committed to providing the best possible care.</p>
                        </div>

                        <label for="approvalReason"><i class="fas fa-pen"></i> Please provide your approval message:</label>
                        <textarea id="approvalReason" class="reason-textarea" 
                            placeholder="Example: We are pleased to welcome you to [Hospital Name]. Our team is committed to providing you with excellent care and support throughout your journey. We look forward to working with you."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="modal-btn modal-btn-cancel" onclick="closeModal('approvalModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="modal-btn modal-btn-confirm" onclick="submitApproval()">
                            <i class="fas fa-check"></i> Confirm & Accept Responsibility
                        </button>
                    </div>
                </div>
            </div>

            <!-- Rejection Modal -->
            <div id="rejectionModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <i class="fas fa-times-circle"></i>
                        <h3>Request Rejection</h3>
                    </div>
                    <div class="modal-body">
                        <p>You are about to reject the request from <strong id="rejectionName"></strong></p>

                        <div class="responsibility-message">
                            <strong><i class="fas fa-exclamation-triangle"></i> IMPORTANT NOTICE</strong>
                            <p><i class="fas fa-comment-alt"></i> Be clear and professional in your explanation</p>
                            <p><i class="fas fa-list-ul"></i> Provide specific reasons for the rejection</p>
                            <p><i class="fas fa-lightbulb"></i> If possible, offer alternative suggestions or guidance</p>
                            <p><i class="fas fa-heart"></i> Maintain a respectful and supportive tone</p>
                            <p><i class="fas fa-info-circle"></i> Remember: Your response can significantly impact someone's healthcare journey.</p>
                        </div>

                        <label for="rejectionReason"><i class="fas fa-pen"></i> Please provide a detailed rejection reason:</label>
                        <textarea id="rejectionReason" class="reason-textarea" 
                            placeholder="Example: After careful review of your application and our current capabilities, we regret to inform you that we cannot proceed with your request because [specific reason]. We recommend [alternative suggestion if applicable]."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="modal-btn modal-btn-cancel" onclick="closeModal('rejectionModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="modal-btn modal-btn-reject" onclick="submitRejection()">
                            <i class="fas fa-paper-plane"></i> Submit Rejection
                        </button>
                    </div>
                </div>
            </div>

            <script>
                let currentRequestId = null;
                let currentRequestType = null;
                let currentName = null;

                function showApprovalModal(id, type, name) {
                    currentRequestId = id;
                    currentRequestType = type;
                    currentName = name;
                    document.getElementById('approvalName').textContent = name;
                    const modal = document.getElementById('approvalModal');
                    modal.classList.add('show');
                }

                function showRejectionModal(id, type, name) {
                    currentRequestId = id;
                    currentRequestType = type;
                    currentName = name;
                    document.getElementById('rejectionName').textContent = name;
                    const modal = document.getElementById('rejectionModal');
                    modal.classList.add('show');
                }

                function closeModal(modalId) {
                    const modal = document.getElementById(modalId);
                    modal.classList.remove('show');
                    currentRequestId = null;
                    currentRequestType = null;
                    currentName = null;
                }

                function submitApproval() {
                    const reason = document.getElementById('approvalReason').value.trim();
                    if (!reason) {
                        alert('Please enter an approval reason');
                        return;
                    }

                    $.post('handle_hospital_approval.php', {
                        approval_id: currentRequestId,
                        type: currentRequestType,
                        approval_reason: reason
                    }, function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert('Request approved successfully! WhatsApp message has been sent.');
                                location.reload();
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch (e) {
                            alert('Error processing the response');
                        }
                    }).fail(function() {
                        alert('Error occurred while processing the request');
                    });

                    closeModal('approvalModal');
                }

                function submitRejection() {
                    const reason = document.getElementById('rejectionReason').value.trim();
                    if (!reason) {
                        alert('Please enter a rejection reason');
                        return;
                    }

                    $.post('handle_hospital_rejection.php', {
                        approval_id: currentRequestId,
                        type: currentRequestType,
                        reject_reason: reason
                    }, function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert('Request rejected. WhatsApp message has been sent with your feedback.');
                                location.reload();
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch (e) {
                            alert('Error processing the response');
                        }
                    }).fail(function() {
                        alert('Error occurred while processing the request');
                    });

                    closeModal('rejectionModal');
                }
            </script>
        </main>
    </div>
</body>
</html>
