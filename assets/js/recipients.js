// Recipients Management JavaScript

// Load recipients section content
function loadRecipientsSection() {
    const recipientsSection = document.getElementById('recipients');
    recipientsSection.innerHTML = `
        <div class="section-header">
            <h2>Recipient Management</h2>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddRecipientModal()">
                    <i class="fas fa-plus"></i> Add New Recipient
                </button>
            </div>
        </div>

        <div class="filters-section">
            <div class="search-box">
                <input type="text" id="recipientSearch" placeholder="Search recipients...">
                <i class="fas fa-search"></i>
            </div>
            <div class="filter-options">
                <select id="organNeededFilter">
                    <option value="">All Organs</option>
                    <option value="kidney">Kidney</option>
                    <option value="liver">Liver</option>
                    <option value="heart">Heart</option>
                    <option value="lungs">Lungs</option>
                    <option value="pancreas">Pancreas</option>
                </select>
                <select id="urgencyFilter">
                    <option value="">All Urgency Levels</option>
                    <option value="critical">Critical</option>
                    <option value="urgent">Urgent</option>
                    <option value="normal">Normal</option>
                </select>
                <select id="statusFilter">
                    <option value="">All Status</option>
                    <option value="waiting">Waiting</option>
                    <option value="matched">Matched</option>
                    <option value="transplanted">Transplanted</option>
                </select>
            </div>
        </div>

        <div class="recipients-table-container">
            <table class="data-table" id="recipientsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Organ Needed</th>
                        <th>Blood Type</th>
                        <th>Age</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="recipientsTableBody">
                    <!-- Recipient rows will be inserted here -->
                </tbody>
            </table>
        </div>

        <!-- Add Recipient Modal -->
        <div class="modal" id="addRecipientModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add New Recipient</h3>
                    <button class="close-btn" onclick="closeAddRecipientModal()">×</button>
                </div>
                <form id="addRecipientForm" onsubmit="handleAddRecipient(event)">
                    <div class="form-group">
                        <label for="recipientName">Full Name</label>
                        <input type="text" id="recipientName" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="recipientAge">Age</label>
                            <input type="number" id="recipientAge" required min="0">
                        </div>
                        <div class="form-group">
                            <label for="recipientBloodType">Blood Type</label>
                            <select id="recipientBloodType" required>
                                <option value="">Select Blood Type</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="recipientOrgan">Organ Needed</label>
                            <select id="recipientOrgan" required>
                                <option value="">Select Organ</option>
                                <option value="kidney">Kidney</option>
                                <option value="liver">Liver</option>
                                <option value="heart">Heart</option>
                                <option value="lungs">Lungs</option>
                                <option value="pancreas">Pancreas</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="recipientUrgency">Urgency Level</label>
                            <select id="recipientUrgency" required>
                                <option value="">Select Urgency</option>
                                <option value="critical">Critical</option>
                                <option value="urgent">Urgent</option>
                                <option value="normal">Normal</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="recipientContact">Contact Number</label>
                        <input type="tel" id="recipientContact" required>
                    </div>
                    <div class="form-group">
                        <label for="recipientEmail">Email</label>
                        <input type="email" id="recipientEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="recipientAddress">Address</label>
                        <textarea id="recipientAddress" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="medicalHistory">Medical History</label>
                        <textarea id="medicalHistory" required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeAddRecipientModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Recipient</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Match Donor Modal -->
        <div class="modal" id="matchDonorModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Match Donor</h3>
                    <button class="close-btn" onclick="closeMatchDonorModal()">×</button>
                </div>
                <div class="compatible-donors-list">
                    <!-- Compatible donors will be listed here -->
                </div>
            </div>
        </div>
    `;

    // Initialize recipients table
    loadRecipientsData();
    
    // Set up event listeners
    setupRecipientsEventListeners();
}

// Load recipients data
function loadRecipientsData() {
    fetch('../../backend/php/fetch_recipients.php')
        .then(response => response.json())
        .then(data => {
            updateRecipientsTable(data);
        })
        .catch(error => {
            console.error('Error fetching recipients:', error);
            showNotification('Error loading recipients data', 'error');
        });
}

