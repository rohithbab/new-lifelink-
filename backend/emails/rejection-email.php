<?php
$to = $hospital['email'];
$subject = "LifeLink Hospital Registration Status Update";

$message = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(45deg, #2196F3, #dc3545);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background: #f9f9f9;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 0.8em;
            color: #666;
        }
        .contact-button {
            display: inline-block;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>LifeLink Registration Update</h1>
        </div>
        <div class='content'>
            <h2>Dear " . htmlspecialchars($hospital['name']) . ",</h2>
            <p>We have reviewed your hospital registration application for LifeLink. Unfortunately, we are unable to approve your registration at this time.</p>
            <p>This decision may be due to one or more of the following reasons:</p>
            <ul>
                <li>Incomplete or incorrect documentation</li>
                <li>Unable to verify hospital credentials</li>
                <li>Missing required certifications</li>
                <li>Non-compliance with our platform requirements</li>
            </ul>
            <p>If you believe this decision was made in error or would like to submit additional information, please contact our support team.</p>
            <center>
                <a href='mailto:support@lifelink.com' class='contact-button'>Contact Support</a>
            </center>
        </div>
        <div class='footer'>
            <p>This is an automated message from LifeLink. Please do not reply to this email.</p>
            <p>&copy; " . date('Y') . " LifeLink. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
";

// Headers for HTML email
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: LifeLink <noreply@lifelink.com>\r\n";

// Send email
mail($to, $subject, $message, $headers);
?>
