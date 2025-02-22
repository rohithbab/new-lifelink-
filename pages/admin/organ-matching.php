<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

// Get available donors and recipients
$availableDonors = getAvailableDonors($conn);
$waitingRecipients = getWaitingRecipients($conn);
$potentialMatches = [];

// If a donor is selected, find potential matches
if (isset($_GET['donor_id'])) {
    $potentialMatches = findPotentialMatches($conn, $_GET['donor_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organ Matching - LifeLink Admin</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="logo">
                <span class="logo-life">Life</span>Link Admin
            </a>
            <div class="nav-links">
                <a href="view-hospitals.php">Hospitals</a>
                <a href="view-donors.php">Donors</a>
                <a href="view-recipients.php">Recipients</a>
                <a href="analytics.php">Analytics</a>
                <a href="organ-matching.php" class="active">Matching</a>
                <a href="../logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="matching-grid">
            <!-- Available Donors -->
            <div class="card donors-list">
                <h2>Available Donors</h2>
                <div class="search-filter">
                    <input type="text" id="donorSearch" placeholder="Search donors...">
                    <select id="organFilter">
                        <option value="">All Organs</option>
                        <option value="kidney">Kidney</option>
                        <option value="liver">Liver</option>
                        <option value="heart">Heart</option>
                        <option value="lungs">Lungs</option>
                    </select>
                </div>
                <div class="donor-items">
                    <?php foreach ($availableDonors as $donor): ?>
                    <div class="donor-item" data-organ="<?php echo htmlspecialchars($donor['organ_type']); ?>">
                        <h3><?php echo htmlspecialchars($donor['name']); ?></h3>
                        <p>Blood Type: <?php echo htmlspecialchars($donor['blood_type']); ?></p>
                        <p>Organ: <?php echo htmlspecialchars($donor['organ_type']); ?></p>
                        <p>Hospital: <?php echo htmlspecialchars($donor['hospital_name']); ?></p>
                        <button class="btn btn-primary find-matches-btn" 
                                data-donor-id="<?php echo $donor['id']; ?>">
                            Find Matches
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Potential Matches -->
            <div class="card matches-list">
                <h2>Potential Matches</h2>
                <div id="matchesList">
                    <?php if (empty($potentialMatches)): ?>
                    <p class="no-matches">Select a donor to find potential matches</p>
                    <?php else: ?>
                        <?php foreach ($potentialMatches as $match): ?>
                        <div class="match-item">
                            <div class="match-details">
                                <h3><?php echo htmlspecialchars($match['name']); ?></h3>
                                <p>Blood Type: <?php echo htmlspecialchars($match['blood_type']); ?></p>
                                <p>Urgency: <?php echo htmlspecialchars($match['urgency_level']); ?></p>
                                <p>Waiting Since: <?php echo date('M d, Y', strtotime($match['registration_date'])); ?></p>
                                <p>Hospital: <?php echo htmlspecialchars($match['hospital_name']); ?></p>
                            </div>
                            <div class="match-actions">
                                <button class="btn btn-primary confirm-match-btn" 
                                        data-recipient-id="<?php echo $match['id']; ?>"
                                        data-donor-id="<?php echo $_GET['donor_id']; ?>">
                                    Confirm Match
                                </button>
                                <button class="btn btn-outline view-details-btn"
                                        data-recipient-id="<?php echo $match['id']; ?>">
                                    View Details
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Match Details Modal -->
        <div id="matchDetailsModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Match Details</h2>
                <div class="match-comparison">
                    <div class="donor-details">
                        <h3>Donor Information</h3>
                        <div id="donorDetails"></div>
                    </div>
                    <div class="recipient-details">
                        <h3>Recipient Information</h3>
                        <div id="recipientDetails"></div>
                    </div>
                </div>
                <div class="compatibility-score">
                    <h3>Compatibility Score</h3>
                    <div class="score-meter">
                        <div class="score-bar"></div>
                        <span class="score-value"></span>
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-primary confirm-match-btn">Confirm Match</button>
                    <button class="btn btn-outline close-modal-btn">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/organ-matching.js"></script>
</body>
</html>
