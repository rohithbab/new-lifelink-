// Donors Management JavaScript

// Load donors section content
function loadDonorsSection() {
    const donorsSection = document.getElementById('donors');
    donorsSection.innerHTML = `
        <div class="section-header">
            <h2>Donor Management</h2>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddDonorModal()">
                    <i class="fas fa-plus"></i> Add New Donor
                </button>
            </div>
        </div>

        <div class="filters-section">
            <div class="search-box">
                <input type="text" id="donorSearch" placeholder="Search donors...">
                <i class="fas fa-search"></i>
            </div>
            <div class="filter-options">
                <select id="organFilter">
                    <option value="">All Organs</option>
                    <option value="kidney">Kidney</option>
                    <option value="liver">Liver</option>
                    <option value="heart">Heart</option>
                    <option value="lungs">Lungs</option>
                    <option value="pancreas">Pancreas</option>
                </select>
                <select id="statusFilter">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>

        <div class="donors-table-container">
            <table class="data-table" id="donorsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Organ</th>
                        <th>Blood Type</th>
                        <th>Age</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="donorsTableBody">
                    <!-- Donor rows will be inserted here -->
                </tbody>
            </table>
        </div>

        <!-- Add Donor Modal -->
        <div class="modal" id="addDonorModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add New Donor</h3>
                    <button class="close-btn" onclick="closeAddDonorModal()">×</button>
                </div>
                <form id="addDonorForm" onsubmit="handleAddDonor(event)">
                    <div class="form-group">
                        <label for="donorName">Full Name</label>
                        <input type="text" id="donorName" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="donorAge">Age</label>
                            <input type="number" id="donorAge" required min="18" max="70">
                        </div>
                        <div class="form-group">
                            <label for="donorBloodType">Blood Type</label>
                            <select id="donorBloodType" required>
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
                    <div class="form-group">
                        <label for="donorOrgan">Organ for Donation</label>
                        <select id="donorOrgan" required>
                            <option value="">Select Organ</option>
                            <option value="kidney">Kidney</option>
                            <option value="liver">Liver</option>
                            <option value="heart">Heart</option>
                            <option value="lungs">Lungs</option>
                            <option value="pancreas">Pancreas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="donorContact">Contact Number</label>
                        <input type="tel" id="donorContact" required>
                    </div>
                    <div class="form-group">
                        <label for="donorEmail">Email</label>
                        <input type="email" id="donorEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="donorAddress">Address</label>
                        <textarea id="donorAddress" required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeAddDonorModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Donor</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Initialize donors table
    loadDonorsData();
    
    // Set up event listeners
    setupDonorsEventListeners();
}

// Load donors data
function loadDonorsData() {
    fetch('../../backend/php/fetch_donors.php')
        .then(response => response.json())
        .then(data => {
            updateDonorsTable(data);
        })
        .catch(error => {
            console.error('Error fetching donors:', error);
            showNotification('Error loading donors data', 'error');
        });
}

// Update donors table
function updateDonorsTable(donors) {
    const tbody = document.getElementById('donorsTableBody');
    tbody.innerHTML = '';

    donors.forEach(donor => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${donor.name}</td>
            <td>${donor.organ}</td>
            <td>${donor.blood_type}</td>
            <td>${donor.age}</td>
            <td>${donor.contact}</td>
            <td>
                <span class="status-badge ${donor.status}">
                    ${donor.status.charAt(0).toUpperCase() + donor.status.slice(1)}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon" onclick="viewDonorDetails(${donor.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon" onclick="editDonor(${donor.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${donor.status === 'pending' ? `
                        <button class="btn-icon approve" onclick="approveDonor(${donor.id})" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-icon reject" onclick="rejectDonor(${donor.id})" title="Reject">
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Setup donors event listeners
function setupDonorsEventListeners() {
    const searchInput = document.getElementById('donorSearch');
    const organFilter = document.getElementById('organFilter');
    const statusFilter = document.getElementById('statusFilter');

    searchInput.addEventListener('input', filterDonors);
    organFilter.addEventListener('change', filterDonors);
    statusFilter.addEventListener('change', filterDonors);
}

// Filter donors
function filterDonors() {
    const searchTerm = document.getElementById('donorSearch').value.toLowerCase();
    const organFilter = document.getElementById('organFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    fetch(`../../backend/php/fetch_donors.php?search=${searchTerm}&organ=${organFilter}&status=${statusFilter}`)
        .then(response => response.json())
        .then(data => {
            updateDonorsTable(data);
        })
        .catch(error => {
            console.error('Error filtering donors:', error);
            showNotification('Error filtering donors', 'error');
        });
}

// Modal functions
function openAddDonorModal() {
    document.getElementById('addDonorModal').classList.add('active');
}

function closeAddDonorModal() {
    document.getElementById('addDonorModal').classList.remove('active');
    document.getElementById('addDonorForm').reset();
}

// Handle add donor form submission
function handleAddDonor(event) {
    event.preventDefault();

    const formData = {
        name: document.getElementById('donorName').value,
        age: document.getElementById('donorAge').value,
        blood_type: document.getElementById('donorBloodType').value,
        organ: document.getElementById('donorOrgan').value,
        contact: document.getElementById('donorContact').value,
        email: document.getElementById('donorEmail').value,
        address: document.getElementById('donorAddress').value
    };

    fetch('../../backend/php/add_donor.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Donor added successfully', 'success');
            closeAddDonorModal();
            loadDonorsData();
        } else {
            showNotification(data.error || 'Error adding donor', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding donor:', error);
        showNotification('Error adding donor', 'error');
    });
}

// Donor actions
function viewDonorDetails(donorId) {
    // Implement view donor details logic
}

function editDonor(donorId) {
    // Implement edit donor logic
}

function approveDonor(donorId) {
    updateDonorStatus(donorId, 'approved');
}

function rejectDonor(donorId) {
    updateDonorStatus(donorId, 'rejected');
}

function updateDonorStatus(donorId, status) {
    fetch('../../backend/php/update_donor_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ donorId, status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Donor ${status} successfully`, 'success');
            loadDonorsData();
        } else {
            showNotification(data.error || `Error updating donor status`, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating donor status:', error);
        showNotification('Error updating donor status', 'error');
    });
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
