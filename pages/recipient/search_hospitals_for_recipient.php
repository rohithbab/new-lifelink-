<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in as recipient
if (!isset($_SESSION['is_recipient']) || !$_SESSION['is_recipient']) {
    header("Location: ../recipient_login.php");
    exit();
}

// Get recipient info from session
$recipient_id = $_SESSION['recipient_id'];

// Fetch recipient details
$stmt = $conn->prepare("SELECT full_name, blood_type, organ_required FROM recipient_registration WHERE id = ?");
$stmt->execute([$recipient_id]);
$recipient = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle AJAX search request
if (isset($_GET['search']) && isset($_GET['filter'])) {
    $search = '%' . $_GET['search'] . '%';
    $filter = $_GET['filter'];
    
    // Base query to get hospitals with approved donors for the required organ
    $baseQuery = "SELECT DISTINCT h.*, 
                  (SELECT COUNT(*) FROM hospital_donor_approvals hda 
                   JOIN donor d ON hda.donor_id = d.donor_id 
                   WHERE hda.hospital_id = h.hospital_id 
                   AND hda.status = 'approved' 
                   AND hda.organ_type = ?) as organ_count
                  FROM hospitals h
                  LEFT JOIN hospital_donor_approvals hda ON h.hospital_id = hda.hospital_id
                  WHERE 1=1";

    $params = [$recipient['organ_required']];

    // Add filter conditions
    switch($filter) {
        case 'name':
            $baseQuery .= " AND h.name LIKE ?";
            $params[] = $search;
            break;
        case 'address':
            $baseQuery .= " AND h.address LIKE ?";
            $params[] = $search;
            break;
        case 'phone':
            $baseQuery .= " AND h.phone LIKE ?";
            $params[] = $search;
            break;
        case 'organ':
            $baseQuery .= " AND EXISTS (
                SELECT 1 FROM hospital_donor_approvals hda2 
                JOIN donor d2 ON hda2.donor_id = d2.donor_id
                WHERE hda2.hospital_id = h.hospital_id 
                AND hda2.status = 'approved' 
                AND hda2.organ_type = ?)";
            $params[] = $recipient['organ_required'];
            break;
    }

    $baseQuery .= " GROUP BY h.hospital_id ORDER BY organ_count DESC";
    
    $stmt = $conn->prepare($baseQuery);
    $stmt->execute($params);
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode(['hospitals' => $hospitals]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Hospitals - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/recipient-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .search-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-header {
            margin-bottom: 20px;
        }

        .search-header h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 24px;
        }

        .search-subtitle {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .search-subtitle i {
            color: var(--primary-color);
            font-size: 16px;
        }

        .search-subtitle p {
            margin: 0;
            font-size: 14px;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            background: rgba(var(--primary-color-rgb), 0.1);
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .hospitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .hospital-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .hospital-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .hospital-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .hospital-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .hospital-name {
            font-size: 1.2em;
            color: var(--text-primary);
            margin: 0;
        }

        .hospital-info {
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .organ-availability {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .organ-availability i {
            color: #28a745;
        }

        .request-btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .request-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php require_once 'includes/sidebar_for_recipients_dashboard.php'; ?>

        <main class="main-content">
            <div class="search-container">
                <div class="search-header">
                    <h2>Search Hospitals</h2>
                    <div class="search-subtitle">
                        <i class="fas fa-info-circle"></i>
                        <p>Find hospitals with available <?php echo htmlspecialchars(strtolower($recipient['organ_required'])); ?> donors</p>
                    </div>
                </div>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search hospitals..." id="searchInput">
                </div>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="name">Name</button>
                    <button class="filter-btn" data-filter="address">Address</button>
                    <button class="filter-btn" data-filter="phone">Phone</button>
                    <button class="filter-btn" data-filter="organ">Organ Availability</button>
                </div>
                <div class="hospitals-grid" id="hospitalsGrid">
                    <!-- Hospital cards will be dynamically added here -->
                </div>
            </div>
        </main>
    </div>

    <script>
        let currentFilter = 'name';
        let searchTimeout = null;

        // Filter button click handler
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                currentFilter = button.dataset.filter;
                performSearch();
            });
        });

        // Search input handler
        document.getElementById('searchInput').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => performSearch(), 300);
        });

        // Perform search function
        function performSearch() {
            const searchTerm = document.getElementById('searchInput').value;
            
            fetch(`search_hospitals_for_recipient.php?search=${encodeURIComponent(searchTerm)}&filter=${currentFilter}`)
                .then(response => response.json())
                .then(data => {
                    const hospitalsGrid = document.getElementById('hospitalsGrid');
                    hospitalsGrid.innerHTML = '';

                    if (data.hospitals.length === 0) {
                        hospitalsGrid.innerHTML = `
                            <div class="no-results">
                                <i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                                <h3>No hospitals found</h3>
                                <p>Try adjusting your search criteria</p>
                            </div>`;
                        return;
                    }

                    data.hospitals.forEach(hospital => {
                        const card = document.createElement('div');
                        card.className = 'hospital-card';
                        card.innerHTML = `
                            <div class="hospital-header">
                                <div class="hospital-icon">
                                    <i class="fas fa-hospital"></i>
                                </div>
                                <h3 class="hospital-name">${hospital.name}</h3>
                            </div>
                            <div class="hospital-info">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${hospital.address}</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-phone"></i>
                                    <span>${hospital.phone}</span>
                                </div>
                            </div>
                            <div class="organ-availability">
                                <i class="fas fa-check-circle"></i>
                                <span>${hospital.organ_count} potential ${hospital.organ_count === 1 ? 'donor' : 'donors'} available</span>
                            </div>
                            <button class="request-btn" onclick="confirmRequest('${hospital.hospital_id}', '${hospital.name}'); return false;">
                                Make Request
                            </button>
                        `;
                        hospitalsGrid.appendChild(card);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Initial search on page load
        performSearch();

        // Confirmation dialog for request
        function confirmRequest(hospitalId, hospitalName) {
            if (confirm(`Do you want to make a request to ${hospitalName}?`)) {
                window.location.href = `recipient_requests_hospital.php?hospital_id=${hospitalId}`;
            }
        }
    </script>
</body>
</html>
