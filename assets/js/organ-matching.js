document.addEventListener('DOMContentLoaded', function() {
    // Initialize search and filter functionality
    initializeSearchAndFilter();
    
    // Initialize match finding buttons
    initializeMatchButtons();
    
    // Initialize modal functionality
    initializeModal();
});

function initializeSearchAndFilter() {
    const donorSearch = document.getElementById('donorSearch');
    const organFilter = document.getElementById('organFilter');
    const donorItems = document.querySelectorAll('.donor-item');

    // Search functionality
    donorSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        filterDonors(searchTerm, organFilter.value);
    });

    // Organ filter functionality
    organFilter.addEventListener('change', function() {
        const searchTerm = donorSearch.value.toLowerCase();
        filterDonors(searchTerm, this.value);
    });

    function filterDonors(searchTerm, organType) {
        donorItems.forEach(item => {
            const name = item.querySelector('h3').textContent.toLowerCase();
            const organ = item.dataset.organ.toLowerCase();
            const matchesSearch = name.includes(searchTerm);
            const matchesOrgan = !organType || organ === organType.toLowerCase();
            
            item.style.display = matchesSearch && matchesOrgan ? 'block' : 'none';
        });
    }
}

function initializeMatchButtons() {
    // Find matches buttons
    document.querySelectorAll('.find-matches-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const donorId = this.dataset.donorId;
            try {
                const response = await fetch(`../../backend/php/find_matches.php?donor_id=${donorId}`);
                const data = await response.json();
                
                if (data.success) {
                    updateMatchesList(data.matches, donorId);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error finding matches', 'error');
            }
        });
    });

    // Confirm match buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('confirm-match-btn')) {
            const donorId = e.target.dataset.donorId;
            const recipientId = e.target.dataset.recipientId;
            confirmMatch(donorId, recipientId);
        }
    });
}

function initializeModal() {
    const modal = document.getElementById('matchDetailsModal');
    const closeBtn = modal.querySelector('.close');
    const closeModalBtn = modal.querySelector('.close-modal-btn');

    // View details buttons
    document.addEventListener('click', async function(e) {
        if (e.target.classList.contains('view-details-btn')) {
            const recipientId = e.target.dataset.recipientId;
            const donorId = document.querySelector('.find-matches-btn').dataset.donorId;
            await showMatchDetails(donorId, recipientId);
        }
    });

    // Close modal
    [closeBtn, closeModalBtn].forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
}

async function showMatchDetails(donorId, recipientId) {
    try {
        const response = await fetch(`../../backend/php/get_match_details.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ donor_id: donorId, recipient_id: recipientId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const modal = document.getElementById('matchDetailsModal');
            
            // Update donor details
            document.getElementById('donorDetails').innerHTML = `
                <p><strong>Name:</strong> ${data.donor.name}</p>
                <p><strong>Age:</strong> ${data.donor.age}</p>
                <p><strong>Blood Type:</strong> ${data.donor.blood_type}</p>
                <p><strong>Organ:</strong> ${data.donor.organ_type}</p>
                <p><strong>Hospital:</strong> ${data.donor.hospital_name}</p>
            `;
            
            // Update recipient details
            document.getElementById('recipientDetails').innerHTML = `
                <p><strong>Name:</strong> ${data.recipient.name}</p>
                <p><strong>Age:</strong> ${data.recipient.age}</p>
                <p><strong>Blood Type:</strong> ${data.recipient.blood_type}</p>
                <p><strong>Urgency:</strong> ${data.recipient.urgency_level}</p>
                <p><strong>Hospital:</strong> ${data.recipient.hospital_name}</p>
            `;
            
            // Update compatibility score
            const scoreBar = modal.querySelector('.score-bar');
            const scoreValue = modal.querySelector('.score-value');
            scoreBar.style.width = `${data.compatibility_score}%`;
            scoreValue.textContent = `${data.compatibility_score}%`;
            
            // Update confirm button
            const confirmBtn = modal.querySelector('.confirm-match-btn');
            confirmBtn.dataset.donorId = donorId;
            confirmBtn.dataset.recipientId = recipientId;
            
            modal.style.display = 'block';
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error getting match details', 'error');
    }
}

async function confirmMatch(donorId, recipientId) {
    try {
        const response = await fetch('../../backend/php/confirm_match.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                donor_id: donorId,
                recipient_id: recipientId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Match confirmed successfully', 'success');
            // Remove the matched donor and update matches list
            document.querySelector(`[data-donor-id="${donorId}"]`).closest('.donor-item').remove();
            document.getElementById('matchesList').innerHTML = '<p class="no-matches">Select a donor to find potential matches</p>';
            // Close modal if open
            document.getElementById('matchDetailsModal').style.display = 'none';
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error confirming match', 'error');
    }
}

function updateMatchesList(matches, donorId) {
    const matchesList = document.getElementById('matchesList');
    
    if (matches.length === 0) {
        matchesList.innerHTML = '<p class="no-matches">No potential matches found</p>';
        return;
    }
    
    matchesList.innerHTML = matches.map(match => `
        <div class="match-item">
            <div class="match-details">
                <h3>${match.name}</h3>
                <p>Blood Type: ${match.blood_type}</p>
                <p>Urgency: ${match.urgency_level}</p>
                <p>Waiting Since: ${new Date(match.registration_date).toLocaleDateString()}</p>
                <p>Hospital: ${match.hospital_name}</p>
            </div>
            <div class="match-actions">
                <button class="btn btn-primary confirm-match-btn" 
                        data-recipient-id="${match.id}"
                        data-donor-id="${donorId}">
                    Confirm Match
                </button>
                <button class="btn btn-outline view-details-btn"
                        data-recipient-id="${match.id}">
                    View Details
                </button>
            </div>
        </div>
    `).join('');
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
