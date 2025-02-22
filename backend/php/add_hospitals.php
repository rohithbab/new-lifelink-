<?php
require_once 'connection.php';

try {
    // Add sample pending hospitals
    $stmt = $conn->prepare("INSERT INTO hospitals (name, email, password, address, phone, region, license_number, status) VALUES 
        (?, ?, ?, ?, ?, ?, ?, 'pending')");
        
    $hospitals = [
        [
            'City General Hospital',
            'citygeneral@hospital.com',
            password_hash('hospital123', PASSWORD_DEFAULT),
            '123 Main Street, City Center',
            '555-0123',
            'North',
            'LIC20241201'
        ],
        [
            'Central Medical Center',
            'central@hospital.com',
            password_hash('hospital123', PASSWORD_DEFAULT),
            '456 Oak Avenue, Downtown',
            '555-0124',
            'South',
            'LIC20241202'
        ],
        [
            'Unity Healthcare',
            'unity@hospital.com',
            password_hash('hospital123', PASSWORD_DEFAULT),
            '789 Pine Road, Westside',
            '555-0125',
            'West',
            'LIC20241203'
        ]
    ];
    
    foreach ($hospitals as $hospital) {
        try {
            $stmt->execute($hospital);
            echo "Added hospital: " . $hospital[0] . "<br>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                echo "Hospital " . $hospital[0] . " already exists<br>";
            } else {
                throw $e;
            }
        }
    }
    
    echo "<br>Sample hospitals added successfully!<br>";
    echo "<a href='../../pages/admin/admin_dashboard.php'>Go to Admin Dashboard</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
