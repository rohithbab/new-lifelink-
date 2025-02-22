<?php
require_once '../config/connection.php';

// Function to clear hospitals table
function clearHospitalsTable($conn) {
    try {
        // First clear donor table
        $sql = "DELETE FROM donor";
        $conn->query($sql);
        $conn->query("ALTER TABLE donor AUTO_INCREMENT = 1");
        echo "Successfully cleared donor table\n";

        // Then clear recipient_registration table
        $sql = "DELETE FROM recipient_registration";
        $conn->query($sql);
        $conn->query("ALTER TABLE recipient_registration AUTO_INCREMENT = 1");
        echo "Successfully cleared recipient_registration table\n";

        // Finally clear hospitals table
        $sql = "DELETE FROM hospitals";
        if ($conn->query($sql)) {
            $conn->query("ALTER TABLE hospitals AUTO_INCREMENT = 1");
            echo "Successfully cleared hospitals table\n";
        } else {
            echo "Error clearing hospitals table: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Function to clear donor table
function clearDonorTable($conn) {
    try {
        $sql = "DELETE FROM donor";
        if ($conn->query($sql)) {
            $conn->query("ALTER TABLE donor AUTO_INCREMENT = 1");
            echo "Successfully cleared donor table\n";
        } else {
            echo "Error clearing donor table: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Function to clear recipient_registration table
function clearRecipientTable($conn) {
    try {
        $sql = "DELETE FROM recipient_registration";
        if ($conn->query($sql)) {
            $conn->query("ALTER TABLE recipient_registration AUTO_INCREMENT = 1");
            echo "Successfully cleared recipient_registration table\n";
        } else {
            echo "Error clearing recipient_registration table: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Get the table to clear from the URL parameter
$table = isset($_GET['table']) ? $_GET['table'] : '';

// Disable foreign key checks temporarily
$conn->query('SET FOREIGN_KEY_CHECKS = 0');

switch ($table) {
    case 'hospitals':
        clearHospitalsTable($conn);
        break;
    case 'donor':
        clearDonorTable($conn);
        break;
    case 'recipient':
        clearRecipientTable($conn);
        break;
    case 'all':
        // Clear in the correct order
        clearDonorTable($conn);
        clearRecipientTable($conn);
        clearHospitalsTable($conn);
        break;
    default:
        echo "Please specify a table to clear using the 'table' parameter:\n";
        echo "- hospitals (will also clear donor and recipient tables)\n";
        echo "- donor\n";
        echo "- recipient\n";
        echo "- all (to clear all tables)\n";
        echo "\nExample: clear_tables.php?table=hospitals\n";
}

// Re-enable foreign key checks
$conn->query('SET FOREIGN_KEY_CHECKS = 1');
?>
