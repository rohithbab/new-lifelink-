// Function to load pending donor approvals
function loadPendingDonorApprovals() {
    fetch('../../backend/php/get_pending_donor_approvals.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#pendingDonorTable tbody');
            tbody.innerHTML = '';

            data.forEach(donor => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${donor.name}</td>
                    <td>${donor.organs_to_donate}</td>
                    <td>${donor.blood_group}</td>
                    <td>${donor.address}</td>
                    <td>${new Date(donor.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-approve" onclick="approveDonor(${donor.donor_id})">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-reject" onclick="rejectDonor(${donor.donor_id})">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error loading pending donors:', error));
}

// Function to load pending recipient approvals
function loadPendingRecipientApprovals() {
    fetch('../../backend/php/get_pending_recipient_approvals.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#pendingRecipientTable tbody');
            tbody.innerHTML = '';

            data.forEach(recipient => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${recipient.full_name}</td>
                    <td>${recipient.organ_required}</td>
                    <td>${recipient.blood_type}</td>
                    <td>${recipient.urgency_level}</td>
                    <td>${recipient.address}</td>
                    <td>${new Date(recipient.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-approve" onclick="approveRecipient(${recipient.id})">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-reject" onclick="rejectRecipient(${recipient.id})">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error loading pending recipients:', error));
}

// Function to approve donor
function approveDonor(donorId) {
    if (confirm('Are you sure you want to approve this donor?')) {
        fetch('../../backend/php/approve_donor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ donor_id: donorId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Donor approved successfully!');
                loadPendingDonorApprovals();
                updateDashboardMetrics();
            } else {
                alert('Error approving donor: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Function to reject donor
function rejectDonor(donorId) {
    const reason = prompt('Please enter the reason for rejection:');
    if (reason) {
        fetch('../../backend/php/reject_donor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                donor_id: donorId,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Donor rejected successfully!');
                loadPendingDonorApprovals();
                updateDashboardMetrics();
            } else {
                alert('Error rejecting donor: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Function to approve recipient
function approveRecipient(recipientId) {
    if (confirm('Are you sure you want to approve this recipient?')) {
        fetch('../../backend/php/approve_recipient.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ recipient_id: recipientId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Recipient approved successfully!');
                loadPendingRecipientApprovals();
                updateDashboardMetrics();
            } else {
                alert('Error approving recipient: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Function to reject recipient
function rejectRecipient(recipientId) {
    const reason = prompt('Please enter the reason for rejection:');
    if (reason) {
        fetch('../../backend/php/reject_recipient.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                recipient_id: recipientId,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Recipient rejected successfully!');
                loadPendingRecipientApprovals();
                updateDashboardMetrics();
            } else {
                alert('Error rejecting recipient: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Function to update dashboard metrics
function updateDashboardMetrics() {
    fetch('../../backend/php/get_hospital_metrics.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalDonors').textContent = data.total_donors;
            document.getElementById('totalRecipients').textContent = data.total_recipients;
            document.getElementById('pendingRequests').textContent = data.pending_requests;
            document.getElementById('approvedMatches').textContent = data.approved_matches;
        })
        .catch(error => console.error('Error updating metrics:', error));
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadPendingDonorApprovals();
    loadPendingRecipientApprovals();
    updateDashboardMetrics();
});

// Refresh data every 5 minutes
setInterval(() => {
    loadPendingDonorApprovals();
    loadPendingRecipientApprovals();
    updateDashboardMetrics();
}, 300000);
