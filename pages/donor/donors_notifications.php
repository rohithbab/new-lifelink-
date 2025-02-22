<?php
session_start();
require_once '../../config/db_connect.php';

// Check if donor is logged in
if (!isset($_SESSION['is_donor']) || !$_SESSION['is_donor']) {
    header("Location: ../donor_login.php");
    exit();
}

$donor_id = $_SESSION['donor_id'];

// Get filter from URL parameter, default to 'all'
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get notifications
try {
    $where_clause = "";
    if ($filter === 'read') {
        $where_clause = "AND is_read = 1";
    } elseif ($filter === 'unread') {
        $where_clause = "AND is_read = 0";
    }

    $stmt = $conn->prepare("
        SELECT * FROM donor_notifications 
        WHERE donor_id = ? $where_clause
        ORDER BY created_at DESC
    ");
    $stmt->execute([$donor_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/donor-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .notifications-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .page-title {
            font-size: 36px;
            margin: 0;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .filter-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            color: #666;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-decoration: none;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .notification-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border-left: 4px solid transparent;
            position: relative;
            display: flex;
            gap: 15px;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .notification-card.unread {
            background-color: #e3f2fd;
            border-left-color: #2196F3;
        }

        .notification-icon {
            font-size: 24px;
            color: #2196F3;
            background: rgba(33, 150, 243, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .notification-time {
            color: #666;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notification-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            margin-right: 8px;
        }

        .type-request {
            background-color: #E3F2FD;
            color: #1565C0;
        }

        .type-match {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }

        .read-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            color: #ccc;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .read-toggle:hover {
            background: rgba(0,0,0,0.05);
        }

        .read-toggle.read {
            color: #4CAF50;
        }

        .delete-btn {
            position: absolute;
            top: 20px;
            right: 70px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            color: #dc3545;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .delete-btn:hover {
            background: rgba(220, 53, 69, 0.1);
            color: #c82333;
        }

        .delete-btn i {
            color: inherit;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .notifications-container {
                padding: 10px;
            }

            .page-title {
                font-size: 28px;
            }

            .filter-buttons {
                flex-wrap: wrap;
            }

            .filter-btn {
                width: 100%;
            }

            .notification-card {
                flex-direction: column;
            }

            .notification-icon {
                margin: 0 auto 10px;
            }

            .notification-message {
                text-align: center;
            }

            .notification-time {
                justify-content: center;
            }

            .read-toggle {
                position: relative;
                top: auto;
                right: auto;
                margin: 10px auto 0;
            }

            .delete-btn {
                position: relative;
                top: auto;
                right: auto;
                margin: 10px auto 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-heartbeat"></i>
                <span>LifeLink</span>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="donor_dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="donor_personal_details.php">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="search_hospitals_for_donors.php">
                            <i class="fas fa-search"></i>
                            <span>Search Hospitals</span>
                        </a>
                    </li>
                    <li>
                        <a href="my_requests_for_donors.php">
                            <i class="fas fa-list"></i>
                            <span>My Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="donors_notifications.php" class="active">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                        </a>
                    </li>
                    <li>
                        <a href="../donor_login.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="notifications-container">
                <div class="page-header">
                    <h1 class="page-title">Notifications</h1>
                </div>

                <div class="filter-buttons">
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
                            <div class="notification-icon">
                                <i class="fas <?php echo $notification['type'] === 'request_status' ? 'fa-file-medical' : 'fa-handshake'; ?>"></i>
                            </div>
                            <div class="notification-content">
                                <span class="notification-type <?php echo $notification['type'] === 'request_status' ? 'type-request' : 'type-match'; ?>">
                                    <?php echo $notification['type'] === 'request_status' ? 'Request Update' : 'Match Found'; ?>
                                </span>
                                <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <span class="notification-time">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                                </span>
                            </div>
                            <button class="read-toggle <?php echo $notification['is_read'] ? 'read' : ''; ?>" 
                                    onclick="toggleRead(<?php echo $notification['notification_id']; ?>, this)"
                                    title="<?php echo $notification['is_read'] ? 'Mark as unread' : 'Mark as read'; ?>">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <?php if ($notification['is_read']): ?>
                            <button class="delete-btn" onclick="deleteNotification(<?php echo $notification['notification_id']; ?>, this)">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
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
        
        fetch('../../backend/php/toggle_donor_notification_read.php', {
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
                button.classList.toggle('read');
                button.title = newReadStatus ? 'Mark as unread' : 'Mark as read';
                const card = button.closest('.notification-card');
                card.classList.toggle('unread');
                if (newReadStatus) {
                    const deleteBtn = card.querySelector('.delete-btn');
                    if (!deleteBtn) {
                        const deleteButton = document.createElement('button');
                        deleteButton.classList.add('delete-btn');
                        deleteButton.onclick = function() {
                            deleteNotification(notificationId, this);
                        };
                        deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
                        card.appendChild(deleteButton);
                    }
                } else {
                    const deleteBtn = card.querySelector('.delete-btn');
                    if (deleteBtn) {
                        deleteBtn.remove();
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function deleteNotification(notificationId, button) {
        if (!confirm('Are you sure you want to delete this notification?')) {
            return;
        }

        fetch('../../backend/php/delete_donor_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the notification item from the UI
                const notificationItem = button.closest('.notification-card');
                notificationItem.remove();
            } else {
                alert(data.error || 'Failed to delete notification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the notification');
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
