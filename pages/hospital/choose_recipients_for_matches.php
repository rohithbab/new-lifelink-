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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Choose Recipients</h1>
            </div>

            <div class="search-section">
                <h2>Search Recipients from Other Hospitals</h2>
                <div class="search-container">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by name, blood type, or organ...">
                    <button class="filter-btn" data-filter="name">Name</button>
                    <button class="filter-btn" data-filter="blood_type">Blood Type</button>
                    <button class="filter-btn" data-filter="organ">Organ</button>
                </div>
                <div id="searchResults">
                    <!-- Search results will be displayed here -->
                </div>
            </div>

            <div class="approved-recipients-section">
                <h2>Approved Recipients</h2>
                <?php if (empty($hospital_recipients)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-times"></i>
                        <h2>No Approved Recipients</h2>
                        <p>You don't have any approved recipients available for matching.</p>
                    </div>
                <?php else: ?>
                    <table class="recipients-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Blood Type</th>
                                <th>Organ Required</th>
                                <th>Urgency Level</th>
                                <th>From Hospital</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hospital_recipients as $recipient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($recipient['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['blood_type']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['organ_required']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['urgency_level']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['from_hospital']); ?></td>
                                    <td>
                                        <button class="select-btn" onclick="selectRecipient(
                                            '<?php echo htmlspecialchars($recipient['recipient_id']); ?>',
                                            '<?php echo htmlspecialchars($recipient['full_name']); ?>',
                                            '<?php echo htmlspecialchars($recipient['blood_type']); ?>',
                                            '<?php echo htmlspecialchars($recipient['organ_required']); ?>',
                                            '<?php echo htmlspecialchars($recipient['urgency_level']); ?>',
                                            '<?php echo htmlspecialchars($recipient['from_hospital']); ?>'
                                        )">
                                            <i class="fas fa-check"></i> Select
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        let activeFilter = null;
        const searchInput = document.getElementById('searchInput');
        const filterButtons = document.querySelectorAll('.filter-btn');

        // Filter button click handlers
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (activeFilter === button.dataset.filter) {
                    button.classList.remove('active');
                    activeFilter = null;
                } else {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    activeFilter = button.dataset.filter;
                }
                searchInput.focus();
            });
        });

        function selectRecipient(recipientId, name, bloodType, organ, urgency, hospital) {
            if (confirm('Are you sure you want to select this recipient?')) {
                // Store recipient details in sessionStorage
                const recipientData = {
                    id: recipientId,
                    name: name,
                    bloodType: bloodType,
                    organ: organ,
                    urgency: urgency,
                    hospital: hospital
                };
                sessionStorage.setItem('selectedRecipient', JSON.stringify(recipientData));
                
                // Redirect to make_matches.php
                window.location.href = 'make_matches.php';
            }
        }

        // Search functionality
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.trim().toLowerCase();
            
            if (!activeFilter) {
                document.getElementById('searchResults').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        <h2>Select a Filter</h2>
                        <p>Please select a filter (Name, Blood Type, or Organ) to search for recipients.</p>
                    </div>
                `;
                return;
            }

            if (!searchTerm) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }

            // Make API call to search recipients
            fetch(`../../backend/php/search_recipients.php?filter=${activeFilter}&term=${searchTerm}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        document.getElementById('searchResults').innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h2>No Results Found</h2>
                                <p>No recipients found matching your search criteria.</p>
                            </div>
                        `;
                        return;
                    }

                    let tableHtml = `
                        <table class="recipients-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Blood Type</th>
                                    <th>Organ Required</th>
                                    <th>Urgency Level</th>
                                    <th>From Hospital</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    data.forEach(recipient => {
                        tableHtml += `
                            <tr>
                                <td>${recipient.full_name}</td>
                                <td>${recipient.blood_type}</td>
                                <td>${recipient.organ_required}</td>
                                <td>${recipient.urgency_level}</td>
                                <td>${recipient.from_hospital}</td>
                                <td>
                                    <button class="btn btn-request" onclick="requestRecipient('${recipient.recipient_id}')">
                                        Request
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    tableHtml += `</tbody></table>`;
                    document.getElementById('searchResults').innerHTML = tableHtml;
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
    </script>
</body>
</html>
