<?php
// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../pages/hospital_login.php");
    exit();
}

// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2 class="logo-text">LifeLink</h2>
        <div class="sub-text">HospitalHub</div>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="hospital_dashboard.php" <?php echo $current_page == 'hospital_dashboard.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="hospitals_handles_donors_status.php" <?php echo $current_page == 'hospitals_handles_donors_status.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-users"></i>
                    <span>Manage Donors</span>
                </a>
            </li>
            <li>
                <a href="hospitals_handles_recipients_status.php" <?php echo $current_page == 'hospitals_handles_recipients_status.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-procedures"></i>
                    <span>Manage Recipients</span>
                </a>
            </li>
            <li>
                <a href="make_matches.php" <?php echo $current_page == 'make_matches.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-link"></i>
                    <span>Make Matches</span>
                </a>
            </li>
            <li>
                <a href="my_matches.php" <?php echo $current_page == 'my_matches.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-handshake"></i>
                    <span>My Matches</span>
                </a>
            </li>
            <!-- New Request Management Section -->
            <li class="has-submenu">
                <a href="#" class="submenu-toggle <?php echo in_array($current_page, ['donor_requests.php', 'recipient_requests.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Requests</span>
                    <i class="fas fa-chevron-down submenu-icon"></i>
                </a>
                <ul class="submenu">
                    <li class="submenu-category">
                        <span>Donor Requests</span>
                        <ul>
                            <li>
                                <a href="donor_requests.php?type=incoming" <?php echo $current_page == 'donor_requests.php' && $_GET['type'] == 'incoming' ? 'class="active"' : ''; ?>>
                                    <i class="fas fa-inbox"></i>
                                    <span>Incoming Requests</span>
                                </a>
                            </li>
                            <li>
                                <a href="donor_requests.php?type=outgoing" <?php echo $current_page == 'donor_requests.php' && $_GET['type'] == 'outgoing' ? 'class="active"' : ''; ?>>
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Outgoing Requests</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="submenu-category">
                        <span>Recipient Requests</span>
                        <ul>
                            <li>
                                <a href="recipient_requests.php?type=incoming" <?php echo $current_page == 'recipient_requests.php' && $_GET['type'] == 'incoming' ? 'class="active"' : ''; ?>>
                                    <i class="fas fa-inbox"></i>
                                    <span>Incoming Requests</span>
                                </a>
                            </li>
                            <li>
                                <a href="recipient_requests.php?type=outgoing" <?php echo $current_page == 'recipient_requests.php' && $_GET['type'] == 'outgoing' ? 'class="active"' : ''; ?>>
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Outgoing Requests</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li>
                <a href="notifications.php" <?php echo $current_page == 'notifications.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
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
</aside>

<style>
.has-submenu > a {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.submenu-icon {
    transition: transform 0.3s ease;
}

.has-submenu.active > a .submenu-icon {
    transform: rotate(180deg);
}

.submenu {
    display: none;
    padding-left: 20px;
    background: rgba(0, 0, 0, 0.05);
    border-left: 3px solid var(--primary-blue);
}

.has-submenu.active .submenu {
    display: block;
}

.submenu-category {
    margin: 10px 0;
}

.submenu-category > span {
    display: block;
    padding: 5px 10px;
    font-weight: bold;
    color: var(--primary-blue);
}

.submenu a {
    padding: 5px 15px !important;
    font-size: 0.9em;
}

.submenu i {
    font-size: 0.8em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle submenu
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const menuItem = toggle.closest('.has-submenu');
            menuItem.classList.toggle('active');
        });
    });

    // Auto-expand if submenu has active item
    const activeSubmenuItems = document.querySelectorAll('.submenu .active');
    activeSubmenuItems.forEach(item => {
        const parentMenu = item.closest('.has-submenu');
        if (parentMenu) {
            parentMenu.classList.add('active');
        }
    });
});
</script>
