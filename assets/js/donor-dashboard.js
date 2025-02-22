$(document).ready(function() {
    // Navigation
    $('#dashboardLink').click(function(e) {
        e.preventDefault();
        showSection('dashboardSection');
    });

    $('#searchHospitalLink').click(function(e) {
        e.preventDefault();
        showSection('searchHospitalSection');
    });

    $('#myRequestsLink').click(function(e) {
        e.preventDefault();
        showSection('myRequestsSection');
        loadMyRequests();
    });

    $('#notificationsLink').click(function(e) {
        e.preventDefault();
        showSection('notificationsSection');
        loadNotifications();
    });

    // Real-time hospital search
    let searchTimeout;
    $('#hospitalSearch').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        
        searchTimeout = setTimeout(function() {
            if (searchTerm.length >= 2) {
                searchHospitals(searchTerm);
            } else {
                $('#searchResults').empty();
            }
        }, 300);
    });

    // Modal handling
    $('.close').click(function() {
        $(this).closest('.modal').hide();
    });

    $(window).click(function(e) {
        if ($(e.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // Send Request button in hospital modal
    $('#sendRequestBtn').click(function() {
        $('#hospitalModal').hide();
        $('#requestModal').show();
    });

    // Form submission
    $('#donationRequestForm').submit(function(e) {
        e.preventDefault();
        submitDonationRequest();
    });

    // Initial load
    loadNotificationCount();
    showSection('dashboardSection'); // Show dashboard by default
    
    // Periodic updates
    setInterval(loadNotificationCount, 30000);
    setInterval(loadDashboardData, 30000);
});

function showSection(sectionId) {
    // Hide all sections
    $('.section').addClass('hidden');
    // Show selected section
    $(`#${sectionId}`).removeClass('hidden');
    
    // Remove active class from all nav items
    $('.sidebar-nav li').removeClass('active');
    
    // Add active class to current nav item
    let linkId;
    switch(sectionId) {
        case 'dashboardSection':
            linkId = 'dashboardLink';
            loadDashboardData();
            break;
        case 'searchHospitalSection':
            linkId = 'searchHospitalLink';
            break;
        case 'myRequestsSection':
            linkId = 'myRequestsLink';
            loadMyRequests();
            break;
        case 'notificationsSection':
            linkId = 'notificationsLink';
            loadNotifications();
            break;
    }
    
    if (linkId) {
        $(`#${linkId}`).parent('li').addClass('active');
    }
}

function searchHospitals(searchTerm) {
    $.ajax({
        url: '../../backend/php/search_hospitals.php',
        method: 'POST',
        data: { search: searchTerm },
        success: function(response) {
            const hospitals = JSON.parse(response);
            displayHospitalResults(hospitals);
        },
        error: function(xhr, status, error) {
            console.error('Error searching hospitals:', error);
            $('#searchResults').html('<p class="error-message">Error searching hospitals. Please try again.</p>');
        }
    });
}

function displayHospitalResults(hospitals) {
    const resultsContainer = $('#searchResults');
    resultsContainer.empty();

    if (hospitals.length === 0) {
        resultsContainer.html('<p class="no-results">No hospitals found matching your search.</p>');
        return;
    }

    hospitals.forEach(hospital => {
        const card = `
            <div class="hospital-card">
                <h3>${hospital.name}</h3>
                <div class="hospital-info">
                    <p><i class="fas fa-map-marker-alt"></i> ${hospital.address}</p>
                    <p><i class="fas fa-phone"></i> ${hospital.phone}</p>
                </div>
                <div class="hospital-actions">
                    <button onclick="viewHospitalDetails(${hospital.id})" class="btn-primary">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                </div>
            </div>
        `;
        resultsContainer.append(card);
    });
}

function viewHospitalDetails(hospitalId) {
    $.ajax({
        url: '../../backend/php/get_hospital_details.php',
        method: 'POST',
        data: { hospital_id: hospitalId },
        success: function(response) {
            const hospital = JSON.parse(response);
            $('#hospitalDetails').html(`
                <div class="hospital-detail-info">
                    <h3>${hospital.name}</h3>
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> ${hospital.address}</p>
                    <p><i class="fas fa-phone"></i> <strong>Contact:</strong> ${hospital.phone}</p>
                    <p><i class="fas fa-envelope"></i> <strong>Email:</strong> ${hospital.email}</p>
                </div>
            `);
            $('#selectedHospitalId').val(hospitalId);
            $('#hospitalModal').show();
        },
        error: function(xhr, status, error) {
            console.error('Error fetching hospital details:', error);
            alert('Error loading hospital details. Please try again.');
        }
    });
}

function submitDonationRequest() {
    const hospitalId = $('#selectedHospitalId').val();
    const organType = $('#organType').val();

    if (!organType) {
        alert('Please select an organ type');
        return;
    }

    $.ajax({
        url: '../../backend/php/submit_donation_request.php',
        method: 'POST',
        data: {
            hospital_id: hospitalId,
            organ_type: organType
        },
        success: function(response) {
            const result = JSON.parse(response);
            if (result.success) {
                alert(result.message);
                $('#requestModal').hide();
                $('#donationRequestForm')[0].reset();
                loadMyRequests(); // Refresh the requests list
            } else {
                alert('Error: ' + (result.error || 'Failed to submit request'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error submitting request:', error);
            alert('An error occurred while submitting your request. Please try again.');
        }
    });
}

function loadMyRequests() {
    $.ajax({
        url: '../../backend/php/get_donor_requests.php',
        method: 'GET',
        success: function(response) {
            const requests = JSON.parse(response);
            displayRequests(requests);
        },
        error: function(xhr, status, error) {
            console.error('Error loading requests:', error);
            $('#requestsList').html('<p class="error-message">Error loading your requests. Please try again.</p>');
        }
    });
}

function displayRequests(requests) {
    const container = $('#requestsList');
    container.empty();

    if (requests.length === 0) {
        container.html('<p class="no-data">You haven\'t made any donation requests yet.</p>');
        return;
    }

    const table = `
        <table class="requests-table">
            <thead>
                <tr>
                    <th>Hospital</th>
                    <th>Organ Type</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Feedback</th>
                </tr>
            </thead>
            <tbody>
                ${requests.map(request => `
                    <tr class="status-${request.status.toLowerCase()}">
                        <td>${request.hospital_name}</td>
                        <td>${request.organ_type}</td>
                        <td>${new Date(request.request_date).toLocaleDateString()}</td>
                        <td><span class="status-badge ${request.status.toLowerCase()}">${request.status}</span></td>
                        <td>${request.rejection_reason || '-'}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    container.html(table);
}

function loadNotifications() {
    $.ajax({
        url: '../../backend/php/get_donor_notifications.php',
        method: 'GET',
        success: function(response) {
            const notifications = JSON.parse(response);
            displayNotifications(notifications);
        },
        error: function(xhr, status, error) {
            console.error('Error loading notifications:', error);
            $('#notificationsList').html('<p class="error-message">Error loading notifications. Please try again.</p>');
        }
    });
}

function displayNotifications(notifications) {
    const container = $('#notificationsList');
    container.empty();

    if (notifications.length === 0) {
        container.html('<p class="no-data">No notifications found.</p>');
        return;
    }

    notifications.forEach(notification => {
        const notificationCard = `
            <div class="notification-card ${notification.read_status ? 'read' : 'unread'}">
                <div class="notification-icon">
                    <i class="fas ${getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <p class="notification-message">${notification.message}</p>
                    <span class="notification-time">${new Date(notification.created_at).toLocaleString()}</span>
                </div>
            </div>
        `;
        container.append(notificationCard);
    });
}

function getNotificationIcon(type) {
    switch (type) {
        case 'request_approved':
            return 'fa-check-circle';
        case 'request_rejected':
            return 'fa-times-circle';
        case 'request_update':
            return 'fa-info-circle';
        default:
            return 'fa-bell';
    }
}

function loadNotificationCount() {
    $.ajax({
        url: '../../backend/php/get_unread_notification_count.php',
        method: 'GET',
        success: function(response) {
            const result = JSON.parse(response);
            const count = result.count || 0;
            $('#notificationCount, #headerNotificationCount').text(count > 0 ? count : '');
            $('#notificationCount, #headerNotificationCount').toggle(count > 0);
        },
        error: function(xhr, status, error) {
            console.error('Error loading notification count:', error);
        }
    });
}

// Dashboard functions
function loadDashboardData() {
    $.ajax({
        url: '../../backend/php/get_dashboard_data.php',
        method: 'GET',
        success: function(response) {
            updateStatistics(response.statistics);
            updateSuggestion(response.suggestion);
            updateRecentActivities(response.recent_activities);
        },
        error: function(xhr, status, error) {
            console.error('Error loading dashboard data:', error);
        }
    });
}

function updateStatistics(stats) {
    // Update numbers with animation
    animateNumber('totalRequests', stats.total);
    animateNumber('pendingRequests', stats.pending);
    animateNumber('approvedRequests', stats.approved);
    animateNumber('livesImpacted', stats.completed);

    // Update tooltip content
    $('#completedCount').text(stats.completed);
    $('#inProgressCount').text(stats.approved - stats.completed);

    // Trigger impact animation if there are completed requests
    if (stats.completed > 0) {
        $('.impact-animation').addClass('active');
    }
}

function animateNumber(elementId, target) {
    const element = document.getElementById(elementId);
    const current = parseInt(element.textContent);
    const increment = (target - current) / 20;
    let value = current;

    const animate = () => {
        if (Math.abs(target - value) > Math.abs(increment)) {
            value += increment;
            element.textContent = Math.round(value);
            requestAnimationFrame(animate);
        } else {
            element.textContent = target;
        }
    };

    animate();
}

function updateSuggestion(suggestion) {
    $('#suggestionText').text(suggestion.message);
    $('#suggestionAction').text(suggestion.action);

    // Update action button click handler
    $('#suggestionAction').off('click').on('click', function() {
        switch(suggestion.action_type) {
            case 'search':
                showSection('searchHospitalSection');
                break;
            case 'requests':
                showSection('myRequestsSection');
                break;
        }
    });
}

function updateRecentActivities(activities) {
    const container = $('#activityTimeline');
    container.empty();

    if (activities.length === 0) {
        container.html('<p class="no-data">No recent activities found.</p>');
        return;
    }

    activities.forEach(activity => {
        const timelineItem = `
            <div class="timeline-item ${activity.status.toLowerCase()}">
                <div class="timeline-date">
                    ${new Date(activity.request_date).toLocaleDateString()}
                </div>
                <div class="timeline-content">
                    <strong>${activity.hospital_name}</strong>
                    <span class="timeline-status ${activity.status.toLowerCase()}">
                        ${activity.status}
                    </span>
                    <p>Organ Type: ${activity.organ_type}</p>
                </div>
            </div>
        `;
        container.append(timelineItem);
    });
}
