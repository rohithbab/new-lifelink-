<?php
$to = $hospital['email'];
$subject = "LifeLink Hospital Registration Approved";

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
            background: linear-gradient(45deg, #2196F3, #4CAF50);
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
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
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
            <h1>Welcome to LifeLink!</h1>
        </div>
        <div class='content'>
            <h2>Congratulations, " . htmlspecialchars($hospital['name']) . "!</h2>
            <p>Your hospital registration has been approved. You can now access the LifeLink platform and start managing organ donation processes.</p>
            <p>Here's what you can do now:</p>
            <ul>
                <li>Log in to your hospital dashboard</li>
                <li>Update your hospital profile</li>
                <li>Manage organ donation requests</li>
                <li>Coordinate with donors and recipients</li>
            </ul>
            <center>
                <a href='http://localhost/lifelink/pages/hospital_login.php' class='button'>Login to Dashboard</a>
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
