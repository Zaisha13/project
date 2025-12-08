/* PURPOSE: Authentication helpers — registration, login, and smart redirects.
  Initializes default users and handles role-based redirects used across
  the auth pages. */

// Shared data structure for all users
const USER_DATA_KEY = "jessie_users_unified";

// Initialize default users with shared data structure
function initializeDefaultUsers() {
    let userData = JSON.parse(localStorage.getItem(USER_DATA_KEY) || "{}");
    
    // Initialize structure if it doesn't exist
    if (!userData.admins || userData.admins.length === 0) {
        userData.admins = [
            {
                email: "admin@gmail.com",
                password: "admin123",
                name: "Main Admin"
            }
        ];
    }
    
    if (!userData.cashiers || userData.cashiers.length === 0) {
        userData.cashiers = [
            {
                email: "cashier@gmail.com",
                password: "cashier123",
                name: "Cashier One"
            }
        ];
    }
    
    if (!userData.customers || userData.customers.length === 0) {
        userData.customers = [];
    }
    
    localStorage.setItem(USER_DATA_KEY, JSON.stringify(userData));
    
    // Also keep the old structure for backward compatibility
    const oldUsers = JSON.parse(localStorage.getItem("jessie_users") || "[]");
    if (oldUsers.length === 0) {
        const adminUser = {
            name: 'Administrator',
            username: 'admin',
            email: 'admin@jessiecane.com',
            password: 'admin123',
            role: 'admin',
            dateCreated: new Date().toISOString()
        };
        oldUsers.push(adminUser);
        localStorage.setItem("jessie_users", JSON.stringify(oldUsers));
    }
}


