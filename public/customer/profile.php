<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Profile | Jessie Cane</title>
  <link rel="stylesheet" href="../../assets/css/shared.css">
  <link rel="stylesheet" href="css/profile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    // If unauthenticated, show an in-page banner then redirect to login
    (function(){
      try {
        if (sessionStorage.getItem('isLoggedIn') !== 'true') {
          var banner = document.createElement('div');
          banner.className = 'auth-banner auth-banner--visible';
          banner.setAttribute('role','status');
          banner.innerHTML = '<div>Please log in or register to access your profile.</div><small>Redirecting to login...</small>';
          document.addEventListener('DOMContentLoaded', function(){ document.body.appendChild(banner); });
          // allow users to cancel the redirect by interacting (clicking) so nav is usable
          var cancelFn = function(){
            try { clearTimeout(redirectT); } catch(e){}
            try { banner.classList.remove('auth-banner--visible'); } catch(e){}
            setTimeout(function(){ if (banner && banner.parentNode) banner.parentNode.removeChild(banner); }, 300);
            document.removeEventListener('click', cancelFn, true);
          };
          document.addEventListener('click', cancelFn, true);
          var redirectT = setTimeout(function(){ window.location.href = 'customer_dashboard.php'; }, 1400);
        }
      } catch (err) { /* ignore in non-browser contexts */ }
    })();
  </script>
</head>
<body>
  <!-- PURPOSE: Profile page — view and edit user profile information and change password -->
  <!-- header (shared) -->
  <header>
    <div class="left-header">
      <img src="../../assets/images/logo.png" id="drink_logo" alt="Jessie Cane Logo" onerror="this.style.display='none'">
    </div>
    <div class="right-header">
      <a href="../index.php" class="nav-btn">Portal</a>
      <a href="customer_dashboard.php" class="nav-btn">Home</a>
      <a href="drinks.php" class="nav-btn">Menu</a>
      <a href="profile.php" class="nav-btn active">Profile</a>
      <a href="inquiry.php" class="nav-btn">Inquiry</a>
      <a href="../index.php" class="nav-btn logout">Logout</a>
    </div>
  </header>

  <main class="profile-main">
    <section class="profile-card">
      <aside class="profile-sidebar">
        <div class="profile-avatar">
          <div class="avatar-circle">
            <i class="fas fa-user"></i>
          </div>
        </div>
        <div class="profile-title">My Account</div>
        <nav class="profile-nav">
          <button id="tab-profile" class="nav-item active" type="button">
            <i class="fas fa-user-edit"></i> Manage Profile
          </button>
          <button id="tab-history" class="nav-item" type="button">
            <i class="fas fa-history"></i> Order History
          </button>
        </nav>
      </aside>

      <div class="right-col">
        <div style="display:flex;gap:10px;margin-bottom:10px;">
          <!-- tabs mirrored in sidebar -->
        </div>

        <h2 id="section-title">Profile Information</h2>

        <form id="profile-form">
          <div class="form-row">
            <div class="form-col">
              <label for="firstname">First name</label>
              <input id="firstname" type="text" required />
              <div class="error-text" id="err-firstname" aria-live="polite"></div>
            </div>
            <div class="form-col">
              <label for="lastname">Last name</label>
              <input id="lastname" type="text" required />
              <div class="error-text" id="err-lastname" aria-live="polite"></div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-col">
              <label for="username">Username</label>
              <input id="username" type="text" required />
              <div class="error-text" id="err-username" aria-live="polite"></div>
            </div>
            <div class="form-col">
              <label for="phone">Mobile phone</label>
              <input id="phone" type="tel" placeholder="e.g. +63 9123456789" />
              <div class="error-text" id="err-phone" aria-live="polite"></div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-col">
              <label for="email">Email</label>
              <input id="email" type="email" required />
              <div class="error-text" id="err-email" aria-live="polite"></div>
            </div>
          </div>

          <div class="form-row form-row-full">
            <label for="address">Address</label>
            <textarea id="address" rows="2"></textarea>
          </div>

          <div class="actions-row">
            <button type="button" id="save-profile" class="btn primary">Save Profile</button>
            <button type="button" id="reset-password" class="btn">Reset Password</button>
          </div>
        </form>
        <!-- Order History lives INSIDE the right column -->
        <section id="history-section" style="display:none;">
          <div id="order-history-list" style="display:flex;flex-direction:column;gap:10px;"></div>
        </section>
      </div>
    </section>
  </main>

  <!-- Password modal -->
  <div id="pw-modal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <h3>Reset Password</h3>
      <p>Enter your current password and new password.</p>
      <div class="field-row">
        <label for="current-pw">Current password</label>
        <div style="position: relative;">
          <input id="current-pw" type="password" style="width: 100%; padding-right: 40px; box-sizing: border-box;">
          <button type="button" id="toggle-current-pw" aria-label="Show/Hide password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #2E5D47; cursor: pointer; font-size: 16px; padding: 5px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="field-row">
        <label for="new-pw">New password</label>
        <div style="position: relative;">
          <input id="new-pw" type="password" style="width: 100%; padding-right: 40px; box-sizing: border-box;">
          <button type="button" id="toggle-new-pw" aria-label="Show/Hide password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #2E5D47; cursor: pointer; font-size: 16px; padding: 5px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="field-row">
        <label for="confirm-pw">Confirm new password</label>
        <div style="position: relative;">
          <input id="confirm-pw" type="password" style="width: 100%; padding-right: 40px; box-sizing: border-box;">
          <button type="button" id="toggle-confirm-pw" aria-label="Show/Hide password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #2E5D47; cursor: pointer; font-size: 16px; padding: 5px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="actions-row">
        <button id="pw-cancel" class="btn">Cancel</button>
        <button id="pw-save" class="btn primary">Save Password</button>
      </div>
    </div>
  </div>

  <!-- Save confirmation modal -->
  <div id="save-confirm-modal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <h3>Save Changes</h3>
      <p>Are you sure you want to save changes?</p>
      <div class="actions-row">
        <button id="save-cancel" class="btn">Cancel</button>
        <button id="save-confirm" class="btn primary">Yes, Save</button>
      </div>
    </div>
  </div>

  <script src="../../assets/js/api-helper.js"></script>
  <script src="../../assets/js/header.js"></script>
  <script src="js/customer.js"></script>
  <script src="js/profile.js"></script>
</body>
</html>
