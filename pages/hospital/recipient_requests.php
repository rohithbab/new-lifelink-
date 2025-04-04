<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$request_type = isset($_GET['type']) ? $_GET['type'] : 'incoming';

// Get requests based on type
try {
    if ($request_type === 'incoming') {
        // Get requests made to this hospital
        $stmt = $conn->prepare("
            SELECT 
                rr.request_id,
                rr.request_date,
                rr.status,
                rr.response_date,
                rr.response_message,
                h.hospital_id as requesting_hospital_id,
                h.name as requesting_hospital_name,
                h.phone as requesting_hospital_phone,
                h.address as requesting_hospital_address,
                r.id as recipient_id,
                r.full_name as recipient_name,
                r.blood_type,
                r.medical_condition,
                r.urgency_level,
                ha.organ_required
            FROM recipient_requests rr
            JOIN hospitals h ON h.hospital_id = rr.requesting_hospital_id
            JOIN recipient_registration r ON r.id = rr.recipient_id
            LEFT JOIN hospital_recipient_approvals ha ON ha.recipient_id = rr.recipient_id AND ha.hospital_id = rr.requesting_hospital_id AND ha.status = 'approved'
            WHERE rr.recipient_hospital_id = ?
            ORDER BY rr.request_date DESC
        ");
        $stmt->execute([$hospital_id]);
    } else {
        // Get requests made by this hospital
        $stmt = $conn->prepare("
            SELECT 
                rr.request_id,
                rr.request_date,
                rr.status,
                rr.response_date,
                rr.response_message,
                h.hospital_id as recipient_hospital_id,
                h.name as recipient_hospital_name,
                h.phone as recipient_hospital_phone,
                h.address as recipient_hospital_address,
                r.id as recipient_id,
                r.full_name as recipient_name,
                r.blood_type,
                r.medical_condition,
                r.urgency_level,
                ha.organ_required
            FROM recipient_requests rr
            JOIN hospitals h ON h.hospital_id = rr.recipient_hospital_id
            JOIN recipient_registration r ON r.id = rr.recipient_id
            LEFT JOIN hospital_recipient_approvals ha ON ha.recipient_id = rr.recipient_id AND ha.hospital_id = rr.requesting_hospital_id AND ha.status = 'approved'
            WHERE rr.requesting_hospital_id = ?
            ORDER BY rr.request_date DESC
        ");
        $stmt->execute([$hospital_id]);
    }
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching requests: " . $e->getMessage());
    $requests = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($request_type); ?> Recipient Requests - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .requests-container {
            padding: 2rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            background: white;
            border-radius: 5px;
            text-decoration: none;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .tab.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
        }

        .request-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .request-card:hover {
            transform: translateY(-2px);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .hospital-info h3 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .hospital-info p {
            margin: 0.25rem 0;
            color: #666;
        }

        .status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status.pending {
            background-color: #ffc107;
            color: #000;
        }

        .status.approved {
            background-color: #28a745;
            color: white;
        }

        .status.rejected {
            background-color: #dc3545;
            color: white;
        }

        .status.cancelled {
            background-color: #6c757d;
            color: white;
        }

        .request-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .recipient-info h4, .request-meta h4 {
            margin: 0 0 1rem 0;
            color: #333;
        }

        .recipient-info p, .request-meta p {
            margin: 0.5rem 0;
            color: #666;
        }

        .request-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .approve-btn {
            background-color: #28a745;
            color: white;
        }

        .approve-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .reject-btn {
            background-color: #dc3545;
            color: white;
        }

        .reject-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
        }

        .cancel-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .empty-state h2 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .empty-state p {
            margin: 0;
            color: #666;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

        textarea {
            width: 100%;
            padding: 0.75rem;
            margin: 1rem 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h1><?php echo ucfirst($request_type); ?> Recipient Requests</h1>
            </div>

            <div class="requests-container">
                <div class="tabs">
                    <a href="?type=incoming" class="tab <?php echo $request_type === 'incoming' ? 'active' : ''; ?>">
                        <i class="fas fa-inbox"></i> Incoming Requests
                    </a>
                    <a href="?type=outgoing" class="tab <?php echo $request_type === 'outgoing' ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane"></i> Outgoing Requests
                    </a>
                </div>

                <?php if (empty($requests)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h2>No <?php echo $request_type; ?> requests found</h2>
                        <p>There are no <?php echo $request_type; ?> recipient requests at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="hospital-info">
                                    <h3>
                                        <?php 
                                        if ($request_type === 'incoming') {
                                            echo htmlspecialchars($request['requesting_hospital_name']);
                                            $hospital_phone = $request['requesting_hospital_phone'];
                                            $hospital_address = $request['requesting_hospital_address'];
                                        } else {
                                            echo htmlspecialchars($request['recipient_hospital_name']);
                                            $hospital_phone = $request['recipient_hospital_phone'];
                                            $hospital_address = $request['recipient_hospital_address'];
                                        }
                                        ?>
                                    </h3>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($hospital_phone); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hospital_address); ?></p>
                                </div>
                                <div class="status <?php echo strtolower($request['status']); ?>">
                                    <?php echo htmlspecialchars($request['status']); ?>
                                </div>
                            </div>

                            <div class="request-details">
                                <div class="recipient-info">
                                    <h4>Recipient Details</h4>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($request['recipient_name']); ?></p>
                                    <p><strong>Blood Type:</strong> <?php echo htmlspecialchars($request['blood_type']); ?></p>
                                    <p><strong>Medical Condition:</strong> <?php echo htmlspecialchars($request['medical_condition']); ?></p>
                                    <p><strong>Urgency Level:</strong> <?php echo htmlspecialchars($request['urgency_level']); ?></p>
                                    <p><strong>Required Organ:</strong> <?php echo htmlspecialchars($request['organ_required']); ?></p>
                                </div>

                                <div class="request-meta">
                                    <p><strong>Request Date:</strong> <?php echo date('M d, Y H:i', strtotime($request['request_date'])); ?></p>
                                    <?php if ($request['response_date']): ?>
                                        <p><strong>Response Date:</strong> <?php echo date('M d, Y H:i', strtotime($request['response_date'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($request['response_message']): ?>
                                        <p><strong>Response Message:</strong> <?php echo htmlspecialchars($request['response_message']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($request['status'] === 'Pending' && $request_type === 'incoming'): ?>
                                <div class="request-actions">
                                    <button class="action-btn approve-btn" onclick="handleAction(<?php echo $request['request_id']; ?>, 'approve')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="action-btn reject-btn" onclick="handleAction(<?php echo $request['request_id']; ?>, 'reject')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Response</h3>
            <textarea id="responseMessage" placeholder="Enter your response message"></textarea>
            <div class="modal-actions">
                <button class="action-btn" onclick="closeModal()">Cancel</button>
                <button id="submitResponse" class="action-btn" onclick="submitResponse()">Submit</button>
            </div>
        </div>
    </div>

    <script>
        let currentRequestId = null;
        let currentAction = null;

        function handleAction(requestId, action) {
            currentRequestId = requestId;
            currentAction = action;
            
            // Show modal
            const modal = document.getElementById('responseModal');
            modal.style.display = 'block';
            
            // Update modal title and button
            const modalTitle = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitResponse');
            
            if (action === 'approve') {
                modalTitle.textContent = 'Approve Request';
                submitBtn.textContent = 'Approve';
                submitBtn.className = 'action-btn approve-btn';
            } else if (action === 'reject') {
                modalTitle.textContent = 'Reject Request';
                submitBtn.textContent = 'Reject';
                submitBtn.className = 'action-btn reject-btn';
            }
        }

        function closeModal() {
            const modal = document.getElementById('responseModal');
            modal.style.display = 'none';
            document.getElementById('responseMessage').value = '';
            currentRequestId = null;
            currentAction = null;
        }

        function submitResponse() {
            const message = document.getElementById('responseMessage').value.trim();
            
            if (!message) {
                alert('Please enter a response message');
                return;
            }

            // Create form data
            const formData = new FormData();
            formData.append('request_id', currentRequestId);
            formData.append('action', currentAction);
            formData.append('message', message);

            // Send request to backend
            fetch('../../backend/php/update_recipient_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                // Show success message
                alert(data.message);
                // Reload page to show updated status
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            })
            .finally(() => {
                closeModal();
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('responseModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
