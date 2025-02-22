<?php
session_start();

// If no success flag in session, redirect to registration page
if (!isset($_SESSION['registration_success'])) {
    header("Location: hospital_registration.php");
    exit();
}

// Get the hospital name from session
$hospital_name = $_SESSION['hospital_name'] ?? 'your hospital';

// Clear the session variables
unset($_SESSION['registration_success']);
unset($_SESSION['hospital_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2rem;
            text-align: center;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--primary-green);
            margin-bottom: 1rem;
        }
        
        .success-title {
            color: var(--primary-blue);
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        
        .success-message {
            color: var(--dark-gray);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .next-steps {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .next-steps h3 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
        
        .next-steps ul {
            list-style-type: none;
            padding: 0;
        }
        
        .next-steps li {
            margin-bottom: 0.8rem;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .next-steps li:before {
            content: '✓';
            color: var(--primary-green);
            position: absolute;
            left: 0;
        }
        
        .hospital-name {
            font-weight: bold;
            color: var(--primary-blue);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                <span class="logo-text">Life<span class="logo-gradient">Link</span></span>
            </a>
        </div>
    </nav>

    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1 class="success-title">Registration Successful!</h1>
        <p class="success-message">
            Thank you for registering <span class="hospital-name"><?php echo htmlspecialchars($hospital_name); ?></span> with LifeLink. 
            Your registration has been submitted successfully and is pending approval.
        </p>
        
        <div class="next-steps">
            <h3>Next Steps:</h3>
            <ul>
                <li>Our admin team will review your registration details</li>
                <li>We will verify your hospital license</li>
                <li>You will receive an email notification once your registration is approved</li>
                <li>After approval, you can log in using your registered email and password</li>
            </ul>
        </div>
        
        <a href="../index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Return to Homepage
        </a>
    </div>
</body>
</html>
