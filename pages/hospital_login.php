<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Login - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
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
                    background: var(--primary-blue);
                    color: var(--white);
                    transition: all 0.3s ease;
                    border: 2px solid var(--primary-blue);
                    padding: 0.5rem 1rem;
                    font-size: 0.9rem;
                "><i class="fas fa-home"></i> Back to Home</a>
            </div>
        </div>
    </nav>

    <div class="login-container">
        <h2 class="login-title">Hospital Login</h2>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="server-error">
                    <i class="fas fa-exclamation-circle"></i>
                    ' . htmlspecialchars($_SESSION['error']) . '
                  </div>';
            unset($_SESSION['error']);
        }
        ?>

        <form action="../backend/php/hospital_login_process.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="odml_id">ODML ID</label>
                <input type="text" id="odml_id" name="odml_id" required>
                <div class="odm-id-info">This ID is provided by the admin after verification</div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="hospital_register.php">Register here</a>
        </div>
    </div>

    <style>
        .login-container {
            max-width: 400px;
            margin: 8rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-title {
            text-align: center;
            color: var(--primary-blue);
            margin-bottom: 2rem;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }
        .login-btn:hover {
            background-color: #0056b3;
        }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .register-link a {
            color: var(--primary-blue);
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .server-error {
            background: #ffe6e6;
            color: #dc3545;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .odm-id-info {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
        }
        /* Remove hover styles from home button */
        .nav-links .btn:hover {
            opacity: 0.9;
        }
    </style>
</body>
</html>