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
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .match-container {
            padding: 2rem;
            max-width: 1000px;
            width: 95%;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .match-section {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .match-column {
            flex: 1;
            min-width: 280px;
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

        @media screen and (max-width: 768px) {
            .match-container {
                padding: 1rem;
                margin: 1rem auto;
            }
            
            .match-section {
                gap: 1rem;
            }

            .match-column {
                flex: 0 0 100%;
                max-width: 100%;
            }
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
                            Choose Donors
                        </button>
                        <div id="donorInfo" class="selected-info">
                            <div id="donorDetails"></div>
                        </div>
                    </div>
                    <div class="match-column">
                        <button class="match-button recipient" onclick="navigateToChoose('recipient')">
                            <i class="fas fa-user"></i>
                            Choose Recipients
                        </button>
                        <div id="recipientInfo" class="selected-info">
                            <div id="recipientDetails"></div>
                        </div>
                    </div>
                </div>

                <button id="makeMatchBtn" class="make-match-btn" onclick="makeMatch()" disabled>
                    Make Match
                </button>
            </div>
        </main>
    </div>

    <script>
        function navigateToChoose(type) {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const currentDonor = urlParams.get('donor');
            const currentRecipient = urlParams.get('recipient');

            // Store current selections
            if (currentDonor) {
                sessionStorage.setItem('selectedDonor', currentDonor);
            }
            if (currentRecipient) {
                sessionStorage.setItem('selectedRecipient', currentRecipient);
            }

            // Navigate to appropriate page
            window.location.href = type === 'donor' ? 'choose_donors_for_matches.php' : 'choose_recipients_for_matches.php';
        }

        function updateMatchButton() {
            const donorData = sessionStorage.getItem('selectedDonor');
            const recipientData = sessionStorage.getItem('selectedRecipient');
            const makeMatchBtn = document.getElementById('makeMatchBtn');

            if (donorData && recipientData) {
                makeMatchBtn.disabled = false;
                makeMatchBtn.classList.add('active');
            } else {
                makeMatchBtn.disabled = true;
                makeMatchBtn.classList.remove('active');
            }
        }

        function displaySelectedDonor() {
            const donorInfo = document.getElementById('donorInfo');
            const donorDetails = document.getElementById('donorDetails');
            const donorData = sessionStorage.getItem('selectedDonor');

            if (donorData) {
                const donor = JSON.parse(donorData);
                donorInfo.classList.add('show');
                donorDetails.innerHTML = `
                    <div class="info-card">
                        <p><strong>Name:</strong> ${donor.name}</p>
                        <p><strong>Blood Type:</strong> ${donor.bloodType}</p>
                        <p><strong>Organs:</strong> ${donor.organs}</p>
                        <p><strong>Hospital:</strong> ${donor.hospital}</p>
                        <button class="remove-btn" onclick="removeDonorSelection()">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                `;
            } else {
                donorInfo.classList.remove('show');
                donorDetails.innerHTML = `
                    <div class="no-selection">No donor selected</div>
                `;
            }
            updateMatchButton();
        }

        function displaySelectedRecipient() {
            const recipientInfo = document.getElementById('recipientInfo');
            const recipientDetails = document.getElementById('recipientDetails');
            const recipientData = sessionStorage.getItem('selectedRecipient');

            if (recipientData) {
                const recipient = JSON.parse(recipientData);
                recipientInfo.classList.add('show');
                recipientDetails.innerHTML = `
                    <div class="info-card">
                        <p><strong>Name:</strong> ${recipient.name}</p>
                        <p><strong>Blood Type:</strong> ${recipient.bloodType}</p>
                        <p><strong>Organ:</strong> ${recipient.organ}</p>
                        <p><strong>Urgency:</strong> ${recipient.urgency}</p>
                        <p><strong>Hospital:</strong> ${recipient.hospital}</p>
                        <button class="remove-btn" onclick="removeRecipientSelection()">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                `;
            } else {
                recipientInfo.classList.remove('show');
                recipientDetails.innerHTML = `
                    <div class="no-selection">No recipient selected</div>
                `;
            }
            updateMatchButton();
        }

        function removeDonorSelection() {
            sessionStorage.removeItem('selectedDonor');
            displaySelectedDonor();
        }

        function removeRecipientSelection() {
            sessionStorage.removeItem('selectedRecipient');
            displaySelectedRecipient();
        }

        function makeMatch() {
            const donorData = JSON.parse(sessionStorage.getItem('selectedDonor'));
            const recipientData = JSON.parse(sessionStorage.getItem('selectedRecipient'));

            if (!donorData || !recipientData) {
                alert('Please select both a donor and a recipient to make a match.');
                return;
            }

            if (confirm('Are you sure you want to create this match?')) {
                // Make API call to create match using the correct endpoint
                fetch('../../backend/php/organ_matches.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        donor_id: donorData.id,
                        recipient_id: recipientData.id,
                        match_made_by: <?php echo $hospital_id; ?>,
                        donor_hospital_id: donorData.hospital === '<?php echo $hospital_name; ?>' ? <?php echo $hospital_id; ?> : null,
                        recipient_hospital_id: recipientData.hospital === '<?php echo $hospital_name; ?>' ? <?php echo $hospital_id; ?> : null,
                        organ_type: donorData.organs
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.match_id) {
                        alert('Match created successfully!');
                        // Clear selections
                        sessionStorage.removeItem('selectedDonor');
                        sessionStorage.removeItem('selectedRecipient');
                        // Refresh displays
                        displaySelectedDonor();
                        displaySelectedRecipient();
                        // Redirect to matches page
                        window.location.href = 'my_matches.php';
                    } else {
                        throw new Error(data.error || 'Failed to create match');
                    }
                })
                .catch(error => {
                    alert('Error creating match: ' + error.message);
                });
            }
        }

        // Initialize displays when page loads
        displaySelectedDonor();
        displaySelectedRecipient();
    </script>
</body>
</html>
