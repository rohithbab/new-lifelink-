<?php
require_once 'connection.php';

try {
    // Add sample hospitals
    $stmt = $conn->prepare("INSERT INTO hospitals (name, email, password, address, phone, region, license_number, status) VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?)");
        
    $hospitals = [
        ['City General Hospital', 'city.general@example.com', password_hash('hospital123', PASSWORD_DEFAULT), 
         '123 Main St', '555-0101', 'North', 'LIC001', 'approved'],
        ['Central Medical Center', 'central.med@example.com', password_hash('hospital123', PASSWORD_DEFAULT), 
         '456 Oak Ave', '555-0102', 'South', 'LIC002', 'pending']
    ];
    
    foreach ($hospitals as $hospital) {
        $stmt->execute($hospital);
    }
    echo "Added sample hospitals<br>";
    
    // Add sample donors
    $stmt = $conn->prepare("INSERT INTO donors (name, email, password, age, blood_type, organ_type, medical_history, hospital_id) VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?)");
        
    $donors = [
        ['John Doe', 'john@example.com', password_hash('donor123', PASSWORD_DEFAULT), 
         35, 'O+', 'kidney', 'Healthy', 1],
        ['Jane Smith', 'jane@example.com', password_hash('donor123', PASSWORD_DEFAULT), 
         28, 'A+', 'liver', 'Healthy', 1]
    ];
    
    foreach ($donors as $donor) {
        $stmt->execute($donor);
    }
    echo "Added sample donors<br>";
    
    // Add sample recipients
    $stmt = $conn->prepare("INSERT INTO recipients (name, email, password, age, blood_type, needed_organ, medical_history, urgency_level, hospital_id) VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
    $recipients = [
        ['Mike Johnson', 'mike@example.com', password_hash('recipient123', PASSWORD_DEFAULT), 
         45, 'O+', 'kidney', 'Kidney failure', 'high', 1],
        ['Sarah Wilson', 'sarah@example.com', password_hash('recipient123', PASSWORD_DEFAULT), 
         32, 'A+', 'liver', 'Liver disease', 'medium', 1]
    ];
    
    foreach ($recipients as $recipient) {
        $stmt->execute($recipient);
    }
    echo "Added sample recipients<br>";
    
    // Add sample notifications
    $stmt = $conn->prepare("INSERT INTO notifications (type, message, created_at) VALUES (?, ?, NOW())");
    
    $notifications = [
        ['new_hospital', 'New hospital registration: Central Medical Center'],
        ['urgent_case', 'New urgent recipient case: Mike Johnson needs kidney transplant']
    ];
    
    foreach ($notifications as $notification) {
        $stmt->execute($notification);
    }
    echo "Added sample notifications<br>";
    
    echo "<br>Sample data added successfully!<br>";
    echo "<a href='../../pages/admin/admin_dashboard.php'>Go to Admin Dashboard</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
