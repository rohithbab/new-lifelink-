<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header("Location: ../../pages/hospital_login.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];

// Fetch hospital's approved recipients
try {
    $stmt = $conn->prepare("
        SELECT 
            r.id as recipient_id,
            r.full_name,
            r.blood_type,
            r.medical_condition,
            r.urgency_level,
            r.phone_number,
            r.email,
            hra.organ_required,
            h.name as from_hospital
        FROM recipient_registration r
        JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id
        LEFT JOIN hospitals h ON hra.hospital_id = h.hospital_id
        WHERE (hra.hospital_id = ? OR 
            EXISTS (
                SELECT 1 FROM recipient_requests rr 
                WHERE rr.recipient_id = r.id 
                AND rr.requesting_hospital_id = ? 
                AND rr.status = 'approved'
            )
        )
        AND hra.status = 'approved'
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE recipient_id = r.id
        )
        ORDER BY r.urgency_level DESC, r.full_name ASC
    ");
    
    $stmt->execute([$hospital_id, $hospital_id]);
    $hospital_recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error fetching recipients: " . $e->getMessage());
    $hospital_recipients = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Recipients - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .search-container {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #eee;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-blue);
            outline: none;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #eee;
            color: #333;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
        }

        .switch-list-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        /* Table Styles */
        .recipients-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .recipients-table th,
        .recipients-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .recipients-table th {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            font-weight: 500;
        }

        .recipients-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .select-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .select-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .empty-state h2 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        #searchResults {
            margin-top: 2rem;
        }

        .section-title {
            margin: 2rem 0 1rem;
            color: #333;
            font-size: 1.5rem;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .status-approved {
            background-color: #28a745;
            color: white;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }

        .status-rejected {
            background-color: #dc3545;
            color: white;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        /* Button Styles */
        .btn-request, .btn-cancel {
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            min-width: 120px;
            font-size: 0.9rem;
        }

        .btn-request {
            background-color: #007bff;
            color: white;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .btn-request:hover {
            background-color: #0056b3;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
            transform: translateY(-1px);
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        .btn-cancel:hover {
            background-color: #c82333;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            transform: translateY(-1px);
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background-color: #28a745;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
        }

        .alert-error {
            background-color: #dc3545;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Choose Recipients</h1>
                </div>
                <div class="header-right">
                    <a href="choose_donors_for_matches.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Donors List
                    </a>
                </div>
            </div>

            <!-- Search other hospitals' recipients -->
<div class="search-section">
    <h2 class="section-title">Search Other Hospitals' Recipients</h2>
    <div class="search-container">
        <button class="filter-btn" data-type="blood">
            <i class="fas fa-tint"></i> Filter by Blood Type
        </button>
        <button class="filter-btn" data-type="organ">
            <i class="fas fa-heart"></i> Filter by Organ Type
        </button>
        <input type="text" id="searchInput" class="search-input" placeholder="Type to search...">
    </div>
    <div id="searchResults"></div>
</div>

<!-- Hospital's own approved recipients -->
<div class="search-section mt-4">
    <h2 class="section-title">Your Approved Recipients</h2>
    <table class="recipients-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Blood Type</th>
                <th>Required Organ</th>
                <th>Medical Info</th>
                <th>Contact</th>
                <th>From Hospital</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($hospital_recipients)): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-user-alt-slash"></i>
                        <h2>No recipients found</h2>
                        <p>There are no approved recipients available for matching.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($hospital_recipients as $recipient): ?>
                    <tr data-recipient-id="<?php echo $recipient['recipient_id']; ?>">
                        <td><?php echo htmlspecialchars($recipient['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($recipient['blood_type']); ?></td>
                        <td><?php echo htmlspecialchars($recipient['organ_required']); ?></td>
                        <td>
                            <p>Condition: <?php echo htmlspecialchars($recipient['medical_condition']); ?></p>
                            <p>Urgency: <?php echo htmlspecialchars($recipient['urgency_level']); ?></p>
                        </td>
                        <td>
                            <p>Email: <?php echo htmlspecialchars($recipient['email']); ?></p>
                            <p>Phone: <?php echo htmlspecialchars($recipient['phone_number']); ?></p>
                        </td>
                        <td><?php echo $recipient['from_hospital'] === $hospital_name ? 'Your Hospital' : htmlspecialchars($recipient['from_hospital']); ?></td>
                        <td>
                            <button class="btn btn-request" onclick="selectRecipient(<?php echo $recipient['recipient_id']; ?>, '<?php echo htmlspecialchars($recipient['full_name']); ?>')">
                                Select
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
        </main>
    </div>

    <script>
        let activeFilter = null;
        const searchInput = document.getElementById('searchInput');
        const filterButtons = document.querySelectorAll('.filter-btn');

        // Add click event to filter buttons
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (button.classList.contains('active')) {
                    button.classList.remove('active');
                    activeFilter = null;
                    searchInput.placeholder = "Type to search...";
                } else {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    activeFilter = button.dataset.type;
                    searchInput.placeholder = activeFilter === 'blood' 
                        ? "Enter blood type (e.g., A+, B-, O+)"
                        : "Enter organ type (e.g., kidney, heart)";
                }
                searchInput.value = '';
                document.getElementById('searchResults').innerHTML = '';
            });
        });

        async function handleRecipientRequest(recipientId, hospitalId, action) {
            try {
                const response = await fetch('../../ajax/handle_recipient_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        recipientId: recipientId,
                        recipientHospitalId: hospitalId,
                        action: action
                    })
                });

                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                // Refresh the search results
                if (searchInput.value.trim() !== '') {
                    searchInput.dispatchEvent(new Event('input'));
                }

                showNotification(data.message, 'success');

            } catch (error) {
                console.error('Error:', error);
                showNotification(error.message, 'error');
            }
        }

        function showNotification(message, type) {
            const notificationDiv = document.createElement('div');
            notificationDiv.className = `alert alert-${type} notification`;
            notificationDiv.textContent = message;
            document.body.appendChild(notificationDiv);

            setTimeout(() => {
                notificationDiv.remove();
            }, 3000);
        }

        // Add input event to search input
        searchInput.addEventListener('input', () => {
            if (!activeFilter) {
                document.getElementById('searchResults').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        <h2>Select a Filter</h2>
                        <p>Please select either Blood Type or Organ Type to search</p>
                    </div>
                `;
                return;
            }

            const searchValue = searchInput.value.trim();
            if (searchValue === '') {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }

            const formData = new FormData();
            if (activeFilter === 'blood') {
                formData.append('bloodType', searchValue);
            } else {
                formData.append('organType', searchValue);
            }

            fetch('../../ajax/search_recipients.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('searchResults');
                if (!Array.isArray(data) || data.length === 0) {
                    resultsDiv.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h2>No Results Found</h2>
                            <p>Try adjusting your search criteria</p>
                        </div>
                    `;
                    return;
                }

                resultsDiv.innerHTML = `
                    <table class="recipients-table">
                        <thead>
                            <tr>
                                <th>Recipient Name</th>
                                <th>Blood Type</th>
                                <th>Organ Required</th>
                                <th>Medical Condition</th>
                                <th>Urgency Level</th>
                                <th>Hospital</th>
                                <th>Hospital Contact</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(recipient => `
                                <tr>
                                    <td>${recipient.recipient_name}</td>
                                    <td>${recipient.blood_type}</td>
                                    <td>${recipient.organ_required}</td>
                                    <td>${recipient.medical_condition}</td>
                                    <td>${recipient.urgency_level}</td>
                                    <td>${recipient.hospital_name}</td>
                                    <td>
                                        Email: ${recipient.hospital_email}<br>
                                        Phone: ${recipient.hospital_phone}
                                    </td>
                                    <td>
                                        ${recipient.request_status ? `
                                            <span class="status-badge status-${recipient.request_status.toLowerCase()}">
                                                ${recipient.request_status}
                                            </span>
                                        ` : ''}
                                    </td>
                                    <td>
                                        ${!recipient.request_status ? `
                                            <button class="btn btn-request" onclick="handleRecipientRequest(${recipient.recipient_id}, ${recipient.hospital_id}, 'request')">
                                                Request
                                            </button>
                                        ` : recipient.request_status === 'Pending' ? `
                                            <button class="btn btn-cancel" onclick="handleRecipientRequest(${recipient.recipient_id}, ${recipient.hospital_id}, 'cancel')">
                                                Cancel
                                            </button>
                                        ` : ''}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('searchResults').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h2>Error</h2>
                        <p>An error occurred while searching. Please try again.</p>
                    </div>
                `;
            });
        });

        function selectRecipient(recipientId, recipientName) {
            if (confirm('Are you sure you want to select this recipient?')) {
                // Get all the recipient details from the row
                const row = document.querySelector(`[data-recipient-id="${recipientId}"]`);
                const recipientInfo = {
                    id: recipientId,
                    name: recipientName,
                    blood_group: row.querySelector('td:nth-child(2)').textContent,
                    organ_type: row.querySelector('td:nth-child(3)').textContent,
                    medical_condition: row.querySelector('td:nth-child(4)').textContent.split('Condition: ')[1].split('\n')[0],
                    urgency: row.querySelector('td:nth-child(4)').textContent.split('Urgency: ')[1],
                    email: row.querySelector('td:nth-child(5)').textContent.split('Email: ')[1].split('\n')[0],
                    phone: row.querySelector('td:nth-child(5)').textContent.split('Phone: ')[1],
                    from_hospital: row.querySelector('td:nth-child(6)').textContent
                };

                // Store in session storage
                sessionStorage.setItem('selectedRecipient', JSON.stringify(recipientInfo));

                // Redirect to make matches page
                window.location.href = 'make_matches.php';
            }
        }
    </script>
</body>
</html>
