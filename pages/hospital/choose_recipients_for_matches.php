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
            r.organ_required,
            h.name as from_hospital
        FROM recipient_registration r
        INNER JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id
        INNER JOIN hospitals h ON hra.hospital_id = h.hospital_id
        LEFT JOIN recipient_requests rr ON r.id = rr.recipient_id AND rr.requesting_hospital_id = ?
        WHERE (
            hra.hospital_id = ?  -- Hospital's own recipients
            OR 
            (rr.requesting_hospital_id = ? AND rr.status = 'approved')  -- Approved requests from other hospitals
        )
        AND hra.status = 'approved'
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE recipient_id = r.id
        )
        ORDER BY 
            CASE WHEN hra.hospital_id = ? THEN 0 ELSE 1 END,  -- Show own recipients first
            r.urgency_level DESC,
            r.full_name ASC
    ");
    
    $stmt->execute([$hospital_id, $hospital_id, $hospital_id, $hospital_id]);
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

        /* Source Badge Styling */
        .hospital-name {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .hospital-name.your-hospital {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.2);
        }

        .hospital-name.other-hospital {
            background: linear-gradient(45deg, #17a2b8, #0dcaf0);
            color: white;
            box-shadow: 0 2px 10px rgba(23, 162, 184, 0.2);
        }

        /* Organ Badge Styling */
        .organ-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            background: linear-gradient(45deg, #007bff, #66d9ef);
            color: white;
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.2);
        }

        /* Urgency Badge Styling */
        .urgency-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .urgency-critical {
            background: linear-gradient(45deg, #dc3545, #ff6f6f);
            color: white;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.2);
        }

        .urgency-high {
            background: linear-gradient(45deg, #ffc107, #ffd07b);
            color: white;
            box-shadow: 0 2px 10px rgba(255, 193, 7, 0.2);
        }

        .urgency-medium {
            background: linear-gradient(45deg, #28a745, #51cf66);
            color: white;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.2);
        }

        .urgency-low {
            background: linear-gradient(45deg, #17a2b8, #45b3fa);
            color: white;
            box-shadow: 0 2px 10px rgba(23, 162, 184, 0.2);
        }

        /* Confirmation Dialog Styling */
        .confirmation-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .confirmation-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            text-align: center;
        }

        .confirmation-dialog h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .confirmation-dialog p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .confirm-btn, .cancel-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .confirm-btn {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .cancel-btn {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
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
                <th>Organ Required</th>
                <th>Urgency</th>
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
                        <td>
                            <span class="organ-badge">
                                <?php echo htmlspecialchars($recipient['organ_required']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="urgency-badge urgency-<?php echo strtolower($recipient['urgency_level']); ?>">
                                <?php echo htmlspecialchars($recipient['urgency_level']); ?>
                            </span>
                        </td>
                        <td>
                            Email: <?php echo htmlspecialchars($recipient['email']); ?><br>
                            Phone: <?php echo htmlspecialchars($recipient['phone_number']); ?>
                        </td>
                        <td>
                            <span class="hospital-name <?php echo $recipient['from_hospital'] === $hospital_name ? 'your-hospital' : 'other-hospital'; ?>">
                                <?php echo $recipient['from_hospital'] === $hospital_name ? 'Your Hospital' : htmlspecialchars($recipient['from_hospital']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-request" onclick="handleRecipientRequest('<?php echo $recipient['recipient_id']; ?>', '<?php echo $recipient['hospital_id']; ?>', '<?php echo htmlspecialchars($recipient['from_hospital']); ?>')">
                                Request Access
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

    <!-- Confirmation Dialog HTML -->
    <div id="confirmationOverlay" class="confirmation-overlay">
        <div class="confirmation-dialog">
            <h2>Request Recipient Access</h2>
            <p>Are you sure you want to request access to this recipient from <span id="hospitalName"></span>?</p>
            <div class="confirmation-buttons">
                <button id="confirmRequest" class="confirm-btn">Yes, Request Access</button>
                <button id="cancelRequest" class="cancel-btn">Cancel</button>
            </div>
        </div>
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

        async function handleRecipientRequest(recipientId, hospitalId, hospitalName) {
            // Update the confirmation dialog
            document.getElementById('hospitalName').textContent = hospitalName;
            document.getElementById('confirmationOverlay').style.display = 'block';

            // Store the IDs for the confirmation handler
            document.getElementById('confirmRequest').onclick = function() {
                document.getElementById('confirmationOverlay').style.display = 'none';
                
                // Create form data
                const formData = new FormData();
                formData.append('recipientId', recipientId);
                formData.append('hospitalId', hospitalId);
                formData.append('action', 'request');

                // Send the request
                fetch('../../ajax/recipient_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the button to show pending status
                        const button = document.querySelector(`[data-recipient-id="${recipientId}"] .btn-request`);
                        if (button) {
                            button.outerHTML = '<button class="btn btn-secondary" disabled>Request Pending</button>';
                        }
                        showNotification('Request sent successfully!', 'success');
                    } else {
                        showNotification(data.message || 'Failed to send request', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while sending the request', 'error');
                });
            };

            // Handle cancel button
            document.getElementById('cancelRequest').onclick = function() {
                document.getElementById('confirmationOverlay').style.display = 'none';
            };
        }

        // Close dialog when clicking outside
        document.getElementById('confirmationOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

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

                let tableHTML = `
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
                `;

                data.forEach(recipient => {
                    tableHTML += `
                        <tr data-recipient-id="${recipient.recipient_id}">
                            <td>${recipient.full_name}</td>
                            <td>${recipient.blood_type}</td>
                            <td>
                                <span class="organ-badge">
                                    ${recipient.organ_required}
                                </span>
                            </td>
                            <td>${recipient.medical_condition}</td>
                            <td>
                                <span class="urgency-badge urgency-${recipient.urgency_level.toLowerCase()}">
                                    ${recipient.urgency_level}
                                </span>
                            </td>
                            <td>
                                <span class="hospital-name ${recipient.hospital_name === '<?php echo $hospital_name; ?>' ? 'your-hospital' : 'other-hospital'}">
                                    ${recipient.hospital_name === '<?php echo $hospital_name; ?>' ? 'Your Hospital' : recipient.hospital_name}
                                </span>
                            </td>
                            <td>
                                Email: ${recipient.email}<br>
                                Phone: ${recipient.phone_number}
                            </td>
                            <td>
                                ${recipient.request_status === 'Pending' 
                                    ? '<button class="btn btn-secondary" disabled>Request Pending</button>'
                                    : recipient.request_status === 'Approved'
                                    ? '<button class="select-btn" onclick="selectRecipient(\'' + recipient.recipient_id + '\', \'' + recipient.full_name + '\')">Select</button>'
                                    : '<button class="btn-request" onclick="handleRecipientRequest(\'' + recipient.recipient_id + '\', \'' + recipient.hospital_id + '\', \'' + recipient.hospital_name + '\')">Request Access</button>'
                                }
                            </td>
                        </tr>`;
                });

                tableHTML += `
                        </tbody>
                    </table>
                `;

                resultsDiv.innerHTML = tableHTML;
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

        function showNotification(message, type) {
            const notificationDiv = document.createElement('div');
            notificationDiv.className = `alert alert-${type} notification`;
            notificationDiv.textContent = message;
            document.body.appendChild(notificationDiv);

            setTimeout(() => {
                notificationDiv.remove();
            }, 3000);
        }

        function selectRecipient(recipientId, recipientName) {
            if (confirm('Are you sure you want to select this recipient?')) {
                // Get all the recipient details from the row
                const row = document.querySelector(`[data-recipient-id="${recipientId}"]`);
                const recipientInfo = {
                    id: recipientId,
                    name: recipientName,
                    bloodGroup: row.querySelector('td:nth-child(2)').textContent.trim(),
                    requiredOrgan: row.querySelector('td:nth-child(3)').textContent.trim(),
                    medical_condition: row.querySelector('td:nth-child(4)').textContent.split('Condition: ')[1].split('\n')[0].trim(),
                    urgency: row.querySelector('td:nth-child(4)').textContent.split('Urgency: ')[1].trim(),
                    email: row.querySelector('td:nth-child(5)').textContent.split('Email: ')[1].split('\n')[0].trim(),
                    phone: row.querySelector('td:nth-child(5)').textContent.split('Phone: ')[1].trim(),
                    from_hospital: row.querySelector('td:nth-child(6)').textContent.trim()
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
