<?php
session_start();
require_once 'db.php'; // Add this line to include the database connection

// Update your login process (index.php or login.php)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #e0f2e9; 
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        
        .page-container {
            display: flex;
            width: 850px;
            height: 560px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .brand-section {
            background: linear-gradient(135deg, #0a6b50 0%, #084a38 100%);
            width: 40%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .brand-section::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='rgba(255,255,255,0.05)' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.4;
            top: -50%;
            left: -50%;
            z-index: 0;
            animation: animatePattern 30s linear infinite;
        }
        
        @keyframes animatePattern {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        .logo {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .logo img {
            height: 190px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 3px solid rgba(255, 255, 255, 0.3);
            padding: 5px;
        }
        
        .brand-title {
            font-size: 34px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .brand-tagline {
            font-size: 14px;
            text-align: center;
            opacity: 0.8;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .login-container {
            background-color: white;
            width: 60%;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-title {
            color: #0a6b50;
            margin-bottom: 40px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .form-subtitle {
            color: #777;
            margin-bottom: 30px;
            font-size: 14px;
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #f9fafb;
        }
        
        .form-control:focus {
            border-color: #0a6b50;
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 107, 80, 0.1);
            background-color: #fff;
        }
        
        .remember {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .remember input {
            margin-right: 8px;
        }
        
        .btn {
            background: linear-gradient(135deg, #0a6b50 0%, #085540 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(10, 107, 80, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(10, 107, 80, 0.2);
        }
        
        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 3px 5px rgba(10, 107, 80, 0.1);
        }
        
        .error-message {
            color: #e53e3e;
            font-size: 14px;
            margin: 15px 0;
            padding: 10px 15px;
            background-color: #fff5f5;
            border-left: 3px solid #e53e3e;
            border-radius: 4px;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .page-container {
                width: 95%;
                flex-direction: column;
                height: auto;
            }
            
            .brand-section {
                width: 100%;
                padding: 30px 20px;
            }
            
            .login-container {
                width: 100%;
                padding: 30px 20px;
            }
        }

        .footer {
    background-color: #f5f5f5;
    padding: 20px 0;
    text-align: center;
    position: fixed;
    bottom: 0;
    width: 100%;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 20px;
}

.footer-btn {
    background-color: #0a6b50;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 8px 15px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.footer-btn:hover {
    background-color: #085540;
    transform: translateY(-2px);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: white;
    padding: 25px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    animation: modalFadeIn 0.3s;
}

@keyframes modalFadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
    color: #0a6b50;
    margin: 0;
    font-size: 22px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #555;
    transition: color 0.3s;
}

.close-btn:hover {
    color: #e53e3e;
}

.modal-body {
    line-height: 1.6;
    color: #333;
}
.footer-links {
    display: flex;
    justify-content: center;
    gap: 40px;
}

.footer-link {
    color: #0a6b50;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
    cursor: pointer;
}

.footer-link:hover {
    color: #085540;
    text-decoration: underline;
}
@media (max-width: 480px) {
    .page-container {
        width: 100%;
        min-width: unset;
        flex-direction: column;
        height: auto;
        box-shadow: none;
        border-radius: 0;
    }
    .brand-section,
    .login-container {
        width: 100%;
        padding: 18px 8px;
    }
    .logo img {
        height: 110px;
    }
    .brand-title {
        font-size: 22px;
    }
    .form-title {
        font-size: 20px;
    }
    .form-subtitle {
        font-size: 12px;
    }
    .form-control {
        font-size: 13px;
        padding: 12px 12px 12px 38px;
    }
    .btn {
        font-size: 14px;
        padding: 12px;
    }
    .footer {
        font-size: 12px;
        padding: 10px 0;
        position: static;
    }
    .footer-links {
        gap: 12px;
    }
}

    </style>
</head>
<body>
    <div class="page-container">
        <div class="brand-section">
            <div class="logo">
                <img src="images/logo.jpg" alt="School Logo">
            </div>
            <h1 class="brand-title">Inventory Management</h1>
        </div>
        
        <div class="login-container">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Login to access your dashboard</p>
            
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="remember">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> Login
                </button>
            </form>
        </div>
    </div>

    <div class="footer">
    <div class="footer-links">
        <a href="#" onclick="openModal('aboutModal'); return false;" class="footer-link">About Us</a>
        <a href="#" onclick="openModal('contactModal'); return false;" class="footer-link">Contact Us</a>
        <a href="#" onclick="openModal('policyModal'); return false;" class="footer-link">Privacy Policy + Terms</a>
    </div>
</div>
</div>

<!-- About Us Modal -->
<div id="aboutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>About Us</h2>
            <button class="close-btn" onclick="closeModal('aboutModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Pasong Tamo Elementary School is geographically situated in the heart of Quezon City is endowed with historical, rich culture and aspirations headed by the Katipunero, Melchora Aquino in Tandang sora. The name was derived from the wild plants called “Tamo” thickly growing along the major paths cutting through from the eastern parts to the western exits of this area. <br><br>The revolutionary, Andres Bonifacio, so named the place.

Pasong Tamo was formally created under Executive Order 27 pursuant to Ordinance No. 4992 during the incumbency of the late Mayor Norberto S. Amoranto, and the school was named after the barangay and was called Pasong Tamo Elementary School.<br><br></p>
            <h3>OUR VISION</h3>

            <p>We dream of Filipinos who passionately love their country and whose values and competencies enable them to realize their full potential and contribute meaningfully to building the nation.

As a learner-centered public institution, the Department of Education continuously improves itself to better serve its stakeholders<br><br></p>
            <h3>OUR MISSION</h3>


            <p>To protect and promote the right of every Filipino to quality, equitable, culture-based, and complete basic education where: Students learn in a child-friendly, gender-sensitive, safe, and motivating environment.

Teachers facilitate learning and constantly nurture every learn-er.

Administrators and staff, as stewards of the institution, ensure an enabling and supportive environment for effective learning to happen. UBI

Family, community, and other stakeholders are actively engaged and share responsibility for developing life-long learners.</p><br>

            <h3>OUR CORE VALUES</h3>

            
            <p>Maka-Diyos<br>
            Maka-tao <br>Makakalikasan <br>Makabansa</p>
        </div>
    </div>
</div>

<!-- Contact Us Modal -->
<div id="contactModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Contact Us</h2>
            <button class="close-btn" onclick="closeModal('contactModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>We'd love to hear from you! Please use any of the following methods to get in touch with our support team:</p>
            
            <h3>Email</h3>
            <p><i class="fas fa-envelope"></i> pasongtamoes@gmail.com</p>
            
            <h3>Phone</h3>
            <p><i class="fas fa-phone"></i> 7-256-6917</p>
            
            <h3>Address</h3>
            <p><i class="fas fa-map-marker-alt"></i> Tandang Sora Ave., Brgy. Pasong Tamo, Quezon City, 1107 Metro Manila</p>
            
            <h3>Facebook</h3>
            <p><i class="icon-facebook"></i> https://www.facebook.com/profile.php?id=100063573294640 </p>
            
        </div>
    </div>
</div>

<!-- Privacy Policy + Terms Modal -->
<div id="policyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Privacy Policy & Terms</h2>
            <button class="close-btn" onclick="closeModal('policyModal')">&times;</button>
        </div>
        <div class="modal-body">
            <h3>Privacy Policy</h3>
            <p>Last updated: April 22, 2025</p>
            
            <h4>1. Information We Collect</h4>
            <p>We collect information that you provide directly to us, such as when you create an account, update your profile, use interactive features, complete forms, or otherwise communicate with us.</p>
            
            <h4>2. How We Use Your Information</h4>
            <p>We use the information we collect to provide, maintain, and improve our services, to develop new features, and to protect our system and users.</p>
            
            <h4>3. Data Security</h4>
            <p>We implement appropriate security measures to protect your information from unauthorized access, alteration, disclosure, or destruction.</p>
            
            <h3>Terms of Service</h3>
            <p>By accessing or using our Inventory Management System, you agree to be bound by these Terms and all applicable laws and regulations.</p>
            
            <h4>1. User Accounts</h4>
            <p>You are responsible for safeguarding your password and for all activities that occur under your account.</p>
            
            <h4>2. Acceptable Use</h4>
            <p>You agree not to misuse the services or help anyone else do so. For example, you must not attempt to access the services using unauthorized methods.</p>
            
            <h4>3. Modification of Terms</h4>
            <p>We reserve the right to modify these terms at any time. Your continued use of the platform following any changes indicates your acceptance of the new terms.</p>
            
            <h4>4. Termination</h4>
            <p>We may terminate or suspend your access to the services immediately, without prior notice or liability, for any reason.</p>
        </div>
    </div>
</div>
</body>
</html>

<script>
// JavaScript to handle modal functionality
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto'; // Re-enable scrolling
}

// Close modal when clicking outside the modal content
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let i = 0; i < modals.length; i++) {
        if (event.target == modals[i]) {
            modals[i].style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
            if (modals[i].style.display === 'flex') {
                modals[i].style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    }
});
</script>