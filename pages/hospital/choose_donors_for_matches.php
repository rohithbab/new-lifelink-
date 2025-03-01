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

// Fetch hospital's own approved donors and approved requests from other hospitals
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT
            d.donor_id,
            d.name,
            d.blood_group,
            d.email,
            d.phone,
            hda.organ_type,
            h.name as from_hospital
        FROM donor d
        LEFT JOIN donor_requests dr ON d.donor_id = dr.donor_id
        JOIN hospital_donor_approvals hda ON d.donor_id = hda.donor_id
        LEFT JOIN hospitals h ON hda.hospital_id = h.hospital_id
        WHERE (
            (hda.hospital_id = ? AND hda.status = 'approved')
            OR 
            (dr.requesting_hospital_id = ? AND dr.status = 'Approved')
        )
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE donor_id = d.donor_id
        )
        ORDER BY d.name ASC
    ");
    
    $stmt->execute([$hospital_id, $hospital_id]);
    $hospital_donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error fetching donors: " . $e->getMessage());
    $hospital_donors = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Donors - LifeLink</title>
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

        .donors-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .donors-table th,
        .donors-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .donors-table th {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            font-weight: 500;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-request {
            background: #007bff;
            color: white;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending {
            background: #ffc107;
            color: #000;
        }

        .status-approved {
            background: #28a745;
            color: white;
        }

        .status-rejected {
            background: #dc3545;
            color: white;
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

        .section-title {
            margin: 2rem 0 1rem;
            color: #333;
            font-size: 1.5rem;
        }

        .mt-4 {
            margin-top: 2rem;
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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Choose Donors</h1>
                </div>
                <div class="header-right">
                    <a href="choose_recipients_for_matches.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Recipients List
                    </a>
                </div>
            </div>

            <!-- Hospital's own approved donors -->
            <?php if (!empty($hospital_donors)): ?>
                <div class="search-section">
                    <h2 class="section-title">Your Approved Donors</h2>
                    <table class="donors-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Blood Group</th>
                                <th>Organ Type</th>
                                <th>Contact</th>
                                <th>From Hospital</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hospital_donors as $donor): ?>
                                <tr data-donor-id="<?php echo $donor['donor_id']; ?>">
                                    <td><?php echo htmlspecialchars($donor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($donor['blood_group']); ?></td>
                                    <td><?php echo htmlspecialchars($donor['organ_type']); ?></td>
                                    <td>
                                        Email: <?php echo htmlspecialchars($donor['email']); ?><br>
                                        Phone: <?php echo htmlspecialchars($donor['phone']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($donor['from_hospital'] == $hospital_name) {
                                            echo "Your Hospital";
                                        } else {
                                            echo htmlspecialchars($donor['from_hospital']);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="select-btn" onclick="selectDonor('<?php echo $donor['donor_id']; ?>', '<?php echo htmlspecialchars($donor['name']); ?>')">
                                            Select
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="search-section">
                    <h2 class="section-title">Your Approved Donors</h2>
                    <table class="donors-table">
                        <tbody>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-user-times"></i>
                                        <h2>No approved donors found</h2>
                                        <p>There are currently no approved donors in your hospital.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Search other hospitals' donors -->
            <div class="search-section mt-4">
                <h2 class="section-title">Search Other Hospitals' Donors</h2>
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

        async function handleDonorRequest(donorId, hospitalId, action) {
            try {
                const response = await fetch('../../ajax/handle_donor_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        donorId: donorId,
                        donorHospitalId: hospitalId,
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

        function selectDonor(donorId, donorName) {
            if (confirm('Are you sure you want to select this donor?')) {
                // Get all the donor details from the row
                const row = document.querySelector(`[data-donor-id="${donorId}"]`);
                const donorInfo = {
                    id: donorId,
                    name: donorName,
                    blood_group: row.querySelector('td:nth-child(2)').textContent,
                    organ_type: row.querySelector('td:nth-child(3)').textContent,
                    email: row.querySelector('td:nth-child(4)').textContent.split('Email: ')[1].split('\n')[0],
                    phone: row.querySelector('td:nth-child(4)').textContent.split('Phone: ')[1],
                    from_hospital: row.querySelector('td:nth-child(5)').textContent
                };

                // Store in session storage
                sessionStorage.setItem('selectedDonor', JSON.stringify(donorInfo));

                // Redirect to make matches page
                window.location.href = 'make_matches.php';
            }
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

            fetch('../../ajax/search_donors.php', {
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
                    <table class="donors-table">
                        <thead>
                            <tr>
                                <th>Donor Name</th>
                                <th>Blood Group</th>
                                <th>Organ Type</th>
                                <th>Hospital</th>
                                <th>Hospital Contact</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(donor => `
                                <tr>
                                    <td>${donor.donor_name}</td>
                                    <td>${donor.blood_group}</td>
                                    <td>${donor.organ_type}</td>
                                    <td>${donor.hospital_name}</td>
                                    <td>
                                        Email: ${donor.hospital_email}<br>
                                        Phone: ${donor.hospital_phone}
                                    </td>
                                    <td>
                                        ${donor.request_status ? `
                                            <span class="status-badge status-${donor.request_status.toLowerCase()}">
                                                ${donor.request_status}
                                            </span>
                                        ` : ''}
                                    </td>
                                    <td>
                                        ${!donor.request_status ? `
                                            <button class="btn btn-request" onclick="handleDonorRequest(${donor.donor_id}, ${donor.hospital_id}, 'request')">
                                                Request
                                            </button>
                                        ` : donor.request_status === 'Pending' ? `
                                            <button class="btn btn-cancel" onclick="handleDonorRequest(${donor.donor_id}, ${donor.hospital_id}, 'cancel')">
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
    </script>
</body>
</html>
