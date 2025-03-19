<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/db_connect.php';

// Check if user is logged in (either admin or hospital)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['hospital_logged_in'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

header('Content-Type: application/json');

// Handle different actions
$action = isset($_GET['action']) ? $_GET['action'] : 'get';

try {
    switch ($action) {
        case 'get':
            if (isset($_SESSION['admin_id'])) {
                // Admin notifications
                $notifications = getAdminNotifications($conn);
            } else {
                // Hospital notifications
                $hospital_id = $_SESSION['hospital_id'];
                $notifications = getHospitalNotifications($conn, $hospital_id);
            }
            echo json_encode($notifications);
            break;

        case 'mark_read':
            if (empty($_POST['notification_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID is required']);
                exit;
            }
            $notification_id = intval($_POST['notification_id']);
            $result = markNotificationAsRead($conn, $notification_id);
            if (isset($result['error'])) {
                http_response_code(400);
            }
            echo json_encode($result);
            break;

        case 'delete':
            if (empty($_POST['notification_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID is required']);
                exit;
            }
            $notification_id = intval($_POST['notification_id']);
            $result = deleteNotification($conn, $notification_id);
            if (isset($result['error'])) {
                http_response_code(400);
            }
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
} catch (Exception $e) {
    error_log("Error in get_notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Function to get admin notifications
function getAdminNotifications($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                notification_id,
                type,
                action,
                entity_id,
                message,
                is_read,
                can_delete,
                COALESCE(created_at, CURRENT_TIMESTAMP) as created_at,
                link_url
            FROM notifications 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process notifications to ensure all fields are set
        foreach ($notifications as &$notification) {
            $notification['is_read'] = (bool)$notification['is_read'];
            $notification['can_delete'] = (bool)$notification['can_delete'];
            $notification['created_at'] = $notification['created_at'] ?? date('Y-m-d H:i:s');
            $notification['link_url'] = $notification['link_url'] ?? '';
        }

        return $notifications;
    } catch (Exception $e) {
        error_log("Error getting admin notifications: " . $e->getMessage());
        throw $e;
    }
}

// Function to get hospital notifications
function getHospitalNotifications($conn, $hospital_id) {
    // Get donor requests notifications
    $stmt = $conn->prepare("
        SELECT 
            'donor_request' as type,
            dr.request_id as notification_id,
            'request' as action,
            dr.donor_id as entity_id,
            CONCAT(
                'Donor request from ', h.name, ' for donor ', d.name,
                ' (', d.blood_group, ' - ', ha.organ_type, ')'
            ) as message,
            CASE 
                WHEN dr.status != 'pending' THEN 1 
                ELSE 0 
            END as is_read,
            CASE 
                WHEN dr.status != 'pending' THEN 1 
                ELSE 0 
            END as can_delete,
            COALESCE(dr.request_date, CURRENT_TIMESTAMP) as created_at,
            CONCAT('../donor/view_donor.php?id=', dr.donor_id) as link_url
        FROM donor_requests dr
        JOIN hospitals h ON (h.hospital_id = dr.requesting_hospital_id OR h.hospital_id = dr.donor_hospital_id)
        JOIN donor d ON d.donor_id = dr.donor_id
        JOIN hospital_donor_approvals ha ON ha.donor_id = d.donor_id
        WHERE (dr.requesting_hospital_id = ? OR dr.donor_hospital_id = ?)
    ");
    $stmt->execute([$hospital_id, $hospital_id]);
    $request_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get registration notifications
    $stmt = $conn->prepare("
        SELECT 
            type,
            notification_id,
            action,
            entity_id,
            message,
            is_read,
            can_delete,
            COALESCE(created_at, CURRENT_TIMESTAMP) as created_at,
            link_url
        FROM hospital_notifications
        WHERE hospital_id = ? 
        AND (type = 'donor_registration' OR type = 'recipient_registration')
    ");
    $stmt->execute([$hospital_id]);
    $registration_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge and sort notifications by date
    $notifications = array_merge($request_notifications, $registration_notifications);
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Process notifications to ensure all fields are set
    foreach ($notifications as &$notification) {
        $notification['is_read'] = (bool)$notification['is_read'];
        $notification['can_delete'] = (bool)$notification['can_delete'];
        $notification['created_at'] = $notification['created_at'] ?? date('Y-m-d H:i:s');
        $notification['link_url'] = $notification['link_url'] ?? '';
    }

    return array_slice($notifications, 0, 50); // Return only the latest 50
}

// Function to mark a notification as read
function markNotificationAsRead($conn, $notification_id) {
    try {
        $conn->beginTransaction();

        // First check if notification exists
        $stmt = $conn->prepare("
            SELECT notification_id, is_read, can_delete, COALESCE(created_at, CURRENT_TIMESTAMP) as created_at
            FROM notifications 
            WHERE notification_id = ?
        ");
        $stmt->execute([$notification_id]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$notification) {
            $conn->rollBack();
            return ['error' => 'Notification not found'];
        }

        if ($notification['is_read']) {
            $conn->commit();
            return [
                'success' => true,
                'message' => 'Notification already marked as read',
                'can_delete' => (bool)$notification['can_delete'],
                'created_at' => $notification['created_at']
            ];
        }

        // Update the notification
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1,
                can_delete = 1,
                created_at = COALESCE(created_at, CURRENT_TIMESTAMP)
            WHERE notification_id = ?
        ");
        $result = $stmt->execute([$notification_id]);

        if (!$result) {
            $conn->rollBack();
            return ['error' => 'Failed to update notification'];
        }

        $conn->commit();
        return [
            'success' => true,
            'message' => 'Notification marked as read',
            'can_delete' => true,
            'created_at' => $notification['created_at']
        ];
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error marking notification as read: " . $e->getMessage());
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

// Function to delete a notification
function deleteNotification($conn, $notification_id) {
    try {
        $conn->beginTransaction();

        // First check if notification exists and is read
        $stmt = $conn->prepare("
            SELECT notification_id 
            FROM notifications 
            WHERE notification_id = ? 
            AND is_read = 1
        ");
        $stmt->execute([$notification_id]);
        
        if (!$stmt->fetch()) {
            $conn->rollBack();
            return ['error' => 'Notification not found or not marked as read'];
        }

        // Delete the notification
        $stmt = $conn->prepare("
            DELETE FROM notifications 
            WHERE notification_id = ?
        ");
        $result = $stmt->execute([$notification_id]);

        if (!$result) {
            $conn->rollBack();
            return ['error' => 'Failed to delete notification'];
        }

        $conn->commit();
        return [
            'success' => true,
            'message' => 'Notification deleted successfully'
        ];
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error deleting notification: " . $e->getMessage());
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}
?>
