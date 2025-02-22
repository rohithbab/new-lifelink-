<?php
require_once 'connection.php';
require_once 'organ_matches.php';

try {
    // Get all matches that don't have notifications yet
    $sql = "SELECT m.match_id 
            FROM made_matches_by_hospitals m
            LEFT JOIN notifications n ON n.entity_id = m.match_id AND n.type = 'organ_match'
            WHERE n.notification_id IS NULL";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $created_count = 0;
    foreach ($matches as $match) {
        if (createMatchNotification($conn, $match['match_id'])) {
            $created_count++;
        }
    }

    echo "Successfully created {$created_count} notifications for existing matches.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
