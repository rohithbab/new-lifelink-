<?php
session_start();
require_once '../../config/db_connect.php';

// Add debug output to see the actual file paths
function debug_file_exists($path) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $path;
    return file_exists($full_path) ? "File exists" : "File not found";
}

// Check if user is logged in as donor
if (!isset($_SESSION['is_donor']) || !$_SESSION['is_donor']) {
    header("Location: ../donor_login.php");
    exit();
}

// Check if donor_id is set in session
if (!isset($_SESSION['donor_id'])) {
    die("Error: Donor ID not found in session. Please login again.");
}

// Get donor details
$donor_id = $_SESSION['donor_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM donor WHERE donor_id = :donor_id");
    $stmt->execute([':donor_id' => $donor_id]);
    $donor = $stmt->fetch();

    if (!$donor) {
        error_log("No donor found with ID: " . $donor_id);
        die("Donor not found");
    }
} catch(PDOException $e) {
    error_log("Error fetching donor details: " . $e->getMessage());
    die("An error occurred while fetching your details");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Details - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/donor-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/donor-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="details-container">
        <!-- Header -->
        <div class="details-header">
            <h1>Personal Details</h1>
            <a href="donor_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Personal Information Section -->
        <div class="details-section">
            <h2><i class="fas fa-user"></i> Basic Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name</label>
                    <p><?php echo htmlspecialchars($donor['name'] ?? 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Date of Birth</label>
                    <p><?php echo htmlspecialchars($donor['dob'] ?? 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Gender</label>
                    <p><?php echo htmlspecialchars($donor['gender'] ?? 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Blood Group</label>
                    <p><?php echo htmlspecialchars($donor['blood_group'] ?? 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <p><?php echo htmlspecialchars($donor['email'] ?? 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <p><?php echo htmlspecialchars($donor['phone'] ?? 'Not provided'); ?></p>
                </div>
                <div class="info-item full-width">
                    <label>Address</label>
                    <p><?php echo htmlspecialchars($donor['address'] ?? 'Not provided'); ?></p>
                </div>
            </div>
        </div>

        <!-- Guardian Information -->
        <div class="details-section">
            <h2><i class="fas fa-users"></i> Guardian Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Guardian Name</label>
                    <p><?php echo htmlspecialchars($donor['guardian_name'] ?? 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Guardian Phone</label>
                    <p><?php echo htmlspecialchars($donor['guardian_phone'] ?? 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Relationship</label>
                    <p><?php echo htmlspecialchars($donor['guardian_relation'] ?? 'Not provided'); ?></p>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="details-section">
            <h2><i class="fas fa-file-alt"></i> Documents</h2>
            <div class="documents-grid">
                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="document-info">
                        <h3>ID Proof</h3>
                        <?php if (!empty($donor['id_proof_path'])): ?>
                            <?php $id_path = "/LIFELINKFORPDD-main/LIFELINKFORPDD/uploads/donors/id_proof_path/" . $donor['id_proof_path']; ?>
                            <a href="javascript:void(0);" onclick="openInNewWindow('<?php echo $id_path; ?>')" class="view-btn">
                                <i class="fas fa-eye"></i> View Document
                            </a>
                            <p class="file-name"><?php echo basename($donor['id_proof_path']); ?></p>
                        <?php else: ?>
                            <p class="no-doc">No document uploaded</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div class="document-info">
                        <h3>Medical Reports</h3>
                        <?php if (!empty($donor['medical_reports_path'])): ?>
                            <?php $medical_path = "/LIFELINKFORPDD-main/LIFELINKFORPDD/uploads/donors/medical_reports_path/" . $donor['medical_reports_path']; ?>
                            <a href="javascript:void(0);" onclick="openInNewWindow('<?php echo $medical_path; ?>')" class="view-btn">
                                <i class="fas fa-eye"></i> View Document
                            </a>
                            <p class="file-name"><?php echo basename($donor['medical_reports_path']); ?></p>
                        <?php else: ?>
                            <p class="no-doc">No document uploaded</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div class="document-info">
                        <h3>Guardian ID Proof</h3>
                        <?php if (!empty($donor['guardian_id_proof_path'])): ?>
                            <?php $guardian_path = "/LIFELINKFORPDD-main/LIFELINKFORPDD/uploads/donors/guardian_id_proof_path/" . $donor['guardian_id_proof_path']; ?>
                            <a href="javascript:void(0);" onclick="openInNewWindow('<?php echo $guardian_path; ?>')" class="view-btn">
                                <i class="fas fa-eye"></i> View Document
                            </a>
                            <p class="file-name"><?php echo basename($donor['guardian_id_proof_path']); ?></p>
                        <?php else: ?>
                            <p class="no-doc">No document uploaded</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Request Button -->
        <div class="edit-request-section">
            <button id="editRequestBtn" class="edit-request-btn">
                <i class="fas fa-edit"></i>
                Request to Edit Details
            </button>
        </div>
    </div>

    <!-- Edit Request Modal -->
    <div id="editRequestModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Request to Edit Details</h2>
            <form id="editRequestForm">
                <div class="form-group">
                    <label for="fieldsToEdit">What would you like to edit?</label>
                    <textarea id="fieldsToEdit" name="fieldsToEdit" rows="4" required 
                              placeholder="Please specify which details you want to update..."></textarea>
                </div>
                <div class="form-group">
                    <label for="reason">Reason for Edit</label>
                    <textarea id="reason" name="reason" rows="4" required
                              placeholder="Please provide the reason for requesting these changes..."></textarea>
                </div>
                <button type="submit" class="submit-btn">Submit Request</button>
            </form>
        </div>
    </div>

    <script src="../../assets/js/donor-details.js"></script>
    <script>
        $(document).ready(function() {
            // Handle document view clicks
            $('.view-btn').click(function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                window.open(url, '_blank', 'width=800,height=600');
            });
        });
    </script>
    <script>
        function openInNewWindow(url) {
            window.open(url, '_blank', 'width=800,height=600');
            return false;
        }
    </script>
</body>
</html>
