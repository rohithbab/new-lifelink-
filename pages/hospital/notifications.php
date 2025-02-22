<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get filter from URL parameter, default to 'all'
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get all notifications for this hospital
try {
    $request_notifications = [];
    $registration_notifications = [];

    // Get donor requests notifications
    $stmt = $conn->prepare("
        SELECT 
            'donor_request' as type,
            dr.request_id,
            dr.requesting_hospital_id,
            dr.donor_hospital_id,
            dr.request_date,
            dr.status,
            dr.response_date,
            dr.response_message,
            h.name as hospital_name,
            d.name as donor_name,
            d.blood_group,
            ha.organ_type,
            0 as is_read,
            dr.request_date as created_at,
            dr.request_id as notification_id,
            NULL as link_url,
            NULL as person_name,
            NULL as blood_info,
            NULL as organ_info
        FROM donor_requests dr
        JOIN hospitals h ON (h.hospital_id = dr.requesting_hospital_id OR h.hospital_id = dr.donor_hospital_id)
        JOIN donor d ON d.donor_id = dr.donor_id
        JOIN hospital_donor_approvals ha ON ha.donor_id = d.donor_id
        WHERE (dr.requesting_hospital_id = ? OR dr.donor_hospital_id = ?)
    ");
    
    if ($stmt->execute([$hospital_id, $hospital_id])) {
        $request_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("Error fetching donor requests for hospital_id: $hospital_id");
    }

    // Get registration notifications
    $stmt = $conn->prepare("
        SELECT n.*, 
            CASE 
                WHEN n.type = 'donor_registration' THEN d.name
                WHEN n.type = 'recipient_registration' THEN r.full_name
            END as person_name,
            CASE 
                WHEN n.type = 'donor_registration' THEN d.blood_group
                WHEN n.type = 'recipient_registration' THEN r.blood_type
            END as blood_info,
            CASE 
                WHEN n.type = 'donor_registration' THEN d.organs_to_donate
                WHEN n.type = 'recipient_registration' THEN r.organ_required
            END as organ_info
        FROM hospital_notifications n
        LEFT JOIN donor d ON (n.type = 'donor_registration' AND d.donor_id = n.related_id)
        LEFT JOIN recipient_registration r ON (n.type = 'recipient_registration' AND r.id = n.related_id)
        WHERE n.hospital_id = ?
    ");
    
    if ($stmt->execute([$hospital_id])) {
        $registration_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("Error fetching registration notifications for hospital_id: $hospital_id");
    }

    // Safely merge notifications
    $notifications = [];
    if (!empty($request_notifications) || !empty($registration_notifications)) {
        $notifications = array_merge($request_notifications, $registration_notifications);
        
        // Sort notifications by date
        usort($notifications, function($a, $b) {
            $date_a = isset($a['request_date']) ? $a['request_date'] : $a['created_at'];
            $date_b = isset($b['request_date']) ? $b['request_date'] : $b['created_at'];
            return strtotime($date_b) - strtotime($date_a);
        });

        // Filter notifications based on read status
        if ($filter !== 'all') {
            $notifications = array_filter($notifications, function($notification) use ($filter) {
                $is_read = isset($notification['is_read']) ? $notification['is_read'] : 0;
                return ($filter === 'read' && $is_read) || ($filter === 'unread' && !$is_read);
            });
        }
    }

} catch(PDOException $e) {
    error_log("Database error in notifications.php: " . $e->getMessage());
    $notifications = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notifications-container {
            padding: 2rem;
        }

        .notification-filters {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
        }

        .filter-btn {
            padding: 0.5rem 1.5rem;
            border: 2px solid var(--primary-blue);
            border-radius: 20px;
            background: white;
            color: var(--primary-blue);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: var(--primary-blue);
            color: white;
        }

        .filter-btn:hover {
            background: var(--primary-blue);
            color: white;
        }

        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid transparent;
            position: relative;
        }

        .notification-card.unread {
            border-left-color: var(--primary-blue);
        }

        .notification-card:hover {
            transform: translateY(-2px);
        }

        .read-toggle {
            position: absolute;
            top: auto;
            bottom: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.8rem;
            color: #ccc;
            transition: all 0.3s ease;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .read-toggle:hover {
            transform: scale(1.1);
        }

        .read-toggle.read {
            color: #4CAF50;
        }

        .read-toggle i {
            font-size: 1.8rem;
        }

        .read-toggle::after {
            content: attr(title);
            font-size: 0.9rem;
            color: #666;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .notification-type {
            background: var(--primary-blue);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .notification-date {
            color: #666;
            font-size: 0.9em;
        }

        .notification-content {
            margin: 1rem 0;
        }

        .notification-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9em;
            margin-top: 0.5rem;
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

        .notification-actions {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-blue);
            color: white;
        }

        .action-btn:hover {
            background: var(--primary-green);
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

        .response-message {
            margin-top: 1rem;
            padding: 1rem;
            background: #f5f5f5;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Notifications</h1>
            </div>

            <div class="notifications-container">
                <div class="notification-filters">
                    <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        All
                    </a>
                    <a href="?filter=unread" class="filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                        Unread
                    </a>
                    <a href="?filter=read" class="filter-btn <?php echo $filter === 'read' ? 'active' : ''; ?>">
                        Read
                    </a>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h2>No Notifications</h2>
                        <p>You don't have any <?php echo $filter !== 'all' ? $filter . ' ' : ''; ?>notifications at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                            <button class="read-toggle <?php echo $notification['is_read'] ? 'read' : ''; ?>" 
                                    onclick="toggleRead(<?php echo $notification['notification_id']; ?>, this)"
                                    title="<?php echo $notification['is_read'] ? 'Mark as unread' : 'Mark as read'; ?>">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <div class="notification-header">
                                <span class="notification-type">
                                    <?php if ($notification['type'] === 'donor_request'): ?>
                                        <i class="fas fa-user"></i> Donor Request
                                    <?php elseif ($notification['type'] === 'donor_registration'): ?>
                                        <i class="fas fa-user-plus"></i> New Donor
                                    <?php elseif ($notification['type'] === 'recipient_registration'): ?>
                                        <i class="fas fa-procedures"></i> New Recipient
                                    <?php endif; ?>
                                </span>
                                <span class="notification-date">
                                    <?php 
                                        $date = isset($notification['request_date']) ? 
                                            $notification['request_date'] : 
                                            $notification['created_at'];
                                        echo date('M d, Y h:i A', strtotime($date)); 
                                    ?>
                                </span>
                            </div>
                            <div class="notification-content">
                                <?php if ($notification['type'] === 'donor_request'): ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars($notification['hospital_name']); ?></strong>
                                        has requested donor 
                                        <strong><?php echo htmlspecialchars($notification['donor_name']); ?></strong>
                                        (<?php echo htmlspecialchars($notification['blood_group']); ?>, 
                                        <?php echo htmlspecialchars($notification['organ_type']); ?>)
                                    </p>
                                    <span class="notification-status status-<?php echo strtolower($notification['status']); ?>">
                                        <?php echo ucfirst($notification['status']); ?>
                                    </span>
                                    <?php if ($notification['response_message']): ?>
                                        <p class="response-message">
                                            <strong>Response:</strong> <?php echo htmlspecialchars($notification['response_message']); ?>
                                        </p>
                                    <?php endif; ?>
                                <?php elseif ($notification['type'] === 'donor_registration'): ?>
                                    <p>New donor <strong><?php echo htmlspecialchars($notification['person_name']); ?></strong> 
                                       (Blood Group: <?php echo htmlspecialchars($notification['blood_info']); ?>) 
                                       has registered to donate <?php echo htmlspecialchars($notification['organ_info']); ?></p>
                                <?php elseif ($notification['type'] === 'recipient_registration'): ?>
                                    <p>New recipient <strong><?php echo htmlspecialchars($notification['person_name']); ?></strong> 
                                       (Blood Type: <?php echo htmlspecialchars($notification['blood_info']); ?>) 
                                       needs <?php echo htmlspecialchars($notification['organ_info']); ?> transplant</p>
                                <?php endif; ?>
                            </div>
                            <div class="notification-actions">
                                <?php if ($notification['type'] === 'donor_request'): ?>
                                    <a href="donor_requests.php?type=<?php echo $notification['requesting_hospital_id'] == $hospital_id ? 'outgoing' : 'incoming'; ?>" class="action-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                <?php else: ?>
                                    <a href="<?php 
                                        echo $notification['type'] === 'donor_registration' 
                                            ? 'hospitals_handles_donors_status.php' 
                                            : 'hospitals_handles_recipients_status.php'; 
                                        ?>" 
                                       class="action-btn" 
                                       onclick="markAsRead(<?php echo $notification['notification_id']; ?>)">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    function toggleRead(notificationId, button) {
        const isCurrentlyRead = button.classList.contains('read');
        const newReadStatus = !isCurrentlyRead;
        
        fetch('../../backend/php/toggle_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId,
                is_read: newReadStatus ? 1 : 0
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Toggle the read status visually
                button.classList.toggle('read');
                button.closest('.notification-card').classList.toggle('unread');
                button.title = newReadStatus ? 'Mark as unread' : 'Mark as read';
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function markAsRead(notificationId) {
        fetch('../../backend/php/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        });
    }

    // Auto-refresh notifications every 30 seconds
    setInterval(function() {
        const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all';
        window.location.href = `?filter=${currentFilter}`;
    }, 30000);
    </script>
</body>
</html>
