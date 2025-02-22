<?php
require_once 'connection.php';
require_once 'queries.php';

header('Content-Type: application/json');

try {
    // Get unread count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
    $stmt->execute();
    $unread_count = $stmt->fetchColumn();
    
    // Get 5 most recent unread notifications
    $stmt = $conn->prepare("
        SELECT 
            notification_id,
            type,
            action,
            entity_id,
            message,
            is_read,
            created_at,
            link_url
        FROM notifications 
        WHERE is_read = 0
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format timestamps and prepare response
    $formatted_notifications = array_map(function($notification) {
        $created_at = new DateTime($notification['created_at']);
        $now = new DateTime();
        $interval = $created_at->diff($now);
        
        if ($interval->days > 0) {
            $time_ago = $interval->days . ' days ago';
        } elseif ($interval->h > 0) {
            $time_ago = $interval->h . ' hours ago';
        } elseif ($interval->i > 0) {
            $time_ago = $interval->i . ' minutes ago';
        } else {
            $time_ago = 'Just now';
        }
        
        return [
            'id' => $notification['notification_id'],
            'type' => $notification['type'],
            'message' => $notification['message'],
            'time_ago' => $time_ago,
            'link_url' => $notification['link_url']
        ];
    }, $notifications);

    echo json_encode([
        'unread_count' => $unread_count,
        'notifications' => $formatted_notifications
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
