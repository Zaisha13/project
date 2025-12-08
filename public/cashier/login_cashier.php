<?php
// ---------- BACKEND LOGIN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start output buffering to prevent any unwanted output
    ob_start();
    error_reporting(0);
    ini_set('display_errors', 0);
    
    header('Content-Type: application/json');
    
    // Try to connect to database
    $conn = null;
    if (file_exists('db_connect.php')) {
        @include 'db_connect.php';
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    try {
        // ✅ Define allowed emails for each branch
        $branchEmails = [
            'cashierfairview@gmail.com' => 'fairview',
            'cashiersjdm@gmail.com' => 'sjdm'
        ];

        // Check if email belongs to a valid branch
        // Also check database for updated emails that might match the branch
        $branch = null;
        foreach ($branchEmails as $allowedEmail => $branchName) {
            if (strtolower($email) === strtolower($allowedEmail)) {
                $branch = $branchName;
                break;
            }
        }
        
        // If not found in default emails, check database for updated emails
        if (!$branch && $conn && !$conn->connect_error) {
            $checkStmt = $conn->prepare("SELECT branch_id FROM cashiers WHERE email = ?");
            if ($checkStmt) {
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                if (method_exists($checkStmt, 'get_result')) {
                    $checkResult = $checkStmt->get_result();
                    if ($checkRow = $checkResult->fetch_assoc()) {
                        // branch_id 1 = fairview, branch_id 2 = sjdm
                        $branch = ($checkRow['branch_id'] == 1) ? 'fairview' : 'sjdm';
                    }
                }
                $checkStmt->close();
            }
        }

        if (!$branch) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Access denied: Invalid cashier account.']);
            exit;
        }

        // If database is available, check credentials
        if ($conn && !$conn->connect_error) {
            $stmt = $conn->prepare("SELECT * FROM cashiers WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                // Use get_result() if available, otherwise use store_result()
                if (method_exists($stmt, 'get_result')) {
                    $result = $stmt->get_result();
                    $cashier = $result->fetch_assoc();
                    $num_rows = $result->num_rows;
                } else {
                    $stmt->store_result();
                    $num_rows = $stmt->num_rows;
                    $cashier = null;
                    if ($num_rows > 0) {
                        $stmt->bind_result($cashier_id, $cashier_email, $cashier_password, $firstname, $lastname, $branch_id, $is_active, $date_created);
                        $stmt->fetch();
                        $cashier = [
                            'cashier_id' => $cashier_id,
                            'email' => $cashier_email,
                            'password' => $cashier_password,
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'branch_id' => $branch_id,
                            'is_active' => $is_active,
                            'date_created' => $date_created
                        ];
                    }
                }

                if ($num_rows === 0 || !$cashier) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
                    if ($stmt) $stmt->close();
                    exit;
                }

            // ✅ Match password - try password_verify first (works for hashed), then plain text
            $passwordValid = false;
            
            // Always try password_verify first - it will return false if password is not hashed
            // This handles all hash formats ($2y$, $2a$, $2b$, etc.)
            if (password_verify($password, $cashier['password'])) {
                $passwordValid = true;
            } 
            // If password_verify fails, try plain text comparison (for backward compatibility)
            elseif ($cashier['password'] === $password) {
                $passwordValid = true;
            }
            
            if ($passwordValid) {
                // Generate token for API authentication
                $token = base64_encode(json_encode([
                    'user_id' => $cashier['cashier_id'],
                    'role' => 'cashier'
                ]));
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'branch' => $branch,
                    'token' => $token,
                    'cashier' => [
                        'id' => $cashier['cashier_id'],
                        'email' => $cashier['email'],
                        'role' => 'cashier',
                        'branch' => $branch
                    ]
                ]);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            }

                if ($stmt) $stmt->close();
                if ($conn) $conn->close();
            } else {
                // SQL prepare failed, use fallback
                throw new Exception('Database query failed');
            }
        } else {
            // Fallback: Check localStorage credentials (set by admin panel) or hardcoded defaults
            // Note: This is a server-side fallback, but we'll use the default credentials
            // The actual localStorage check happens on the client side via JavaScript
            $hardcodedCredentials = [
                'cashierfairview@gmail.com' => 'cashierfairview',
                'cashiersjdm@gmail.com' => 'cashiersjdm'
            ];
            
            if (isset($hardcodedCredentials[strtolower($email)]) && $password === $hardcodedCredentials[strtolower($email)]) {
                // Generate token for API authentication
                $token = base64_encode(json_encode([
                    'user_id' => 1,
                    'role' => 'cashier'
                ]));
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'branch' => $branch,
                    'token' => $token,
                    'cashier' => [
                        'id' => 1,
                        'email' => $email,
                        'role' => 'cashier',
                        'branch' => $branch
                    ]
                ]);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            }
        }
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cashier Login | Jessie Cane</title>
  <link rel="stylesheet" href="../../assets/css/shared.css">
  <link rel="stylesheet" href="css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <style>
    html, body { height: 100%; overflow: hidden; }
    body { background-color: #fffde9; margin: 0; padding: 0; }
    
    /* Unified theme - Purple gradient for unified cashier portal */
    .cashier-branding {
      background: linear-gradient(135deg, #6B21A8, #A855F7);
      color: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      text-align: center;
    }
    .cashier-branding h2 { margin: 0; font-size: 22px; }
    .cashier-branding p { margin: 8px 0 0 0; font-size: 14px; opacity: 0.9; }
    .cashier-branding .branch-info {
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid rgba(255,255,255,0.2);
      font-size: 13px;
    }
    .cashier-branding .branch-badges {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 10px;
    }
    .cashier-branding .branch-badge {
      background: rgba(255,255,255,0.2);
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
    }
    .cashier-branding .branch-badge.fairview { background: rgba(30, 58, 138, 0.8); }
    .cashier-branding .branch-badge.sjdm { background: rgba(217, 119, 6, 0.8); }
    
    .forgot-password { text-align: center; margin-top: 10px; }
    .forgot-password a { color: #A855F7; text-decoration: none; font-size: 14px; }
    .forgot-password a:hover { text-decoration: underline; }
    
    :root { 
      --header-bg: #6B21A8; 
      --header-text: #FAF9F4; 
      --header-accent: #A855F7; 
    }
    
    header, .navbar { background-color: var(--header-bg) !important; color: var(--header-text) !important; }
    .nav-btn, .navbar a { color: var(--header-text) !important; }
    .nav-btn::after, .navbar a::after { background-color: var(--header-accent) !important; }
    
    .modal-overlay { 
      position: fixed; inset: 0; 
      background: rgba(0,0,0,.45); 
      display: none; 
      align-items: center; 
      justify-content: center; 
      z-index: 1200; 
      backdrop-filter: blur(3px); 
    }
    .modal { 
      background: #fffef9; 
      border-radius: 16px; 
      width: min(520px, 92vw); 
      box-shadow: 0 18px 48px rgba(0,0,0,.18); 
      border: 1px solid rgba(0,0,0,.06); 
      overflow: hidden;
    }
    .modal__header { 
      background: linear-gradient(135deg, var(--header-bg), var(--header-accent)); 
      color: #fff; 
      padding: 14px 18px; 
      display:flex; 
      justify-content: space-between; 
      align-items:center; 
    }
    .modal__body { padding: 18px; color: #1f2937; font-size: 14px; }
    .btn-modal { border: 0; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor:pointer; }
    .btn-modal--primary { background: linear-gradient(135deg, var(--header-bg), var(--header-accent)); color:#fff; }
    
    /* Login button with unified purple theme */
    .btn-main {
      background: linear-gradient(135deg, #6B21A8, #A855F7) !important;
    }
    .btn-main:hover {
      background: linear-gradient(135deg, #7c3aed, #c084fc) !important;
    }
    
    /* Success modal */
    .modal--success .modal__header {
      background: linear-gradient(135deg, #059669, #10B981);
    }
    .branch-redirect-info {
      text-align: center;
      padding: 15px;
    }
    .branch-redirect-info i {
      font-size: 48px;
      margin-bottom: 15px;
    }
    .branch-redirect-info.fairview i { color: #1E3A8A; }
    .branch-redirect-info.sjdm i { color: #D97706; }
    .branch-redirect-info h4 { margin: 10px 0 5px; font-size: 18px; }
    .branch-redirect-info p { margin: 0; color: #666; }
    
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-right: 8px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <header>
    <div class="left-header">
      <img src="../../assets/images/logo.png" alt="Jessie Cane Logo" onerror="this.style.display='none'">
      <span id="portalTitle" style="font-size: 18px; font-weight: bold;">Jessie Cane Cashier Portal</span>
    </div>
    <div class="right-header">
      <a href="../index.php" class="nav-btn">Back to Portal</a>
      <a href="login_cashier.php" class="nav-btn active">Login</a>
    </div>
  </header>

  <main style="display: flex; justify-content: center; align-items: center; height: calc(100vh - 80px); padding: 20px;">
    <div class="login-container">
      <div class="cashier-branding" id="cashierBranding">
        <h2><i class="fas fa-cash-register"></i> CASHIER LOGIN</h2>
        <p>Unified Cashier Portal</p>
        <div class="branch-info">
          <span>Supported Branches:</span>
          <div class="branch-badges">
            <span class="branch-badge fairview"><i class="fas fa-store"></i> SM Fairview</span>
            <span class="branch-badge sjdm"><i class="fas fa-store"></i> SM San Jose del Monte</span>
          </div>
        </div>
      </div>

      <form class="auth-form" id="cashierLoginForm">
        <label for="email">Email</label>
        <input type="email" id="email" placeholder="Enter your branch email" required>

        <label for="password">Password</label>
        <div class="password-wrapper">
          <input type="password" id="password" placeholder="Enter your password" required>
          <button type="button" class="password-toggle" id="togglePassword"><i class="fas fa-eye"></i></button>
        </div>

        <button type="submit" class="btn-main" id="loginBtn">
          <i class="fas fa-sign-in-alt"></i> Log In
        </button>

        <div class="forgot-password">
          <a href="#" id="forgotPasswordLink">Forgot Password?</a>
        </div>
      </form>
    </div>
  </main>

  <!-- Forgot Password Modal -->
  <div class="modal-overlay" id="forgotModal">
    <div class="modal">
      <div class="modal__header">
        <h3><i class="fas fa-key"></i> Password Assistance</h3>
        <button id="forgotClose" style="background:none;border:0;color:#fff;font-size:20px;">&times;</button>
      </div>
      <div class="modal__body">
        Please contact the <strong>system administrator</strong> to reset your password.<br><br>
        Email <strong>support@jessiecane.com</strong> or call <strong>+63 912 345 6789</strong>.
      </div>
      <div style="padding: 0 18px 18px 18px; text-align:right;">
        <button class="btn-modal btn-modal--primary" id="forgotOk">OK</button>
      </div>
    </div>
  </div>

  <!-- Error Modal -->
  <div class="modal-overlay" id="errorModal">
    <div class="modal">
      <div class="modal__header">
        <h3><i class="fas fa-triangle-exclamation"></i> Login Error</h3>
        <button id="errorClose" style="background:none;border:0;color:#fff;font-size:20px;">&times;</button>
      </div>
      <div class="modal__body" id="errorMessage">Invalid email or password!</div>
    </div>
  </div>

  <!-- Success/Redirect Modal -->
  <div class="modal-overlay" id="successModal">
    <div class="modal modal--success">
      <div class="modal__header">
        <h3><i class="fas fa-check-circle"></i> Login Successful</h3>
      </div>
      <div class="modal__body">
        <div class="branch-redirect-info" id="branchRedirectInfo">
          <i class="fas fa-store"></i>
          <h4 id="branchName">Loading...</h4>
          <p>Redirecting to your branch dashboard...</p>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('cashierLoginForm');
      const forgotModal = document.getElementById('forgotModal');
      const forgotClose = document.getElementById('forgotClose');
      const forgotOk = document.getElementById('forgotOk');
      const forgotLink = document.getElementById('forgotPasswordLink');
      const errorModal = document.getElementById('errorModal');
      const errorClose = document.getElementById('errorClose');
      const errorMessage = document.getElementById('errorMessage');
      const successModal = document.getElementById('successModal');
      const branchRedirectInfo = document.getElementById('branchRedirectInfo');
      const branchName = document.getElementById('branchName');
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
      const loginBtn = document.getElementById('loginBtn');

      // Branch display names and redirect URLs
      const branchConfig = {
        fairview: {
          name: 'SM Fairview',
          url: '../cashier_fairview/cashier.php',
          color: '#1E3A8A'
        },
        sjdm: {
          name: 'SM San Jose del Monte',
          url: '../cashier_sjdm/cashier.php',
          color: '#D97706'
        }
      };

      // Helper function to get credentials from localStorage (set by admin panel)
      function getCredentialsFromLocalStorage(inputEmail) {
        const CASHIER_FAIRVIEW_KEY = 'jessie_cashier_fairview_credentials';
        const CASHIER_SJDM_KEY = 'jessie_cashier_sjdm_credentials';
        
        // Check Fairview credentials
        const fairviewCreds = localStorage.getItem(CASHIER_FAIRVIEW_KEY);
        if (fairviewCreds) {
          try {
            const creds = JSON.parse(fairviewCreds);
            // Check if input email matches stored email (handles email updates)
            if (creds.email && inputEmail.toLowerCase() === creds.email.toLowerCase()) {
              return creds;
            }
            // Also check if input matches default Fairview email
            if (inputEmail.toLowerCase() === 'cashierfairview@gmail.com') {
              return creds;
            }
          } catch (e) {
            // Invalid JSON, ignore
          }
        }
        
        // Check SJDM credentials
        const sjdmCreds = localStorage.getItem(CASHIER_SJDM_KEY);
        if (sjdmCreds) {
          try {
            const creds = JSON.parse(sjdmCreds);
            // Check if input email matches stored email (handles email updates)
            if (creds.email && inputEmail.toLowerCase() === creds.email.toLowerCase()) {
              return creds;
            }
            // Also check if input matches default SJDM email
            if (inputEmail.toLowerCase() === 'cashiersjdm@gmail.com') {
              return creds;
            }
          } catch (e) {
            // Invalid JSON, ignore
          }
        }
        
        return null;
      }

      // 🔐 Login Handler
      form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();
        const password = passwordInput.value;

        // Check localStorage first (credentials updated by admin panel)
        const localStorageCreds = getCredentialsFromLocalStorage(email);
        if (localStorageCreds) {
          // Verify credentials against localStorage (handles both email and password updates)
          if (localStorageCreds.email && localStorageCreds.email.toLowerCase() === email.toLowerCase() && localStorageCreds.password === password) {
            // Credentials match localStorage - proceed with login
            console.log('Credentials validated against localStorage');
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

        // Show loading state
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<span class="loading-spinner"></span> Logging in...';

        try {
          const formData = new FormData();
          formData.append('email', email);
          formData.append('password', password);

          const response = await fetch('login_cashier.php', { method: 'POST', body: formData });
          
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
              console.log('Server validation failed, but localStorage credentials match - allowing login');
              // Determine branch from email
              let branch = 'sjdm';
              if (credsEmail.includes('fairview') || inputEmail.includes('fairview')) {
                branch = 'fairview';
              }
              
              // Create a success response using localStorage credentials
              data = {
                success: true,
                branch: branch,
                token: btoa(JSON.stringify({ user_id: 1, role: 'cashier' })),
                cashier: {
                  id: 1,
                  email: localStorageCreds.email || email,
                  role: 'cashier',
                  branch: branch
                }
              };
            }
          }

          if (data.success) {
            // Store user data
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('currentUser', JSON.stringify(data.cashier));
            // Store token for API authentication
            if (data.token) {
              localStorage.setItem('token', data.token);
              sessionStorage.setItem('token', data.token);
            }
            
            // Get branch config
            const branch = branchConfig[data.branch];
            
            // Show success modal with branch info
            branchRedirectInfo.className = 'branch-redirect-info ' + data.branch;
            branchName.textContent = branch.name;
            branchRedirectInfo.querySelector('i').style.color = branch.color;
            successModal.style.display = 'flex';
            
            // Redirect after 1.5 seconds
            setTimeout(() => {
              window.location.href = branch.url;
            }, 1500);
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
      function openForgot() { forgotModal.style.display = 'flex'; }
      function closeForgot() { forgotModal.style.display = 'none'; }
      forgotLink.addEventListener('click', e => { e.preventDefault(); openForgot(); });
      forgotClose.addEventListener('click', closeForgot);
      forgotOk.addEventListener('click', closeForgot);
      forgotModal.addEventListener('click', e => { if (e.target === forgotModal) closeForgot(); });

      // Error modal
      function openErrorModal(msg) { errorMessage.textContent = msg; errorModal.style.display = 'flex'; }
      errorClose.addEventListener('click', () => errorModal.style.display = 'none');
      errorModal.addEventListener('click', e => { if (e.target === errorModal) errorModal.style.display = 'none'; });

      // Password toggle
      togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        const icon = togglePassword.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
      });
    });
  </script>
</body>
</html>

