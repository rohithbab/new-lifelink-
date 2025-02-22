<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in as recipient
if (!isset($_SESSION['is_recipient']) || !$_SESSION['is_recipient']) {
    header("Location: ../recipient_login.php");
    exit();
}

// Check if recipient_id is set in session
if (!isset($_SESSION['recipient_id'])) {
    die("Error: Recipient ID not found in session. Please login again.");
}

// Get recipient details
$recipient_id = $_SESSION['recipient_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM recipient_registration WHERE id = :recipient_id");
    $stmt->execute([':recipient_id' => $recipient_id]);
    $recipient = $stmt->fetch();

    if (!$recipient) {
        error_log("No recipient found with ID: " . $recipient_id);
        die("Recipient not found");
    }
} catch(PDOException $e) {
    error_log("Error fetching recipient details: " . $e->getMessage());
    die("An error occurred while fetching your details");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Details - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/recipient-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/donor-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php require_once 'includes/sidebar_for_recipients_dashboard.php'; ?>

        <main class="main-content">
            <div class="details-container">
                <!-- Header -->
                <div class="details-header">
                    <h1>Personal Details</h1>
                    <a href="recipient_dashboard.php" class="back-btn">
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
                            <p><?php echo htmlspecialchars($recipient['full_name'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Date of Birth</label>
                            <p><?php echo htmlspecialchars($recipient['date_of_birth'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Gender</label>
                            <p><?php echo htmlspecialchars($recipient['gender'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Blood Type</label>
                            <p><?php echo htmlspecialchars($recipient['blood_type'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($recipient['email'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Phone</label>
                            <p><?php echo htmlspecialchars($recipient['phone_number'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item full-width">
                            <label>Address</label>
                            <p><?php echo htmlspecialchars($recipient['address'] ?? 'Not provided'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Medical Information -->
                <div class="details-section">
                    <h2><i class="fas fa-heartbeat"></i> Medical Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Organ Required</label>
                            <p><?php echo htmlspecialchars($recipient['organ_required'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Urgency Level</label>
                            <p class="urgency-<?php echo strtolower($recipient['urgency_level'] ?? 'normal'); ?>">
                                <?php echo htmlspecialchars($recipient['urgency_level'] ?? 'Not provided'); ?>
                            </p>
                        </div>
                        <div class="info-item full-width">
                            <label>Medical Condition</label>
                            <p><?php echo htmlspecialchars($recipient['medical_condition'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item full-width">
                            <label>Reason for Organ Requirement</label>
                            <p><?php echo htmlspecialchars($recipient['organ_reason'] ?? 'Not provided'); ?></p>
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
                                <h3>ID Document (<?php echo htmlspecialchars($recipient['id_proof_type'] ?? 'Not specified'); ?>)</h3>
                                <?php if (!empty($recipient['id_document'])): ?>
                                    <?php $id_path = "/LIFELINKFORPDD-main/LIFELINKFORPDD/uploads/recipient_registration/id_document/" . $recipient['id_document']; ?>
                                    <a href="javascript:void(0);" onclick="openInNewWindow('<?php echo $id_path; ?>')" class="view-btn">
                                        <i class="fas fa-eye"></i> View Document
                                    </a>
                                    <p class="file-name"><?php echo basename($recipient['id_document']); ?></p>
                                    <p class="id-number">ID Number: <?php echo htmlspecialchars($recipient['id_proof_number']); ?></p>
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
                                <?php if (!empty($recipient['recipient_medical_reports'])): ?>
                                    <?php $medical_path = "/LIFELINKFORPDD-main/LIFELINKFORPDD/uploads/recipient_registration/recipient_medical_reports/" . $recipient['recipient_medical_reports']; ?>
                                    <a href="javascript:void(0);" onclick="openInNewWindow('<?php echo $medical_path; ?>')" class="view-btn">
                                        <i class="fas fa-eye"></i> View Document
                                    </a>
                                    <p class="file-name"><?php echo basename($recipient['recipient_medical_reports']); ?></p>
                                <?php else: ?>
                                    <p class="no-doc">No document uploaded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Section -->
                <div class="details-section">
                    <h2><i class="fas fa-info-circle"></i> Registration Status</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>ODML ID</label>
                            <p><?php echo htmlspecialchars($recipient['odml_id'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Request Status</label>
                            <p class="status-<?php echo strtolower($recipient['request_status'] ?? 'pending'); ?>">
                                <?php echo htmlspecialchars($recipient['request_status'] ?? 'Pending'); ?>
                            </p>
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

            <script>
                function openInNewWindow(path) {
                    window.open(path, '_blank', 'width=800,height=600');
                }

                // Modal functionality
                const modal = document.getElementById('editRequestModal');
                const btn = document.getElementById('editRequestBtn');
                const span = document.getElementsByClassName('close')[0];

                btn.onclick = function() {
                    modal.style.display = "block";
                }

                span.onclick = function() {
                    modal.style.display = "none";
                }

                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                }

                // Handle form submission
                $('#editRequestForm').on('submit', function(e) {
                    e.preventDefault();
                    // Add your form submission logic here
                    alert('Your edit request has been submitted. We will review it shortly.');
                    modal.style.display = "none";
                });
            </script>
        </main>
    </div>
</body>
</html>
