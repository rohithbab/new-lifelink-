<nav class="sidebar">
    <div class="sidebar-header">
        <img src="../../assets/images/logo.png" alt="LifeLink Logo" class="logo">
        <h2>LifeLink</h2>
    </div>
    
    <ul class="nav-links">
        <li>
            <a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="manage_hospitals.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_hospitals.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital"></i>
                <span>Manage Hospitals</span>
            </a>
        </li>
        <li>
            <a href="manage_donors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_donors.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i>
                <span>Manage Donors</span>
            </a>
        </li>
        <li>
            <a href="manage_recipients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_recipients.php' ? 'active' : ''; ?>">
                <i class="fas fa-procedures"></i>
                <span>Manage Recipients</span>
            </a>
        </li>
        <li>
            <a href="organ_match_info_for_admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organ_match_info_for_admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-handshake-angle"></i>
                <span>Organ Matches</span>
            </a>
        </li>
        <li>
            <a href="analytics.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
        </li>
        <li>
            <a href="notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>
