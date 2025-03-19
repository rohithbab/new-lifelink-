<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get all notifications
$notifications = getAdminNotifications($conn, 50);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - LifeLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        body {
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
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

        .back-btn {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .back-btn:hover {
            transform: translateY(-52%);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
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
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border-left: 4px solid transparent;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .notification-card.unread {
            background-color: #e3f2fd;
            border-left-color: #2196F3;
        }

        .notification-time {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .notification-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            margin-right: 8px;
        }

        .type-hospital {
            background-color: #E3F2FD;
            color: #1565C0;
        }

        .type-donor {
            background-color: #FCE4EC;
            color: #C2185B;
        }

        .type-recipient {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .type-organ_match {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .type-donor_request {
            background-color: #F3E5F5;
            color: #7B1FA2;
        }

        .type-donor_registration {
            background-color: #FCE4EC;
            color: #C2185B;
        }

        .type-recipient_registration {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .type-default {
            background-color: #ECEFF1;
            color: #455A64;
        }

        .empty-notifications {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-left: 15px;
        }

        .mark-read-mini-btn, .delete-btn {
            border: none;
            min-width: 40px;
            min-height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .mark-read-mini-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .delete-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .mark-read-mini-btn:hover, .delete-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .mark-read-mini-btn:active, .delete-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .mark-read-mini-btn::before, .delete-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .mark-read-mini-btn:hover::before, .delete-btn:hover::before {
            transform: translateX(0);
        }

        .mark-read-mini-btn i, .delete-btn i {
            font-size: 18px;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 768px) {
            .mark-read-mini-btn, .delete-btn {
                min-width: 48px;
                min-height: 48px;
            }

            .mark-read-mini-btn i, .delete-btn i {
                font-size: 20px;
            }
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-start {
            align-items: flex-start;
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <div class="page-header">
            <h1 class="page-title">All Notifications</h1>
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterNotifications('all')">All</button>
            <button class="filter-btn" onclick="filterNotifications('unread')">Unread</button>
            <button class="filter-btn" onclick="filterNotifications('read')">Read</button>
        </div>

        <div id="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="empty-notifications">
                    <i class="fas fa-bell" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                    <p>No notifications yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                         data-status="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>"
                         data-id="<?php echo htmlspecialchars($notification['notification_id']); ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="notification-content">
                                <span class="notification-type type-<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $notification['type']))); ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $notification['type'])); ?>
                                </span>
                                <div>
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                    <div class="notification-time">
                                        <?php 
                                        $timestamp = isset($notification['created_at']) ? $notification['created_at'] : date('Y-m-d H:i:s');
                                        echo date('M d, Y h:i A', strtotime($timestamp)); 
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                    <button class="mark-read-mini-btn" onclick="markAsRead('<?php echo htmlspecialchars($notification['notification_id']); ?>')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($notification['is_read']): ?>
                                    <button class="delete-btn" onclick="deleteNotification('<?php echo htmlspecialchars($notification['notification_id']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function filterNotifications(filter) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => {
                if (btn.textContent.toLowerCase() === filter) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            const cards = document.querySelectorAll('.notification-card');
            cards.forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function markAsRead(notificationId) {
            $.ajax({
                url: '../../backend/php/get_notifications.php?action=mark_read',
                type: 'POST',
                data: { notification_id: notificationId },
                success: function(response) {
                    if (response.success) {
                        // Update UI
                        const card = $(`.notification-card[data-id="${notificationId}"]`);
                        card.removeClass('unread');
                        card.attr('data-status', 'read');
                        
                        // Remove mark as read button
                        card.find('.mark-read-mini-btn').remove();
                        
                        // Add delete button
                        const deleteBtn = `
                            <button class="delete-btn" onclick="deleteNotification('${notificationId}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        card.find('.notification-actions').append(deleteBtn);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error marking notification as read:', error);
                }
            });
        }

        function deleteNotification(notificationId) {
            if (confirm('Are you sure you want to delete this notification?')) {
                $.ajax({
                    url: '../../backend/php/get_notifications.php?action=delete',
                    type: 'POST',
                    data: { notification_id: notificationId },
                    success: function(response) {
                        if (response.success) {
                            // Remove the notification card from UI
                            $(`.notification-card[data-id="${notificationId}"]`).fadeOut(300, function() {
                                $(this).remove();
                                // Check if there are no more notifications
                                if ($('.notification-card').length === 0) {
                                    $('#notifications-list').html(`
                                        <div class="empty-notifications">
                                            <i class="fas fa-bell" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                                            <p>No notifications yet</p>
                                        </div>
                                    `);
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting notification:', error);
                    }
                });
            }
        }

        // Refresh notifications every 30 seconds
        setInterval(function() {
            $.get('../../backend/php/get_notifications.php', function(data) {
                // Update notifications list
                location.reload();
            });
        }, 30000);
    </script>
</body>
</html>
