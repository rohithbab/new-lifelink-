<?php
require_once 'connection.php';

try {
    // Get all hospitals that don't have notifications yet
    $sql = "SELECT h.hospital_id, h.name 
            FROM hospitals h
            LEFT JOIN notifications n ON n.entity_id = h.hospital_id AND n.type = 'hospital'
            WHERE n.notification_id IS NULL";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $created_count = 0;
    foreach ($hospitals as $hospital) {
        // Create notification for each hospital
        $sql = "INSERT INTO notifications (
            type, action, entity_id, message, is_read, created_at, link_url
        ) VALUES (
            'hospital', 'registered', :hospital_id, :message, 0, NOW(), :link_url
        )";

        $message = sprintf(
            "New hospital registration: %s",
            $hospital['name']
        );

        $link_url = "manage_hospitals.php?id=" . $hospital['hospital_id'];

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'hospital_id' => $hospital['hospital_id'],
            'message' => $message,
            'link_url' => $link_url
        ]);

        $created_count++;
    }

    echo "Successfully created {$created_count} notifications for existing hospitals.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
