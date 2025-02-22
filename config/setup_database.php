<?php
require_once 'connection.php';

// Function to create tables
function createTables($conn) {
    // Array of table creation SQL statements
    $tables = [
        // Hospitals table
        "CREATE TABLE IF NOT EXISTS hospitals (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            registration_number VARCHAR(50) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            address TEXT NOT NULL,
            license_path VARCHAR(255) NOT NULL,
            odml_id VARCHAR(50) UNIQUE,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Donors table
        "CREATE TABLE IF NOT EXISTS donors (
            id INT PRIMARY KEY AUTO_INCREMENT,
            hospital_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            age INT NOT NULL,
            blood_type VARCHAR(5) NOT NULL,
            organ VARCHAR(50) NOT NULL,
            contact VARCHAR(20) NOT NULL,
            email VARCHAR(255) NOT NULL,
            address TEXT NOT NULL,
            medical_history TEXT,
            status ENUM('pending', 'approved', 'rejected', 'matched', 'donated') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
        )",

        // Recipients table
        "CREATE TABLE IF NOT EXISTS recipients (
            id INT PRIMARY KEY AUTO_INCREMENT,
            hospital_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            age INT NOT NULL,
            blood_type VARCHAR(5) NOT NULL,
            organ_needed VARCHAR(50) NOT NULL,
            urgency ENUM('normal', 'urgent', 'critical') DEFAULT 'normal',
            contact VARCHAR(20) NOT NULL,
            email VARCHAR(255) NOT NULL,
            address TEXT NOT NULL,
            medical_history TEXT NOT NULL,
            status ENUM('waiting', 'matched', 'transplanted') DEFAULT 'waiting',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
        )",

        // Donor-Recipient Matches table
        "CREATE TABLE IF NOT EXISTS donor_recipient_matches (
            id INT PRIMARY KEY AUTO_INCREMENT,
            donor_id INT NOT NULL,
            recipient_id INT NOT NULL,
            match_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'approved', 'completed', 'cancelled') DEFAULT 'pending',
            notes TEXT,
            FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
            FOREIGN KEY (recipient_id) REFERENCES recipients(id) ON DELETE CASCADE
        )",

        // Notifications table
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            type VARCHAR(50) NOT NULL,
            recipient_type ENUM('admin', 'hospital') NOT NULL,
            recipient_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // Hospital Analytics table
        "CREATE TABLE IF NOT EXISTS hospital_analytics (
            id INT PRIMARY KEY AUTO_INCREMENT,
            hospital_id INT NOT NULL,
            total_donors INT DEFAULT 0,
            total_recipients INT DEFAULT 0,
            successful_matches INT DEFAULT 0,
            pending_requests INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
        )"
    ];

    // Execute each table creation statement
    foreach ($tables as $sql) {
        if (!$conn->query($sql)) {
            throw new Exception("Error creating table: " . $conn->error);
        }
    }

    return true;
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . $db_name;
$conn->query($sql);

// Select the database
$conn->select_db($db_name);

try {
    // Create tables
    if (createTables($conn)) {
        echo "Database setup completed successfully!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