// Smart redirect function for src folder structure
function getRedirectPath(role) {
  const currentPath = window.location.pathname;
  
  // If we're in customer_portal-main folder (login.html location)
  if (currentPath.includes('customer_portal-main')) {
    switch(role) {
      case 'admin': return '../admin/admin.php';
      case 'cashier': return '../cashier/login_cashier.php';
      default: return 'customer_dashboard.php';
    }
  } 
  // If we're somewhere else
  else {
    switch(role) {
      case 'admin': return '../admin/admin.php';
      case 'cashier': return '../cashier/login_cashier.php';
      default: return 'customer_dashboard.php';
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  // Initialize default users
  initializeDefaultUsers();

  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");
  const adminLoginBtn = document.getElementById("adminLoginBtn");

  // -----------------------
  // ADMIN LOGIN FUNCTIONALITY
  // -----------------------
  if (adminLoginBtn) {
    adminLoginBtn.addEventListener("click", () => {
      // Auto-fill admin credentials
      document.getElementById("email").value = "admin";
      document.getElementById("password").value = "admin123";
      
      // Visual feedback
      const originalText = adminLoginBtn.innerHTML;
      adminLoginBtn.innerHTML = '<i class="fas fa-check"></i> Credentials Filled';
      adminLoginBtn.style.background = 'linear-gradient(135deg, #10B981, #059669)';
      
      if (typeof showToast !== 'undefined') {
        showToast('info', 'Admin Login', 'Admin credentials filled. Click "Log In" to proceed.');
      }
      
      setTimeout(() => {
        adminLoginBtn.innerHTML = originalText;
        adminLoginBtn.style.background = 'linear-gradient(135deg, #8B4513, #A0522D)';
      }, 2000);
    });
  }

  // -----------------------
// REGISTER FUNCTIONALITY (Now connected to PHP + MySQL)
// -----------------------
if (registerForm) {
  registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const firstname = document.getElementById("firstname").value.trim();
    const lastname = document.getElementById("lastname").value.trim();
    const name = `${firstname} ${lastname}`.trim();
    const username = document.getElementById("username").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const email = document.getElementById("email").value.trim();
    const address = document.getElementById("address").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm-password").value;

    // Validate all fields (keep your existing function)
    if (!validateRegistrationForm(firstname, lastname, username, phone, email, address, password, confirmPassword)) {
      return;
    }

    if (password !== confirmPassword) {
      showError('confirm-password', 'Passwords do not match');
      return;
    }

    // Build form data for PHP backend
    const formData = new FormData();
    formData.append("firstname", firstname);
    formData.append("lastname", lastname);
    formData.append("username", username);
    formData.append("phone", phone);
    formData.append("email", email);
    formData.append("address", address);
    formData.append("password", password);

    const submitBtn = registerForm.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
      submitBtn.disabled = true;
    }

    try {
      // Send registration request to PHP
      const response = await fetch("../../register.php", {
        method: "POST",
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
        // Success message
        sessionStorage.setItem("isLoggedIn", "true");
        sessionStorage.setItem("currentUser", JSON.stringify({
          name,
          username,
          email,
          phone,
          address,
          role: "customer"
        }));

        if (typeof showPopup !== "undefined") {
          showPopup("success", {
            message: "Registration successful! You are now logged in.",
            actions: [
              {
                text: "Continue",
                type: "primary",
                handler: () => {
                  if (typeof hidePopup !== "undefined") hidePopup();
                  window.location.href = "drinks.php";
                }
              }
            ]
          });
        } else {
          alert("Registration successful!");
          window.location.href = "drinks.php";
        }
      } else {
        // Handle registration error
        if (typeof showPopup !== "undefined") {
          showPopup("error", { message: data.message || "Registration failed." });
        } else {
          alert(data.message || "Registration failed.");
        }
      }
    } catch (error) {
      console.error("Registration Error:", error);
      const errorMessage = error.message || "Server error. Please try again later.";
      if (typeof showPopup !== "undefined") {
        showPopup("error", { message: errorMessage });
      } else {
        alert(errorMessage);
      }
    } finally {
      if (submitBtn) {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    }
  });
}


  // Helper: generate next order id (same logic as in drinks.js)
  function generateOrderId() {
    try {
      const orders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
      let maxNum = 0;
      orders.forEach(o => {
        if (!o || !o.id) return;
        const id = String(o.id).trim();
        if (/^\d+$/.test(id)) { maxNum = Math.max(maxNum, parseInt(id,10)); return; }
        const m = id.match(/(\d+)$/);
        if (m) maxNum = Math.max(maxNum, parseInt(m[1],10));
      });
      const next = maxNum + 1;
      return 'ORD-' + String(next).padStart(3, '0');
    } catch (err) {
      return 'ORD-' + String(Date.now()).slice(-6);
    }
  }

  // Helper: generate a safe user key used for localStorage keys
  function userStorageKeyFor(user) {
    const id = (user && (user.email || user.username)) ? (user.email || user.username) : ('user_' + Date.now());
    // Use encodeURIComponent to make a safe key (email safe-encoded)
    return encodeURIComponent(String(id).toLowerCase());
  }

  // Helper: if guest has a pending checkout, merge it into any existing saved cart for the provided user
  // This preserves the cart so when the user returns to the menu it will be restored into the checkout bar.
  function processPendingGuestOrder(user) {
    try {
      const pendingRaw = localStorage.getItem('guest_pending_checkout');
      if (!pendingRaw) return false;
      const pending = JSON.parse(pendingRaw || '{}');
      if (!pending || !Array.isArray(pending.cart) || pending.cart.length === 0) {
        localStorage.removeItem('guest_pending_checkout');
        return false;
      }

      const key = userStorageKeyFor(user);
      const savedCartKey = `saved_cart_${key}`;

      // Load existing saved cart for this user (if any)
      let existing = [];
      try {
        existing = JSON.parse(localStorage.getItem(savedCartKey) || '[]');
        if (!Array.isArray(existing)) existing = [];
      } catch (e) { existing = []; }

      // Merge pending.cart into existing by matching on productId, size, special, notes
      const merged = [...existing];
      pending.cart.forEach(pItem => {
        // Merge by productId only (summing quantities) per requested behavior.
        // This intentionally collapses variations (size/special/notes) by productId.
        const matchIdx = merged.findIndex(m => String(m.productId) === String(pItem.productId));
        if (matchIdx > -1) {
          // sum quantities
          merged[matchIdx].qty = (Number(merged[matchIdx].qty) || 0) + (Number(pItem.qty) || 0);
        } else {
          // normalize item shape (ensure unitPrice exists)
          const copy = Object.assign({}, pItem);
          if (typeof copy.unitPrice === 'undefined' && typeof copy.price !== 'undefined') copy.unitPrice = copy.price;
          merged.push(copy);
        }
      });

      try {
        localStorage.setItem(savedCartKey, JSON.stringify(merged));
      } catch (e) { console.warn('Failed to save merged user cart', e); }

      // Clear the guest pending snapshot
      localStorage.removeItem('guest_pending_checkout');

      if (typeof showToast !== 'undefined') showToast('success', 'Cart Saved', 'Your cart has been saved to your account.');
      return true;
    } catch (err) {
      console.warn('processPendingGuestOrder error', err);
      try { localStorage.removeItem('guest_pending_checkout'); } catch (e) {}
      return false;
    }
  }

  // -----------------------
  // LOGIN FUNCTIONALITY WITH ROLE-BASED REDIRECTS
  // -----------------------
  if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const emailInput = document.getElementById("email").value.trim();
      const passwordInput = document.getElementById("password").value;

      // Get saved users from localStorage
      const storedUsers = JSON.parse(localStorage.getItem("jessie_users") || "[]");

      if (storedUsers.length === 0) {
        if (typeof showPopup !== 'undefined') {
          showPopup('error', {
            message: 'No users found. Please register first.'
          });
        } else {
          alert('No users found. Please register first.');
        }
        return;
      }

      // Check credentials - only allow customer role accounts in customer portal
      const foundUser = storedUsers.find(user => 
        (user.email === emailInput || user.username === emailInput) && 
        user.password === passwordInput &&
        user.role === 'customer'
      );

      if (foundUser) {
        // Show loading state
        const submitBtn = loginForm.querySelector('.btn-main');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging In...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
          // Save login state and user info (sessionStorage - clears on browser close)
          sessionStorage.setItem("isLoggedIn", "true");
          sessionStorage.setItem("currentUser", JSON.stringify(foundUser));
          
          if (typeof showToast !== 'undefined') {
            showToast('success', 'Welcome!', `Logged in as ${foundUser.name}`);
          }
          
          // Redirect based on role using smart path detection
          setTimeout(() => {
            const redirectPath = getRedirectPath(foundUser.role);
            console.log(`Redirecting ${foundUser.role} user to: ${redirectPath}`);
            window.location.href = redirectPath;
          }, 1000);
        }, 1500);
      } else {
        // Check if credentials match admin or cashier to show specific error
        const adminMatch = storedUsers.find(user => 
          (user.email === emailInput || user.username === emailInput) && 
          user.password === passwordInput && 
          (user.role === 'admin' || user.role === 'Administrator')
        );
        const cashierMatch = storedUsers.find(user => 
          (user.email === emailInput || user.username === emailInput) && 
          user.password === passwordInput && 
          user.role === 'cashier'
        );
        
        // Shake animation for error
        loginForm.classList.add('animate-shake');
        setTimeout(() => {
          loginForm.classList.remove('animate-shake');
        }, 500);
        
        if (adminMatch || cashierMatch) {
          if (typeof showPopup !== 'undefined') {
            showPopup('error', {
              message: 'Admin/Cashier accounts cannot login in customer portal. Please use the appropriate login page.'
            });
          } else {
            alert('Admin/Cashier accounts cannot login in customer portal. Please use the appropriate login page.');
          }
        } else {
          if (typeof showPopup !== 'undefined') {
            showPopup('error', {
              message: 'Incorrect email/username or password.'
            });
          } else {
            alert('Incorrect email/username or password.');
          }
        }
      }
    });
  }

  // -----------------------
  // GOOGLE AUTH PLACEHOLDER
  // -----------------------
  const googleLogin = document.getElementById("googleLogin");
  const googleRegister = document.getElementById("googleRegister");

  [googleLogin, googleRegister].forEach((btn) => {
    if (btn) {
      btn.addEventListener("click", () => {
        if (typeof showPopup !== 'undefined') {
          showPopup('info', {
            title: 'Google Authentication',
            message: 'Google Auth simulated. Connect Firebase or OAuth here for production.',
            actions: [
              {
                text: 'Simulate Login',
                type: 'primary',
                handler: () => {
                  // Create a demo user for Google auth (customer role)
                  const demoUser = {
                    name: 'Google User',
                    username: 'google_user',
                    email: 'google@example.com',
                    password: 'google_auth',
                    role: 'customer',
                    dateCreated: new Date().toISOString()
                  };
                  
                  const existingUsers = JSON.parse(localStorage.getItem("jessie_users") || "[]");
                  existingUsers.push(demoUser);
                  localStorage.setItem("jessie_users", JSON.stringify(existingUsers));
                  
                  sessionStorage.setItem("isLoggedIn", "true");
                  sessionStorage.setItem("currentUser", JSON.stringify(demoUser));

                  // If there was a pending guest checkout, process it now for this user
                  try {
                    const saved = processPendingGuestOrder(demoUser);
                    if (saved) {
                      try { localStorage.setItem('open_checkout_after_login', 'true'); } catch (e) {}
                    }
                  } catch (err) { console.warn('processPendingGuestOrder failed', err); }

                  if (typeof showToast !== 'undefined') {
                    showToast('success', 'Google Login', 'Successfully signed in with Google!');
                  }
                  setTimeout(() => {
                    // Redirect to menu so the checkout modal can auto-open
                    window.location.href = 'drinks.php';
                  }, 800);
                }
              }
            ]
          });
        } else {
          alert('Google authentication would be implemented here in production.');
        }
      });
    }
  });
});

