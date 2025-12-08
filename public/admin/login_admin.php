<?php
// ---------- BACKEND LOGIN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start output buffering to prevent any unwanted output
    ob_start();
    
    // Suppress errors and warnings
    error_reporting(0);
    ini_set('display_errors', 0);
    
    header('Content-Type: application/json');
    
    // Connect to database
    $conn = null;
    if (file_exists('../db_connect.php')) {
        @include '../db_connect.php';
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    try {
        $adminEmail = 'admin@gmail.com';
        $adminPassword = 'admin123';
        
        // Check database for admin user
        if ($conn && !$conn->connect_error) {
            // Query users table for admin - check by email, username, or any admin email
            $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR username = ? OR (email LIKE ? AND role = 'admin')) AND role = 'admin' AND is_active = 1");
            $emailLike = '%admin%';
            if ($stmt) {
                $stmt->bind_param("sss", $email, $email, $emailLike);
                $stmt->execute();
                
                // Use get_result() if available, otherwise use store_result()
                if (method_exists($stmt, 'get_result')) {
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $num_rows = $result->num_rows;
                } else {
                    $stmt->store_result();
                    $num_rows = $stmt->num_rows;
                    $user = null;
                    if ($num_rows > 0) {
                        $stmt->bind_result($user_id, $firstname, $lastname, $username, $user_email, $phone, $address, $user_password, $role, $is_active, $date_created);
                        $stmt->fetch();
                        $user = [
                            'user_id' => $user_id,
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'username' => $username,
                            'email' => $user_email,
                            'phone' => $phone,
                            'address' => $address,
                            'password' => $user_password,
                            'role' => $role,
                            'is_active' => $is_active,
                            'date_created' => $date_created
                        ];
                    }
                }
                
                if ($num_rows > 0 && $user) {
                    // Verify password - try password_verify first (works for hashed), then plain text
                    $passwordValid = false;
                    
                    // Always try password_verify first - it will return false if password is not hashed
                    if (password_verify($password, $user['password'])) {
                        $passwordValid = true;
                    } 
                    // If password_verify fails, try plain text comparison (for backward compatibility)
                    elseif ($user['password'] === $password) {
                        $passwordValid = true;
                    }
                    
                    if ($passwordValid) {
                        @session_start();
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_id'] = $user['user_id'];
                        
                        ob_clean();
                        echo json_encode([
                            'success' => true,
                            'admin' => [
                                'id' => $user['user_id'],
                                'email' => $user['email'],
                                'username' => $user['username'],
                                'role' => 'admin',
                                'name' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?: 'Admin User'
                            ]
                        ]);
                        if ($stmt) $stmt->close();
                        exit;
                    }
                }
                if ($stmt) $stmt->close();
            }
        }
        
        // Fallback: Use hardcoded admin credentials if database check fails or user not found
        if (($email === $adminEmail || $email === 'admin') && $password === $adminPassword) {
            @session_start();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $adminEmail;
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'admin' => [
                    'email' => $adminEmail,
                    'role' => 'admin',
                    'name' => 'Admin User'
                ]
            ]);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } catch (Exception $e) {
        // Fallback on any exception
        $adminEmail = 'admin@gmail.com';
        $adminPassword = 'admin123';
        
        if (($email === $adminEmail || $email === 'admin') && $password === $adminPassword) {
            @session_start();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $adminEmail;
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'admin' => [
                    'email' => $adminEmail,
                    'role' => 'admin',
                    'name' => 'Admin User'
                ]
            ]);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Jessie Cane</title>
  <link rel="stylesheet" href="../../assets/css/shared.css">
  <link rel="stylesheet" href="css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <style>
    html, body { height: 100%; overflow: hidden; }
    body {
      background-color: #fffde9;
      margin: 0;
      padding: 0;
    }
    .admin-branding {
      background: linear-gradient(135deg, #8B4513, #A0522D);
      color: white;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .admin-branding h2 {
      color: white;
      margin: 0;
    }
    .admin-branding p {
      margin: 5px 0 0 0;
      font-size: 14px;
      opacity: 0.9;
    }
    .forgot-password {
      text-align: center;
      margin-top: 10px;
    }
    .forgot-password a {
      color: #A0522D;
      text-decoration: none;
      font-size: 14px;
    }
    .forgot-password a:hover {
      text-decoration: underline;
    }
    :root {
      --header-bg: #8B4513;
      --header-text: #FAF9F4;
      --header-accent: #E5C100;
    }
    header, .navbar {
      background-color: var(--header-bg) !important;
      color: var(--header-text) !important;
    }
    .nav-btn, .navbar a { color: var(--header-text) !important; }
    .nav-btn::after, .navbar a::after { background-color: var(--header-accent) !important; }
    .admin-branding h2 {
      font-family: "Plus Jakarta Sans", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      font-weight: 800;
      letter-spacing: 0.4px;
    }
    .left-header span {
      font-family: "Plus Jakarta Sans", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      font-weight: 700;
      letter-spacing: 0.2px;
    }
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: none; align-items: center; justify-content: center; z-index: 1200; backdrop-filter: blur(3px); }
    .modal { background: #fffef9; border-radius: 16px; width: min(520px, 92vw); box-shadow: 0 18px 48px rgba(0,0,0,.18); overflow: hidden; border: 1px solid rgba(0,0,0,.06); }
    .modal__header { background: linear-gradient(135deg, var(--header-bg), var(--header-accent)); color: #fff; padding: 14px 18px; display:flex; align-items:center; justify-content: space-between; }
    .modal__title { margin: 0; font-weight: 800; letter-spacing: .3px; }
    .modal__close { background: transparent; border: 0; color: #fff; font-size: 22px; cursor: pointer; line-height: 1; }
    .modal__body { padding: 18px; color: #1f2937; font-size: 14px; }
    .modal__actions { padding: 0 18px 18px 18px; display:flex; justify-content:flex-end; gap:10px; }
    .btn-modal { border: 0; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor:pointer; }
    .btn-modal--primary { background: linear-gradient(135deg, var(--header-bg), var(--header-accent)); color:#fff; }
    .btn-modal--ghost { background: #fff; color: var(--header-bg); border: 1px solid rgba(0,0,0,.1); }
    .loading-spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-right: 8px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>
  <header>
    <div class="left-header">
      <img src="../../assets/images/logo.png" alt="Jessie Cane Logo" onerror="this.style.display='none'">
      <span style="font-size: 18px; font-weight: bold;">Admin Portal</span>
    </div>
    <div class="right-header">
      <a href="../index.php" class="nav-btn">Back to Portal</a>
      <a href="login_admin.php" class="nav-btn active">Login</a>
    </div>
  </header>

  <main style="display: flex; justify-content: center; align-items: center; height: calc(100vh - 80px); padding: 20px;">
    <div class="login-container">
      <div class="admin-branding">
        <h2><i class="fas fa-user-shield"></i> ADMIN LOGIN</h2>
        <p>Administrator access only</p>
      </div>
      <form class="auth-form" id="adminLoginForm">
        <label for="email">Email</label>
        <input type="email" id="email" placeholder="Enter your email" required>

        <label for="password">Password</label>
        <div class="password-wrapper">
          <input type="password" id="password" placeholder="Enter your password" required>
          <button type="button" class="password-toggle" id="togglePassword">
            <i class="fas fa-eye"></i>
          </button>
        </div>

        <button type="submit" class="btn-main" id="adminLoginBtn" style="background: linear-gradient(135deg, #8B4513, #A0522D);">
          <i class="fas fa-sign-in-alt"></i> Log In
        </button>

        <div class="forgot-password">
          <a href="#" id="forgotPasswordLink">Forgot Password?</a>
        </div>
      </form>
    </div>
  </main>

  <!-- Forgot Password Modal -->
  <div class="modal-overlay" id="forgotModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
      <div class="modal__header">
        <h3 class="modal__title" id="forgotTitle"><i class="fas fa-key"></i> Password Assistance</h3>
        <button class="modal__close" id="forgotClose" aria-label="Close">&times;</button>
      </div>
      <div class="modal__body">
        Please contact the system administrator to reset your password.<br><br>
        Default credentials: <strong>admin@gmail.com</strong> / <strong>admin123</strong>
      </div>
      <div class="modal__actions">
        <button class="btn-modal btn-modal--primary" id="forgotOk">OK</button>
      </div>
    </div>
  </div>

  <!-- Login Error Modal -->
  <div class="modal-overlay" id="errorModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="errorTitle">
      <div class="modal__header">
        <h3 class="modal__title" id="errorTitle"><i class="fas fa-triangle-exclamation"></i> Login Error</h3>
        <button class="modal__close" id="errorClose" aria-label="Close">&times;</button>
      </div>
      <div class="modal__body" id="errorMessage">Invalid email or password!</div>
      <div class="modal__actions">
        <button class="btn-modal btn-modal--primary" id="errorOk">OK</button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('adminLoginForm');
      const forgotLink = document.getElementById('forgotPasswordLink');
      const loginBtn = document.getElementById('adminLoginBtn');

      // Helper function to get admin credentials from localStorage (set by admin panel)
      function getAdminCredentialsFromLocalStorage(inputEmail) {
        const ADMIN_CREDENTIALS_KEY = 'jessie_admin_credentials';
        const creds = localStorage.getItem(ADMIN_CREDENTIALS_KEY);
        if (creds) {
          try {
            const parsed = JSON.parse(creds);
            // Check if input email matches stored email (handles email updates)
            if (parsed.email && inputEmail.toLowerCase() === parsed.email.toLowerCase()) {
              return parsed;
            }
            // Also check if input matches default admin email
            if (inputEmail.toLowerCase() === 'admin@gmail.com' || inputEmail.toLowerCase() === 'admin') {
              return parsed;
            }
          } catch (e) {
            // Invalid JSON, ignore
          }
        }
        return null;
      }

      // Login handler
      form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        
        // Check localStorage first (credentials updated by admin panel)
        const localStorageCreds = getAdminCredentialsFromLocalStorage(email);
        if (localStorageCreds) {
          // Verify credentials against localStorage (handles both email and password updates)
          if (localStorageCreds.email && localStorageCreds.email.toLowerCase() === email.toLowerCase() && localStorageCreds.password === password) {
            // Credentials match localStorage - proceed with login
            console.log('Admin credentials validated against localStorage');
          } else if (localStorageCreds.password && localStorageCreds.password !== password) {
            // Password doesn't match - show error
            openErrorModal('Invalid password. Please use the password set in admin profile management.');
            resetLoginButton();
            return;
          } else if (localStorageCreds.email && localStorageCreds.email.toLowerCase() !== email.toLowerCase()) {
            // Email doesn't match - show error
            openErrorModal('Invalid email. Please use the email set in admin profile management: ' + localStorageCreds.email);
            resetLoginButton();
            return;
          }
        }
        
        // Show loading
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<span class="loading-spinner"></span> Logging in...';

        try {
          const formData = new FormData();
          formData.append('email', email);
          formData.append('password', password);

          const response = await fetch('login_admin.php', { method: 'POST', body: formData });
          
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          const text = await response.text();
          let data;
          try {
            data = JSON.parse(text);
          } catch (e) {
            console.error('Invalid JSON response:', text);
            throw new Error('Invalid server response. Check console for details.');
          }

          // If server validation failed but we have localStorage credentials, allow login
          if (!data.success && localStorageCreds) {
            const credsEmail = localStorageCreds.email ? localStorageCreds.email.toLowerCase() : '';
            const credsPassword = localStorageCreds.password || '';
            const inputEmail = email.toLowerCase();
            
            if (credsEmail === inputEmail && credsPassword === password) {
              console.log('Server validation failed, but localStorage credentials match - allowing admin login');
              data = {
                success: true,
                admin: {
                  email: localStorageCreds.email || email,
                  role: 'admin',
                  name: 'Admin User'
                }
              };
            }
          }

          if (data.success) {
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('currentUser', JSON.stringify({
              username: 'admin',
              email: data.admin.email,
              role: 'admin',
              name: data.admin.name
            }));
            window.location.href = 'admin.php';
          } else {
            openErrorModal(data.message || 'Invalid email or password!');
            resetLoginButton();
          }
        } catch (err) {
          console.error('Login error:', err);
          openErrorModal('Server error: ' + (err.message || 'Please try again later.'));
          resetLoginButton();
        }
      });

      function resetLoginButton() {
        loginBtn.disabled = false;
        loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Log In';
      }

      // Forgot password modal
      const forgotModal = document.getElementById('forgotModal');
      const forgotClose = document.getElementById('forgotClose');
      const forgotOk = document.getElementById('forgotOk');
      function openForgot(){ if (forgotModal) { forgotModal.style.display = 'flex'; } }
      function closeForgot(){ if (forgotModal) { forgotModal.style.display = 'none'; } }
      forgotLink.addEventListener('click', function(e){ e.preventDefault(); openForgot(); });
      if (forgotClose) forgotClose.addEventListener('click', closeForgot);
      if (forgotOk) forgotOk.addEventListener('click', closeForgot);
      if (forgotModal) forgotModal.addEventListener('click', function(e){ if (e.target === forgotModal) closeForgot(); });

      // Error modal
      const errorModal = document.getElementById('errorModal');
      const errorClose = document.getElementById('errorClose');
      const errorOk = document.getElementById('errorOk');
      const errorMessage = document.getElementById('errorMessage');
      function openErrorModal(message){ if (errorMessage) errorMessage.textContent = message || 'Something went wrong.'; if (errorModal) errorModal.style.display = 'flex'; }
      function closeErrorModal(){ if (errorModal) errorModal.style.display = 'none'; }
      if (errorClose) errorClose.addEventListener('click', closeErrorModal);
      if (errorOk) errorOk.addEventListener('click', closeErrorModal);
      if (errorModal) errorModal.addEventListener('click', function(e){ if (e.target === errorModal) closeErrorModal(); });
      
      // Password toggle
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
      
      if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
          const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
          passwordInput.setAttribute('type', type);
          const icon = togglePassword.querySelector('i');
          if (type === 'password') {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
          } else {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
          }
        });
      }
    });
  </script>
</body>
</html>

