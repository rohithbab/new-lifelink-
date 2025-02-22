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

// Fetch hospital's donors
try {
    $stmt = $conn->prepare("
        (
            -- Regular donors
            SELECT 
                d.*,
                ha.organ_type,
                'Own' as donor_type,
                NULL as shared_from_hospital,
                ha.hospital_id as donor_hospital_id
            FROM donor d
            JOIN hospital_donor_approvals ha ON d.donor_id = ha.donor_id
            WHERE ha.hospital_id = ? 
            AND ha.status = 'Approved'
            AND ha.is_matched = FALSE
        )
        UNION
        (
            -- Shared donors
            SELECT 
                d.*,
                sda.organ_type,
                'Shared' as donor_type,
                h2.name as shared_from_hospital,
                sda.from_hospital_id as donor_hospital_id
            FROM donor d
            JOIN shared_donor_approvals sda ON d.donor_id = sda.donor_id
            JOIN hospitals h2 ON h2.hospital_id = sda.from_hospital_id
            WHERE sda.to_hospital_id = ?
            AND sda.is_matched = FALSE
        )
        ORDER BY name ASC
    ");
    
    $stmt->execute([$hospital_id, $hospital_id]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error fetching donors: " . $e->getMessage());
    $donors = [];
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
        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 2rem;
        }

        .search-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem;
            padding-left: 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f0f0f0;
            color: #666;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
        }

        .search-results {
            margin-top: 2rem;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .results-table th {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            padding: 1rem;
            text-align: left;
        }
        
        .results-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .results-table tr:hover {
            background: #f8f9fa;
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .contact-info span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }
        
        .donor-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .donor-count {
            font-weight: bold;
            color: var(--primary-blue);
        }
        
        .donor-details {
            color: #666;
        }
        
        .view-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .view-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .donors-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
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

        .donors-table tr:hover {
            background-color: #f8f9fa;
        }

        .select-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
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

        .shared-donor {
            background-color: rgba(33, 150, 243, 0.05);
            border-left: 4px solid var(--primary-blue);
        }
        
        .shared-badge {
            display: inline-block;
            background: var(--primary-blue);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85em;
            margin-top: 0.5rem;
        }

        .shared-badge i {
            margin-right: 0.3rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .switch-list-btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .switch-list-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .switch-list-btn i {
            font-size: 0.9em;
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
                    <a href="choose_recipients_for_matches.php" class="switch-list-btn">
                        <i class="fas fa-users"></i>
                        Recipients List
                    </a>
                </div>
            </div>

            <div class="search-section">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search for donors..." id="searchInput">
                </div>

                <div class="filter-buttons">
                    <button class="filter-btn" data-filter="blood_group">
                        <i class="fas fa-tint"></i> Blood Group
                    </button>
                    <button class="filter-btn" data-filter="organs">
                        <i class="fas fa-heart"></i> Organs
                    </button>
                </div>

                <?php if (empty($donors)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <h2>No Donors Found</h2>
                        <p>There are no approved donors in your hospital at the moment.</p>
                    </div>
                <?php else: ?>
                    <table class="donors-table">
                        <thead>
                            <tr>
                                <th>Donor Name</th>
                                <th>Blood Group</th>
                                <th>Organ Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donors as $donor): ?>
                                <tr class="<?php echo $donor['donor_type'] === 'Shared' ? 'shared-donor' : ''; ?>" 
                                    data-donor-type="<?php echo $donor['donor_type']; ?>" 
                                    data-hospital-name="<?php echo $donor['donor_type'] === 'Shared' ? htmlspecialchars($donor['shared_from_hospital']) : htmlspecialchars($hospital_name); ?>"
                                    data-hospital-id="<?php echo $donor['donor_hospital_id']; ?>">
                                    <td>
                                        <?php echo htmlspecialchars($donor['name']); ?>
                                        <?php if ($donor['donor_type'] === 'Shared'): ?>
                                            <span class="shared-badge">Shared from <?php echo htmlspecialchars($donor['shared_from_hospital']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($donor['blood_group']); ?></td>
                                    <td><?php echo htmlspecialchars($donor['organ_type']); ?></td>
                                    <td>
                                        <button class="select-btn" onclick="selectDonor(<?php echo $donor['donor_id']; ?>)">
                                            Select for Match
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div id="searchResults" class="search-results">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Hospital Name</th>
                            <th>Contact Details</th>
                            <th>Available Donors</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody">
                        <!-- Results will be populated here -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const filterButtons = document.querySelectorAll('.filter-btn');
        let currentFilter = 'name';

        // Filter button click handling
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                currentFilter = button.dataset.filter;
                searchInput.placeholder = `Search by ${currentFilter}...`;
                searchInput.value = '';
                searchResults.style.display = 'none';
            });
        });

        // Real-time search handling
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const searchTerm = searchInput.value.trim();

            if (searchTerm.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch('../../ajax/search_donors.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        search: searchTerm,
                        filter: currentFilter
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.results.length > 0) {
                        displayResults(data.results);
                        searchResults.style.display = 'block';
                    } else {
                        displayResults([]);
                        searchResults.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayResults([]);
                    searchResults.style.display = 'block';
                });
            }, 500);
        });

        function displayResults(results) {
            const tbody = document.getElementById('resultsBody');
            tbody.innerHTML = '';

            if (results.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center">No hospitals found matching your search criteria</td>
                    </tr>`;
                return;
            }

            results.forEach(hospital => {
                const row = document.createElement('tr');
                
                // Hospital Name
                row.innerHTML = `
                    <td>${hospital.hospital_name}</td>
                    <td>
                        <div class="contact-info">
                            <span><i class="fas fa-phone"></i> ${hospital.phone}</span>
                            <span><i class="fas fa-map-marker-alt"></i> ${hospital.address}</span>
                        </div>
                    </td>
                    <td>
                        <div class="donor-info">
                            <span class="donor-count">Donors: ${hospital.donor_count}</span>
                            <span class="donor-details">Blood Groups: ${hospital.blood_groups.join(', ')}</span>
                            <span class="donor-details">Organs: ${hospital.organ_types.join(', ')}</span>
                        </div>
                    </td>
                    <td>
                        <a href="choose_other_donors.php?hospital_id=${hospital.hospital_id}" class="view-btn">
                            View Donors
                        </a>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            document.getElementById('searchResults').style.display = results.length ? 'block' : 'none';
        }

        function selectDonor(donorId) {
            // Get donor details from the row
            const row = event.target.closest('tr');
            const donorName = row.cells[0].textContent.trim();
            const bloodGroup = row.cells[1].textContent.trim();
            const organType = row.cells[2].textContent.trim();
            const donorType = row.getAttribute('data-donor-type');
            
            // Get hospital info based on donor type
            let hospitalId, hospitalName;
            if (donorType === 'Own') {
                hospitalId = <?php echo $hospital_id; ?>;
                hospitalName = <?php echo json_encode($hospital_name); ?>;
            } else {
                // For shared donors, get the original hospital's info
                hospitalName = row.getAttribute('data-hospital-name');
                hospitalId = row.getAttribute('data-hospital-id');
            }

            // Create donor info object
            const donorInfo = {
                id: donorId,
                name: donorName,
                bloodGroup: bloodGroup,
                organType: organType,
                hospitalId: hospitalId,
                hospitalName: hospitalName
            };
            
            // Store in session storage
            sessionStorage.setItem('selectedDonor', JSON.stringify(donorInfo));
            
            // Redirect to make matches page
            window.location.href = 'make_matches.php?donor=' + encodeURIComponent(donorId);
        }

        function viewHospitalDonors(hospitalId) {
            // Redirect to choose_other_donors.php with the selected hospital
            window.location.href = `choose_other_donors.php?hospital_id=${hospitalId}`;
        }

        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    </script>
</body>
</html>