// -----------------------
// UNIFIED LOGIN FUNCTION
// -----------------------
async function loginUser(role) {
  const emailInput = document.getElementById("email").value.trim();
  const passwordInput = document.getElementById("password").value;
  
  // Try backend API first
  try {
    if (typeof window.AuthAPI !== 'undefined') {
      const submitBtn = document.querySelector('.btn-main');
      const originalText = submitBtn ? submitBtn.innerHTML : '';
      
      if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging In...';
        submitBtn.disabled = true;
      }
      
      const result = await window.AuthAPI.login(emailInput, passwordInput, role);
      
      if (result.success) {
        sessionStorage.setItem("isLoggedIn", "true");
        sessionStorage.setItem("currentUser", JSON.stringify(result.data.user));
        sessionStorage.setItem("token", (result.data.token || '').trim());
        
        if (typeof showToast !== 'undefined') {
          showToast('success', 'Welcome!', `Logged in as ${result.data.user.name}`);
        } else {
          alert(`Welcome! Logged in as ${result.data.user.name}`);
        }
        
        setTimeout(() => {
          if (role === 'admin') {
            window.location.href = "../admin/admin.php";
          } else if (role === 'cashier') {
            window.location.href = "../cashier_fairview/cashier.php";
          } else {
            window.location.href = "customer_dashboard.php";
          }
        }, 1000);
        return;
      } else {
        throw new Error(result.message || 'Login failed');
      }
    }
  } catch (error) {
    console.warn('Backend login failed, trying localStorage fallback:', error);
    // Continue to localStorage fallback below
  }

  const userData = JSON.parse(localStorage.getItem(USER_DATA_KEY) || "{}");
  
  let foundUser = null;
  
  // Check the appropriate array based on role
  if (role === 'admin' && userData.admins) {
    foundUser = userData.admins.find(user => 
      user.email === emailInput && user.password === passwordInput
    );
  } else if (role === 'cashier' && userData.cashiers) {
    foundUser = userData.cashiers.find(user => 
      user.email === emailInput && user.password === passwordInput
    );
  } else if (role === 'customer') {
    // First check if credentials match admin or cashier - prevent them from logging in as customer
    if (userData.admins) {
      const adminMatch = userData.admins.find(user => 
        user.email === emailInput && user.password === passwordInput
      );
      if (adminMatch) {
        const form = document.querySelector('form');
        if (form) form.classList.add('animate-shake');
        setTimeout(() => {
          if (form) form.classList.remove('animate-shake');
        }, 500);
        alert('Admin accounts cannot login in customer portal. Please use the admin login page.');
        return;
      }
    }
    
    if (userData.cashiers) {
      const cashierMatch = userData.cashiers.find(user => 
        user.email === emailInput && user.password === passwordInput
      );
      if (cashierMatch) {
        const form = document.querySelector('form');
        if (form) form.classList.add('animate-shake');
        setTimeout(() => {
          if (form) form.classList.remove('animate-shake');
        }, 500);
        alert('Cashier accounts cannot login in customer portal. Please use the cashier login page.');
        return;
      }
    }
    
    // For customers, check the old structure for backward compatibility
    const oldUsers = JSON.parse(localStorage.getItem("jessie_users") || "[]");
    // Also check if credentials match admin or cashier in old structure
    const adminMatchOld = oldUsers.find(user => 
      (user.email === emailInput || user.username === emailInput) && 
      user.password === passwordInput && 
      (user.role === 'admin' || user.role === 'Administrator')
    );
    const cashierMatchOld = oldUsers.find(user => 
      (user.email === emailInput || user.username === emailInput) && 
      user.password === passwordInput && 
      user.role === 'cashier'
    );
    
    if (adminMatchOld || cashierMatchOld) {
      const form = document.querySelector('form');
      if (form) form.classList.add('animate-shake');
      setTimeout(() => {
        if (form) form.classList.remove('animate-shake');
      }, 500);
      alert('Admin/Cashier accounts cannot login in customer portal. Please use the appropriate login page.');
      return;
    }
    
    foundUser = oldUsers.find(user => 
      (user.email === emailInput || user.username === emailInput) && 
      user.password === passwordInput && user.role === 'customer'
    );
  }

  if (foundUser) {
    const submitBtn = document.querySelector('.btn-main');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    
    if (submitBtn) {
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging In...';
      submitBtn.disabled = true;
    }
    
    setTimeout(() => {
      sessionStorage.setItem("isLoggedIn", "true");
      sessionStorage.setItem("currentUser", JSON.stringify({ ...foundUser, role }));
      
      alert(`Welcome! Logged in as ${foundUser.name}`);
      
      // Redirect based on role
      setTimeout(() => {
        if (role === 'admin') {
          window.location.href = "../admin/admin.php";
        } else if (role === 'cashier') {
          window.location.href = "../cashier_fairview/cashier.php";
        } else {
          window.location.href = "customer_dashboard.php";
        }
      }, 1000);
    }, 1500);
  } else {
    const form = document.querySelector('form');
    form.classList.add('animate-shake');
    setTimeout(() => form.classList.remove('animate-shake'), 500);
    alert('Incorrect email or password.');
  }
}

