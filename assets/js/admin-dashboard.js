// Function to update dashboard statistics
function updateDashboardStats() {
    fetch('../../backend/php/admin_ajax.php?action=get_stats')
        .then(response => response.json())
        .then(stats => {
            // Update all stat cards
            document.querySelector('[data-stat="total_hospitals"]').textContent = stats.total_hospitals;
            document.querySelector('[data-stat="total_donors"]').textContent = stats.total_donors;
            document.querySelector('[data-stat="total_recipients"]').textContent = stats.total_recipients;
            document.querySelector('[data-stat="pending_hospitals"]').textContent = stats.pending_hospitals;
            document.querySelector('[data-stat="successful_matches"]').textContent = stats.successful_matches;
            document.querySelector('[data-stat="pending_matches"]').textContent = stats.pending_matches;
            document.querySelector('[data-stat="urgent_matches"]').textContent = stats.urgent_matches;
        })
        .catch(error => console.error('Error updating stats:', error));
}

// Function to update pending hospitals table
function updatePendingHospitals() {
    fetch('../../backend/php/admin_ajax.php?action=get_pending_hospitals')
        .then(response => response.json())
        .then(hospitals => {
            const tbody = document.querySelector('#pending-hospitals-table tbody');
            if (!tbody) return;
            tbody.innerHTML = '';

            hospitals.forEach(hospital => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${hospital.hospital_name || ''}</td>
                    <td>${hospital.email || ''}</td>
                    <td>${hospital.registration_date || ''}</td>
                    <td>
                        <button class="btn-action btn-approve" onclick="updateHospitalStatus(${hospital.id}, 'approved')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn-action btn-reject" onclick="updateHospitalStatus(${hospital.id}, 'rejected')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            // Update pending count in header
            const pendingCount = hospitals.length;
            const countElement = document.querySelector('#pending-hospitals-count');
            if (countElement) {
                countElement.textContent = pendingCount;
            }
        })
        .catch(error => console.error('Error updating pending hospitals:', error));
}

// Function to update pending donors table
function updatePendingDonors() {
    console.log('Fetching pending donors...');
    fetch('../../backend/php/admin_ajax.php?action=get_pending_donors')
        .then(response => response.json())
        .then(donors => {
            console.log('Received donors:', donors);
            const tbody = document.querySelector('#pending-donors-table tbody');
            if (!tbody) {
                console.error('Donors table body not found!');
                return;
            }
            tbody.innerHTML = '';

            donors.forEach(donor => {
                console.log('Processing donor:', donor);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${donor.name || ''}</td>
                    <td>${donor.email || ''}</td>
                    <td>${donor.blood_type || ''}</td>
                    <td>${donor.organ_type || ''}</td>
                    <td>${donor.registration_date || ''}</td>
                    <td>
                        <button class="btn-action btn-approve" onclick="updateDonorStatus(${donor.id}, 'approved')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn-action btn-reject" onclick="updateDonorStatus(${donor.id}, 'rejected')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            const pendingCount = donors.length;
            const countElement = document.querySelector('#pending-donors-count');
            if (countElement) {
                countElement.textContent = pendingCount;
            }
        })
        .catch(error => console.error('Error updating pending donors:', error));
}

// Function to update pending recipients table
function updatePendingRecipients() {
    console.log('Fetching pending recipients...');
    fetch('../../backend/php/admin_ajax.php?action=get_pending_recipients')
        .then(response => response.json())
        .then(recipients => {
            console.log('Received recipients:', recipients);
            const tbody = document.querySelector('#pending-recipients-table tbody');
            if (!tbody) {
                console.error('Recipients table body not found!');
                return;
            }
            tbody.innerHTML = '';

            recipients.forEach(recipient => {
                console.log('Processing recipient:', recipient);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(recipient.name) || ''}</td>
                    <td>${escapeHtml(recipient.email) || ''}</td>
                    <td>${escapeHtml(recipient.blood_type) || ''}</td>
                    <td>${escapeHtml(recipient.needed_organ) || ''}</td>
                    <td>
                        <span class="urgency-badge urgency-${(recipient.urgency || 'normal').toLowerCase()}">
                            ${recipient.urgency || 'Normal'}
                        </span>
                    </td>
                    <td>${recipient.registration_date || ''}</td>
                    <td>
                        <button class="btn-action btn-approve" onclick="updateRecipientStatus(${recipient.id}, 'accepted')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn-action btn-reject" onclick="updateRecipientStatus(${recipient.id}, 'rejected')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            const pendingCount = recipients.length;
            const countElement = document.querySelector('#pending-recipients-count');
            if (countElement) {
                countElement.textContent = pendingCount;
            }
        })
        .catch(error => console.error('Error updating pending recipients:', error));
}

// Function to update notifications
function updateNotifications() {
    fetch('../../backend/php/admin_ajax.php?action=get_notifications')
        .then(response => response.json())
        .then(notifications => {
            const container = document.querySelector('#notifications-container');
            container.innerHTML = ''; // Clear existing notifications

            notifications.forEach(notification => {
                const notificationElement = document.createElement('div');
                notificationElement.className = 'notification-item';
                notificationElement.innerHTML = `
                    <div class="notification-content">
                        <span class="notification-type">${notification.type}</span>
                        <p>${notification.message}</p>
                        <small>${notification.created_at}</small>
                    </div>
                `;
                container.appendChild(notificationElement);
            });
        })
        .catch(error => console.error('Error updating notifications:', error));
}

// Function to update all dashboard data
function updateAllDashboardData() {
    updateDashboardStats();
    updatePendingHospitals();
    updatePendingDonors();
    updatePendingRecipients();
    updateNotifications();
}

// Function to update hospital status
function updateHospitalStatus(hospitalId, status) {
    const formData = new FormData();
    formData.append('hospital_id', hospitalId);
    formData.append('status', status);

    fetch('../../backend/php/admin_ajax.php?action=update_hospital_status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            updateAllDashboardData(); // Update everything immediately
            showNotification('Hospital status updated successfully', 'success');
        } else {
            showNotification('Failed to update hospital status', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating hospital status:', error);
        showNotification('Error updating hospital status', 'error');
    });
}

// Function to update donor status
function updateDonorStatus(donorId, status) {
    console.log('Updating donor status:', donorId, status);
    const formData = new FormData();
    formData.append('donor_id', donorId);
    formData.append('status', status);

    fetch('../../backend/php/admin_ajax.php?action=update_donor_status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(`Donor ${status} successfully`, 'success');
            // Update both the pending donors table and the total stats
            updatePendingDonors();
            updateDashboardStats();
        } else {
            showNotification('Error updating donor status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating donor status', 'error');
    });
}

// Function to update recipient status
function updateRecipientStatus(recipientId, status) {
    console.log('Updating recipient status:', recipientId, status);
    
    const formData = new FormData();
    formData.append('recipient_id', recipientId);
    formData.append('status', status);

    fetch('../../backend/php/admin_ajax.php?action=update_recipient_status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(`Recipient ${status} successfully`, 'success');
            // Update both the pending recipients table and the total stats
            updatePendingRecipients();
            updateDashboardStats();
        } else {
            showNotification('Error updating recipient status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating recipient status', 'error');
    });
}

// Function to show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Update all tables every 30 seconds
setInterval(() => {
    updateDashboardStats();
    updatePendingHospitals();
    updatePendingDonors();
    updatePendingRecipients();
    updateNotifications();
}, 30000);

// Initial update when page loads
document.addEventListener('DOMContentLoaded', () => {
    updateDashboardStats();
    updatePendingHospitals();
    updatePendingDonors();
    updatePendingRecipients();
    updateNotifications();
});
