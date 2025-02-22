<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Hub - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .hub-container {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 2rem;
            text-align: center;
        }

        .hub-title {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hub-description {
            color: var(--dark-gray);
            margin-bottom: 3rem;
            font-size: 1.1rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .hub-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .hub-option {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark-gray);
            position: relative;
            overflow: hidden;
        }

        .hub-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }

        .hub-option:hover {
            transform: translateY(-5px);
        }

        .hub-option:hover::before {
            opacity: 0.1;
        }

        .hub-option.register {
            border: 2px solid var(--primary-blue);
        }

        .hub-option.login {
            border: 2px solid var(--primary-green);
        }

        .hub-option i {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 2;
        }

        .hub-option h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark-gray);
            position: relative;
            z-index: 2;
        }

        .hub-option p {
            color: #666;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 4rem;
            text-align: left;
        }

        .feature-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .feature-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-blue);
        }

        .feature-card h4 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
        }

        @media (max-width: 768px) {
            .hub-options {
                grid-template-columns: 1fr;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
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
        </div>
    </nav>

    <div class="hub-container">
        <h1 class="hub-title">Welcome to Hospital Hub</h1>
        <p class="hub-description">
            Join our network of healthcare providers and make a difference in organ donation. 
            Together, we can save more lives through efficient organ donation management.
        </p>

        <div class="hub-options">
            <a href="hospital_registration.php" class="hub-option register">
                <i class="fas fa-hospital-user"></i>
                <h3>Register Hospital</h3>
                <p>New to LifeLink? Register your hospital to join our organ donation network and help save lives.</p>
            </a>

            <a href="hospital_login.php" class="hub-option login">
                <i class="fas fa-sign-in-alt"></i>
                <h3>Hospital Login</h3>
                <p>Already registered? Log in to manage your hospital's organ donation activities and records.</p>
            </a>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-heartbeat"></i>
                <h4>Efficient Matching</h4>
                <p>Advanced algorithms to match donors with recipients quickly and accurately.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <h4>Secure Platform</h4>
                <p>State-of-the-art security measures to protect sensitive medical information.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-line"></i>
                <h4>Real-time Analytics</h4>
                <p>Comprehensive dashboard with real-time statistics and reports.</p>
            </div>
        </div>
    </div>
</body>
</html>
