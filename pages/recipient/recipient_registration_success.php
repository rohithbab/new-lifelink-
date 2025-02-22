<?php
session_start();
if (!isset($_SESSION['registration_success'])) {
    header("Location: ../recipient_registration.php");
    exit();
}

// Clear the success flag after displaying the page
$email = isset($_SESSION['recipient_email']) ? $_SESSION['recipient_email'] : '';
unset($_SESSION['registration_success']);
unset($_SESSION['recipient_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Success - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        .success-title {
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        .timeline {
            margin: 2rem 0;
            padding: 0;
            list-style: none;
            position: relative;
        }
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 100%;
            background: #ddd;
        }
        .timeline-item {
            margin-bottom: 2rem;
            position: relative;
            padding-left: 50%;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: calc(50% - 6px);
            width: 12px;
            height: 12px;
            background: #fff;
            border: 2px solid #007bff;
            border-radius: 50%;
        }
        .timeline-item.active:before {
            background: #28a745;
            border-color: #28a745;
        }
        .timeline-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-left: 2rem;
        }
        .timeline-title {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.5rem;
        }
        .timeline-item.active .timeline-title {
            color: #28a745;
        }
        .back-button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 1rem;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1 class="success-title">Registration Successful!</h1>
        <p>Thank you for registering as a recipient with LifeLink. Your application is now being processed.</p>
        
        <?php if ($email): ?>
            <p>A confirmation email will be sent to: <strong><?php echo htmlspecialchars($email); ?></strong></p>
        <?php endif; ?>

        <div class="timeline">
            <div class="timeline-item active">
                <div class="timeline-content">
                    <div class="timeline-title">Registration Submitted</div>
                    <p>Your recipient registration has been successfully submitted.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-title">Document Verification</div>
                    <p>Our team will verify your medical records and ID proof.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-title">Medical Review</div>
                    <p>Medical professionals will review your case and assess urgency.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-title">Application Decision</div>
                    <p>You will be notified about your application status.</p>
                </div>
            </div>
        </div>

        <p><strong>What's Next?</strong></p>
        <p>Please check your email regularly. We will contact you with updates about your application status and next steps.</p>
        
        <a href="../../index.php" class="back-button">Back to Home</a>
    </div>
</body>
</html>
