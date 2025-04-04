<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$stats = getDashboardStats($conn);
$notifications = getAdminNotifications($conn, 5);
$pendingHospitals = getPendingHospitals($conn);
$pendingDonors = getPendingDonors($conn);
$pendingRecipients = getPendingRecipients($conn);
$urgentRecipients = getUrgentRecipients($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LifeLink</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="stylesheet" href="../../assets/css/notification-bell.css">
    
    <!-- Custom Styles -->
    <style>
        .odml-input {
            width: 150px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            transition: border-color 0.3s ease;
            font-size: 14px;
            margin-right: 8px;
        }

        .odml-input:focus {
            border-color: #1a73e8;
            outline: none;
            box-shadow: 0 0 5px rgba(26, 115, 232, 0.3);
        }

        /* Button Styles */
        .update-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #0396FF, #4CAF50);
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 0 10px rgba(3, 150, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .update-btn::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, 
                rgba(255,255,255,0), 
                rgba(255,255,255,0.2), 
                rgba(255,255,255,0));
            transform: rotate(45deg);
            animation: glowing 2s linear infinite;
        }

        @keyframes glowing {
            0% { transform: rotate(45deg) translateX(-100%); }
            100% { transform: rotate(45deg) translateX(100%); }
        }

        .update-btn:hover {
            background: linear-gradient(135deg, #0377cc, #388E3C);
            transform: translateY(-1px);
            box-shadow: 0 0 15px rgba(3, 150, 255, 0.4);
        }

        .reject-btn {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        .reject-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .view-btn {
            padding: 6px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .view-btn:hover {
            background: #388E3C;
            text-decoration: none;
            color: white;
        }

        .action-cell {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .approve-btn, .reject-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            color: white;
        }

        .approve-btn {
            background: #28a745;
        }

        .approve-btn:hover {
            background: #218838;
        }

        .reject-btn {
            background: #dc3545;
        }

        .reject-btn:hover {
            background: #c82333;
        }
        
        /* Update gradient header for pending hospitals table */
        .table-container h2 {
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
            padding: 15px;
            border-radius: 5px 5px 0 0;
            margin: 0;
        }
        
        #pending-hospitals-table thead tr {
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
        }
        
        #pending-hospitals-table thead th {
            padding: 12px;
            font-weight: 600;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .stat-card i {
            font-size: 2.5rem;
        }

        .stat-card:nth-child(1) i { /* Total Hospitals */
            color: #2196F3; /* Blue */
        }
        
        .stat-card:nth-child(2) i { /* Total Donors */
            color: #4CAF50; /* Green */
        }
        
        .stat-card:nth-child(3) i { /* Total Recipients */
            color: #9C27B0; /* Purple */
        }
        
        .stat-card:nth-child(4) i { /* Pending Hospitals */
            color: #FF9800; /* Orange */
        }
        
        .stat-card:nth-child(5) i { /* Successful Matches */
            color: #00BCD4; /* Cyan */
        }
        
        .stat-card:nth-child(6) i { /* Pending Matches */
            color: #F44336; /* Red */
        }
        
        .stat-card:nth-child(7) i { /* Urgent Recipients */
            color: #E91E63; /* Pink */
        }

        .stat-card:nth-child(1):hover { border-left: 4px solid #2196F3; }
        .stat-card:nth-child(2):hover { border-left: 4px solid #4CAF50; }
        .stat-card:nth-child(3):hover { border-left: 4px solid #9C27B0; }
        .stat-card:nth-child(4):hover { border-left: 4px solid #FF9800; }
        .stat-card:nth-child(5):hover { border-left: 4px solid #00BCD4; }
        .stat-card:nth-child(6):hover { border-left: 4px solid #F44336; }
        .stat-card:nth-child(7):hover { border-left: 4px solid #E91E63; }

        .stat-info h3 {
            margin: 0;
            font-size: 1rem;
            color: #666;
        }

        .stat-info p {
            margin: 5px 0 0;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }
        
        /* Added styles for smaller buttons */
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .btn-sm .fas {
            font-size: 1rem;
        }

        .btn-approve {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .btn-reject {
            background: linear-gradient(135deg, #f44336, #e53935);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #e53935, #d32f2f);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .odml-input {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 120px;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .odml-input:focus {
            border-color: #2196F3;
            box-shadow: 0 0 5px rgba(33, 150, 243, 0.3);
            outline: none;
        }

        .update-odml-btn {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .update-odml-btn:hover {
            background: linear-gradient(135deg, #1976D2, #1565C0);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .update-odml-btn i {
            margin-right: 4px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background: linear-gradient(to bottom, #f8f9fa, #ffffff);
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-top: 4px solid #4CAF50;
        }

        .modal h2 {
            color: #2c3e50;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .modal input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .modal input[type="text"]:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(74, 144, 226, 0.2);
        }

        .modal-notification {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 4px;
            margin: 15px 0;
        }

        .modal-notification i {
            color: #25D366;
            font-size: 20px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn.approve {
            background: linear-gradient(135deg, #1a73e8, #4CAF50);
            color: white;
            border: none;
        }

        .modal-btn.approve:hover {
            background: linear-gradient(135deg, #1557b0, #388E3C);
            transform: translateY(-1px);
        }

        .modal-btn.cancel {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #6c757d;
        }

        .modal-btn.cancel:hover {
            background: #e9ecef;
        }
        
        /* Notification Bell Styles */
        .notification-bell-container {
            position: relative;
            display: inline-block;
        }

        .notification-bell {
            cursor: pointer;
            font-size: 24px;
            color: #666;
            transition: color 0.3s ease;
        }

        .notification-bell:hover {
            color: #333;
        }

        .notification-count {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 12px;
            background: #f44336;
            color: white;
            padding: 2px 5px;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        .notification-dropdown {
            position: absolute;
            top: 30px;
            right: 0;
            background: white;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: none;
        }

        .notification-dropdown.show {
            display: block;
        }

        .notification-header {
            margin-bottom: 10px;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #f0f2f5;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #f8f9fa;
        }

        .notification-badge {
            font-size: 12px;
            padding: 2px 5px;
            border-radius: 4px;
            color: white;
            margin-right: 5px;
        }

        .notification-badge.type-match {
            background: #2196F3;
        }

        .notification-badge.type-donor {
            background: #4CAF50;
        }

        .notification-badge.type-recipient {
            background: #9C27B0;
        }

        .notification-content {
            font-size: 14px;
            color: #666;
        }

        .notification-time {
            font-size: 12px;
            color: #999;
        }

        .no-notifications {
            padding: 10px;
            text-align: center;
            color: #666;
        }

        .notification-footer {
            padding: 10px;
            text-align: center;
            border-top: 1px solid #f0f2f5;
        }

        .notification-footer a {
            color: #2196F3;
            text-decoration: none;
        }

        .notification-footer a:hover {
            text-decoration: underline;
        }
        
        /* ODML Update Modal Styles */
        .odml-input-group {
            margin: 20px 0;
        }

        .odml-input-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .notification-info, .status-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }

        .notification-info {
            background: rgba(37, 211, 102, 0.1);
        }

        .status-info {
            background: rgba(52, 152, 219, 0.1);
        }

        .notification-info i {
            color: #25d366;
        }

        .status-info i {
            color: #3498db;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e3e8f0;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        /* Button Styles */
        .update-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #0396FF, #4CAF50);
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 0 10px rgba(3, 150, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .update-btn::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, 
                rgba(255,255,255,0), 
                rgba(255,255,255,0.2), 
                rgba(255,255,255,0));
            transform: rotate(45deg);
            animation: glowing 2s linear infinite;
        }

        @keyframes glowing {
            0% { transform: rotate(45deg) translateX(-100%); }
            100% { transform: rotate(45deg) translateX(100%); }
        }

        .update-btn:hover {
            background: linear-gradient(135deg, #0377cc, #388E3C);
            transform: translateY(-1px);
            box-shadow: 0 0 15px rgba(3, 150, 255, 0.4);
        }

        .reject-btn {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        .reject-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .view-btn {
            padding: 6px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .view-btn:hover {
            background: #388E3C;
            text-decoration: none;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }

        .modal h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body p {
            margin-bottom: 15px;
        }

        .modal-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .modal-info i {
            color: #1a73e8;
        }

        .odml-input {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            padding: 8px 16px;
            background: #f1f3f4;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: #5f6368;
        }

        .btn-approve {
            padding: 8px 16px;
            background: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-approve:hover {
            background: #1557b0;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: relative;
            background: linear-gradient(to bottom, #f8f9fa, #ffffff);
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-top: 5px solid #4CAF50;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(76, 175, 80, 0.1);
            position: relative;
        }

        .modal-header h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: #4CAF50;
        }

        .modal-header .hospital-name {
            color: #666;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .input-group input, 
        .input-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            outline: none;
        }

        .notification-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem;
            background: rgba(37, 211, 102, 0.1);
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .notification-box i {
            color: #25D366;
            font-size: 1.5rem;
        }

        .notification-box p {
            color: #2c3e50;
            margin: 0;
            font-size: 0.9rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .modal-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-btn.cancel {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #dee2e6;
        }

        .modal-btn.cancel:hover {
            background: #e9ecef;
        }

        .modal-btn.approve {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
        }

        .modal-btn.approve:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.2);
        }

        .modal-btn.reject {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
        }

        .modal-btn.reject:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }
        
        /* Urgency Badges */
        .urgency-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .urgency-badge.high {
            background-color: #dc3545;
            color: white;
        }

        .urgency-badge.medium {
            background-color: #ffc107;
            color: black;
        }

        .urgency-badge.low {
            background-color: #28a745;
            color: white;
        }
        
        /* Modal Styles */
        .modal-body {
            margin: 15px 0;
        }
        
        .checkbox-container {
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-container label {
            color: #333;
            font-size: 14px;
            cursor: pointer;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .cancel-btn {
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .cancel-btn:hover {
            background: #5a6268;
        }
        
        /* Rejection Modal Styles */
        .modal-header .header-icon {
            font-size: 2em;
            color: #f0ad4e;
            margin-bottom: 15px;
        }
        
        .modal-subtitle {
            color: #666;
            margin-top: 5px;
        }
        
        .entity-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: 600;
            width: 100px;
        }
        
        .detail-value {
            color: #333;
        }
        
        .confirmation-box {
            background: #fff5f5;
            border: 1px solid #ffe0e0;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .confirmation-box ul {
            margin-top: 10px;
            padding-left: 20px;
        }
        
        .confirmation-box li {
            margin-bottom: 5px;
            color: #dc3545;
        }
        
        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        #rejectionReason {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-outline {
            border: 1px solid #ccc;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: #f8f9fa;
        }
    </style>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/responsive.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><span class="logo-gradient">LifeLink</span> Admin</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_hospitals.php" class="nav-link">
                        <i class="fas fa-hospital"></i>
                        Manage Hospitals
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_donors.php" class="nav-link">
                        <i class="fas fa-hand-holding-heart"></i>
                        Manage Donors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_recipients.php" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        Manage Recipients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="organ_match_info_for_admin.php" class="nav-link">
                        <i class="fas fa-handshake-angle"></i>
                        Organ Matches
                    </a>
                </li>
                <li class="nav-item">
                    <a href="analytics.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a href="notifications.php" class="nav-link">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../admin_dashboard_logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <!-- Notification Bell -->
                <div class="notification-bell-container">
                    <div class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count">0</span>
                    </div>
                    <div class="notification-dropdown">
                        <div class="notification-header">
                            <h3>Recent Notifications</h3>
                        </div>
                        <div class="notification-list">
                            <!-- Notifications will be dynamically inserted here -->
                        </div>
                        <div class="notification-footer">
                            <a href="notifications.php">View All Notifications</a>
                        </div>
                    </div>
                </div>
                
                <!-- Rest of top bar content -->
                <div class="top-bar-content">
                    <!-- Add any other top bar content here -->
                </div>
            </div>
            <!-- Stats Cards Section -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-hospital"></i>
                    <div class="stat-info">
                        <h3>Total Hospitals</h3>
                        <p><?php echo $stats['total_hospitals']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-plus"></i>
                    <div class="stat-info">
                        <h3>Total Donors</h3>
                        <p><?php echo $stats['total_donors']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-procedures"></i>
                    <div class="stat-info">
                        <h3>Total Recipients</h3>
                        <p><?php echo $stats['total_recipients']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-info">
                        <h3>Pending Hospitals</h3>
                        <p><?php echo $stats['pending_hospitals']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-info">
                        <h3>Successful Matches</h3>
                        <p><?php echo $stats['successful_matches']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hourglass-half"></i>
                    <div class="stat-info">
                        <h3>Pending Matches</h3>
                        <p><?php echo $stats['pending_matches']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="stat-info">
                        <h3>Urgent Recipients</h3>
                        <p><?php echo $stats['urgent_recipients']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Hospitals Section -->
            <div class="table-container">
                <h2>Pending Hospital Approvals (<?php echo count($pendingHospitals); ?>)</h2>
                <div class="table-responsive">
                    <table class="table" id="pending-hospitals-table">
                        <thead>
                            <tr>
                                <th>Hospital Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Update ODML ID</th>
                                <th>Actions</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingHospitals as $hospital): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hospital['hospital_name']); ?></td>
                                <td><?php echo htmlspecialchars($hospital['email']); ?></td>
                                <td><?php echo htmlspecialchars($hospital['phone']); ?></td>
                                <td>
                                    <button class="update-btn" data-entity-id="<?php echo $hospital['hospital_id']; ?>" data-entity-type="hospital">
                                        <i class="fas fa-edit"></i> Update ODML ID
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <button class="reject-btn reject-hospital-btn" 
                                            data-id="<?php echo $hospital['hospital_id']; ?>"
                                            data-reject-type="hospital"
                                            data-name="<?php echo htmlspecialchars($hospital['hospital_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($hospital['email']); ?>">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                                <td>
                                    <a href="view_hospital_details_in_pending.php?id=<?php echo $hospital['hospital_id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Donors Table -->
            <div class="table-container">
                <h2>Pending Donor Approvals (<?php echo count($pendingDonors); ?>)</h2>
                <div class="table-responsive">
                    <table class="table" id="pending-donors-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Blood Type</th>
                                <th>Update ODML ID</th>
                                <th>Actions</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingDonors as $donor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donor['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($donor['email']); ?></td>
                                <td><?php echo htmlspecialchars($donor['phone']); ?></td>
                                <td><?php echo htmlspecialchars($donor['blood_type']); ?></td>
                                <td>
                                    <button class="update-btn" data-entity-id="<?php echo $donor['donor_id']; ?>" data-entity-type="donor">
                                        <i class="fas fa-edit"></i> Update ODML ID
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <button class="reject-btn reject-donor-btn"
                                            data-id="<?php echo $donor['donor_id']; ?>"
                                            data-reject-type="donor"
                                            data-name="<?php echo htmlspecialchars($donor['full_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($donor['email']); ?>">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                                <td>
                                    <a href="view_donor_details.php?id=<?php echo $donor['donor_id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Recipients Table -->
            <div class="table-container">
                <h2>Pending Recipient Approvals (<?php echo count($pendingRecipients); ?>)</h2>
                <div class="table-responsive">
                    <table class="table" id="pending-recipients-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Blood Type</th>
                                <th>Organ Required</th>
                                <th>Update ODML ID</th>
                                <th>Actions</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRecipients as $recipient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($recipient['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['email']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['blood_type']); ?></td>
                                <td><?php echo htmlspecialchars($recipient['organ_required']); ?></td>
                                <td>
                                    <button class="update-btn" data-entity-id="<?php echo $recipient['recipient_id']; ?>" data-entity-type="recipient">
                                        <i class="fas fa-edit"></i> Update ODML ID
                                    </button>
                                </td>
                                <td class="action-cell">
                                    <button class="reject-btn reject-recipient-btn"
                                            data-id="<?php echo $recipient['recipient_id']; ?>"
                                            data-reject-type="recipient"
                                            data-name="<?php echo htmlspecialchars($recipient['full_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($recipient['email']); ?>">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                                <td>
                                    <a href="view_recipient_details.php?id=<?php echo $recipient['recipient_id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Matches Table -->
            <div class="table-container">
                <h2>Recent Organ Match Activities</h2>
                <div class="table-responsive">
                    <table class="table" id="recent-matches-table">
                        <thead>
                            <tr>
                                <th>Match ID</th>
                                <th>Hospital</th>
                                <th>Donor Name</th>
                                <th>Recipient Name</th>
                                <th>Match Date</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            require_once '../../backend/php/organ_matches.php';
                            $recent_matches = getRecentOrganMatches($conn);
                            
                            if (empty($recent_matches)) {
                                echo '<tr><td colspan="6" class="text-center">No recent matches found</td></tr>';
                            } else {
                                foreach ($recent_matches as $match) {
                                    $match_date = date('M d, Y', strtotime($match['match_date']));
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($match['match_id']); ?></td>
                                        <td><?php echo htmlspecialchars($match['match_made_by_hospital_name']); ?></td>
                                        <td><?php echo htmlspecialchars($match['donor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($match['recipient_name']); ?></td>
                                        <td><?php echo $match_date; ?></td>
                                        <td>
                                            <a href="organ_match_info_for_admin.php?match_id=<?php echo $match['match_id']; ?>" class="view-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Match Details Modal -->
            <div id="matchDetailsModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Match Details</h2>
                    </div>
                    <div class="modal-body" id="matchDetailsContent"></div>
                    <span class="close">&times;</span>
                </div>
            </div>

            <!-- ODML Update Modal -->
            <div id="odmlModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>Update ODML ID</h2>
                    <div class="modal-body">
                        <input type="text" id="odmlIdInput" class="odml-input" placeholder="Enter ODML ID">
                        <div class="checkbox-container">
                            <input type="checkbox" id="approveCheckbox" required>
                            <label for="approveCheckbox" id="approveCheckboxLabel">By updating ODML ID, you are approving this registration</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="cancelOdmlUpdate" class="cancel-btn">Cancel</button>
                        <button id="submitOdmlId" class="update-btn">Update</button>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div id="rejectModal" class="modal">
                <div class="modal-content">
                    <h2>Reject Registration</h2>
                    <div id="rejectEntityDetails"></div>
                    
                    <div class="reject-input-group">
                        <label for="reject_reason">Reason for Rejection</label>
                        <textarea id="reject_reason" class="reject-input" placeholder="Please provide a reason for rejection" rows="4"></textarea>
                    </div>

                    <div class="whatsapp-notice">
                        <i class="fab fa-whatsapp"></i>
                        A WhatsApp notification will be sent with the rejection reason
                    </div>

                    <div class="modal-actions">
                        <button class="modal-btn cancel" onclick="closeRejectModal()">Cancel</button>
                        <button class="modal-btn reject" onclick="confirmReject()">Confirm Rejection</button>
                    </div>
                </div>
            </div>

            <style>
                .reject-input {
                    width: 100%;
                    padding: 10px;
                    border: 2px solid #e0e0e0;
                    border-radius: 4px;
                    font-size: 14px;
                    resize: vertical;
                    min-height: 100px;
                }

                .reject-input:focus {
                    border-color: #dc3545;
                    outline: none;
                    box-shadow: 0 0 5px rgba(220, 53, 69, 0.3);
                }

                .reject-input-group {
                    margin: 20px 0;
                }

                .reject-input-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 500;
                }
            </style>

            <script>
                $(document).ready(function() {
                    // Update ODML ID functionality
                    $('.update-btn').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const entityId = $(this).data('entity-id');
                        const entityType = $(this).data('entity-type');
                        
                        // Update checkbox label based on entity type
                        let checkboxLabel = "By updating ODML ID, you are approving this ";
                        switch(entityType) {
                            case 'hospital':
                                checkboxLabel += "hospital's registration";
                                break;
                            case 'donor':
                                checkboxLabel += "donor's registration";
                                break;
                            case 'recipient':
                                checkboxLabel += "recipient's registration";
                                break;
                        }
                        $('#approveCheckboxLabel').text(checkboxLabel);
                        
                        // Reset form
                        $('#odmlIdInput').val('');
                        $('#approveCheckbox').prop('checked', false);
                        
                        // Show modal
                        $('#odmlModal').css('display', 'block');
                        
                        // Handle ODML ID submission
                        $('#submitOdmlId').off('click').on('click', function() {
                            const odmlId = $('#odmlIdInput').val().trim();
                            
                            if (!odmlId) {
                                alert('Please enter an ODML ID');
                                return;
                            }
                            
                            if (!$('#approveCheckbox').is(':checked')) {
                                alert('Please confirm the approval by checking the checkbox');
                                return;
                            }
                            
                            // Make AJAX call to update ODML ID
                            $.ajax({
                                url: '../../backend/php/update_odml_id.php',
                                method: 'POST',
                                data: {
                                    type: entityType,
                                    id: entityId,
                                    odml_id: odmlId
                                },
                                success: function(response) {
                                    try {
                                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                                        if (result.success) {
                                            if (result.whatsapp && result.whatsapp.success) {
                                                alert('ODML ID updated successfully and WhatsApp notification sent!');
                                            } else {
                                                alert('ODML ID updated successfully but WhatsApp notification failed: ' + 
                                                    (result.whatsapp ? result.whatsapp.message : 'Unknown error'));
                                            }
                                            location.reload();
                                        } else {
                                            alert('Error: ' + (result.message || 'Unknown error'));
                                        }
                                    } catch (e) {
                                        console.error('Error parsing response:', e);
                                        alert('Error updating ODML ID');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX Error:', error);
                                    alert('An error occurred while updating the ODML ID');
                                }
                            });
                            
                            // Close modal
                            $('#odmlModal').css('display', 'none');
                        });
                    });
                    
                    // Handle modal close button
                    $('.close-modal, #cancelOdmlUpdate').on('click', function() {
                        $('#odmlModal').css('display', 'none');
                        $('#odmlIdInput').val('');
                        $('#approveCheckbox').prop('checked', false);
                    });
                    
                    // Close modal when clicking outside
                    $(window).on('click', function(e) {
                        if ($(e.target).is('#odmlModal')) {
                            $('#odmlModal').css('display', 'none');
                            $('#odmlIdInput').val('');
                            $('#approveCheckbox').prop('checked', false);
                        }
                    });
                    
                    // Initialize notification system
                    updateNotifications();
                    
                    // Toggle notification dropdown
                    $('#notificationBell').click(function(e) {
                        e.stopPropagation();
                        $('.notification-dropdown').toggleClass('show');
                    });
                    
                    // Close dropdown when clicking outside
                    $(document).click(function() {
                        $('.notification-dropdown').removeClass('show');
                    });
                    
                    // Update notifications every 30 seconds
                    setInterval(updateNotifications, 30000);
                    
                    // Add event listeners for all dynamic elements
                    $('.update-btn').on('click', function(e) {
                        e.preventDefault();
                        const type = $(this).data('type');
                        const id = $(this).data('id');
                        const name = $(this).data('name');
                        const email = $(this).data('email');
                        openOdmlModal(type, id, name, email);
                    });
                });
                
                function updateNotifications() {
                    $.ajax({
                        url: '../../backend/php/get_recent_notifications.php',
                        method: 'GET',
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                // Update notification count
                                $('#notificationCount').text(data.unread_count);
                                
                                // Clear existing notifications
                                const notificationList = $('#notificationList');
                                notificationList.empty();
                                
                                // Add new notifications
                                data.notifications.forEach(function(notification) {
                                    const notificationItem = $('<div>').addClass('notification-item');
                                    
                                    // Add type badge
                                    const badge = $('<span>')
                                        .addClass('notification-badge')
                                        .addClass('type-' + notification.type)
                                        .text(notification.type.replace('_', ' '));
                                    
                                    // Add message and time
                                    const content = $('<div>').addClass('notification-content');
                                    content.append($('<p>').addClass('notification-message').text(notification.message));
                                    content.append($('<span>').addClass('notification-time').text(notification.time_ago));
                                    
                                    // Add link if available
                                    if (notification.link_url) {
                                        notificationItem.css('cursor', 'pointer');
                                        notificationItem.click(function() {
                                            window.location.href = notification.link_url;
                                        });
                                    }
                                    
                                    notificationItem.append(badge).append(content);
                                    notificationList.append(notificationItem);
                                });
                                
                                // Show "No notifications" message if empty
                                if (data.notifications.length === 0) {
                                    notificationList.append(
                                        $('<div>')
                                            .addClass('no-notifications')
                                            .text('No unread notifications')
                                    );
                                }
                            } catch (e) {
                                console.error('Error parsing notifications:', e);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching notifications:', error);
                        }
                    });
                }

                // Close modal when clicking the close button or outside
                $('.close').click(function() {
                    $('#matchDetailsModal').fadeOut(300);
                });

                $(window).click(function(event) {
                    if ($(event.target).is('#matchDetailsModal')) {
                        $('#matchDetailsModal').fadeOut(300);
                    }
                });

                $(document).ready(function() {
                    // Add event listeners for all dynamic elements
                    $('.update-btn').on('click', function(e) {
                        e.preventDefault();
                        const hospitalId = $(this).data('hospital-id');
                        updateHospitalODMLID(hospitalId);
                    });
                });

                function updateHospitalODMLID(hospitalId) {
                    const odmlId = $(`#odml_id_${hospitalId}`).val();
                    
                    $.ajax({
                        url: '../../backend/php/update_odml_id.php',
                        method: 'POST',
                        data: {
                            type: 'hospital',
                            id: hospitalId,
                            odml_id: odmlId
                        },
                        success: function(response) {
                            try {
                                const data = typeof response === 'string' ? JSON.parse(response) : response;
                                if (data.success) {
                                    alert('ODML ID updated successfully');
                                } else {
                                    alert('Failed to update ODML ID: ' + (data.message || 'Unknown error'));
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                alert('Error updating ODML ID');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert('Error updating ODML ID');
                        }
                    });
                }

                function updateHospitalStatus(hospitalId, status) {
                    if (status.toLowerCase() === 'rejected') {
                        const button = document.querySelector(`button[data-id="${hospitalId}"].reject-hospital-btn`);
                        openRejectionModal('hospital', hospitalId, button.getAttribute('data-name'), button.getAttribute('data-email'));
                        return;
                    }
                    // Rest of the function for approval remains unchanged
                }

                function updateDonorStatus(donorId, status) {
                    if (status.toLowerCase() === 'rejected') {
                        const button = document.querySelector(`button[data-id="${donorId}"].reject-donor-btn`);
                        openRejectionModal('donor', donorId, button.getAttribute('data-name'), button.getAttribute('data-email'));
                        return;
                    }
                    // Rest of the function for approval remains unchanged
                }

                function updateRecipientStatus(recipientId, status) {
                    if (status.toLowerCase() === 'rejected') {
                        const button = document.querySelector(`button[data-id="${recipientId}"].reject-recipient-btn`);
                        openRejectionModal('recipient', recipientId, button.getAttribute('data-name'), button.getAttribute('data-email'));
                        return;
                    }
                    // Rest of the function for approval remains unchanged
                }
                
                function updateButtonHandlers() {
                    // For hospitals
                    document.querySelectorAll('.reject-hospital-btn').forEach(btn => {
                        btn.onclick = function() {
                            const id = this.getAttribute('data-id');
                            const name = this.getAttribute('data-name');
                            const email = this.getAttribute('data-email');
                            openRejectionModal('hospital', id, name, email);
                        };
                    });

                    // For donors
                    document.querySelectorAll('.reject-donor-btn').forEach(btn => {
                        btn.onclick = function() {
                            const id = this.getAttribute('data-id');
                            const name = this.getAttribute('data-name');
                            const email = this.getAttribute('data-email');
                            openRejectionModal('donor', id, name, email);
                        };
                    });

                    // For recipients
                    document.querySelectorAll('.reject-recipient-btn').forEach(btn => {
                        btn.onclick = function() {
                            const id = this.getAttribute('data-id');
                            const name = this.getAttribute('data-name');
                            const email = this.getAttribute('data-email');
                            openRejectionModal('recipient', id, name, email);
                        };
                    });
                }

                // Call updateButtonHandlers when document is ready
                $(document).ready(function() {
                    updateButtonHandlers();
                });
            </script>
            <script src="../../assets/js/notifications.js"></script>
        </body>
    </html>

    <!-- Rejection Modal -->
    <div id="rejectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="header-icon">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <h2>Confirm Rejection</h2>
                <p class="modal-subtitle">Please review the details carefully before proceeding</p>
            </div>
            
            <div class="modal-body">
                <div class="entity-details">
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span id="rejectionEntityName" class="detail-value"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span id="rejectionEntityEmail" class="detail-value"></span>
                    </div>
                </div>
                
                <input type="hidden" id="rejectionEntityType">
                <input type="hidden" id="rejectionEntityId">
                
                <div class="form-group mt-4">
                    <label for="rejectionReason">Reason for Rejection:</label>
                    <textarea id="rejectionReason" class="form-control" rows="3" 
                        placeholder="Please provide a detailed reason for rejection..."></textarea>
                </div>
                
                <div class="confirmation-box mt-3">
                    <label class="checkbox-container">
                        <input type="checkbox" id="rejectionConfirm">
                        <span class="checkbox-text">
                            I understand that by rejecting this registration:
                            <ul>
                                <li>The applicant will not be approved for the LifeLink platform</li>
                                <li>They will receive a WhatsApp message with the rejection reason</li>
                                <li>This action cannot be undone</li>
                            </ul>
                        </span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeRejectionModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">
                    <i class="fas fa-times"></i> Confirm Rejection
                </button>
            </div>
        </div>
    </div>

    <style>
        .modal-header .header-icon {
            font-size: 2em;
            color: #f0ad4e;
            margin-bottom: 15px;
        }
        
        .modal-subtitle {
            color: #666;
            margin-top: 5px;
        }
        
        .entity-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: 600;
            width: 100px;
        }
        
        .detail-value {
            color: #333;
        }
        
        .confirmation-box {
            background: #fff5f5;
            border: 1px solid #ffe0e0;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .confirmation-box ul {
            margin-top: 10px;
            padding-left: 20px;
        }
        
        .confirmation-box li {
            margin-bottom: 5px;
            color: #dc3545;
        }
        
        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        #rejectionReason {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-outline {
            border: 1px solid #ccc;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: #f8f9fa;
        }
    </style>

    <script>
    $(document).ready(function() {
        // Initialize rejection handlers
        $('.reject-btn').on('click', function(e) {
            e.preventDefault();
            console.log('Reject button clicked:', this);
            
            const type = $(this).data('reject-type');
            const id = $(this).data('id');
            const name = $(this).data('name');
            const email = $(this).data('email');
            
            console.log('Opening rejection modal:', { type, id, name, email });
            openRejectionModal(type, id, name, email);
        });

        // Handle rejection confirmation
        $('#confirmReject').on('click', function() {
            const type = $('#rejectionEntityType').val();
            const id = $('#rejectionEntityId').val();
            const reason = $('#rejectionReason').val().trim();
            const confirmed = $('#rejectionConfirm').prop('checked');

            if (!confirmed) {
                alert('Please confirm the rejection by checking the checkbox');
                return;
            }

            if (!reason) {
                alert('Please provide a reason for rejection');
                return;
            }

            // Show loading state
            const $confirmBtn = $(this);
            const originalHtml = $confirmBtn.html();
            $confirmBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            // Send rejection request
            $.ajax({
                url: '../../backend/php/handle_rejection.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    type: type,
                    id: id,
                    reason: reason
                }),
                success: function(response) {
                    if (response.success) {
                        let message = 'Registration rejected successfully.';
                        if (response.whatsappStatus) {
                            message += '\nWhatsApp notification: ' + response.whatsappStatus;
                        }
                        alert(message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                        $confirmBtn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('An error occurred while processing the rejection');
                    $confirmBtn.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });

    function openRejectionModal(type, id, name, email) {
        $('#rejectionEntityType').val(type);
        $('#rejectionEntityId').val(id);
        $('#rejectionEntityName').text(name);
        $('#rejectionEntityEmail').text(email);
        $('#rejectionReason').val('');
        $('#rejectionConfirm').prop('checked', false);
        $('#rejectionModal').css('display', 'block');
    }

    function closeRejectionModal() {
        $('#rejectionModal').css('display', 'none');
        $('#rejectionReason').val('');
        $('#rejectionConfirm').prop('checked', false);
    }
    </script>
