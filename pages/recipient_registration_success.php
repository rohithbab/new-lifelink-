<?php
session_start();

// If no success flag in session, redirect to registration page
if (!isset($_SESSION['registration_success'])) {
    header("Location: recipient_registration.php");
    exit();
}

// Get the recipient name from session
$recipient_name = $_SESSION['recipient_name'] ?? 'recipient';

// Clear the session variables
unset($_SESSION['registration_success']);
unset($_SESSION['recipient_name']);
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
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .success-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-green) 0%, var(--primary-blue) 100%);
        }
        
        .success-icon {
            font-size: 4rem;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        
        .success-title {
            color: var(--primary-blue);
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .success-message {
            color: var(--dark-gray);
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: 1.1rem;
        }
        
        .steps-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: left;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .steps-title {
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .step-item:hover {
            background: #f8f9fa;
        }
        
        .step-number {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .step-content {
            flex-grow: 1;
        }
        
        .step-title {
            color: var(--primary-blue);
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .whatsapp-box {
            background: #dcf8c6;
            border-radius: 10px;
            padding: 1rem;
            margin: 0.5rem 0;
            border-left: 4px solid #25d366;
        }
        
        .whatsapp-number {
            font-family: monospace;
            font-size: 1.1rem;
            color: #075e54;
            background: rgba(255,255,255,0.5);
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            margin: 0.3rem 0;
            display: inline-block;
        }
        
        .copy-btn {
            background: #25d366;
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #128c7e;
        }
        
        .note-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1.5rem;
            text-align: left;
        }
        
        .note-title {
            color: #856404;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .success-container {
                margin: 2rem;
                padding: 1.5rem;
            }
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
            Thank you <span class="hospital-name"><?php echo htmlspecialchars($recipient_name); ?></span> for registering with LifeLink. 
            Follow these steps to receive your ODML ID through WhatsApp.
        </p>
        
        <div class="steps-container">
            <h3 class="steps-title">
                <i class="fas fa-list-ol"></i>
                Complete These Steps
            </h3>
            
            <div class="step-item">
                <div class="step-number">1</div>
                <div class="step-content">
                    <div class="step-title">Save Our WhatsApp Number</div>
                    <div class="whatsapp-box">
                        <span class="whatsapp-number">+14155238886</span>
                        <button class="copy-btn" onclick="copyNumber()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="step-item">
                <div class="step-number">2</div>
                <div class="step-content">
                    <div class="step-title">Send the Following Message</div>
                    <div class="whatsapp-box">
                        <span class="whatsapp-number">join paint-taught</span>
                        <button class="copy-btn" onclick="copyMessage()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="step-item">
                <div class="step-number">3</div>
                <div class="step-content">
                    <div class="step-title">Wait for Confirmation</div>
                    <p>You'll receive a confirmation message on WhatsApp.</p>
                </div>
            </div>
            
            <div class="step-item">
                <div class="step-number">4</div>
                <div class="step-content">
                    <div class="step-title">Receive Updates</div>
                    <p>You'll receive:</p>
                    <ul style="list-style-type: none; padding-left: 0;">
                        <li><i class="fas fa-check-circle" style="color: var(--primary-green);"></i> Your ODML ID if approved</li>
                        <li><i class="fas fa-times-circle" style="color: #dc3545;"></i> Reason for rejection if not approved</li>
                    </ul>
                </div>
            </div>
            
            <div class="note-box">
                <div class="note-title">
                    <i class="fas fa-info-circle"></i>
                    Important Note
                </div>
                <p>This is a one-time setup. Keep your WhatsApp notifications enabled to receive important updates about your registration.</p>
            </div>
        </div>
        
        <a href="../index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Return to Homepage
        </a>
    </div>

    <script>
    function copyNumber() {
        navigator.clipboard.writeText('+14155238886');
        alert('WhatsApp number copied to clipboard!');
    }
    
    function copyMessage() {
        navigator.clipboard.writeText('join paint-taught');
        alert('Message copied to clipboard!');
    }
    </script>
</body>
</html>
