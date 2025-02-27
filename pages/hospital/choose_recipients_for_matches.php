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

// Fetch hospital's recipients
try {
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            h.name as hospital_name,
            hra.status as approval_status
        FROM recipient_registration r
        INNER JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id
        INNER JOIN hospitals h ON hra.hospital_id = h.hospital_id
        WHERE hra.hospital_id = ?
        AND hra.status = 'approved'
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE recipient_id = r.id
        )
        ORDER BY r.full_name ASC
    ");
    
    $stmt->execute([$hospital_id]);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error fetching recipients: " . $e->getMessage());
    $recipients = [];
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
                    <a href="choose_donors_for_matches.php" class="switch-list-btn">
                        <i class="fas fa-user-plus"></i>
                        Donors List
                    </a>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <h2>Search Recipients</h2>
                <div class="search-container">
                    <input type="text" id="searchInput" class="search-input" placeholder="Type to search...">
                    <button class="filter-btn" data-type="blood">Blood Type</button>
                    <button class="filter-btn" data-type="organ">Organ Type</button>
                </div>
            </div>

            <!-- Search Results Section -->
            <div id="searchResults"></div>

            <!-- Current Approved Recipients Section -->
            <div class="approved-recipients-section">
                <h2 class="section-title">Your Hospital's Approved Recipients</h2>
                <?php if (empty($recipients)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-plus"></i>
                        <h2>No Approved Recipients Found</h2>
                        <p>There are no approved recipients available for matching at the moment.</p>
                    </div>
                <?php else: ?>
                    <table class="recipients-table">
                        <thead>
                            <tr>
                                <th>Recipient Name</th>
                                <th>Blood Group</th>
                                <th>Required Organ</th>
                                <th>Hospital</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recipients as $recipient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($recipient['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['blood_type']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['organ_required']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['hospital_name']); ?></td>
                                    <td><?php echo htmlspecialchars($recipient['approval_status']); ?></td>
                                    <td>
                                        <button class="select-btn" onclick="selectRecipient(<?php echo $recipient['id']; ?>)">
                                            Select for Match
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

        // Add click event to filter buttons
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // If this button is already active, deactivate it
                if (button.classList.contains('active')) {
                    button.classList.remove('active');
                    activeFilter = null;
                    searchInput.placeholder = "Type to search...";
                } else {
                    // Deactivate all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Activate this button
                    button.classList.add('active');
                    activeFilter = button.dataset.type;
                    searchInput.placeholder = activeFilter === 'blood' 
                        ? "Enter blood type (e.g., A+, B-, O+)"
                        : "Enter organ type (e.g., kidney, heart)";
                }
                // Clear search input
                searchInput.value = '';
                // Clear search results
                document.getElementById('searchResults').innerHTML = '';
            });
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
                if (data.length === 0) {
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
                    <h2 class="section-title">Search Results</h2>
                    <table class="recipients-table">
                        <thead>
                            <tr>
                                <th>Recipient Name</th>
                                <th>Blood Group</th>
                                <th>Required Organ</th>
                                <th>Hospital</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(recipient => `
                                <tr>
                                    <td>${recipient.full_name}</td>
                                    <td>${recipient.blood_type}</td>
                                    <td>${recipient.organ_required}</td>
                                    <td>${recipient.hospital_name}</td>
                                    <td>${recipient.approval_status}</td>
                                    <td>
                                        <button class="select-btn" onclick="selectRecipient(${recipient.id})">
                                            Select for Match
                                        </button>
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

        function selectRecipient(recipientId) {
            // Get recipient details from the row
            const row = event.target.closest('tr');
            const recipientName = row.cells[0].textContent.trim();
            const bloodGroup = row.cells[1].textContent.trim();
            const organType = row.cells[2].textContent.trim();
            const hospital = row.cells[3].textContent.trim();

            // Here you can add the logic to handle the recipient selection
            // For example, redirect to a matching page or show a modal
            console.log('Selected recipient:', {
                id: recipientId,
                name: recipientName,
                bloodGroup: bloodGroup,
                organType: organType,
                hospital: hospital
            });
        }
    </script>
</body>
</html>
