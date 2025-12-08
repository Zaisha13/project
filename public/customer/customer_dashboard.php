<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Dashboard | Jessie Cane</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/shared.css" />
  <link rel="stylesheet" href="css/customer_dashboard.css" />
  <link rel="stylesheet" href="css/auth-modal.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script defer src="../../assets/js/api-helper.js"></script>
  <script defer src="../../assets/js/header.js"></script>
  <script defer src="js/customer_dashboard.js"></script>
  <script>
    // Handle Google auth callback and payment verification
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const authStatus = urlParams.get('auth');
      const token = urlParams.get('token');
      const errorMessage = urlParams.get('message');
      const paymentSuccess = urlParams.get('payment');
      const orderId = urlParams.get('order');
      
      console.log('Google OAuth Callback Handler:', {
        authStatus: authStatus,
        hasToken: !!token,
        errorMessage: errorMessage,
        fullURL: window.location.href
      });
      
      if (authStatus === 'error') {
        const message = errorMessage || 'Google authentication failed. Please try again or use email/password login.';
        console.error('Google OAuth Error:', message);
        alert(message);
        window.history.replaceState({}, document.title, window.location.pathname);
        return;
      }
      
      if (authStatus === 'success' && token) {
        try {
          // Decode token to get user info
          const tokenData = JSON.parse(atob(token));
          console.log('Decoded token data:', tokenData);
          
          // Use the API base URL from api-helper.js (already set globally)
          const apiBaseUrl = (typeof getAPIBaseURL === 'function') ? getAPIBaseURL() : (window.API_BASE_URL || 'http://localhost:8080/project/api/api');
          console.log('Using API base URL:', apiBaseUrl);
          
          // Fetch user details from backend
          const profileUrl = apiBaseUrl + '/users/profile.php?user_id=' + tokenData.user_id;
          console.log('Fetching user profile from:', profileUrl);
          
          fetch(profileUrl)
            .then(response => {
              console.log('Profile API response status:', response.status);
              if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
              }
              return response.json();
            })
            .then(data => {
              console.log('Profile API response data:', data);
              if (data.success && data.data) {
                const user = data.data;
                sessionStorage.setItem('isLoggedIn', 'true');
                sessionStorage.setItem('currentUser', JSON.stringify(user));
                sessionStorage.setItem('token', token);
                
                // Show success message
                alert('Welcome! Successfully logged in with Google.');
                
                // Clean URL and reload
                window.history.replaceState({}, document.title, window.location.pathname);
                window.location.reload();
              } else {
                throw new Error(data.message || 'Failed to fetch user data');
              }
            })
            .catch(error => {
              console.error('Google auth callback error:', error);
              console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                profileUrl: profileUrl,
                apiBaseUrl: apiBaseUrl,
                userId: tokenData.user_id
              });
              alert('Failed to complete Google login: ' + error.message + '\n\nPlease check the browser console for more details.');
              window.history.replaceState({}, document.title, window.location.pathname);
            });
        } catch (error) {
          console.error('Token decode error:', error);
          alert('Invalid authentication token. Please try logging in again.');
          window.history.replaceState({}, document.title, window.location.pathname);
        }
      } else if (authStatus === 'error') {
        const message = urlParams.get('message') || 'Google authentication failed. Please try again or use email/password login.';
        alert(message);
        window.history.replaceState({}, document.title, window.location.pathname);
      }

      // Handle payment success verification
      if (paymentSuccess === 'success' && orderId) {
        (async function() {
          try {
            // Wait for API helper to load
            if (typeof getAPIBaseURL === 'undefined') {
              await new Promise(resolve => setTimeout(resolve, 500));
            }

            const apiBaseUrl = (typeof getAPIBaseURL === 'function') 
              ? getAPIBaseURL() 
              : (window.API_BASE_URL || 'http://localhost:8080/project/api/api');
            
            const verifyUrl = apiBaseUrl + '/payments/gcash-verify.php?order_id=' + encodeURIComponent(orderId);
            
            const response = await fetch(verifyUrl);
            const data = await response.json();
            
            if (data.success && data.data.status === 'Paid') {
              console.log('Payment verified successfully');
              // Clean URL
              window.history.replaceState({}, document.title, window.location.pathname);
              // Optionally redirect to thank-you page
              window.location.href = 'thank-you.php?order=' + encodeURIComponent(orderId);
            }
          } catch (error) {
            console.error('Error verifying payment:', error);
            // Don't show error to user as webhook should handle it
          }
        })();
      }
    });
  </script>
