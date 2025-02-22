<?php
session_start();
$userType = isset($_SESSION['admin_id']) ? 'admin' : 
           (isset($_SESSION['hospital_id']) ? 'hospital' : 
           (isset($_SESSION['donor_id']) ? 'donor' : 
           (isset($_SESSION['recipient_id']) ? 'recipient' : 'hospital')));
session_destroy();

switch($userType) {
    case 'admin':
        header("Location: admin_login.php");
        break;
    case 'hospital':
        header("Location: hospital_login.php");
        break;
    case 'donor':
        header("Location: donor_login.php");
        break;
    case 'recipient':
        header("Location: recipient_login.php");
        break;
    default:
        header("Location: hospital_login.php");
}
exit();
