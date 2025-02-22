<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLink - Connecting Lives Through Organ Donation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .nav-links a:not(.btn) {
            position: relative;
            text-decoration: none;
            color: var(--dark-gray);
            transition: color 0.3s ease;
        }

        .nav-links a:not(.btn)::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            transition: width 0.3s ease;
        }

        .nav-links a:not(.btn):hover::after {
            width: 100%;
        }

        .nav-links a:not(.btn):hover {
            color: var(--primary-blue);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <span class="logo-life">LifeLink</span>
            </a>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#features">Features</a>
                <a href="#testimonials">Testimonials</a>
                <a href="#community">Join Community</a>
                <a href="pages/hospital_hub.php" class="btn" style="
                    background: var(--primary-blue);
                    color: var(--white);
                    transition: all 0.3s ease;
                    margin-left: 1rem;
                    border: 2px solid var(--primary-blue);
                    padding: 0.5rem 1rem;
                    font-size: 0.9rem;
                " onmouseover="
                    this.style.background='transparent';
                    this.style.color='var(--primary-blue)';
                " onmouseout="
                    this.style.background='var(--primary-blue)';
                    this.style.color='var(--white)';
                ">Hospital Hub</a>
                <a href="pages/admin_login.php" class="btn" style="
                    background: var(--primary-green);
                    color: var(--white);
                    transition: all 0.3s ease;
                    margin-left: 0.5rem;
                    border: 2px solid var(--primary-green);
                    padding: 0.5rem 1rem;
                    font-size: 0.9rem;
                " onmouseover="
                    this.style.background='transparent';
                    this.style.color='var(--primary-green)';
                " onmouseout="
                    this.style.background='var(--primary-green)';
                    this.style.color='var(--white)';
                ">Admin Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="section hero" style="
        background: linear-gradient(rgba(33, 150, 243, 0.9), rgba(76, 175, 80, 0.9)),
        url('assets/images/common/hero-bg.jpg') center/cover;
        height: 100vh;
        display: flex;
        align-items: center;
        color: var(--white);
        margin-top: -5rem;
    ">
        <div class="container text-center">
            <h1 style="font-size: 3.5rem; margin-bottom: 1.5rem;">
                Connecting Lives Through<br>Organ Donation
            </h1>
            <p style="font-size: 1.25rem; margin-bottom: 2rem;">
                Join our mission to save lives by bridging the gap between donors and recipients
            </p>
            <div class="hero-buttons" style="display: flex; gap: 1rem; justify-content: center;">
                <a href="pages/donor_registration.php" 
                   class="btn btn-primary donor-btn" 
                   style="
                        background: var(--primary-blue);
                        transition: all 0.3s ease;
                        color: var(--white);
                        padding: 0.8rem 1.5rem;
                        font-size: 1.1rem;
                        transform: translateY(0);
                   " 
                   onmouseover="
                        this.style.background='var(--primary-green)';
                        this.style.transform='translateY(-5px)';
                        document.querySelector('.recipient-btn').style.background='transparent';
                   " 
                   onmouseout="
                        this.style.background='var(--primary-blue)';
                        this.style.transform='translateY(0)';
                        document.querySelector('.recipient-btn').style.background='transparent';
                   ">Register as Donor</a>
                <a href="pages/recipient_registration.php" 
                   class="btn btn-outline recipient-btn" 
                   style="
                        border: 2px solid var(--white);
                        color: var(--white);
                        transition: all 0.3s ease;
                        background: transparent;
                        padding: 0.8rem 1.5rem;
                        font-size: 1.1rem;
                        transform: translateY(0);
                   " 
                   onmouseover="
                        this.style.background='var(--primary-green)';
                        this.style.transform='translateY(-5px)';
                        document.querySelector('.donor-btn').style.background='transparent';
                        document.querySelector('.donor-btn').style.border='2px solid var(--white)';
                   " 
                   onmouseout="
                        this.style.background='transparent';
                        this.style.transform='translateY(0)';
                        document.querySelector('.donor-btn').style.background='var(--primary-blue)';
                   ">Register as Recipient</a>
            </div>
            <div style="margin-top: 1.5rem; color: var(--white);">
                <p style="font-size: 1.1rem;">Already registered? 
                    <a href="pages/donor_login.php" style="
                        color: var(--white);
                        text-decoration: none;
                        margin: 0 0.5rem;
                        padding: 0.3rem 1rem;
                        border-radius: 20px;
                        background: linear-gradient(45deg, rgba(76, 175, 80, 0.3), rgba(33, 150, 243, 0.3));
                        transition: all 0.3s ease;
                        font-weight: 500;
                        border: 1px solid rgba(255, 255, 255, 0.3);
                        display: inline-block;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    " onmouseover="
                        this.style.background='linear-gradient(45deg, rgba(76, 175, 80, 0.6), rgba(33, 150, 243, 0.6))';
                        this.style.transform='translateY(-2px)';
                        this.style.boxShadow='0 4px 15px rgba(0, 0, 0, 0.2)';
                    " onmouseout="
                        this.style.background='linear-gradient(45deg, rgba(76, 175, 80, 0.3), rgba(33, 150, 243, 0.3))';
                        this.style.transform='translateY(0)';
                        this.style.boxShadow='0 2px 10px rgba(0, 0, 0, 0.1)';
                    ">Donor</a> or 
                    <a href="pages/recipient_login.php" style="
                        color: var(--white);
                        text-decoration: none;
                        padding: 0.3rem 1rem;
                        border-radius: 20px;
                        background: linear-gradient(45deg, rgba(33, 150, 243, 0.3), rgba(76, 175, 80, 0.3));
                        transition: all 0.3s ease;
                        font-weight: 500;
                        border: 1px solid rgba(255, 255, 255, 0.3);
                        display: inline-block;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    " onmouseover="
                        this.style.background='linear-gradient(45deg, rgba(33, 150, 243, 0.6), rgba(76, 175, 80, 0.6))';
                        this.style.transform='translateY(-2px)';
                        this.style.boxShadow='0 4px 15px rgba(0, 0, 0, 0.2)';
                    " onmouseout="
                        this.style.background='linear-gradient(45deg, rgba(33, 150, 243, 0.3), rgba(76, 175, 80, 0.3))';
                        this.style.transform='translateY(0)';
                        this.style.boxShadow='0 2px 10px rgba(0, 0, 0, 0.1)';
                    ">Recipient</a>
                </p>
            </div>
        </div>
    </section>
    <!-- About Section -->
<section id="about" class="section" style="background: linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(76, 175, 80, 0.1));">
    <div class="container">
        <h2 class="text-center" style="
            font-size: 2.5rem;
            margin-bottom: 3rem;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        ">About LifeLink</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center;">
            <div class="about-content">
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 15px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                    margin-bottom: 2rem;
                ">
                    <h3 style="
                        color: var(--primary-blue);
                        font-size: 1.5rem;
                        margin-bottom: 1rem;
                    ">Our Mission</h3>
                    <p style="line-height: 1.8;">To revolutionize organ donation by creating a seamless connection between donors and recipients, leveraging technology to save lives and bring hope to those in need.</p>
                </div>

                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 15px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                ">
                    <h3 style="
                        color: var(--primary-green);
                        font-size: 1.5rem;
                        margin-bottom: 1rem;
                    ">Our Vision</h3>
                    <p style="line-height: 1.8;">To build a world where no life is lost due to organ unavailability, fostering a global community of compassionate donors and creating hope for recipients worldwide.</p>
                </div>
            </div>

            <div class="about-features" style="
                background: white;
                padding: 2.5rem;
                border-radius: 15px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            ">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div style="text-align: center;">
                        <i class="fas fa-heartbeat" style="
                            font-size: 2.5rem;
                            color: var(--primary-blue);
                            margin-bottom: 1rem;
                            background: rgba(33, 150, 243, 0.1);
                            padding: 1rem;
                            border-radius: 50%;
                        "></i>
                        <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Life-Saving Impact</h4>
                        <p>Connecting donors with recipients effectively</p>
                    </div>
                    <div style="text-align: center;">
                        <i class="fas fa-shield-alt" style="
                            font-size: 2.5rem;
                            color: var(--primary-green);
                            margin-bottom: 1rem;
                            background: rgba(76, 175, 80, 0.1);
                            padding: 1rem;
                            border-radius: 50%;
                        "></i>
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">Secure Platform</h4>
                        <p>Advanced security for your data protection</p>
                    </div>
                    <div style="text-align: center;">
                        <i class="fas fa-hospital-user" style="
                            font-size: 2.5rem;
                            color: var(--primary-blue);
                            margin-bottom: 1rem;
                            background: rgba(33, 150, 243, 0.1);
                            padding: 1rem;
                            border-radius: 50%;
                        "></i>
                        <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Expert Support</h4>
                        <p>24/7 professional medical assistance</p>
                    </div>
                    <div style="text-align: center;">
                        <i class="fas fa-clock" style="
                            font-size: 2.5rem;
                            color: var(--primary-green);
                            margin-bottom: 1rem;
                            background: rgba(76, 175, 80, 0.1);
                            padding: 1rem;
                            border-radius: 50%;
                        "></i>
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">Quick Matching</h4>
                        <p>Efficient donor-recipient matching system</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
    <!-- Features Section -->
    <section id="features" class="section">
        <div class="container">
            <h2 class="text-center mb-3">Why Choose LifeLink?</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div class="card">
                    <i class="fas fa-heart" style="color: var(--primary-green); font-size: 2.5rem; margin-bottom: 1rem;"></i>
                    <h3>Save Lives</h3>
                    <p>Make a difference by donating organs to those in need</p>
                </div>
                <div class="card">
                    <i class="fas fa-hospital" style="color: var(--primary-blue); font-size: 2.5rem; margin-bottom: 1rem;"></i>
                    <h3>Hospital Network</h3>
                    <p>Connect with verified hospitals and medical professionals</p>
                </div>
                <div class="card">
                    <i class="fas fa-shield-alt" style="color: var(--primary-green); font-size: 2.5rem; margin-bottom: 1rem;"></i>
                    <h3>Secure Platform</h3>
                    <p>Your data is protected with the highest security standards</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="section" style="background: var(--light-blue);">
        <div class="container">
            <h2 class="text-center mb-3">What People Say</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div class="card">
                    <div style="font-size: 4rem; color: var(--primary-blue); text-align: center;">❝</div>
                    <p style="font-style: italic;">"LifeLink made it possible for me to help others. The process was smooth and well-organized."</p>
                    <p style="text-align: right; font-weight: bold;">- John Doe, Donor</p>
                </div>
                <div class="card">
                    <div style="font-size: 4rem; color: var(--primary-green); text-align: center;">❝</div>
                    <p style="font-style: italic;">"Thanks to LifeLink, we found a matching donor quickly. Forever grateful!"</p>
                    <p style="text-align: right; font-weight: bold;">- Jane Smith, Recipient</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Join Community Section -->
    <section id="community" class="section">
        <div class="container text-center">
            <h2 class="mb-3">Join Our Community</h2>
            <p style="max-width: 600px; margin: 0 auto 2rem;">Be part of something bigger. Join our community of donors, recipients, and healthcare professionals making a difference.</p>
            <a href="pages/register.php" class="btn btn-primary">Join Now</a>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="section" style="background-color: var(--light-blue);">
        <div class="container">
            <h2 class="text-center mb-3">How It Works</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div class="card">
                    <div class="step-number" style="
                        background: var(--primary-blue);
                        color: white;
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-bottom: 1rem;
                    ">1</div>
                    <h3>Register</h3>
                    <p>Create your account as a donor or recipient</p>
                </div>
                <div class="card">
                    <div class="step-number" style="
                        background: var(--primary-blue);
                        color: white;
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-bottom: 1rem;
                    ">2</div>
                    <h3>Connect</h3>
                    <p>Match with compatible donors or recipients</p>
                </div>
                <div class="card">
                    <div class="step-number" style="
                        background: var(--primary-blue);
                        color: white;
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-bottom: 1rem;
                    ">3</div>
                    <h3>Save Lives</h3>
                    <p>Complete the donation process with verified hospitals</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="section">
        <div class="container text-center">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <h2 style="color: var(--primary-green);">1000+</h2>
                    <p>Registered Donors</p>
                </div>
                <div>
                    <h2 style="color: var(--primary-blue);">500+</h2>
                    <p>Successful Matches</p>
                </div>
                <div>
                    <h2 style="color: var(--primary-green);">50+</h2>
                    <p>Partner Hospitals</p>
                </div>
                <div>
                    <h2 style="color: var(--primary-blue);">100%</h2>
                    <p>Secure Platform</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section" style="background-color: var(--light-green);">
        <div class="container">
            <h2 class="text-center mb-3">Contact Us</h2>
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <form>
                    <div style="margin-bottom: 1rem;">
                        <label for="name">Name</label>
                        <input type="text" id="name" style="width: 100%; padding: 0.5rem; border: 1px solid var(--gray); border-radius: 5px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="email">Email</label>
                        <input type="email" id="email" style="width: 100%; padding: 0.5rem; border: 1px solid var(--gray); border-radius: 5px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label for="message">Message</label>
                        <textarea id="message" rows="4" style="width: 100%; padding: 0.5rem; border: 1px solid var(--gray); border-radius: 5px;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <h3>LifeLink</h3>
                    <p>Connecting lives through organ donation</p>
                </div>
                <div>
                    <h3>Quick Links</h3>
                    <ul style="list-style: none;">
                        <li><a href="#about" style="color: var(--white); text-decoration: none;">About</a></li>
                        <li><a href="#features" style="color: var(--white); text-decoration: none;">Features</a></li>
                        <li><a href="#how-it-works" style="color: var(--white); text-decoration: none;">How It Works</a></li>
                        <li><a href="#contact" style="color: var(--white); text-decoration: none;">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Contact</h3>
                    <p>Email: info@lifelink.com</p>
                    <p>Phone: (123) 456-7890</p>
                </div>
                <div>
                    <h3>Follow Us</h3>
                    <div style="display: flex; gap: 1rem;">
                        <a href="#" style="color: var(--white);"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: var(--white);"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: var(--white);"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: var(--white);"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <p>&copy; 2024 LifeLink. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
