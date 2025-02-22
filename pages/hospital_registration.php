<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Registration - LifeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .form-section h3 {
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="password"],
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

        .specialization-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .specialization-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-upload {
            border: 2px dashed #ddd;
            padding: 1.5rem;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload:hover {
            border-color: var(--primary-blue);
        }

        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .agreement-section {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(33, 150, 243, 0.1);
            border-radius: 5px;
        }

        .submit-button {
            background: var(--primary-blue);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 2rem;
        }

        .submit-button:hover {
            background: var(--primary-green);
        }

        .required::after {
            content: " *";
            color: red;
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

    <div class="container">
        <div class="form-container">
            <h2 class="text-center" style="
                font-size: 2rem;
                margin-bottom: 2rem;
                background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            ">Hospital Registration</h2>

            <form action="../backend/php/hospital_register_process.php" method="POST" enctype="multipart/form-data" id="hospitalRegistrationForm">
                <!-- Hospital Details Section -->
                <div class="form-section">
                    <h3><i class="fas fa-hospital"></i> Hospital Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="required">Hospital Name</label>
                            <input type="text" name="hospital_name" required>
                        </div>
                        <div class="form-group">
                            <label class="required">Hospital Email</label>
                            <input type="email" name="hospital_email" required>
                        </div>
                        <div class="form-group">
                            <label class="required">Hospital Phone</label>
                            <input type="tel" name="hospital_phone" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Hospital Address</label>
                        <input type="text" name="street" placeholder="Street" required>
                        <div class="form-grid" style="margin-top: 1rem;">
                            <input type="text" name="city" placeholder="City" required>
                            <input type="text" name="state" placeholder="State" required>
                            <input type="text" name="postal_code" placeholder="Postal Code" required>
                            <input type="text" name="country" placeholder="Country" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Specializations</label>
                        <div class="specialization-options">
                            <div class="specialization-option">
                                <input type="checkbox" name="specializations[]" value="general">
                                <label>General</label>
                            </div>
                            <div class="specialization-option">
                                <input type="checkbox" name="specializations[]" value="organ_transplant">
                                <label>Organ Transplants</label>
                            </div>
                            <div class="specialization-option">
                                <input type="checkbox" name="specializations[]" value="cardiology">
                                <label>Cardiology</label>
                            </div>
                            <div class="specialization-option">
                                <input type="checkbox" name="specializations[]" value="neurology">
                                <label>Neurology</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Details Section -->
                <div class="form-section">
                    <h3><i class="fas fa-user-tie"></i> Contact Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="required">Contact Person Name</label>
                            <input type="text" name="contact_name" required>
                        </div>
                        <div class="form-group">
                            <label class="required">Designation</label>
                            <input type="text" name="designation" required>
                        </div>
                        <div class="form-group">
                            <label class="required">Contact Phone</label>
                            <input type="tel" name="contact_phone" required>
                        </div>
                        <div class="form-group">
                            <label class="required">Contact Email</label>
                            <input type="email" name="contact_email" required>
                        </div>
                    </div>
                </div>

                <!-- License Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-certificate"></i> License Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="required">License Number</label>
                            <input type="text" name="license_number" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">License Document</label>
                        <div class="file-upload">
                            <input type="file" name="license_document" accept="image/jpeg,image/png,image/jpg" required
                                   style="display: none;" id="licenseFile">
                            <label for="licenseFile">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload license document</span>
                                <div style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                                    Supported formats: JPG, PNG (Max size: 5MB)
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Login Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-lock"></i> Login Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="required">Username</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label class="required">Password</label>
                            <input type="password" name="password" required>
                            <div class="password-requirements">
                                Password must contain at least 8 characters, including uppercase, lowercase, numbers, and special characters.
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="required">Confirm Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                </div>

                <!-- Agreement Section -->
                <div class="agreement-section">
                    <div class="form-group" style="
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        margin: 15px 0;
                    ">
                        <input type="checkbox" id="terms" name="terms" required style="
                            margin: 0;
                            width: auto;
                        ">
                        <label for="terms" style="
                            margin: 0;
                            font-size: 0.9rem;
                            color: #666;
                        ">I agree to the <a href="#" style="color: var(--primary-blue); text-decoration: none;">Terms and Conditions</a> and confirm that all provided information is accurate.</label>
                    </div>
                </div>

                <!-- reCAPTCHA -->
                <div class="form-group" style="display: flex; justify-content: center; margin: 2rem 0;">
                    <div class="g-recaptcha" data-sitekey="YOUR_RECAPTCHA_SITE_KEY"></div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-button">
                    <i class="fas fa-hospital-user"></i> Register Hospital
                </button>
            </form>
        </div>
    </div>

    <script>
        // File upload preview
        document.getElementById('licenseFile').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const label = this.nextElementSibling.querySelector('span');
                label.textContent = fileName;
            }
        });

        // Form validation
        document.getElementById('hospitalRegistrationForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }

            // Password strength validation
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            if (!passwordRegex.test(password)) {
                e.preventDefault();
                alert('Password must contain at least 8 characters, including uppercase, lowercase, numbers, and special characters.');
                return;
            }
        });
    </script>
</body>
</html>