<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Login - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            border-color: var(--primary-blue);
            outline: none;
        }
        .login-btn {
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: opacity 0.3s ease;
            width: 100%;
        }
        .login-btn:hover {
            opacity: 0.9;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
        .success-message {
            color: #28a745;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
        .info-tooltip {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                <span class="logo-life">LifeLink</span>
            </a>
            <div class="nav-links">
                <a href="../index.php" class="btn" style="
                    color: var(--primary-blue);
                    text-decoration: none;
                    font-weight: 500;
                    padding: 0.5rem 1rem;
                    border-radius: 5px;
                    transition: background-color 0.3s ease;
                " onmouseover="this.style.backgroundColor='rgba(0, 123, 255, 0.1)'" 
                   onmouseout="this.style.backgroundColor='transparent'">
                    Back to Home
                </a>
            </div>
        </div>
    </nav>

    <?php
    // Display debug info if available
    if (isset($_SESSION['debug_info'])) {
        echo '<div style="position: fixed; top: 10px; right: 10px; background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace; max-width: 400px; z-index: 1000;">';
        echo '<strong>Debug Info:</strong><br>';
        
        if (isset($_SESSION['debug_info']['input_email'])) {
            echo 'Input Email: ' . htmlspecialchars($_SESSION['debug_info']['input_email']) . '<br>';
        }
        
        if (isset($_SESSION['debug_info']['input_odml'])) {
            echo 'Input ODML: ' . htmlspecialchars($_SESSION['debug_info']['input_odml']) . '<br>';
        }
        
        if (isset($_SESSION['debug_info']['user_found'])) {
            echo 'User Found: ' . htmlspecialchars($_SESSION['debug_info']['user_found']) . '<br>';
        }
        
        if (isset($_SESSION['debug_info']['db_data'])) {
            echo '<br><strong>Database Data:</strong><br>';
            foreach ($_SESSION['debug_info']['db_data'] as $key => $value) {
                echo htmlspecialchars($key) . ': ' . htmlspecialchars($value) . '<br>';
            }
        }
        
        echo '</div>';
    }

    // Display error message if any
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger" style="position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px; z-index: 1000;">';
        echo htmlspecialchars($_SESSION['error']);
        echo '</div>';
    }
    ?>

    <div class="container">
        <div class="login-container">
            <h2 class="text-center" style="color: var(--primary-blue); margin-bottom: 2rem;">Recipient Login</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message text-center">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message text-center">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="../backend/php/recipient_login_process.php" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="odml_id">ODML ID</label>
                    <input type="text" id="odml_id" name="odml_id" required>
                    <div class="info-tooltip">
                        <i class="fas fa-info-circle"></i> Enter the ODML ID sent to your email after approval
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="login-btn">Login</button>
            </form>

            <div style="text-align: center; margin-top: 1rem;">
                <p style="color: #666;">
                    Don't have an account? 
                    <a href="recipient_registration.php" style="
                        color: var(--primary-blue);
                        text-decoration: none;
                        font-weight: 500;
                    " onmouseover="this.style.textDecoration='underline'" 
                       onmouseout="this.style.textDecoration='none'">
                        Register here
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
// Clear session messages after displaying
unset($_SESSION['error']);
unset($_SESSION['debug_info']);
?>