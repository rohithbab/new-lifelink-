<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Registration - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .registration-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .registration-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 2rem;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #eee;
            border-radius: 10px;
            background: #f9f9f9;
        }

        .section-title {
            font-size: 1.2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-green);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.1);
            outline: none;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload label {
            display: block;
            padding: 0.8rem;
            background: #f0f0f0;
            border: 1px dashed #ddd;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }

        .file-upload label:hover {
            background: #e0e0e0;
            border-color: var(--primary-blue);
        }

        .checkbox-group {
            margin: 1rem 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .checkbox-group label:hover {
            background: #f0f0f0;
            border-color: var(--primary-blue);
        }

        .checkbox-group input[type="checkbox"]:checked + span {
            color: var(--primary-green);
            font-weight: 500;
        }

        .submit-button {
            width: 100%;
            padding: 1rem;
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .submit-button:hover {
            background: var(--primary-blue);
            transform: translateY(-2px);
        }

        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 0.3rem;
            display: none;
        }

        .guardian-section {
            display: block;
        }

        .skip-guardian {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .skip-guardian:hover {
            background: #5a6268;
        }

        .policy-text {
            font-size: 0.9rem;
            color: #666;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                <span class="logo-life">LifeLink</span>
            </a>
            <div class="nav-links">
                <a href="../index.php" class="btn" style="
                    background: var(--primary-blue);
                    color: var(--white);
                    transition: all 0.3s ease;
                    border: 2px solid var(--primary-blue);
                    padding: 0.5rem 1rem;
                    font-size: 0.9rem;
                " onmouseover="
                    this.style.background='transparent';
                    this.style.color='var(--primary-blue)';
                " onmouseout="
                    this.style.background='var(--primary-blue)';
                    this.style.color='var(--white)';
                "><i class="fas fa-home"></i> Back to Home</a>
            </div>
        </div>
    </nav>

    <div class="registration-container">
        <h2 class="registration-title">Donor Registration</h2>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="server-error">
                    <i class="fas fa-exclamation-circle"></i>
                    ' . $_SESSION['error'] . '
                  </div>';
            unset($_SESSION['error']);
        }
        ?>

        <form action="/LIFELINKFORPDD-main/LIFELINKFORPDD/backend/php/donor_registration_process.php" method="POST" enctype="multipart/form-data" id="donorRegistrationForm">
            <!-- Personal Information -->
            <div class="form-section">
                <h3 class="section-title">Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullName">Full Name *</label>
                        <input type="text" id="fullName" name="fullName" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="dob">Date of Birth *</label>
                        <input type="date" id="dob" name="dob" required>
                    </div>
                    <div class="form-group">
                        <label for="bloodGroup">Blood Group *</label>
                        <select id="bloodGroup" name="bloodGroup" required>
                            <option value="">Select Blood Group</option>
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
            </div>

            <!-- Contact Information -->
            <div class="form-section">
                <h3 class="section-title">Contact Information</h3>
                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
            </div>

            <!-- Medical History -->
            <div class="form-section">
                <h3 class="section-title">Medical History</h3>
                <div class="form-group">
                    <label for="medicalConditions">Pre-existing Medical Conditions</label>
                    <textarea id="medicalConditions" name="medicalConditions"></textarea>
                </div>
                <div class="form-group">
                    <label for="organs">Organ(s) Willing to Donate *</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="organs[]" value="kidney"><span>Kidney</span></label>
                        <label><input type="checkbox" name="organs[]" value="liver"><span>Liver</span></label>
                        <label><input type="checkbox" name="organs[]" value="heart"><span>Heart</span></label>
                        <label><input type="checkbox" name="organs[]" value="lungs"><span>Lungs</span></label>
                        <label><input type="checkbox" name="organs[]" value="pancreas"><span>Pancreas</span></label>
                        <label><input type="checkbox" name="organs[]" value="corneas"><span>Corneas</span></label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="medicalReports">Upload Medical Reports (Optional)</label>
                    <div class="file-upload">
                        <label for="medicalReports">
                            <i class="fas fa-upload"></i> Choose Files
                        </label>
                        <input type="file" id="medicalReports" name="medical_reports" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
            </div>

            <!-- ID Proof -->
            <div class="form-section">
                <h3 class="section-title">ID Proof</h3>
                <div class="form-group">
                    <label for="id_proof">Upload ID Proof * (Passport/Driver's License/Aadhaar Card)</label>
                    <div class="file-upload">
                        <label for="id_proof">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <input type="file" id="id_proof" name="id_proof" required accept=".pdf,.jpg,.jpeg,.png">
                        <span class="file-name"></span>
                    </div>
                    <small class="form-text text-muted">Maximum file size: 5MB. Accepted formats: PDF, JPG, JPEG, PNG</small>
                </div>
            </div>

            <!-- Reason for Donation -->
            <div class="form-section">
                <h3 class="section-title">Reason for Organ Donation</h3>
                <div class="form-group">
                    <label for="reason">Why do you want to donate your organs? *</label>
                    <textarea id="reason" name="reason" required></textarea>
                </div>
            </div>

            <!-- Guardian Details -->
            <div class="form-section guardian-section" id="guardianSection">
                <h3 class="section-title">Guardian Details (Optional)</h3>
                <button type="button" class="skip-guardian" onclick="skipGuardian()">Skip Guardian Details</button>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guardianName">Guardian's Name</label>
                        <input type="text" id="guardianName" name="guardianName">
                    </div>
                    <div class="form-group">
                        <label for="guardianEmail">Guardian's Email</label>
                        <input type="email" id="guardianEmail" name="guardianEmail">
                    </div>
                </div>
                <div class="form-group">
                    <label for="guardianPhone">Guardian's Phone Number</label>
                    <input type="tel" id="guardianPhone" name="guardianPhone">
                </div>
                <div class="form-group">
                    <label for="guardian_id_proof">Upload Guardian's ID Proof</label>
                    <div class="file-upload">
                        <label for="guardian_id_proof">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <input type="file" id="guardian_id_proof" name="guardian_id_proof" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="guardianConfirmation" id="guardianConfirmation">
                        I confirm that my guardian can cancel my donation process on my behalf in case of my absence.
                    </label>
                </div>
            </div>

            <!-- Login Information -->
            <div class="form-section">
                <h3 class="section-title">Login Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password *</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                    </div>
                </div>
            </div>

            <!-- Ethical Policy & Rights -->
            <div class="form-section">
                <h3 class="section-title">Ethical Policy & Rights</h3>
                <div class="policy-text">
                    By registering, I understand that I have the right to withdraw from the donation process at any time, 
                    without any obligations. The cancellation process will be confidential and ethical.
                </div>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="policyAgreement" required>
                        I agree to the ethical policy and understand my rights.
                    </label>
                </div>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="termsAgreement" required>
                        I agree to the Terms and Conditions and consent to donate organs after my demise.
                    </label>
                </div>
            </div>

            <!-- reCAPTCHA placeholder -->
            <div class="form-section">
                <div id="recaptcha"></div>
            </div>

            <button type="submit" class="submit-button">
                <i class="fas fa-heart"></i> Register as Donor
            </button>
        </form>
    </div>

    <script>
        // Skip guardian section
        function skipGuardian() {
            const guardianSection = document.getElementById('guardianSection');
            guardianSection.style.display = 'none';
            
            // Clear guardian fields
            document.getElementById('guardianName').value = '';
            document.getElementById('guardianEmail').value = '';
            document.getElementById('guardianPhone').value = '';
            document.getElementById('guardian_id_proof').value = '';
            document.getElementById('guardianConfirmation').checked = false;
        }

        // Form validation
        document.getElementById('donorRegistrationForm').addEventListener('submit', function(e) {
            let isValid = true;

            // Password match validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                isValid = false;
            }

            // Organs selection validation
            const organs = document.querySelectorAll('input[name="organs[]"]:checked');
            if (organs.length === 0) {
                alert('Please select at least one organ to donate.');
                isValid = false;
            }

            // Age validation
            const dob = new Date(document.getElementById('dob').value);
            const age = Math.floor((new Date() - dob) / (365.25 * 24 * 60 * 60 * 1000));
            if (age < 18) {
                alert('You must be at least 18 years old to register as a donor.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // File upload preview (can be implemented if needed)
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.previousElementSibling;
                label.textContent = this.files.length > 0 ? 
                    `Selected ${this.files.length} file(s)` : 
                    'Choose File';
            });
        });
    </script>
</body>
</html>