// -----------------------
// FORGOT PASSWORD FUNCTION
// -----------------------
function handleForgotPassword(role) {
  // Get role-specific colors
  const roleColors = {
    admin: { bg: '#8B4513', accent: '#A0522D' },
    cashier: { bg: '#1E3A8A', accent: '#3B82F6' },
    customer: { bg: '#2E5D47', accent: '#3d8f43' }
  };
  
  const colors = roleColors[role] || roleColors.customer;
  
  // Create modal overlay
  const overlay = document.createElement('div');
  overlay.className = 'forgot-password-overlay';
  overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease;
  `;
  
  // Create modal content
  const modal = document.createElement('div');
  modal.className = 'forgot-password-modal';
  modal.style.cssText = `
    background: white;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    max-width: 450px;
    width: 90%;
    animation: slideDown 0.3s ease;
    position: relative;
  `;
  
  modal.innerHTML = `
    <div class="modal-header" style="margin-bottom: 20px;">
      <h2 style="color: ${colors.bg}; margin: 0; font-size: 24px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-key"></i> Reset Password
      </h2>
      <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">
        Enter your email to reset your password
      </p>
    </div>
    
    <form id="forgotPasswordForm" style="display: flex; flex-direction: column; gap: 15px;">
      <div>
        <label for="resetEmail" style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">
          Email Address
        </label>
        <input 
          type="email" 
          id="resetEmail" 
          placeholder="Enter your email" 
          required
          style="width: 100%; padding: 12px; border: 1px solid #c9e4b6; background-color: #f5fff3; border-radius: 8px; font-size: 14px; font-family: 'Century Gothic', sans-serif; transition: all 0.3s; box-sizing: border-box;"
        >
      </div>
      
      <div style="margin-top: 5px;">
        <label for="newPassword" style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">
          New Password
        </label>
        <div style="position: relative;">
          <input 
            type="password" 
            id="newPassword" 
            placeholder="Enter new password (min 4 characters)" 
            required
            style="width: 100%; padding: 12px 40px 12px 12px; border: 1px solid #c9e4b6; background-color: #f5fff3; border-radius: 8px; font-size: 14px; font-family: 'Century Gothic', sans-serif; transition: all 0.3s; box-sizing: border-box;"
          >
          <button 
            type="button" 
            id="toggleNewPassword"
            style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #888; cursor: pointer; font-size: 16px; padding: 5px 8px; display: flex; align-items: center; justify-content: center; transition: color 0.2s ease;"
          >
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
      
      <div style="margin-top: 10px; display: flex; gap: 10px;">
        <button 
          type="button" 
          id="cancelResetBtn"
          style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 8px; background: white; color: #666; font-weight: 600; cursor: pointer; transition: all 0.3s;"
        >
          Cancel
        </button>
        <button 
          type="submit" 
          style="flex: 2; padding: 12px; border: none; border-radius: 8px; background: linear-gradient(135deg, ${colors.bg}, ${colors.accent}); color: white; font-weight: 600; cursor: pointer; transition: all 0.3s;"
        >
          <i class="fas fa-sync-alt"></i> Reset Password
        </button>
      </div>
    </form>
    
    <button 
      class="close-modal-btn" 
      style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; color: #999; cursor: pointer; padding: 5px; transition: all 0.3s;"
    >
      <i class="fas fa-times"></i>
    </button>
  `;
  
  overlay.appendChild(modal);
  document.body.appendChild(overlay);
  
  // Add CSS animations
  if (!document.getElementById('forgotPasswordStyles')) {
    const style = document.createElement('style');
    style.id = 'forgotPasswordStyles';
    style.textContent = `
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
      }
      .forgot-password-modal input {
        border: 1px solid #c9e4b6 !important;
        background-color: #f5fff3 !important;
      }
      .forgot-password-modal input:focus {
        outline: none;
        border-color: ${colors.bg} !important;
        box-shadow: 0 0 4px ${colors.bg} !important;
      }
      .forgot-password-modal button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }
      .close-modal-btn:hover {
        color: ${colors.bg} !important;
        transform: rotate(90deg);
      }
    `;
    document.head.appendChild(style);
  }
  
  // Focus on email input
  setTimeout(() => {
    document.getElementById('resetEmail').focus();
  }, 100);
  
  // Password toggle functionality for modal
  const toggleNewPasswordBtn = document.getElementById('toggleNewPassword');
  const newPasswordInput = document.getElementById('newPassword');
  
  if (toggleNewPasswordBtn && newPasswordInput) {
    toggleNewPasswordBtn.addEventListener('click', function() {
      const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      newPasswordInput.setAttribute('type', type);
      
      // Toggle icon
      const icon = toggleNewPasswordBtn.querySelector('i');
      if (type === 'password') {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      } else {
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      }
    });
    
    // Hover effect for toggle button
    toggleNewPasswordBtn.addEventListener('mouseenter', function() {
      this.style.color = colors.bg;
    });
    
    toggleNewPasswordBtn.addEventListener('mouseleave', function() {
      this.style.color = '#888';
    });
  }
  
  // Close modal functions
  const closeModal = () => {
    overlay.style.animation = 'fadeIn 0.3s ease reverse';
    setTimeout(() => overlay.remove(), 300);
  };
  
  overlay.querySelector('.close-modal-btn').addEventListener('click', closeModal);
  document.getElementById('cancelResetBtn').addEventListener('click', closeModal);
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal();
  });
  
  // Handle form submission
  document.getElementById('forgotPasswordForm').addEventListener('submit', (e) => {
    e.preventDefault();
    
    const email = document.getElementById('resetEmail').value.trim();
    const newPassword = document.getElementById('newPassword').value;
    
    if (!email) {
      alert('Please enter your email address.');
      return;
    }
    
    if (!newPassword || newPassword.length < 4) {
      alert('Password must be at least 4 characters long.');
      return;
    }
    
    const userData = JSON.parse(localStorage.getItem(USER_DATA_KEY) || "{}");
    let foundUser = null;
    
    if (role === 'admin' && userData.admins) {
      foundUser = userData.admins.find(user => user.email === email);
    } else if (role === 'cashier' && userData.cashiers) {
      foundUser = userData.cashiers.find(user => user.email === email);
    } else if (role === 'customer') {
      const oldUsers = JSON.parse(localStorage.getItem("jessie_users") || "[]");
      foundUser = oldUsers.find(user => user.email === email && user.role === 'customer');
    }
    
    if (foundUser) {
      // Update password based on role
      if (role === 'admin') {
        const index = userData.admins.findIndex(user => user.email === email);
        if (index !== -1) {
          userData.admins[index].password = newPassword;
        }
      } else if (role === 'cashier') {
        const index = userData.cashiers.findIndex(user => user.email === email);
        if (index !== -1) {
          userData.cashiers[index].password = newPassword;
        }
      } else if (role === 'customer') {
        const oldUsers = JSON.parse(localStorage.getItem("jessie_users") || "[]");
        const index = oldUsers.findIndex(user => user.email === email && user.role === 'customer');
        if (index !== -1) {
          oldUsers[index].password = newPassword;
          localStorage.setItem("jessie_users", JSON.stringify(oldUsers));
        }
      }
      
      localStorage.setItem(USER_DATA_KEY, JSON.stringify(userData));
      
      // Show success message
      modal.innerHTML = `
        <div style="text-align: center; padding: 20px;">
          <div style="font-size: 48px; color: ${colors.accent}; margin-bottom: 15px;">
            <i class="fas fa-check-circle"></i>
          </div>
          <h3 style="color: ${colors.bg}; margin: 0 0 10px 0;">Password Reset Successful!</h3>
          <p style="color: #666; margin: 0 0 20px 0;">You can now log in with your new password.</p>
          <button 
            onclick="this.closest('.forgot-password-overlay').remove();"
            style="padding: 12px 30px; border: none; border-radius: 8px; background: linear-gradient(135deg, ${colors.bg}, ${colors.accent}); color: white; font-weight: 600; cursor: pointer; transition: all 0.3s;"
          >
            Close
          </button>
        </div>
      `;
    } else {
      alert('Email not found. Please check your email address.');
    }
  });
}

// Helper function to show error message
function showError(inputId, message) {
  const input = document.getElementById(inputId);
  const errorElement = document.getElementById(inputId + '-error');
  
  if (input) {
    input.classList.add('error');
  }
  
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.classList.add('show');
  }
}

// Helper function to hide error message
function hideError(inputId) {
  const input = document.getElementById(inputId);
  const errorElement = document.getElementById(inputId + '-error');
  
  if (input) {
    input.classList.remove('error');
  }
  
  if (errorElement) {
    errorElement.classList.remove('show');
  }
}

// Password strength checker
function checkPasswordStrength(password) {
  let strength = 0;
  
  if (password.length >= 8) strength++;
  if (password.length >= 12) strength++;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
  if (/\d/.test(password)) strength++;
  if (/[^a-zA-Z\d]/.test(password)) strength++;
  
  return strength;
}

// Update password strength indicator
function updatePasswordStrength(password) {
  const strengthFill = document.getElementById('strength-fill');
  const strengthText = document.getElementById('strength-text');
  
  if (!strengthFill || !strengthText) return;
  
  const strength = checkPasswordStrength(password);
  
  if (password.length === 0) {
    strengthFill.className = 'strength-fill';
    strengthFill.style.width = '0%';
    strengthText.className = 'strength-text';
    strengthText.textContent = 'Password strength';
    return;
  }
  
  if (strength <= 2) {
    strengthFill.className = 'strength-fill weak';
    strengthText.className = 'strength-text weak';
    strengthText.textContent = 'Weak password';
  } else if (strength <= 4) {
    strengthFill.className = 'strength-fill medium';
    strengthText.className = 'strength-text medium';
    strengthText.textContent = 'Medium password';
  } else {
    strengthFill.className = 'strength-fill strong';
    strengthText.className = 'strength-text strong';
    strengthText.textContent = 'Strong password';
  }
}

// Validation function
function validateRegistrationForm(firstname, lastname, username, phone, email, address, password, confirmPassword) {
  let isValid = true;
  
  // Clear previous errors
  hideError('firstname');
  hideError('lastname');
  hideError('username');
  hideError('phone');
  hideError('email');
  hideError('address');
  hideError('password');
  hideError('confirm-password');
  
  // Validate first name
  if (!firstname) {
    showError('firstname', 'First name is required');
    isValid = false;
  }
  
  // Validate last name
  if (!lastname) {
    showError('lastname', 'Last name is required');
    isValid = false;
  }
  
  // Validate username
  if (!username) {
    showError('username', 'Username is required');
    isValid = false;
  } else if (username.length < 3) {
    showError('username', 'Username must be at least 3 characters');
    isValid = false;
  }
  
  // Validate phone
  if (!phone) {
    showError('phone', 'Phone number is required');
    isValid = false;
  } else if (!/^[0-9]+$/.test(phone)) {
    showError('phone', 'Phone must contain digits only');
    isValid = false;
  }
  
  // Validate email
  if (!email) {
    showError('email', 'Email is required');
    isValid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showError('email', 'Invalid email format');
    isValid = false;
  }
  
  // Validate address
  if (!address) {
    showError('address', 'Address is required');
    isValid = false;
  } else if (address.length < 10) {
    showError('address', 'Address must be at least 10 characters');
    isValid = false;
  }
  
  // Validate password
  if (!password) {
    showError('password', 'Password is required');
    isValid = false;
  } else if (password.length < 8) {
    showError('password', 'Password must be at least 8 characters');
    isValid = false;
  } else {
    const strength = checkPasswordStrength(password);
    if (strength <= 2) {
      showError('password', 'Password is too weak');
      isValid = false;
    }
  }
  
  // Validate confirm password
  if (!confirmPassword) {
    showError('confirm-password', 'Please confirm your password');
    isValid = false;
  } else if (password !== confirmPassword) {
    showError('confirm-password', 'Passwords do not match');
    isValid = false;
  }
  
  return isValid;
}

// Add real-time validation listeners when modal is initialized
if (typeof window.initializeAuthInModal !== 'undefined') {
  const originalInit = window.initializeAuthInModal;
  window.initializeAuthInModal = function() {
    originalInit();
    
    // Add password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
      passwordInput.addEventListener('input', function() {
        updatePasswordStrength(this.value);
      });
    }
    
    // Add real-time validation
    const firstnameInput = document.getElementById('firstname');
    const lastnameInput = document.getElementById('lastname');
    const usernameInput = document.getElementById('username');
    const phoneInput = document.getElementById('phone');
    const emailInput = document.getElementById('email');
    const addressInput = document.getElementById('address');
    const passwordConfirmInput = document.getElementById('confirm-password');
    
    if (firstnameInput) {
      firstnameInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
          showError('firstname', 'First name is required');
        } else {
          hideError('firstname');
        }
      });
    }
    
    if (lastnameInput) {
      lastnameInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
          showError('lastname', 'Last name is required');
        } else {
          hideError('lastname');
        }
      });
    }
    
    if (usernameInput) {
      usernameInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
          showError('username', 'Username is required');
        } else if (this.value.length < 3) {
          showError('username', 'Username must be at least 3 characters');
        } else {
          hideError('username');
        }
      });
    }
    
    if (phoneInput) {
      phoneInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
          showError('phone', 'Phone number is required');
        } else if (!/^[0-9]+$/.test(this.value)) {
          showError('phone', 'Phone must contain digits only');
        } else {
          hideError('phone');
        }
      });
      // Sanitize to digits only on input
      phoneInput.addEventListener('input', function() {
        const cleaned = this.value.replace(/[^0-9]/g, '');
        if (this.value !== cleaned) this.value = cleaned;
      });
    }
    
    if (emailInput) {
      emailInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
          showError('email', 'Email is required');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
          showError('email', 'Invalid email format');
        } else {
          hideError('email');
        }
      });
    }
    
    if (addressInput) {
      addressInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
          showError('address', 'Address is required');
        } else if (this.value.length < 10) {
          showError('address', 'Address must be at least 10 characters');
        } else {
          hideError('address');
        }
      });
    }
    
    if (passwordConfirmInput) {
      passwordConfirmInput.addEventListener('blur', function() {
        const passwordValue = passwordInput ? passwordInput.value : '';
        if (this.value !== passwordValue) {
          showError('confirm-password', 'Passwords do not match');
        } else {
          hideError('confirm-password');
        }
      });
    }
  };
}
