let notificationSound;

document.addEventListener('DOMContentLoaded', function() {
    notificationSound = new Audio('../assets/sounds/notification.mp3');
    notificationSound.volume = 0.5; // Set volume to 50%
    
    // Initialize notifications
    updateNotifications();
    
    // Check for new notifications every 30 seconds
    setInterval(updateNotifications, 30000);
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const bell = document.querySelector('.notification-bell-container');
        const dropdown = document.querySelector('.notification-dropdown');
        
        if (!bell.contains(event.target) && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    });

    // Add click handler to bell icon
    document.querySelector('#notificationBell').addEventListener('click', function(e) {
        e.stopPropagation();
        toggleNotifications();
    });
});

function toggleNotifications() {
    const dropdown = document.querySelector('.notification-dropdown');
    dropdown.classList.toggle('show');
}

function updateNotifications() {
    fetch('../../backend/php/get_recent_notifications.php')
        .then(response => response.json())
        .then(data => {
            updateNotificationUI(data.notifications, data.unread_count);
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

function updateNotificationUI(notifications, unreadCount) {
    const container = document.querySelector('.notification-dropdown');
    const countBadge = document.querySelector('.notification-count');
    const notificationsList = container.querySelector('.notification-list');
    
    // Update count badge
    if (unreadCount > 0) {
        countBadge.style.display = 'block';
        countBadge.textContent = unreadCount;
    } else {
        countBadge.style.display = 'none';
    }
    
    // Clear existing notifications
    notificationsList.innerHTML = '';
    
    // Add new notifications
    notifications.forEach(notification => {
        const notificationItem = document.createElement('div');
        notificationItem.className = 'notification-item';
        notificationItem.onclick = () => handleNotificationClick(notification);
        
        const badge = document.createElement('span');
        badge.className = `notification-badge ${notification.type}`;
        badge.textContent = notification.type.replace('_', ' ');
        
        const message = document.createElement('div');
        message.className = 'notification-message';
        message.textContent = notification.message;
        
        const time = document.createElement('span');
        time.className = 'notification-time';
        time.textContent = formatTimeAgo(new Date(notification.created_at));
        
        notificationItem.appendChild(badge);
        notificationItem.appendChild(message);
        notificationItem.appendChild(time);
        notificationsList.appendChild(notificationItem);
    });
}

function handleNotificationClick(notification) {
    // Mark notification as read
    fetch('../../backend/php/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            notification_id: notification.notification_id
        })
    })
    .then(() => {
        // Redirect to the notification link
        if (notification.link_url) {
            window.location.href = notification.link_url;
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

function formatTimeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h ago`;
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) return `${diffInDays}d ago`;
    
    return date.toLocaleDateString();
}

function playNotificationSound() {
    notificationSound.play().catch(error => {
        console.log('Error playing notification sound:', error);
    });
}
