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
        // ✅ Allow cashiersjdm@gmail.com or updated email from database
        $allowedEmail = 'cashiersjdm@gmail.com';
        $emailAllowed = false;

        // Check if email matches default or check database for updated email
        if (strtolower($email) === strtolower($allowedEmail)) {
            $emailAllowed = true;
        } elseif ($conn && !$conn->connect_error) {
            // Check if email exists in database for branch_id = 2 (SJDM)
            $checkStmt = $conn->prepare("SELECT branch_id FROM cashiers WHERE email = ? AND branch_id = 2");
            if ($checkStmt) {
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                if (method_exists($checkStmt, 'get_result')) {
                    $checkResult = $checkStmt->get_result();
                    if ($checkResult->num_rows > 0) {
                        $emailAllowed = true;
                    }
                } else {
                    $checkStmt->store_result();
                    if ($checkStmt->num_rows > 0) {
                        $emailAllowed = true;
                    }
                }
                $checkStmt->close();
            }
        }

        if (!$emailAllowed) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Access denied: Wrong Cashier Branch Account.']);
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

            // ✅ Match plain password (or password_verify if hashed)
            $passwordValid = false;
            if (password_verify($password, $cashier['password'])) {
                $passwordValid = true;
            } elseif ($cashier['password'] === $password) {
                $passwordValid = true;
            }
            
            if ($passwordValid) {
                // Start session and set cashier session variables
                @session_start();
                $_SESSION['cashier_logged_in'] = true;
                $_SESSION['cashier_id'] = $cashier['cashier_id'];
                $_SESSION['cashier_email'] = $cashier['email'];
                $_SESSION['cashier_branch'] = 'sjdm';
                $_SESSION['cashier_role'] = 'cashier';
                
                // Generate token for API authentication
                $token = base64_encode(json_encode([
                    'user_id' => $cashier['cashier_id'],
                    'role' => 'cashier'
                ]));
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'token' => $token,
                    'cashier' => [
                        'id' => $cashier['cashier_id'],
                        'email' => $cashier['email'],
                        'role' => 'cashier'
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
            // Fallback: Hardcoded credentials from database if connection fails
            if ($password === 'cashiersjdm') {
                // Start session and set cashier session variables
                @session_start();
                $_SESSION['cashier_logged_in'] = true;
                $_SESSION['cashier_id'] = 1;
                $_SESSION['cashier_email'] = $email;
                $_SESSION['cashier_branch'] = 'sjdm';
                $_SESSION['cashier_role'] = 'cashier';
                
                // Generate token for API authentication
                $token = base64_encode(json_encode([
                    'user_id' => 1,
                    'role' => 'cashier'
                ]));
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'token' => $token,
                    'cashier' => [
                        'id' => 1,
                        'email' => $email,
                        'role' => 'cashier'
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cashier Login | Jessie Cane</title>
  <link rel="stylesheet" href="../../assets/css/shared.css" />
  <link rel="stylesheet" href="css/auth.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    html, body { height: 100%; overflow: hidden; }
    body { background-color: #fffde9; margin: 0; padding: 0; }

    /* 🔶 Updated gradient colors (orange theme) */
    .cashier-branding {
      background: linear-gradient(135deg, #D97706, #F59E0B);
      color: white;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .forgot-password { text-align: center; margin-top: 10px; }
    .forgot-password a { color: #F59E0B; text-decoration: none; font-size: 14px; }
    .forgot-password a:hover { text-decoration: underline; }

    /* 🔶 Updated header theme colors */
    :root {
      --header-bg: #D97706;
      --header-text: #FAF9F4;
      --header-accent: #F59E0B;
    }

    header {
      background-color: var(--header-bg);
      color: var(--header-text);
      padding: 10px 20px;
      display:flex;
      justify-content:space-between;
      align-items:center;
    }
    .nav-btn { color: var(--header-text); text-decoration:none; }

    /* 🔶 Modal and button color adjustments */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1200;
    }
    .modal {
      background: #fffef9;
      border-radius: 16px;
      width: min(520px, 92vw);
      box-shadow: 0 18px 48px rgba(0,0,0,.18);
      overflow: hidden;
      border: 1px solid rgba(0,0,0,.06);
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
    .btn-modal {
      border: 0;
      border-radius: 10px;
      padding: 10px 16px;
      font-weight: 700;
      cursor:pointer;
    }
    .btn-modal--primary {
      background: linear-gradient(135deg, #D97706, #F59E0B);
      color:#fff;
    }
  </style>
</head>
<body>
  <header>
    <div class="left-header">
      <img src="../../assets/images/logo.png" alt="Jessie Cane Logo" onerror="this.style.display='none'" />
      <span id="portalTitle" style="font-size: 18px; font-weight: bold;">SM San Jose del Monte Cashier Portal</span>
    </div>
    <div class="right-header">
      <a href="login_cashier.php" class="nav-btn active">Login</a>
    </div>
  </header>

  <main style="display: flex; justify-content: center; align-items: center; height: calc(100vh - 80px); padding: 20px;">
    <div class="login-container">
      <div class="cashier-branding" id="cashierBranding">
        <h2><i class="fas fa-cash-register"></i> CASHIER LOGIN</h2>
        <p>Authorized cashier access only<br><strong>SM San Jose del Monte</strong></p>
      </div>

      <form class="auth-form" id="cashierLoginForm">
        <label for="email">Email</label>
        <input type="email" id="email" placeholder="Enter your email" required />

        <label for="password">Password</label>
        <div class="password-wrapper">
          <input type="password" id="password" placeholder="Enter your password" required />
          <button type="button" class="password-toggle" id="togglePassword"><i class="fas fa-eye"></i></button>
        </div>

        <!-- 🔶 Orange gradient log-in button -->
        <button type="submit" class="btn-main" id="loginBtn" style="background: linear-gradient(135deg, #D97706, #F59E0B);">
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
        For assistance, email <strong>support@jessiecane.com</strong> or call <strong>+63 912 345 6789</strong>.
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

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('cashierLoginForm');
      const forgotLink = document.getElementById('forgotPasswordLink');
      const forgotModal = document.getElementById('forgotModal');
      const forgotClose = document.getElementById('forgotClose');
      const forgotOk = document.getElementById('forgotOk');
      const errorModal = document.getElementById('errorModal');
      const errorMessage = document.getElementById('errorMessage');
      const errorClose = document.getElementById('errorClose');

      // Helper function to get credentials from localStorage (set by admin panel)
      function getCredentialsFromLocalStorage(inputEmail) {
        const CASHIER_SJDM_KEY = 'jessie_cashier_sjdm_credentials';
        const creds = localStorage.getItem(CASHIER_SJDM_KEY);
        if (creds) {
          try {
            const parsed = JSON.parse(creds);
            // Check if input email matches stored email (handles email updates)
            if (parsed.email && inputEmail.toLowerCase() === parsed.email.toLowerCase()) {
              return parsed;
            }
            // Also check if input matches default SJDM email
            if (inputEmail.toLowerCase() === 'cashiersjdm@gmail.com') {
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
        const localStorageCreds = getCredentialsFromLocalStorage(email);
        if (localStorageCreds) {
          // Verify credentials against localStorage (handles both email and password updates)
          if (localStorageCreds.email && localStorageCreds.email.toLowerCase() === email.toLowerCase() && localStorageCreds.password === password) {
            // Credentials match localStorage - proceed with login
            console.log('Credentials validated against localStorage');
          } else if (localStorageCreds.password && localStorageCreds.password !== password) {
            // Password doesn't match - show error
            openErrorModal('Invalid password. Please use the password set in admin profile management.');
            return;
          } else if (localStorageCreds.email && localStorageCreds.email.toLowerCase() !== email.toLowerCase()) {
            // Email doesn't match - show error
            openErrorModal('Invalid email. Please use the email set in admin profile management: ' + localStorageCreds.email);
            return;
          }
        }

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
              data = {
                success: true,
                branch: 'sjdm',
                token: btoa(JSON.stringify({ user_id: 1, role: 'cashier' })),
                cashier: {
                  id: 1,
                  email: localStorageCreds.email || email,
                  role: 'cashier',
                  branch: 'sjdm'
                }
              };
            }
          }

          if (data.success) {
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('currentUser', JSON.stringify(data.cashier));
            // Store token for API authentication
            if (data.token) {
              localStorage.setItem('token', data.token);
              sessionStorage.setItem('token', data.token);
            }
            window.location.href = 'cashier.php';
          } else {
            openErrorModal(data.message);
          }
        } catch (err) {
          console.error('Login error:', err);
          openErrorModal('Server error: ' + (err.message || 'Please try again later.'));
        }
      });

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
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
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
