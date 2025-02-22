<?php
session_start();
require_once '../../backend/php/connection.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_name = $_SESSION['hospital_name'];
$hospital_email = $_SESSION['hospital_email'];
$odml_id = $_SESSION['odml_id'];

// Fetch hospital details from database
$query = "SELECT * FROM hospitals WHERE odml_id = :odml_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':odml_id', $odml_id);
$stmt->execute();
$hospital_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Profile - <?php echo htmlspecialchars($hospital_name); ?></title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/hospital-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="profile-page">
        <main class="main-content">
            <div class="dashboard-header">
                <div class="header-left">
                    <h2>Hospital Profile</h2>
                </div>
                <div class="header-right">
                    <button onclick="window.location.href='hospital_dashboard.php'" class="back-button">
                        <i class="fas fa-home"></i>
                        <span>Back to Dashboard</span>
                    </button>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-icon">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($hospital_name); ?></h2>
                </div>

                <div class="profile-content">
                    <div class="info-section">
                        <h3>Hospital Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>ODML ID</label>
                                <p><?php echo htmlspecialchars($hospital_data['odml_id']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($hospital_data['email']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Phone</label>
                                <p><?php echo htmlspecialchars($hospital_data['phone']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Address</label>
                                <p><?php echo htmlspecialchars($hospital_data['address']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Registration Date</label>
                                <p><?php echo date('F d, Y', strtotime($hospital_data['registration_date'])); ?></p>
                            </div>
                            <div class="info-item">
                                <label>License File</label>
                                <p>
                                    <a href="../../uploads/hospitals/license_file/<?php echo htmlspecialchars($hospital_data['license_file']); ?>" 
                                       target="_blank" 
                                       class="license-link">
                                        <i class="fas fa-file-pdf"></i>
                                        View License
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
