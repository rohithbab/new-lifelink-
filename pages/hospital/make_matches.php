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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Matches - LifeLink</title>
    <link rel="stylesheet" href="../../assets/css/hospital-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .match-container {
            padding: 2rem;
            max-width: 1000px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .match-section {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .match-column {
            flex: 1;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .match-button {
            width: 100%;
            padding: 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            color: white;
        }

        .match-button.donor {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
        }

        .match-button.recipient {
            background: linear-gradient(135deg, var(--primary-green), var(--primary-blue));
        }

        .match-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .match-button i {
            font-size: 1.5rem;
        }

        .make-match-btn {
            display: block;
            width: 200px;
            margin: 2rem auto;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            opacity: 0.5;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .make-match-btn.active {
            opacity: 1;
            pointer-events: auto;
        }

        .make-match-btn.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .selected-info {
            width: 100%;
            display: none;
            margin-top: 1rem;
        }

        .selected-info.show {
            display: block;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid var(--primary-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .info-card p {
            margin: 0.5rem 0;
            color: #333;
            font-size: 0.95rem;
        }

        .info-card strong {
            color: var(--primary-blue);
            display: inline-block;
            width: 100px;
        }

        .remove-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.3rem 0.6rem;
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .remove-btn:hover {
            background: #cc0000;
            transform: translateY(-1px);
        }

        .remove-btn i {
            font-size: 0.8rem;
        }

        .no-selection {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .modal-btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-btn.confirm {
            background: #ff4444;
            color: white;
        }

        .modal-btn.cancel {
            background: #eee;
            color: #333;
        }

        .modal-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Enhanced Modal Styles */
        .modal-content {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-content h2 {
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .match-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
            border: 1px solid #e9ecef;
        }

        .match-details .donor-section,
        .match-details .recipient-section {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .match-details .donor-section {
            background: rgba(0, 123, 255, 0.1);
            border-left: 4px solid var(--primary-blue);
        }

        .match-details .recipient-section {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid var(--primary-green);
        }

        .match-details h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .match-details h3 i {
            color: var(--primary-blue);
        }

        .match-details p {
            margin: 0.5rem 0;
            color: #555;
            font-size: 1rem;
            display: flex;
            align-items: center;
        }

        .match-details strong {
            min-width: 120px;
            color: #333;
        }

        .warning {
            color: #dc3545;
            font-size: 0.9rem;
            margin: 1rem 0;
            padding: 0.5rem;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .modal-buttons button {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .modal-buttons .primary-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
        }

        .modal-buttons .secondary-btn {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }

        .modal-buttons button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-buttons .primary-btn:hover {
            background: linear-gradient(135deg, var(--primary-green), var(--primary-blue));
        }

        .modal-buttons .secondary-btn:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../../includes/hospital_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Make Matches</h1>
                </div>
            </div>

            <div class="match-container">
                <div class="match-section">
                    <div class="match-column">
                        <button class="match-button donor" onclick="navigateToChoose('donor')">
                            <i class="fas fa-user"></i>
                            Choose Donor
                        </button>
                        <div id="donorInfo" class="selected-info">
                            <h3>Selected Donor</h3>
                            <div id="donorDetails">No donor selected</div>
                        </div>
                    </div>
                    <div class="match-column">
                        <button class="match-button recipient" onclick="navigateToChoose('recipient')">
                            <i class="fas fa-procedures"></i>
                            Choose Recipient
                        </button>
                        <div id="recipientInfo" class="selected-info">
                            <h3>Selected Recipient</h3>
                            <div id="recipientDetails">No recipient selected</div>
                        </div>
                    </div>
                </div>

                <button id="makeMatchBtn" class="make-match-btn" disabled onclick="makeMatch()">
                    Make Match
                </button>
            </div>
        </main>
    </div>

    <div class="modal" id="removeConfirmationModal">
        <div class="modal-content">
            <h2>Confirm Removal</h2>
            <p>Are you sure you want to remove the selected <span id="remove-type"></span>?</p>
            <div class="modal-buttons">
                <button class="modal-btn confirm" onclick="confirmRemove()">Yes, Remove</button>
                <button class="modal-btn cancel" onclick="cancelRemove()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function navigateToChoose(type) {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const currentDonor = urlParams.get('donor');
            const currentRecipient = urlParams.get('recipient');
            
            // Build URL based on type
            let url;
            if (type === 'donor') {
                url = 'choose_donors_for_matches.php';
                if (currentRecipient) {
                    url += '?recipient=' + encodeURIComponent(currentRecipient);
                }
            } else {
                url = 'choose_recipients_for_matches.php';
                if (currentDonor) {
                    url += '?donor=' + encodeURIComponent(currentDonor);
                }
            }
            
            window.location.href = url;
        }

        // Check URL parameters and session storage for selections
        window.onload = function() {
            // Get stored donor info
            const storedDonor = sessionStorage.getItem('selectedDonor');
            if (storedDonor) {
                const donorInfo = JSON.parse(storedDonor);
                document.getElementById('donorInfo').classList.add('show');
                document.getElementById('donorDetails').innerHTML = `
                    <div class="info-card">
                        <button class="remove-btn" onclick="showRemoveConfirmation('Donor')">
                            <i class="fas fa-times"></i> Remove
                        </button>
                        <p><strong>Name:</strong> ${donorInfo.name}</p>
                        <p><strong>Blood Group:</strong> ${donorInfo.bloodGroup}</p>
                        <p><strong>Organ Type:</strong> ${donorInfo.organType}</p>
                    </div>
                `;
            } else {
                document.getElementById('donorDetails').innerHTML = `
                    <div class="no-selection">No donor selected</div>
                `;
            }

            // Get stored recipient info
            const storedRecipient = sessionStorage.getItem('selectedRecipient');
            if (storedRecipient) {
                const recipientInfo = JSON.parse(storedRecipient);
                document.getElementById('recipientInfo').classList.add('show');
                document.getElementById('recipientDetails').innerHTML = `
                    <div class="info-card">
                        <button class="remove-btn" onclick="showRemoveConfirmation('Recipient')">
                            <i class="fas fa-times"></i> Remove
                        </button>
                        <p><strong>Name:</strong> ${recipientInfo.name}</p>
                        <p><strong>Blood Group:</strong> ${recipientInfo.bloodGroup}</p>
                        <p><strong>Required Organ:</strong> ${recipientInfo.requiredOrgan}</p>
                    </div>
                `;
            } else {
                document.getElementById('recipientDetails').innerHTML = `
                    <div class="no-selection">No recipient selected</div>
                `;
            }

            updateMatchButton();
        }

        function updateMatchButton() {
            const makeMatchBtn = document.getElementById('makeMatchBtn');
            const hasDonor = sessionStorage.getItem('selectedDonor');
            const hasRecipient = sessionStorage.getItem('selectedRecipient');
            
            if (hasDonor && hasRecipient) {
                makeMatchBtn.classList.add('active');
                makeMatchBtn.disabled = false;
            } else {
                makeMatchBtn.classList.remove('active');
                makeMatchBtn.disabled = true;
            }
        }

        function showRemoveConfirmation(type) {
            const modal = document.getElementById('removeConfirmationModal');
            modal.classList.add('show');
            document.getElementById('remove-type').innerText = type;
        }

        function confirmRemove() {
            const modal = document.getElementById('removeConfirmationModal');
            modal.classList.remove('show');
            const removeType = document.getElementById('remove-type').innerText;
            if (removeType === 'Donor') {
                removeDonorSelection();
            } else {
                removeRecipientSelection();
            }
        }

        function cancelRemove() {
            const modal = document.getElementById('removeConfirmationModal');
            modal.classList.remove('show');
        }

        function removeDonorSelection() {
            sessionStorage.removeItem('selectedDonor');
            document.getElementById('donorInfo').classList.remove('show');
            document.getElementById('donorDetails').innerHTML = `
                <div class="no-selection">No donor selected</div>
            `;
            updateMatchButton();
        }

        function removeRecipientSelection() {
            sessionStorage.removeItem('selectedRecipient');
            document.getElementById('recipientInfo').classList.remove('show');
            document.getElementById('recipientDetails').innerHTML = `
                <div class="no-selection">No recipient selected</div>
            `;
            updateMatchButton();
        }

        function makeMatch() {
            const donorInfo = JSON.parse(sessionStorage.getItem('selectedDonor'));
            const recipientInfo = JSON.parse(sessionStorage.getItem('selectedRecipient'));

            if (!donorInfo || !recipientInfo) {
                alert('Please select both a donor and recipient first.');
                return;
            }

            // Create match data
            const matchData = {
                donor_id: donorInfo.id,
                recipient_id: recipientInfo.id,
                match_made_by: <?php echo $hospital_id; ?>,
                donor_hospital_id: donorInfo.from_hospital === '<?php echo $hospital_name; ?>' ? <?php echo $hospital_id; ?> : null,
                recipient_hospital_id: recipientInfo.from_hospital === '<?php echo $hospital_name; ?>' ? <?php echo $hospital_id; ?> : null,
                organ_type: donorInfo.organType
            };

            // Send to backend
            fetch('../../backend/php/organ_matches.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(matchData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.match_id) {
                    // Clear selections
                    sessionStorage.removeItem('selectedDonor');
                    sessionStorage.removeItem('selectedRecipient');
                    
                    // Show success message
                    showSuccessModal(donorInfo.name, recipientInfo.name);
                    
                    // Redirect to my matches after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'my_matches.php';
                    }, 2000);
                } else {
                    alert('Failed to create match. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create match. Please try again.');
            });
        }

        function showSuccessModal(donorName, recipientName) {
            const modalHtml = `
                <div id="successModal" class="modal show">
                    <div class="modal-content">
                        <h2>Match Created Successfully!</h2>
                        <p>Donor: ${donorName}</p>
                        <p>Recipient: ${recipientName}</p>
                        <p>Redirecting to My Matches...</p>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
    </script>
</body>
</html>
