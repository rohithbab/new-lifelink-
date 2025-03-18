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
                dr.request_id,
                dr.request_date,
                dr.status,
                dr.response_date,
                dr.response_message,
                h.hospital_id as requesting_hospital_id,
                h.name as requesting_hospital_name,
                h.phone as requesting_hospital_phone,
                h.address as requesting_hospital_address,
                d.donor_id,
                d.name as donor_name,
                d.blood_group,
                hda.organ_type
            FROM donor_requests dr
            JOIN hospitals h ON h.hospital_id = dr.requesting_hospital_id
            JOIN donor d ON d.donor_id = dr.donor_id
            JOIN hospital_donor_approvals hda ON hda.donor_id = d.donor_id AND hda.hospital_id = dr.donor_hospital_id
            WHERE dr.donor_hospital_id = ? 
            AND dr.status != 'Canceled'
            ORDER BY dr.request_date DESC
        ");
        $stmt->execute([$hospital_id]);
    } else {
        // Get requests made by this hospital
        $stmt = $conn->prepare("
            SELECT 
                dr.request_id,
                dr.request_date,
                dr.status,
                dr.response_date,
                dr.response_message,
                h.hospital_id as donor_hospital_id,
                h.name as donor_hospital_name,
                h.phone as donor_hospital_phone,
                h.address as donor_hospital_address,
                d.donor_id,
                d.name as donor_name,
                d.blood_group,
                hda.organ_type
            FROM donor_requests dr
            JOIN hospitals h ON h.hospital_id = dr.donor_hospital_id
            JOIN donor d ON d.donor_id = dr.donor_id
            JOIN hospital_donor_approvals hda ON hda.donor_id = d.donor_id AND hda.hospital_id = dr.donor_hospital_id
            WHERE dr.requesting_hospital_id = ?
            AND dr.status != 'Canceled'
            ORDER BY dr.request_date DESC
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
    <title><?php echo ucfirst($request_type); ?> Donor Requests - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .requests-container {
            padding: 2rem;
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
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .hospital-info {
            flex: 1;
        }

        .request-date {
            color: #666;
            font-size: 0.9em;
        }

        .donor-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-pending {
            background: #ffd700;
            color: #000;
        }

        .status-approved {
            background: #4CAF50;
            color: white;
        }

        .status-rejected {
            background: #f44336;
            color: white;
        }

        .status-canceled {
            background: #e9ecef;
            color: #495057;
        }

        .request-actions {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .approve-btn {
            background: #4CAF50;
            color: white;
        }

        .reject-btn {
            background: #f44336;
            color: white;
        }

        .cancel-btn {
            background: #dc3545;
            color: white;
        }

        .approve-btn:hover, .reject-btn:hover, .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

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
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-yes, .btn-cancel {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-yes {
            background: #dc3545;
            color: white;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-yes:hover, .btn-cancel:hover {
            transform: translateY(-2px);
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
        }

        .tab.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h1><?php echo ucfirst($request_type); ?> Donor Requests</h1>
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
                        <h2>No <?php echo ucfirst($request_type); ?> Requests</h2>
                        <p>You don't have any <?php echo $request_type; ?> donor requests at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card" data-request-id="<?php echo $request['request_id']; ?>">
                            <div class="request-header">
                                <div class="hospital-info">
                                    <h3>
                                        <?php 
                                        if ($request_type === 'incoming') {
                                            echo htmlspecialchars($request['requesting_hospital_name']);
                                        } else {
                                            echo htmlspecialchars($request['donor_hospital_name']);
                                        }
                                        ?>
                                    </h3>
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <?php 
                                        if ($request_type === 'incoming') {
                                            echo htmlspecialchars($request['requesting_hospital_phone']);
                                        } else {
                                            echo htmlspecialchars($request['donor_hospital_phone']);
                                        }
                                        ?>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php 
                                        if ($request_type === 'incoming') {
                                            echo htmlspecialchars($request['requesting_hospital_address']);
                                        } else {
                                            echo htmlspecialchars($request['donor_hospital_address']);
                                        }
                                        ?>
                                    </div>
                                </div>
                                <span class="request-date">
                                    Requested on <?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?>
                                </span>
                            </div>

                            <div class="donor-info">
                                <div class="info-item">
                                    <i class="fas fa-user"></i>
                                    <span>Donor: <?php echo htmlspecialchars($request['donor_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-tint"></i>
                                    <span>Blood Group: <?php echo htmlspecialchars($request['blood_group']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-heart"></i>
                                    <span>Organ: <?php echo htmlspecialchars($request['organ_type']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo $request['status']; ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($request['response_message']): ?>
                                <div class="response-message">
                                    <strong>Response:</strong> <?php echo htmlspecialchars($request['response_message']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="request-actions">
                                <?php if ($request_type === 'incoming' && $request['status'] === 'Pending'): ?>
                                    <button class="action-btn approve-btn" onclick="handleAction(<?php echo $request['request_id']; ?>, 'approve')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="action-btn reject-btn" onclick="handleAction(<?php echo $request['request_id']; ?>, 'reject')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php elseif ($request_type === 'outgoing' && $request['status'] === 'Pending'): ?>
                                    <button class="action-btn cancel-btn" onclick="cancelRequest(<?php echo $request['request_id']; ?>, 'donor', '<?php echo htmlspecialchars($request['donor_hospital_name']); ?>')">
                                        <i class="fas fa-times"></i> Cancel Request
                                    </button>
                                <?php endif; ?>
                            </div>
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
            <div class="modal-buttons">
                <button id="closeModal" class="btn-cancel">Cancel</button>
                <button id="submitResponse" class="btn-yes">Submit</button>
            </div>
        </div>
    </div>

    <script>
        let currentRequestId = null;
        let currentAction = null;

        function cancelRequest(requestId, type, hospitalName) {
            if (confirm('Are you sure you want to cancel this request?')) {
                const formData = new FormData();
                formData.append('requestId', requestId);
                formData.append('type', type);

                fetch('../../ajax/cancel_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the request card from the UI
                        const requestCard = document.querySelector(`[data-request-id="${requestId}"]`);
                        if (requestCard) {
                            requestCard.remove();
                        }
                        alert('Request canceled successfully');
                    } else {
                        alert(data.message || 'Failed to cancel request');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while canceling the request');
                });
            }
        }

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
                submitBtn.className = 'btn-yes';
            } else if (action === 'reject') {
                modalTitle.textContent = 'Reject Request';
                submitBtn.textContent = 'Reject';
                submitBtn.className = 'btn-cancel';
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
            fetch('../../backend/php/update_donor_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                // Show success message
                alert('Request ' + currentAction + 'ed successfully');
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

        // Add event listeners
        document.getElementById('closeModal').onclick = closeModal;
        document.getElementById('submitResponse').onclick = submitResponse;

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('responseModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>
