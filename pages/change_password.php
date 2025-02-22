<?php
session_start();
if (!isset($_SESSION['hospital_id'])) {
    header("Location: hospital_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - LifeLink</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
        }
        .requirement {
            margin: 5px 0;
            color: #666;
        }
        .requirement.met {
            color: #2ecc71;
        }
        .btn-change {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-change:hover {
            background: #2980b9;
        }
        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
            text-align: center;
        }
        .success-message {
            color: #2ecc71;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <h2 style="text-align: center; margin-bottom: 30px;">
            <?php echo isset($_GET['first']) ? 'Set New Password' : 'Change Password'; ?>
        </h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php 
                    switch($_GET['error']) {
                        case 'current':
                            echo "Current password is incorrect";
                            break;
                        case 'match':
                            echo "New passwords do not match";
                            break;
                        case 'requirements':
                            echo "Password does not meet requirements";
                            break;
                        default:
                            echo "An error occurred. Please try again";
                    }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                Password changed successfully!
            </div>
        <?php endif; ?>

        <form action="../backend/php/change_password_process.php" method="POST" id="passwordForm">
            <?php if (!isset($_GET['first'])): ?>
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="password-requirements">
                <h4>Password Requirements:</h4>
                <div class="requirement" id="length">• At least 8 characters long</div>
                <div class="requirement" id="uppercase">• At least one uppercase letter</div>
                <div class="requirement" id="lowercase">• At least one lowercase letter</div>
                <div class="requirement" id="number">• At least one number</div>
                <div class="requirement" id="special">• At least one special character</div>
            </div>

            <button type="submit" class="btn-change" id="submitBtn" disabled>
                Change Password
            </button>
        </form>
    </div>

    <script>
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        const requirements = {
            length: /.{8,}/,
            uppercase: /[A-Z]/,
            lowercase: /[a-z]/,
            number: /[0-9]/,
            special: /[!@#$%^&*(),.?":{}|<>]/
        };

        function checkPassword() {
            const password = newPassword.value;
            let meetsAll = true;

            // Check each requirement
            for (let req in requirements) {
                const element = document.getElementById(req);
                const meets = requirements[req].test(password);
                element.classList.toggle('met', meets);
                meetsAll = meetsAll && meets;
            }

            // Check if passwords match
            const matching = password === confirmPassword.value;
            
            // Enable submit button if all requirements are met and passwords match
            submitBtn.disabled = !(meetsAll && matching && password.length > 0);
        }

        newPassword.addEventListener('input', checkPassword);
        confirmPassword.addEventListener('input', checkPassword);
    </script>
</body>
</html>
