<?php
session_start();

// If no registration data in session, redirect to registration page
if (!isset($_SESSION['registration'])) {
    header("Location: hospital_registration.php");
    exit();
}

$registration = $_SESSION['registration'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Processing - LifeLink</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .registration-status {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .status-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .status-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .status-header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }

        .timeline {
            position: relative;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            width: 2px;
            height: 100%;
            background: #3498db;
            transform: translateX(-50%);
        }

        .timeline-item {
            position: relative;
            width: 100%;
            padding: 20px 0;
        }

        .timeline-content {
            position: relative;
            width: calc(50% - 30px);
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .timeline-item:nth-child(odd) .timeline-content {
            left: 50%;
            margin-left: 30px;
        }

        .timeline-item:nth-child(even) .timeline-content {
            left: 0;
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            width: 20px;
            height: 20px;
            background: #3498db;
            border-radius: 50%;
            transform: translateX(-50%);
        }

        .timeline-item.active .timeline-dot {
            background: #2ecc71;
        }

        .timeline-item.pending .timeline-dot {
            background: #f1c40f;
        }

        .timeline-content h3 {
            margin: 0 0 10px;
            color: #2c3e50;
        }

        .timeline-content p {
            margin: 0;
            color: #7f8c8d;
        }

        .estimated-time {
            font-style: italic;
            color: #95a5a6;
            margin-top: 5px;
        }

        .current-status {
            text-align: center;
            margin: 40px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .current-status h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .current-status p {
            color: #7f8c8d;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="registration-status">
        <div class="status-header">
            <h1>Registration Successfully Submitted!</h1>
            <p>Thank you for registering with LifeLink. Here's what happens next:</p>
        </div>

        <div class="current-status">
            <h2>Current Status: Pending Review</h2>
            <p>Registration submitted on: <?php echo date('F j, Y g:i A', strtotime($registration['registration_date'])); ?></p>
        </div>

        <div class="timeline">
            <div class="timeline-item active">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h3>Registration Submitted</h3>
                    <p>Your hospital registration has been successfully submitted.</p>
                    <p class="estimated-time">Completed</p>
                </div>
            </div>

            <div class="timeline-item pending">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h3>License Verification</h3>
                    <p>Our team will verify your hospital license and documentation.</p>
                    <p class="estimated-time">Estimated: 1-2 business days</p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h3>ODML ID Generation</h3>
                    <p>Once verified, we'll generate your unique ODML ID.</p>
                    <p class="estimated-time">Estimated: Within 24 hours after verification</p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h3>Account Activation</h3>
                    <p>You'll receive an email with your ODML ID and login credentials.</p>
                    <p class="estimated-time">Estimated: Immediately after ID generation</p>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <p>We'll notify you via email (<?php echo htmlspecialchars($registration['email']); ?>) once your registration is approved.</p>
            <p>If you have any questions, please contact our support team.</p>
        </div>
    </div>
</body>
</html>
<?php
// Clear the registration session data after displaying
unset($_SESSION['registration']);
?>