// Update recipients table
function updateRecipientsTable(recipients) {
    const tbody = document.getElementById('recipientsTableBody');
    tbody.innerHTML = '';

    recipients.forEach(recipient => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${recipient.name}</td>
            <td>${recipient.organ_needed}</td>
            <td>${recipient.blood_type}</td>
            <td>${recipient.age}</td>
            <td>
                <span class="urgency-badge ${recipient.urgency}">
                    ${recipient.urgency.charAt(0).toUpperCase() + recipient.urgency.slice(1)}
                </span>
            </td>
            <td>
                <span class="status-badge ${recipient.status}">
                    ${recipient.status.charAt(0).toUpperCase() + recipient.status.slice(1)}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon" onclick="viewRecipientDetails(${recipient.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon" onclick="editRecipient(${recipient.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${recipient.status === 'waiting' ? `
                        <button class="btn-icon match" onclick="openMatchDonorModal(${recipient.id})" title="Match Donor">
                            <i class="fas fa-link"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Setup recipients event listeners
function setupRecipientsEventListeners() {
    const searchInput = document.getElementById('recipientSearch');
    const organFilter = document.getElementById('organNeededFilter');
    const urgencyFilter = document.getElementById('urgencyFilter');
    const statusFilter = document.getElementById('statusFilter');

    searchInput.addEventListener('input', filterRecipients);
    organFilter.addEventListener('change', filterRecipients);
    urgencyFilter.addEventListener('change', filterRecipients);
    statusFilter.addEventListener('change', filterRecipients);
}

// Filter recipients
function filterRecipients() {
    const searchTerm = document.getElementById('recipientSearch').value.toLowerCase();
    const organFilter = document.getElementById('organNeededFilter').value;
    const urgencyFilter = document.getElementById('urgencyFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    fetch(`../../backend/php/fetch_recipients.php?search=${searchTerm}&organ=${organFilter}&urgency=${urgencyFilter}&status=${statusFilter}`)
        .then(response => response.json())
        .then(data => {
            updateRecipientsTable(data);
        })
        .catch(error => {
            console.error('Error filtering recipients:', error);
            showNotification('Error filtering recipients', 'error');
        });
}

// Modal functions
function openAddRecipientModal() {
    document.getElementById('addRecipientModal').classList.add('active');
}

function closeAddRecipientModal() {
    document.getElementById('addRecipientModal').classList.remove('active');
    document.getElementById('addRecipientForm').reset();
}

function openMatchDonorModal(recipientId) {
    const modal = document.getElementById('matchDonorModal');
    modal.classList.add('active');
    
    // Fetch compatible donors
    fetchCompatibleDonors(recipientId);
}

function closeMatchDonorModal() {
    document.getElementById('matchDonorModal').classList.remove('active');
}

// Handle add recipient form submission
function handleAddRecipient(event) {
    event.preventDefault();

    const formData = {
        name: document.getElementById('recipientName').value,
        age: document.getElementById('recipientAge').value,
        blood_type: document.getElementById('recipientBloodType').value,
        organ_needed: document.getElementById('recipientOrgan').value,
        urgency: document.getElementById('recipientUrgency').value,
        contact: document.getElementById('recipientContact').value,
        email: document.getElementById('recipientEmail').value,
        address: document.getElementById('recipientAddress').value,
        medical_history: document.getElementById('medicalHistory').value
    };

    fetch('../../backend/php/add_recipient.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Recipient added successfully', 'success');
            closeAddRecipientModal();
            loadRecipientsData();
        } else {
            showNotification(data.error || 'Error adding recipient', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding recipient:', error);
        showNotification('Error adding recipient', 'error');
    });
}

// Fetch compatible donors
function fetchCompatibleDonors(recipientId) {
    fetch(`../../backend/php/fetch_compatible_donors.php?recipient_id=${recipientId}`)
        .then(response => response.json())
        .then(data => {
            updateCompatibleDonorsList(data, recipientId);
        })
        .catch(error => {
            console.error('Error fetching compatible donors:', error);
            showNotification('Error fetching compatible donors', 'error');
        });
}

// Update compatible donors list
function updateCompatibleDonorsList(donors, recipientId) {
    const container = document.querySelector('.compatible-donors-list');
    
    if (donors.length === 0) {
        container.innerHTML = '<p class="no-results">No compatible donors found</p>';
        return;
    }

    container.innerHTML = `
        <div class="compatible-donors">
            ${donors.map(donor => `
                <div class="compatible-donor-card">
                    <h4>${donor.name}</h4>
                    <p>Blood Type: ${donor.blood_type}</p>
                    <p>Organ: ${donor.organ}</p>
                    <p>Age: ${donor.age}</p>
                    <button class="btn btn-primary" onclick="matchDonor(${recipientId}, ${donor.id})">
                        Select Donor
                    </button>
                </div>
            `).join('')}
        </div>
    `;
}

// Match donor to recipient
function matchDonor(recipientId, donorId) {
    fetch('../../backend/php/match_donor_recipient.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ recipientId, donorId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Donor matched successfully', 'success');
            closeMatchDonorModal();
            loadRecipientsData();
        } else {
            showNotification(data.error || 'Error matching donor', 'error');
        }
    })
    .catch(error => {
        console.error('Error matching donor:', error);
        showNotification('Error matching donor', 'error');
    });
}

// Recipient actions
function viewRecipientDetails(recipientId) {
    // Implement view recipient details logic
}

function editRecipient(recipientId) {
    // Implement edit recipient logic
}
