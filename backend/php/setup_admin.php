<?php
require_once 'connection.php';

try {
    // First, check if the admin table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() == 0) {
        // Create admins table if it doesn't exist
        $conn->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "Admins table created successfully!<br>";
    }

    // Clear existing admin accounts
    $conn->exec("DELETE FROM admins");
    echo "Cleared existing admin accounts<br>";

    // Create new admin account
    $username = 'admin';
    $email = 'me@lifelink.com';
    $password = 'me123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashed_password]);
    
    echo "New admin account created successfully!<br>";
    echo "Username: " . $username . "<br>";
    echo "Email: " . $email . "<br>";
    echo "Password: " . $password . "<br>";

    // Verify the account exists
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<br>Account verification successful!<br>";
        echo "Found admin account with ID: " . $admin['id'] . "<br>";
        
        // Test password verification
        if (password_verify($password, $admin['password'])) {
            echo "Password verification successful!<br>";
        } else {
            echo "Password verification failed!<br>";
        }
    } else {
        echo "<br>Error: Admin account not found after creation!<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<br>You can now try to login at: <a href='../../pages/admin_login.php'>Admin Login</a>";
?>
