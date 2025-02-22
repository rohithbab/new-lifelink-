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
                ha.required_organ,
                ha.priority_level
            FROM recipient_requests rr
            JOIN hospitals h ON h.hospital_id = rr.requesting_hospital_id
            JOIN recipient_registration r ON r.id = rr.recipient_id
            JOIN hospital_recipient_approvals ha ON ha.recipient_id = r.id
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
                ha.required_organ,
                ha.priority_level
            FROM recipient_requests rr
            JOIN hospitals h ON h.hospital_id = rr.recipient_hospital_id
            JOIN recipient_registration r ON r.id = rr.recipient_id
            JOIN hospital_recipient_approvals ha ON ha.recipient_id = r.id
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

        .recipient-info {
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

        .approve-btn:hover, .reject-btn:hover {
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
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .modal h3 {
            margin-bottom: 1rem;
        }

        .modal textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 1rem;
            resize: vertical;
            min-height: 100px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 1rem;
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
                        <h2>No <?php echo ucfirst($request_type); ?> Requests</h2>
                        <p>You don't have any <?php echo $request_type; ?> recipient requests at the moment.</p>
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
                                        } else {
                                            echo htmlspecialchars($request['recipient_hospital_name']);
                                        }
                                        ?>
                                    </h3>
                                    <div class="request-date">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('F j, Y g:i A', strtotime($request['request_date'])); ?>
                                    </div>
                                </div>
                                <div class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </div>
                            </div>

                            <div class="recipient-info">
                                <div class="info-item">
                                    <i class="fas fa-user"></i>
                                    <span>Recipient: <?php echo htmlspecialchars($request['recipient_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-tint"></i>
                                    <span>Blood Type: <?php echo htmlspecialchars($request['blood_type']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-heart"></i>
                                    <span>Required Organ: <?php echo htmlspecialchars($request['required_organ']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span class="priority-<?php echo strtolower($request['priority_level']); ?>">
                                        Priority: <?php echo htmlspecialchars($request['priority_level']); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($request['response_message']): ?>
                                <div class="response-message">
                                    <i class="fas fa-comment"></i>
                                    <p><?php echo htmlspecialchars($request['response_message']); ?></p>
                                    <small>Response Date: <?php echo date('F j, Y g:i A', strtotime($request['response_date'])); ?></small>
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
            <h3>Response Message</h3>
            <textarea id="responseMessage" placeholder="Enter your response message..."></textarea>
            <div class="modal-actions">
                <button class="action-btn reject-btn" onclick="closeModal()">Cancel</button>
                <button class="action-btn approve-btn" onclick="submitResponse()">Submit</button>
            </div>
        </div>
    </div>

    <script>
        let activeRequestId = null;
        let activeAction = null;

        function handleAction(requestId, action) {
            activeRequestId = requestId;
            activeAction = action;
            
            if (action === 'reject') {
                document.getElementById('responseModal').style.display = 'block';
            } else {
                submitResponse();
            }
        }

        function closeModal() {
            document.getElementById('responseModal').style.display = 'none';
            document.getElementById('responseMessage').value = '';
            activeRequestId = null;
            activeAction = null;
        }

        function submitResponse() {
            const message = document.getElementById('responseMessage').value;
            
            fetch('../../ajax/handle_recipient_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    request_id: activeRequestId,
                    action: activeAction,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Request ' + activeAction + 'ed successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to process request. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                closeModal();
            });
        }
    </script>
</body>
</html>
