document.addEventListener('DOMContentLoaded', function() {
    // Initialize all charts
    initializeCharts();

    // Handle time range changes
    document.getElementById('timeRange').addEventListener('change', function() {
        updateCharts(this.value);
    });
});

function initializeCharts() {
    // Registration Trends Chart
    const registrationCtx = document.getElementById('registrationChart').getContext('2d');
    new Chart(registrationCtx, {
        type: 'line',
        data: {
            labels: getLastNDays(30),
            datasets: [{
                label: 'Donors',
                data: window.analyticsData.registrations.donors,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4
            }, {
                label: 'Recipients',
                data: window.analyticsData.registrations.recipients,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Daily Registration Trends'
                }
            }
        }
    });

    // Organ Type Distribution Chart
    const organTypeCtx = document.getElementById('organTypeChart').getContext('2d');
    new Chart(organTypeCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(window.analyticsData.organTypes),
            datasets: [{
                data: Object.values(window.analyticsData.organTypes),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });

    // Blood Type Distribution Chart
    const bloodTypeCtx = document.getElementById('bloodTypeChart').getContext('2d');
    new Chart(bloodTypeCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(window.analyticsData.bloodTypes),
            datasets: [{
                label: 'Donors',
                data: window.analyticsData.bloodTypes.map(type => type.donors),
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }, {
                label: 'Recipients',
                data: window.analyticsData.bloodTypes.map(type => type.recipients),
                backgroundColor: 'rgba(255, 99, 132, 0.8)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Success Rate Chart
    const successRateCtx = document.getElementById('successRateChart').getContext('2d');
    new Chart(successRateCtx, {
        type: 'line',
        data: {
            labels: window.analyticsData.successRate.map(item => item.month),
            datasets: [{
                label: 'Success Rate (%)',
                data: window.analyticsData.successRate.map(item => 
                    (item.successful / item.matches * 100).toFixed(1)
                ),
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    // Regional Distribution Chart
    const regionalCtx = document.getElementById('regionalChart').getContext('2d');
    new Chart(regionalCtx, {
        type: 'bar',
        data: {
            labels: window.analyticsData.regional.map(item => item.region),
            datasets: [{
                label: 'Donors',
                data: window.analyticsData.regional.map(item => item.donors),
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }, {
                label: 'Recipients',
                data: window.analyticsData.regional.map(item => item.recipients),
                backgroundColor: 'rgba(255, 99, 132, 0.8)'
            }, {
                label: 'Successful Matches',
                data: window.analyticsData.regional.map(item => item.successful_matches),
                backgroundColor: 'rgba(75, 192, 192, 0.8)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

async function updateCharts(days) {
    try {
        const response = await fetch(`../../backend/php/get_analytics_data.php?days=${days}`);
        const data = await response.json();
        
        if (data.success) {
            window.analyticsData = data.data;
            // Destroy existing charts
            Chart.helpers.each(Chart.instances, (instance) => {
                instance.destroy();
            });
            // Reinitialize charts with new data
            initializeCharts();
        }
    } catch (error) {
        console.error('Error updating charts:', error);
    }
}

function getLastNDays(n) {
    const dates = [];
    for (let i = n - 1; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        dates.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
    }
    return dates;
}
