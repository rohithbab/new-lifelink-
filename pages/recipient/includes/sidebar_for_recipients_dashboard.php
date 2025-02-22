<?php
// Get the current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-heartbeat"></i>
        <span>LifeLink</span>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="recipient_dashboard.php" class="<?php echo $current_page == 'recipient_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="recipient_personal_details.php" class="<?php echo $current_page == 'recipient_personal_details.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li>
                <a href="search_hospitals_for_recipient.php" class="<?php echo $current_page == 'search_hospitals_for_recipient.php' ? 'active' : ''; ?>">
                    <i class="fas fa-hospital"></i>
                    <span>Search Hospitals</span>
                </a>
            </li>
            <li>
                <a href="my_requests_for_recipients.php" class="<?php echo $current_page == 'my_requests_for_recipients.php' ? 'active' : ''; ?>">
                    <i class="fas fa-notes-medical"></i>
                    <span>My Requests</span>
                </a>
            </li>
            <li>
                <a href="recipients_notifications.php" class="<?php echo $current_page == 'recipients_notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            <li>
                <a href="../recipient_login.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
