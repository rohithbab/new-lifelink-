<?php
require_once 'connection.php';

try {
    // Check if all required tables exist
    $required_tables = ['admins', 'hospitals', 'donors', 'recipients', 'organ_matches', 'notifications'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo "Missing tables: " . implode(", ", $missing_tables) . "<br>";
        echo "Please run the create_tables.sql script first.<br>";
    } else {
        echo "All required tables exist!<br><br>";
        
        // Check table contents
        foreach ($required_tables as $table) {
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "$table table has $count records<br>";
        }
    }
    
    echo "<br><a href='../../pages/admin/admin_dashboard.php'>Go to Admin Dashboard</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