</head>
<body>
  <!-- PURPOSE: Customer dashboard page — header, banner, featured drinks and debug tools -->
  <!-- HEADER -->
  <header>
    <div class="left-header">
      <img src="../../assets/images/logo.png" id="drink_logo" alt="Jessie Cane Logo" onerror="this.style.display='none'">
    </div>
    <div class="right-header">
      <a href="../index.php" class="nav-btn">Portal</a>
      <a href="customer_dashboard.php" class="nav-btn active">Home</a>
      <a href="drinks.php" class="nav-btn">Menu</a>
      <a href="profile.php" class="nav-btn">Profile</a>
      <a href="inquiry.php" class="nav-btn">Inquiry</a>
      <a href="../index.php" class="nav-btn logout">Logout</a>
    </div>
  </header>

  <!-- BANNER -->
  <section class="banner">
    <img src="../../assets/images/BANNER.png" alt="Banner" />
    <div class="text_overlay">
      <!-- Alternating items: primary heading and service invite -->
      <div id="alt-heading" class="alt-item alt-visible">
        <h1>Welcome to Jessie Cane Juicebar!</h1>
        <p>Refreshing sugarcane drinks made from nature's purest sweetness.</p>
        <a href="drinks.php"><button>ORDER NOW</button></a>
      </div>

      <div id="service-invite" class="alt-item alt-hidden service-invite">
        <p class="service-invite__title">Do you want Jessie Cane Juicebar to serve at your event?</p>
        <div class="service-invite__actions">
          <button id="avail-service-btn" class="view-btn">Avail Service</button>
        </div>
      </div>
      <!-- Pause indicator (hidden by default) -->
      <div id="pause-indicator" class="pause-indicator" aria-hidden="true" title="Slideshow paused">⏸</div>
    </div>
  </section>

  <!-- EVENTS/BOOKING SECTION -->
  <section class="event-section container">
    <div class="event-container">
      <!-- Slideshow on the left -->
      <div class="event-slideshow">
        <div class="slideshow-wrapper">
          <img src="../../assets/images/event_stall1.jpg" alt="Event Stall 1" class="event-slide active" id="event-slide-1">
          <img src="../../assets/images/event_stall2.jpg" alt="Event Stall 2" class="event-slide" id="event-slide-2">
          <img src="../../assets/images/event_stall3.jpg" alt="Event Stall 3" class="event-slide" id="event-slide-3">
          <img src="../../assets/images/event_stall4.jpg" alt="Event Stall 4" class="event-slide" id="event-slide-4">
          <img src="../../assets/images/event_stall5.jpg" alt="Event Stall 5" class="event-slide" id="event-slide-5">
        </div>
        <!-- Slide indicators -->
        <div class="slide-indicators">
          <span class="indicator active" data-slide="0"></span>
          <span class="indicator" data-slide="1"></span>
          <span class="indicator" data-slide="2"></span>
          <span class="indicator" data-slide="3"></span>
          <span class="indicator" data-slide="4"></span>
        </div>
      </div>
      
      <!-- Description on the right -->
      <div class="event-description">
        <h2>BOOK AN EVENT</h2>
        <p class="event-intro">Bring the refreshing taste of Jessie Cane to your special event!</p>
        <div class="event-details">
          <p>Whether you're hosting a corporate gathering, birthday celebration, wedding, or any special occasion, Jessie Cane Juicebar can provide exceptional sugarcane drinks to delight your guests.</p>
          <ul class="event-features">
            <li><i class="fas fa-check-circle"></i> Professional mobile juice bar setup</li>
            <li><i class="fas fa-check-circle"></i> Fresh, natural sugarcane beverages</li>
            <li><i class="fas fa-check-circle"></i> Customizable menu options</li>
            <li><i class="fas fa-check-circle"></i> Experienced staff for service</li>
            <li><i class="fas fa-check-circle"></i> Flexible event packages</li>
          </ul>
          <p class="event-contact">Contact us to discuss your event needs and let us make your occasion memorable with our premium juice offerings.</p>
        </div>
        <button id="book-event-btn" class="event-btn">Avail Service</button>
      </div>
    </div>
  </section>

  <!-- ANNOUNCEMENTS SECTION -->
  <section class="container announcements-section" id="announcements-section">
    <h2>Announcements</h2>
    <div id="announcements-container" class="announcements-grid">
      <!-- Announcements will be populated by JavaScript -->
    </div>
  </section>

  <!-- PURPOSE: Featured drinks section — shows rotated featured items from the menu -->
  <!-- FEATURED DRINKS -->
  <div class="container">
    <h2>Featured Drinks</h2>
    <div class="products" id="featured-drinks">
      <!-- Empty state - will be populated by JavaScript -->
      <div class="empty-state" id="empty-featured">
        <i class="fas fa-glass-water"></i>
        <h3>Menu Coming Soon!</h3>
        <p>Our delicious drinks will be available shortly. Please check back later.</p>
        <a href="drinks.php" class="browse-btn">Browse Menu</a>
      </div>
    </div>
  </div>

  <!-- DEBUG PANEL (Remove in production)
  <div style="position: fixed; bottom: 10px; right: 10px; background: #f8f5e9; padding: 10px; border: 2px solid #146B33; border-radius: 5px; z-index: 1000; font-size: 12px;">
    <strong>Debug Tools:</strong><br>
    <button onclick="checkSyncStatus()" style="margin: 2px; padding: 5px; font-size: 10px;">Check Sync</button>
    <button onclick="manualSync()" style="margin: 2px; padding: 5px; font-size: 10px;">Manual Sync</button>
    <button onclick="debugMenuData()" style="margin: 2px; padding: 5px; font-size: 10px;">Debug Data</button>
  </div> -->

  <!-- FOOTER -->
  <footer>
    <p>© 2025 Jessie Cane. All rights reserved.</p>
  </footer>

  <!-- Auth Modals -->
  <div id="auth-modal" class="auth-modal" style="display:none;">
    <div class="auth-modal-content">
      <button class="auth-modal-close" id="close-auth-modal">&times;</button>
      <div id="auth-modal-body"></div>
    </div>
  </div>

  <script>
    // Wire the service invite buttons on the dashboard
    document.addEventListener('DOMContentLoaded', function(){
      const availBtn = document.getElementById('avail-service-btn');
      const declineBtn = document.getElementById('decline-service-btn');
      const invite = document.getElementById('service-invite');
      const bookEventBtn = document.getElementById('book-event-btn');
      if (availBtn) {
        availBtn.addEventListener('click', function(){
          window.location.href = 'inquiry.php';
        });
      }
      if (bookEventBtn) {
        bookEventBtn.addEventListener('click', function(){
          window.location.href = 'inquiry.php';
        });
      }
      if (declineBtn && invite) {
        declineBtn.addEventListener('click', function(){
          invite.style.display = 'none';
        });
      }

      // Auth Modal functionality
      const authModal = document.getElementById('auth-modal');
      const authModalBody = document.getElementById('auth-modal-body');
      const closeBtn = document.getElementById('close-auth-modal');

      function closeAuthModal() {
        authModal.style.display = 'none';
        authModalBody.innerHTML = '';
      }

      if (closeBtn) {
        closeBtn.addEventListener('click', closeAuthModal);
      }

      if (authModal) {
        authModal.addEventListener('click', function(e) {
          if (e.target === authModal) closeAuthModal();
        });
      }

      // Make auth modal globally accessible
      window.showAuthModal = function(mode) {
        const authModal = document.getElementById('auth-modal');
        const authModalBody = document.getElementById('auth-modal-body');
        const modalContent = authModal ? authModal.querySelector('.auth-modal-content') : null;
        const isRegister = mode === 'register';

        if (modalContent) {
          // Narrow for login, full for register
          if (isRegister) {
            modalContent.classList.remove('login-narrow');
          } else {
            modalContent.classList.add('login-narrow');
          }
        }
        
        if (isRegister) {
          // Render register modal with split design
          authModalBody.innerHTML = `
            <div class="register-split">
              <div class="register-branding">
                <div class="brand-content">
                  <img src="../../assets/images/logo.png" alt="Jessie Cane Logo" class="brand-logo">
                  <p><i class="fas fa-leaf"></i> NATURALLY SWEET<br><i class="fas fa-water"></i> REFRESHINGLY PURE<br><i class="fas fa-bolt"></i> ENERGIZE YOUR DAY</p>
                </div>
              </div>
              <div class="register-form-panel">
                <h2>Sign Up</h2>
                <form class="auth-form" id="registerForm">
                  <div class="form-row">
                    <div class="form-col">
                      <label>First Name</label>
                      <input type="text" id="firstname" required>
                    </div>
                    <div class="form-col">
                      <label>Last Name</label>
                      <input type="text" id="lastname" required>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-col">
                      <label>Username</label>
                      <input type="text" id="username" required>
                    </div>
                    <div class="form-col">
                      <label>Phone</label>
                      <input type="tel" id="phone" required inputmode="numeric" pattern="[0-9]+">
                      <span class="error-message" id="phone-error"></span>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-col">
                      <label>Email</label>
                      <input type="email" id="email" required>
                      <span class="error-message" id="email-error"></span>
                    </div>
                  </div>
                  <div class="form-row form-row-full">
                    <label>Address</label>
                    <input type="text" id="address" required>
                    <span class="error-message" id="address-error"></span>
                  </div>
                  <div class="form-row">
                    <div class="form-col">
                      <label>Password</label>
                      <div class="password-wrapper">
                        <input type="password" id="password" required>
                        <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                      </div>
                      <div class="password-strength">
                        <div class="strength-bar">
                          <div class="strength-fill" id="strength-fill"></div>
                        </div>
                        <span class="strength-text" id="strength-text">Password strength</span>
                      </div>
                      <span class="error-message" id="password-error"></span>
                    </div>
                    <div class="form-col">
                      <label>Confirm Password</label>
                      <div class="password-wrapper">
                        <input type="password" id="confirm-password" required>
                        <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                      </div>
                      <span class="error-message" id="confirm-password-error"></span>
                    </div>
                  </div>
                  <button type="submit" class="btn-main">Sign Up</button>
                  <p class="auth-switch">Already have an account? <a href="#" onclick="window.showAuthModal('login'); return false;">Login here</a></p>
                </form>
              </div>
            </div>
          `;
        } else {
          // Render login modal
          authModalBody.innerHTML = `
            <div class="login-wrapper">
              <h2>LOGIN</h2>
              <form class="auth-form" id="loginForm">
                <div class="form-group">
                  <label>Email or Username</label>
                  <input type="text" id="email" placeholder="Enter your email or username" required>
                  <span class="error-message" id="login-email-error"></span>
                </div>
                <div class="form-group">
                  <label>Password</label>
                  <div class="password-wrapper">
                    <input type="password" id="password" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                  </div>
                  <span class="error-message" id="login-password-error"></span>
                </div>
                <button type="submit" class="btn-main">Login</button>
                <button type="button" id="guestLoginBtn" class="btn-main guest">Continue as Guest</button>
                <button type="button" id="googleLoginBtn" class="btn-main google"><img src="../../assets/images/google_icon.png" alt="" style="height:18px;vertical-align:middle;margin-right:8px;"> Continue with Google</button>
                <p class="auth-switch">Don't have an account? <a href="#" onclick="window.showAuthModal('register'); return false;">Sign Up</a></p>
              </form>
            </div>
          `;
        }
        
        authModal.style.display = 'flex';
        
        // Load auth.js if not already loaded and wire forms
        if (!window.authJSWired) {
          const script = document.createElement('script');
          script.src = 'js/auth.js';
          document.head.appendChild(script);
          script.onload = () => {
            window.authJSWired = true;
            initializeAuthInModal();
          };
        } else {
          initializeAuthInModal();
        }
      };

      function initializeAuthInModal() {
        // Wire password toggles
        document.querySelectorAll('.password-toggle').forEach(btn => {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const wrapper = this.closest('.password-wrapper');
            const input = wrapper ? wrapper.querySelector('input') : this.previousElementSibling;
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            const icon = this.querySelector('i');
            if (icon) {
              icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            }
          });
        });

        // Wire guest login button
        const guestBtn = document.getElementById('guestLoginBtn');
        if (guestBtn) {
          guestBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeAuthModal();
            sessionStorage.removeItem('isLoggedIn');
            sessionStorage.removeItem('currentUser');
            window.location.href = 'drinks.php';
          });
        }

        // Wire Google login button
        const googleLoginBtn = document.getElementById('googleLoginBtn');
        if (googleLoginBtn) {
          googleLoginBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            try {
              // Wait for AuthAPI to be available (api-helper.js loads with defer)
              if (typeof window.AuthAPI === 'undefined' && typeof AuthAPI === 'undefined') {
                // Wait a bit for the script to load
                await new Promise(resolve => setTimeout(resolve, 500));
              }
              
              const authAPI = window.AuthAPI || (typeof AuthAPI !== 'undefined' ? AuthAPI : null);
              
              if (authAPI && typeof authAPI.googleAuth === 'function') {
                try {
                  const result = await authAPI.googleAuth();
                  // If result contains redirect_uri, log it for debugging
                  if (result && result.data && result.data.redirect_uri) {
                    console.log('Google OAuth Redirect URI:', result.data.redirect_uri);
                    console.log('Add this exact URI to Google Cloud Console → OAuth 2.0 Client ID → Authorized redirect URIs');
                  }
                } catch (authError) {
                  console.error('Google auth error:', authError);
                  throw authError;
                }
              } else {
                console.error('AuthAPI not available:', { windowAuthAPI: window.AuthAPI, AuthAPI: typeof AuthAPI });
                alert('Google authentication is not available. Please refresh the page and try again.');
              }
            } catch (error) {
              console.error('Google auth error:', error);
              const errorMessage = error.message || 'Unknown error';
              
              // Check if it's a configuration error
              let helpMessage = '';
              if (errorMessage.includes('not configured') || errorMessage.includes('YOUR_GOOGLE')) {
                helpMessage = '\n\nTo enable Google OAuth:\n' +
                  '1. Get Google OAuth credentials from https://console.cloud.google.com/apis/credentials\n' +
                  '2. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in api/config.php\n' +
                  '3. Add your redirect URI to Google Cloud Console\n' +
                  '4. See GOOGLE-OAUTH-SETUP.md for detailed instructions';
              } else {
                helpMessage = '\n\nPlease check:\n' +
                  '1. Google OAuth credentials are set in api/config.php\n' +
                  '2. Redirect URI is added to Google Cloud Console\n' +
                  '3. See GOOGLE-OAUTH-SETUP.md for setup instructions';
              }
              
              alert('Failed to initiate Google login: ' + errorMessage + helpMessage);
            }
          });
        }

        // Add password strength indicator
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
          passwordInput.addEventListener('input', function() {
            if (typeof updatePasswordStrength === 'function') {
              updatePasswordStrength(this.value);
            }
          });
        }

        // Add real-time validation listeners
        const firstnameInput = document.getElementById('firstname');
        const lastnameInput = document.getElementById('lastname');
        const usernameInput = document.getElementById('username');
        const phoneInput = document.getElementById('phone');
        const emailInput = document.getElementById('email');
        const addressInput = document.getElementById('address');
        const passwordConfirmInput = document.getElementById('confirm-password');
        
        if (firstnameInput && typeof showError === 'function') {
          firstnameInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
              showError('firstname', 'First name is required');
            } else if (typeof hideError === 'function') {
              hideError('firstname');
            }
          });
        }
        
        if (lastnameInput && typeof showError === 'function') {
          lastnameInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
              showError('lastname', 'Last name is required');
            } else if (typeof hideError === 'function') {
              hideError('lastname');
            }
          });
        }
        
        if (usernameInput && typeof showError === 'function') {
          usernameInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
              showError('username', 'Username is required');
            } else if (this.value.length < 3) {
              showError('username', 'Username must be at least 3 characters');
            } else if (typeof hideError === 'function') {
              hideError('username');
            }
          });
        }
        
        if (phoneInput && typeof showError === 'function') {
          phoneInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
              showError('phone', 'Phone number is required');
            } else if (!/^[0-9+\-\s()]+$/.test(this.value)) {
              showError('phone', 'Invalid phone number format');
            } else if (typeof hideError === 'function') {
              hideError('phone');
            }
          });
        }
        
        if (emailInput && typeof showError === 'function') {
          emailInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
              showError('email', 'Email is required');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
              showError('email', 'Invalid email format');
            } else if (typeof hideError === 'function') {
              hideError('email');
            }
          });
        }
        
        if (addressInput && typeof showError === 'function') {
          addressInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
              showError('address', 'Address is required');
            } else if (this.value.length < 10) {
              showError('address', 'Address must be at least 10 characters');
            } else if (typeof hideError === 'function') {
              hideError('address');
            }
          });
        }
        
        if (passwordConfirmInput && typeof showError === 'function') {
          passwordConfirmInput.addEventListener('blur', function() {
            const passwordValue = passwordInput ? passwordInput.value : '';
            if (this.value !== passwordValue) {
              showError('confirm-password', 'Passwords do not match');
            } else if (typeof hideError === 'function') {
              hideError('confirm-password');
            }
          });
        }

        // Wire login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
          loginForm.addEventListener('submit', async function(e) {
  e.preventDefault();

  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;

  if (!email || !password) {
    alert('Please enter both email/username and password.');
    return;
  }

  const formData = new FormData();
  formData.append('email', email);
  formData.append('password', password);

  try {
    const response = await fetch('../login.php', {
      method: 'POST',
      body: formData
    });

    // Check if response is OK
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    // Try to parse JSON response
    let data;
    const text = await response.text();
    try {
      data = JSON.parse(text);
    } catch (parseError) {
      console.error("Response text:", text);
      throw new Error("Invalid JSON response from server: " + text.substring(0, 100));
    }

    if (data.success) {
      alert('Login successful!');
      sessionStorage.setItem('isLoggedIn', 'true');
      sessionStorage.setItem('currentUser', JSON.stringify(data.user));
      closeAuthModal();
      window.location.href = 'customer_dashboard.php';
    } else {
      alert(data.message || 'Invalid login credentials.');
    }
  } catch (err) {
    console.error('Error:', err);
    alert('Server error. Please try again later.');
  }
});

        }

        // Wire register form (modified to connect to PHP/MySQL)
