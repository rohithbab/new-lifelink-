document.addEventListener('DOMContentLoaded', function() {
    // Handle hospital approval/rejection
    const approveButtons = document.querySelectorAll('.approve-btn');
    const rejectButtons = document.querySelectorAll('.reject-btn');

    approveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const hospitalId = this.dataset.id;
            handleHospitalStatus(hospitalId, 'approve');
        });
    });

    rejectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const hospitalId = this.dataset.id;
            handleHospitalStatus(hospitalId, 'reject');
        });
    });

    // Function to handle hospital status changes
    async function handleHospitalStatus(hospitalId, action) {
        try {
            const response = await fetch('../../backend/php/update_hospital_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    hospital_id: hospitalId,
                    action: action
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Remove the hospital card from the pending list
                const hospitalCard = document.querySelector(`[data-id="${hospitalId}"]`).closest('.pending-item');
                hospitalCard.remove();
                
                // Update the pending approvals count
                const pendingCount = document.querySelector('.pending-approvals .stat-number');
                if (pendingCount) {
                    pendingCount.textContent = parseInt(pendingCount.textContent) - 1;
                }

                // Show success message
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            showNotification('An error occurred. Please try again.', 'error');
            console.error('Error:', error);
        }
    }

    // Notification function
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Real-time updates for urgent cases
    function setupUrgentCasesWebSocket() {
        const ws = new WebSocket('ws://localhost:8080');
        
        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.type === 'urgent_case') {
                updateUrgentCasesList(data.case);
            }
        };

        ws.onerror = function(error) {
            console.error('WebSocket Error:', error);
        };
    }

    // Update urgent cases list
    function updateUrgentCasesList(newCase) {
        const urgentList = document.querySelector('.urgent-list');
        if (!urgentList) return;

        const caseElement = document.createElement('div');
        caseElement.className = 'urgent-item';
        caseElement.innerHTML = `
            <h4>${newCase.name}</h4>
            <p>Needed Organ: ${newCase.needed_organ}</p>
            <p>Blood Type: ${newCase.blood_type}</p>
        `;

        urgentList.insertBefore(caseElement, urgentList.firstChild);

        // Remove oldest case if list is too long
        const cases = urgentList.querySelectorAll('.urgent-item');
        if (cases.length > 10) {
            cases[cases.length - 1].remove();
        }
    }

    // Initialize WebSocket for real-time updates
    // Commented out for now - uncomment when WebSocket server is ready
    // setupUrgentCasesWebSocket();

    // Initialize any charts if they exist on the page
    const analyticsCharts = document.querySelectorAll('.analytics-chart');
    if (analyticsCharts.length > 0) {
        initializeCharts();
    }

    // Function to initialize charts
    function initializeCharts() {
        // Example chart initialization - modify based on your needs
        const ctx = document.getElementById('donorsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'New Donors',
                        data: [12, 19, 3, 5, 2, 3],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
});