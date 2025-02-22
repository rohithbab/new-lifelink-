<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['hospital_id'])) {
    header("Location: hospital_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Registration - LifeLink</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../../assets/css/styles.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="../../assets/images/logo.png" alt="LifeLink Logo" class="auth-logo">
                <h1>Hospital Registration</h1>
            </div>
            
            <?php
            // Display error message if any
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            
            // Display success message if any
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>
            
            <form action="../../backend/php/hospital_register_process.php" method="POST" enctype="multipart/form-data" class="auth-form">
                <div class="form-group">
                    <label for="name">Hospital Name</label>
                    <div class="input-group">
                        <i class="fas fa-hospital"></i>
                        <input type="text" id="name" name="name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Official Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                               title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="registration_number">Hospital Registration Number</label>
                    <div class="input-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="registration_number" name="registration_number" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="license">Hospital License (PDF/Image)</label>
                    <div class="input-group">
                        <i class="fas fa-file-medical"></i>
                        <input type="file" id="license" name="license" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <small class="form-text text-muted">Upload your hospital's valid license document</small>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Hospital Address</label>
                    <div class="input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea id="address" name="address" required></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        Register Hospital
                    </button>
                </div>
                
                <div class="auth-links">
                    <p>Already have an ODML ID? <a href="hospital_login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