const registerForm = document.getElementById('registerForm');
if (registerForm) {
  registerForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    const firstname = document.getElementById('firstname').value.trim();
    const lastname = document.getElementById('lastname').value.trim();
    const username = document.getElementById('username').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const email = document.getElementById('email').value.trim();
    const address = document.getElementById('address').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    if (password !== confirmPassword) {
      alert('Passwords do not match!');
      return;
    }

    const formData = new FormData();
    formData.append('firstname', firstname);
    formData.append('lastname', lastname);
    formData.append('username', username);
    formData.append('phone', phone);
    formData.append('email', email);
    formData.append('address', address);
    formData.append('password', password);

    try {
      // Send data to PHP backend
      const response = await fetch('../register.php', {
        method: 'POST',
        body: formData
      });

      // Check if response is OK
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      // Try to parse JSON response
      let data;
      const text = await response.text();
      try {
        data = JSON.parse(text);
      } catch (parseError) {
        console.error("Response text:", text);
        throw new Error("Invalid JSON response from server: " + text.substring(0, 100));
      }

      if (data.success) {
        alert('Account created successfully!');
        closeAuthModal();
        window.location.href = 'customer_dashboard.php'; // Redirect after registration
      } else {
        alert(data.message || 'Registration failed.');
      }
    } catch (err) {
      console.error('Registration Error:', err);
      const errorMessage = err.message || 'Server error. Please try again later.';
      alert(errorMessage);
    }
  });
}

      }

      window.closeAuthModal = closeAuthModal;
    });
  </script>
  <!-- Zoom panel moved to shared header.js/css so it's available on all pages -->

  <script>
    // Alternator: toggles between the heading and the service-invite every 5 seconds
    // with pause/resume when the user hovers or touches the invite.
    (function(){
      const HEADING = document.getElementById('alt-heading');
      const INVITE = document.getElementById('service-invite');
      if (!HEADING || !INVITE) return;

      let showingHeading = true;
      const INTERVAL = 5000; // 5 seconds
      let timerId = null;
      let paused = false;

      function showHeading() {
        INVITE.classList.remove('alt-visible');
        INVITE.classList.add('alt-hidden');
        HEADING.classList.remove('alt-hidden');
        HEADING.classList.add('alt-visible');
        showingHeading = true;
      }

      function showInvite() {
        HEADING.classList.remove('alt-visible');
        HEADING.classList.add('alt-hidden');
        INVITE.classList.remove('alt-hidden');
        INVITE.classList.add('alt-visible');
        showingHeading = false;
      }

      function toggle() {
        if (showingHeading) showInvite(); else showHeading();
      }

      function startTimer() {
        if (timerId) clearInterval(timerId);
        timerId = setInterval(() => { if (!paused) toggle(); }, INTERVAL);
      }

  const PAUSE_IND = document.getElementById('pause-indicator');
  function pause() { paused = true; if (PAUSE_IND) PAUSE_IND.classList.add('pause-indicator--visible'); }
  function resume() { paused = false; if (PAUSE_IND) PAUSE_IND.classList.remove('pause-indicator--visible'); }

      // Pause when user interacts with the invite (hover or touch)
      INVITE.addEventListener('mouseenter', pause);
      INVITE.addEventListener('mouseleave', resume);
      INVITE.addEventListener('touchstart', pause, {passive: true});
      INVITE.addEventListener('touchend', resume);

      // Also pause when hovering over the heading area to avoid accidental flips
      HEADING.addEventListener('mouseenter', pause);
      HEADING.addEventListener('mouseleave', resume);

      // Initialize
      startTimer();
    })();
  </script>
</body>
</html>