// profile.js - handle profile load/save, photo upload, and password reset
/* PURPOSE: Profile page behavior — load/save current user profile, handle
  photo upload, phone normalization, and password reset UI. */
(function(){
  const KEY = 'currentUser';

  function $(id){ return document.getElementById(id); }

  function loadProfile(){
    const raw = localStorage.getItem(KEY);
    if (!raw) return null;
    try { return JSON.parse(raw); } catch(e) { return null; }
  }

  function saveProfile(user){
    localStorage.setItem(KEY, JSON.stringify(user));
    // Also save to sessionStorage for consistency
    sessionStorage.setItem('currentUser', JSON.stringify(user));
  }

  function dataURLFromFile(file, cb){
    const reader = new FileReader();
    reader.onload = () => cb(reader.result);
    reader.readAsDataURL(file);
  }

  document.addEventListener('DOMContentLoaded', async () => {
    // First, try to load from sessionStorage or localStorage
    let user = null;
    try {
      const sessionUser = sessionStorage.getItem('currentUser');
      const localUser = localStorage.getItem('currentUser');
      const userStr = sessionUser || localUser;
      if (userStr) {
        user = JSON.parse(userStr);
      }
    } catch (e) {
      console.warn('Error parsing currentUser from storage:', e);
    }
    
    // If no user in storage, try to load from legacy localStorage
    if (!user || (!user.email && !user.username)) {
      user = loadProfile() || {};
      // Fallback: if currentUser missing, try to infer a customer from legacy store
      if (!user || (!user.email && !user.username)) {
        try {
          const users = JSON.parse(localStorage.getItem('jessie_users') || '[]');
          const customers = users.filter(u => (u && (u.role || 'customer') === 'customer'));
          if (customers.length === 1) {
            user = Object.assign({}, customers[0]);
          }
        } catch (e) {}
      }
    }
    
    // Try to fetch fresh data from API if we have a token
    const token = sessionStorage.getItem('token') || localStorage.getItem('token');
    if (token && typeof UsersAPI !== 'undefined') {
      try {
        const profileResult = await UsersAPI.getProfile();
        if (profileResult && profileResult.success && profileResult.data) {
          // Merge API data with existing user data
          user = Object.assign({}, user, profileResult.data);
          // Update storage with fresh data
          sessionStorage.setItem('currentUser', JSON.stringify(user));
          localStorage.setItem('currentUser', JSON.stringify(user));
        }
      } catch (error) {
        console.warn('Failed to fetch profile from API, using stored data:', error);
      }
    }
    
    const originalUsername = user.username || null;
    const originalEmail = user.email || null;

  const photoImg = $('profile-photo');
  const photoInput = $('photo-input');
  const removeBtn = $('remove-photo');
  const photoFilename = $('photo-filename');

    // Populate fields - prioritize firstname/lastname from API, fallback to parsing name
    const first = user.firstname || user.firstName || (user.name ? String(user.name).split(' ')[0] : '');
    const last = user.lastname || user.lastName || (user.name ? String(user.name).split(' ').slice(1).join(' ') : '');
    $('firstname').value = first || '';
    $('lastname').value = last || '';
    // removed gender/age per request
    $('username').value = user.username || '';
    $('email').value = user.email || '';
    $('phone').value = user.phone || '';
    $('address').value = user.address || '';

  const DEFAULT_AVATAR = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='240' height='240' fill='none' stroke='%232E5D47' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'><rect x='2' y='5' width='20' height='14' rx='2' ry='2'/><circle cx='12' cy='12' r='3'/><path d='M8 5l1.5-2h5L16 5'/></svg>";
  if (photoImg) {
    if (user.photo) photoImg.src = user.photo; else photoImg.src = DEFAULT_AVATAR;
  }

    if (photoInput) photoInput.addEventListener('change', (e)=>{
      const f = e.target.files && e.target.files[0];
      if (!f) return;
      // show filename
      if (photoFilename) photoFilename.textContent = f.name || 'Selected file';
      dataURLFromFile(f, (dataUrl)=>{
        if (photoImg) photoImg.src = dataUrl;
        user.photo = dataUrl;
        saveProfile(user);
        showToast('success','Saved','Profile photo updated');
      });
    });

    if (removeBtn) removeBtn.addEventListener('click', ()=>{
      if (photoImg) photoImg.src = DEFAULT_AVATAR;
      delete user.photo;
      saveProfile(user);
      if (photoFilename) photoFilename.textContent = 'No file chosen';
      // clear native input so same file can be re-chosen if desired
      if (photoInput) photoInput.value = '';
      showToast('info','Removed','Profile photo removed');
    });

    // helper validators
    function setError(id, msg){
      const el = $(id);
      if (!el) return;
      el.textContent = msg || '';
    }
    function markInvalid(inputEl, isInvalid){
      if (!inputEl) return;
      if (isInvalid) inputEl.classList.add('invalid'); else inputEl.classList.remove('invalid');
    }

    // Save profile with confirmation modal, inline validation and phone normalization
    function performSave(){
      const firstName = $('firstname').value.trim();
      const lastName = $('lastname').value.trim();
      const name = `${firstName} ${lastName}`.trim();
      const username = $('username').value.trim();
      const email = $('email').value.trim();
      const age = '';
      const phone = $('phone').value.trim();

      let ok = true;
      // first/last name
      if (!firstName) { setError('err-firstname','First name is required'); markInvalid($('firstname'), true); ok = false; } else { setError('err-firstname',''); markInvalid($('firstname'), false); }
      if (!lastName) { setError('err-lastname','Last name is required'); markInvalid($('lastname'), true); ok = false; } else { setError('err-lastname',''); markInvalid($('lastname'), false); }
      // username
      if (!username) { setError('err-username','Username is required'); markInvalid($('username'), true); ok = false; } else { setError('err-username',''); markInvalid($('username'), false); }
      // email basic
      const emailRe = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
      if (!email || !emailRe.test(email)) { setError('err-email','Valid email is required'); markInvalid($('email'), true); ok = false; } else { setError('err-email',''); markInvalid($('email'), false); }
      // age optional but if provided numeric range
      // age removed
      // phone normalization (accepts local numbers and adds +63)
      if (phone) {
        const normalized = normalizePhone(phone);
        if (!normalized) { setError('err-phone','Enter a valid phone'); markInvalid($('phone'), true); ok = false; } else { setError('err-phone',''); markInvalid($('phone'), false); $('phone').value = normalized; }
      } else { setError('err-phone',''); markInvalid($('phone'), false); }

      if (!ok) return showToast('error','Validation failed','Please fix highlighted fields');

      // uniqueness checks against registered users
      const users = JSON.parse(localStorage.getItem('jessie_users') || '[]');
      const usernameTaken = users.some(u => u.username === username && u.username !== originalUsername);
      const emailTaken = users.some(u => u.email === email && u.email !== originalEmail);
      if (usernameTaken) { setError('err-username','Username already taken'); markInvalid($('username'), true); ok = false; }
      if (emailTaken) { setError('err-email','Email already in use'); markInvalid($('email'), true); ok = false; }
      if (!ok) return showToast('error','Validation failed','Username or email already used');

      // update local jessie_users store: update matching user or add if not present
      const phoneVal = $('phone').value.trim();
      const addrVal = $('address').value.trim();

      const matchIndex = users.findIndex(u => (originalUsername && u.username === originalUsername) || (originalEmail && u.email === originalEmail));
      if (matchIndex >= 0) {
        // merge changes into stored user
        users[matchIndex] = Object.assign({}, users[matchIndex], {
          name: name,
          firstName: firstName,
          lastName: lastName,
          username: username,
          email: email,
          phone: phoneVal,
          address: addrVal,
          
          photo: user.photo || users[matchIndex].photo
        });
      } else {
        // not found in registered users - append as a customer record
        users.push({
          name: name,
          firstName: firstName,
          lastName: lastName,
          username: username,
          email: email,
          password: user.password || '',
          role: user.role || 'customer',
          dateCreated: user.dateCreated || new Date().toISOString(),
          phone: phoneVal,
          address: addrVal,
          
          photo: user.photo || null
        });
      }

      localStorage.setItem('jessie_users', JSON.stringify(users));

      // update currentUser record
      user.name = name;
      user.firstName = firstName;
      user.lastName = lastName;
      user.username = username;
      user.email = email;
      user.phone = phoneVal;
      user.address = addrVal;

      saveProfile(user);
      showToast('success','Saved','Profile updated');
    }

    const saveConfirmModal = $('save-confirm-modal');
    const saveCancelBtn = $('save-cancel');
    const saveConfirmBtn = $('save-confirm');

    $('save-profile').addEventListener('click', ()=>{
      if (saveConfirmModal) saveConfirmModal.setAttribute('aria-hidden','false');
    });
    if (saveCancelBtn) saveCancelBtn.addEventListener('click', ()=>{
      if (saveConfirmModal) saveConfirmModal.setAttribute('aria-hidden','true');
    });
    if (saveConfirmBtn) saveConfirmBtn.addEventListener('click', ()=>{
      performSave();
      if (saveConfirmModal) saveConfirmModal.setAttribute('aria-hidden','true');
    });
    
    // Close save confirmation modal when clicking backdrop
    if (saveConfirmModal) {
      saveConfirmModal.addEventListener('click', (e) => {
        if (e.target === saveConfirmModal) {
          saveConfirmModal.setAttribute('aria-hidden','true');
        }
      });
    }

    // phone normalization on blur
    $('phone').addEventListener('blur', (e)=>{
      const v = e.target.value.trim();
      if (!v) return;
      const n = normalizePhone(v);
      if (n) e.target.value = n;
    });

    function normalizePhone(v){
      // strip non-digits
      const digits = v.replace(/[^0-9+]/g,'');
      if (digits.startsWith('+')) {
        const d = digits.replace(/[^0-9]/g,'');
        return d.length >= 8 ? `+${d}` : null;
      }
      if (digits.startsWith('0') && digits.length >= 10) {
        return '+63' + digits.replace(/^0+/, '');
      }
      if (digits.length === 9 || digits.length === 10) {
        return '+63' + digits.replace(/^0+/, '');
      }
      if (digits.startsWith('63') && digits.length >= 11) return '+' + digits;
      return null;
    }

    // Password modal wiring
    const pwModal = $('pw-modal');
    const pwCancel = $('pw-cancel');
    const pwSave = $('pw-save');

    $('reset-password').addEventListener('click', ()=>{
      pwModal.setAttribute('aria-hidden','false');
      // Reset password fields when opening modal
      $('current-pw').value = '';
      $('new-pw').value = '';
      $('confirm-pw').value = '';
      // Reset all password fields to type="password" and reset eye icons
      $('current-pw').type = 'password';
      $('new-pw').type = 'password';
      $('confirm-pw').type = 'password';
      const toggleCurrent = $('toggle-current-pw');
      const toggleNew = $('toggle-new-pw');
      const toggleConfirm = $('toggle-confirm-pw');
      if (toggleCurrent) toggleCurrent.querySelector('i').className = 'fas fa-eye';
      if (toggleNew) toggleNew.querySelector('i').className = 'fas fa-eye';
      if (toggleConfirm) toggleConfirm.querySelector('i').className = 'fas fa-eye';
    });
    pwCancel.addEventListener('click', ()=> pwModal.setAttribute('aria-hidden','true'));
    
    // Close password modal when clicking backdrop
    if (pwModal) {
      pwModal.addEventListener('click', (e) => {
        if (e.target === pwModal) {
          pwModal.setAttribute('aria-hidden','true');
        }
      });
    }

    // Toggle password visibility functions
    function setupPasswordToggle(inputId, toggleId) {
      const input = $(inputId);
      const toggle = $(toggleId);
      if (!input || !toggle) return;
      
      toggle.addEventListener('click', () => {
        const icon = toggle.querySelector('i');
        if (input.type === 'password') {
          input.type = 'text';
          icon.className = 'fas fa-eye-slash';
        } else {
          input.type = 'password';
          icon.className = 'fas fa-eye';
        }
      });
    }

    // Setup toggles for all three password fields
    setupPasswordToggle('current-pw', 'toggle-current-pw');
    setupPasswordToggle('new-pw', 'toggle-new-pw');
    setupPasswordToggle('confirm-pw', 'toggle-confirm-pw');

    pwSave.addEventListener('click', async ()=>{
      const cur = $('current-pw').value || '';
      const nw = $('new-pw').value || '';
      const conf = $('confirm-pw').value || '';

      if (!cur || !nw || !conf) return showToast('error','Missing','Please fill all password fields');
      if (nw.length < 6) return showToast('error','Weak','New password should be at least 6 characters');
      if (nw !== conf) return showToast('error','Mismatch','New passwords do not match');

      // Get current user info
      let currentUser = null;
      try {
        const sessionUser = sessionStorage.getItem('currentUser');
        const localUser = localStorage.getItem('currentUser');
        const userStr = sessionUser || localUser;
        if (userStr) {
          currentUser = JSON.parse(userStr);
        }
      } catch (e) {
        console.error('Error parsing currentUser:', e);
      }

      if (!currentUser || (!currentUser.email && !currentUser.username)) {
        return showToast('error','Error','User information not found. Please log in again.');
      }

      const emailOrUsername = currentUser.email || currentUser.username;
      let passwordVerified = false;
      let useAPI = false;

      // Try API verification first (if API is available)
      if (typeof AuthAPI !== 'undefined' && typeof AuthAPI.login === 'function') {
        try {
          console.log('🔐 Verifying password via API for:', emailOrUsername);
          const loginResult = await AuthAPI.login(emailOrUsername, cur);
          
          if (loginResult && loginResult.success) {
            passwordVerified = true;
            useAPI = true;
            console.log('✅ Password verified via API');
            
            // Save token if returned
            if (loginResult.data && loginResult.data.token) {
              const token = (loginResult.data.token || '').trim();
              console.log('💾 Saving token:', token.substring(0, 20) + '...');
              sessionStorage.setItem('token', token);
              localStorage.setItem('token', token);
              console.log('✅ Token saved to both storage locations');
            }
          } else {
            console.warn('⚠️ API password verification failed, trying localStorage fallback');
          }
        } catch (apiError) {
          console.warn('⚠️ API verification error, falling back to localStorage:', apiError.message);
        }
      }

      // Fallback to localStorage verification if API failed or not available
      if (!passwordVerified) {
        console.log('🔐 Verifying password via localStorage for:', emailOrUsername);
        
        // Check in currentUser first
        if (currentUser.password && currentUser.password === cur) {
          passwordVerified = true;
          console.log('✅ Password verified via currentUser');
        } else {
          // Check in jessie_users array
          try {
            const users = JSON.parse(localStorage.getItem('jessie_users') || '[]');
            const foundUser = users.find(u => 
              (u.email === emailOrUsername || u.email === currentUser.email) || 
              (u.username === emailOrUsername || u.username === currentUser.username)
            );
            
            if (foundUser && foundUser.password === cur) {
              passwordVerified = true;
              console.log('✅ Password verified via jessie_users');
            }
          } catch (e) {
            console.error('Error checking jessie_users:', e);
          }
        }
      }

      // Verify password was correct
      if (!passwordVerified) {
        return showToast('error','Incorrect','Current password is incorrect');
      }

      // Update password - try API first, then always update localStorage
      let apiUpdateSuccess = false;
      
      if (useAPI && typeof UsersAPI !== 'undefined' && typeof UsersAPI.updateProfile === 'function') {
        try {
          const tokenBeforeUpdate = (typeof getAuthToken === 'function') ? getAuthToken() : (sessionStorage.getItem('token') || localStorage.getItem('token') || '');
          
          if (tokenBeforeUpdate) {
            console.log('🔄 Attempting to update password via API...');
            const updateResult = await UsersAPI.updateProfile({ password: nw });
            
            if (updateResult && updateResult.success) {
              apiUpdateSuccess = true;
              console.log('✅ Password updated via API');
            } else {
              console.warn('⚠️ API update failed, updating localStorage only');
            }
          } else {
            console.warn('⚠️ No token available, updating localStorage only');
          }
        } catch (updateError) {
          console.warn('⚠️ API update error, updating localStorage only:', updateError.message);
        }
      }

      // Always update localStorage (works even if API fails or isn't available)
      try {
        // Update currentUser
        if (currentUser) {
          currentUser.password = nw;
          saveProfile(currentUser);
          console.log('✅ Password updated in currentUser storage');
        }
        
        // Update in jessie_users array for backward compatibility
        try {
          const users = JSON.parse(localStorage.getItem('jessie_users') || '[]');
          const userIndex = users.findIndex(u => 
            (u.email === currentUser.email || u.email === emailOrUsername) || 
            (u.username === currentUser.username || u.username === emailOrUsername)
          );
          if (userIndex >= 0) {
            users[userIndex].password = nw;
            localStorage.setItem('jessie_users', JSON.stringify(users));
            console.log('✅ Password updated in jessie_users');
          } else {
            // If user not found in jessie_users, add them
            const newUser = {
              ...currentUser,
              password: nw,
              role: currentUser.role || 'customer',
              dateCreated: currentUser.dateCreated || new Date().toISOString()
            };
            users.push(newUser);
            localStorage.setItem('jessie_users', JSON.stringify(users));
            console.log('✅ User added to jessie_users with new password');
          }
        } catch (e) {
          console.warn('Failed to update jessie_users:', e);
        }
        
        // Success - close modal and show message
        pwModal.setAttribute('aria-hidden','true');
        $('current-pw').value = $('new-pw').value = $('confirm-pw').value = '';
        
        showToast('success','Saved','Password updated');
      } catch (localError) {
        console.error('Error updating localStorage:', localError);
        showToast('error','Failed','Failed to update password in local storage');
      }
    });
  });

  // Tabs: profile vs order history
  (function setupTabs(){
    const tabProfile = document.getElementById('tab-profile');
    const tabHistory = document.getElementById('tab-history');
    const form = document.getElementById('profile-form');
    const history = document.getElementById('history-section');
    const title = document.getElementById('section-title');

    function setActive(btn){
      document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
      if (btn) btn.classList.add('active');
    }

    function showProfile(){ form.style.display = ''; history.style.display = 'none'; title.textContent = 'Profile Information'; setActive(tabProfile); }
    function showHistory(){ form.style.display = 'none'; history.style.display = ''; title.textContent = 'Order History'; setActive(tabHistory); renderOrderHistory(); }

    if (tabProfile) tabProfile.addEventListener('click', showProfile);
    if (tabHistory) tabHistory.addEventListener('click', showHistory);

    // default to profile
    setActive(tabProfile);
  })();

  async function renderOrderHistory(){
    try {
      const wrap = document.getElementById('order-history-list');
      if (!wrap) return;
      
      // Show loading state
      wrap.innerHTML = '<div style="color:#666;text-align:center;padding:20px;">Loading orders...</div>';
      
      // Get current user - try both sessionStorage and localStorage
      let current = null;
      try {
        const sessionUser = sessionStorage.getItem('currentUser');
        const localUser = localStorage.getItem('currentUser');
        const userStr = sessionUser || localUser;
        if (userStr) {
          current = JSON.parse(userStr);
        }
      } catch (e) {
        console.error('Error parsing currentUser:', e);
      }
      
      if (!current) {
        wrap.innerHTML = '<div style="color:#666;text-align:center;padding:20px;">Please log in to view your orders.</div>';
        return;
      }
      
      // Extract user identifiers - handle different field name variations
      const userId = current.user_id || current.id || null;
      const userEmail = (current.email || '').trim();
      const userUsername = (current.username || '').trim();
      
      console.log('Fetching orders for user:', { userId, userEmail, userUsername });
      
      // Fetch orders from database API using OrdersAPI helper
      let orders = [];
      try {
        // Build filters object for OrdersAPI
        const filters = {};
        if (userId) filters.user_id = userId;
        if (userEmail) filters.customer_email = userEmail;
        if (userUsername) filters.customer_username = userUsername;
        
        console.log('Using filters:', filters);
        
        // Use OrdersAPI helper if available
        if (typeof OrdersAPI !== 'undefined' && OrdersAPI && typeof OrdersAPI.getAll === 'function') {
          console.log('Using OrdersAPI.getAll');
          const result = await OrdersAPI.getAll(filters);
          console.log('OrdersAPI response:', result);
          
          if (result && result.success && Array.isArray(result.data)) {
            orders = result.data;
            console.log('Found orders:', orders.length);
          } else {
            console.warn('Unexpected API response format:', result);
          }
        } else {
          // Fallback to direct fetch if OrdersAPI not available
          console.log('OrdersAPI not available, using direct fetch');
          const apiBaseUrl = (typeof getAPIBaseURL === 'function') ? getAPIBaseURL() : (window.API_BASE_URL || '');
          
          if (apiBaseUrl) {
            const queryParams = new URLSearchParams();
            if (userId) queryParams.append('user_id', userId);
            if (userEmail) queryParams.append('customer_email', userEmail);
            if (userUsername) queryParams.append('customer_username', userUsername);
            
            const queryString = queryParams.toString();
            const apiUrl = `${apiBaseUrl}/orders/get-all.php${queryString ? '?' + queryString : ''}`;
            console.log('Fetching from:', apiUrl);
            
            const response = await fetch(apiUrl);
            if (response.ok) {
              const data = await response.json();
              console.log('API response:', data);
              if (data.success && Array.isArray(data.data)) {
                orders = data.data;
                console.log('Found orders:', orders.length);
              } else {
                console.warn('Unexpected response format:', data);
              }
            } else {
              console.error('API request failed:', response.status, response.statusText);
              const errorText = await response.text();
              console.error('Error response:', errorText);
            }
          }
        }
      } catch (err) {
        console.error('Error fetching orders from API:', err);
        // Fallback to localStorage if API fails
        try {
          const localOrders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
          const name = (current && current.name) || '';
          const username = (current && current.username) || '';
          const email = (current && current.email) || '';
          orders = localOrders.filter(o => 
            (o.customerName === name) || 
            (o.customerUsername && o.customerUsername === username) || 
            (o.customerEmail && o.customerEmail === email)
          );
          console.log('Using localStorage fallback, found orders:', orders.length);
        } catch (localErr) {
          console.error('Error accessing localStorage:', localErr);
        }
      }
      
      wrap.innerHTML = '';
      
      if (orders.length === 0) {
        wrap.innerHTML = '<div style="color:#666;text-align:center;padding:20px;">No orders yet.</div>';
        return;
      }

      // Sort by date/time descending (newest first)
      orders.sort((a, b) => {
        const dateA = a.created_at || a.order_date || a.date || '';
        const dateB = b.created_at || b.order_date || b.date || '';
        const timeA = a.order_time || a.time || '00:00:00';
        const timeB = b.order_time || b.time || '00:00:00';
        const fullA = new Date(dateA + ' ' + timeA);
        const fullB = new Date(dateB + ' ' + timeB);
        return fullB - fullA;
      });

      // Table header
      const table = document.createElement('div');
      table.className = 'history-table';
      table.innerHTML = '<div class="history-row history-header"><div class="col id">ID</div><div class="col date">DATE</div><div class="col branch">BRANCH</div><div class="col status">STATUS</div><div class="col total">TOTAL</div><div class="col act">ACTION</div></div>';
      wrap.appendChild(table);

      orders.forEach(o => {
        const row = document.createElement('div');
        row.className = 'history-row';
        const status = (o.order_status || o.status || 'pending').toUpperCase();
        const branch = o.branch || 'N/A';
        const orderId = o.order_id || o.id || 'N/A';
        const orderDate = o.order_date || o.date || (o.created_at ? o.created_at.split(' ')[0] : 'N/A');
        const orderTotal = parseFloat(o.total || o.total_amount || 0).toFixed(2);
        // Normalize status class for CSS (handle variations)
        const statusClass = (o.order_status || o.status || 'pending').toLowerCase().replace(/\s+/g, '-');
        row.innerHTML = `<div class="col id">${orderId}</div><div class="col date">${orderDate}</div><div class="col branch">${branch}</div><div class="col status"><span class="status-badge ${statusClass}">${status}</span></div><div class="col total">₱ ${orderTotal}</div><div class="col act"><button class="btn primary reorder">Re-order</button></div>`;
        row.addEventListener('click', (ev)=>{ if (!(ev.target && ev.target.classList.contains('reorder'))) showOrderDetail(o); });
        const reorderBtn = row.querySelector('.reorder');
        if (reorderBtn) {
          reorderBtn.addEventListener('click', (ev)=>{ ev.stopPropagation(); reorderOrder(o); });
        }
        table.appendChild(row);
      });
    } catch(e){
      console.error('Error in renderOrderHistory:', e);
      const wrap = document.getElementById('order-history-list');
      if (wrap) {
        wrap.innerHTML = '<div style="color:#d32f2f;text-align:center;padding:20px;">Error loading orders. Please try again later.</div>';
      }
    }
  }

  function showOrderDetail(order){
    const wrap = document.getElementById('order-history-list');
    if (!wrap) return;
    
    // Handle both database format and localStorage format
    const items = order.items || [];
    const itemsHtml = items.map(i => {
      const qty = i.quantity || i.qty || 1;
      const name = i.product_name || i.name || 'Unknown';
      const size = i.size || 'Regular';
      const price = parseFloat(i.price || 0);
      const itemTotal = qty * price;
      return `<div class="detail-item"><span class="item-name">${qty}x ${name} <small>(${size})</small></span><span class="item-price">₱ ${itemTotal.toFixed(2)}</span></div>`;
    }).join('');
    
    const status = (order.order_status || order.status || 'pending').toUpperCase();
    const branch = order.branch || 'N/A';
    const orderId = order.order_id || order.id || 'N/A';
    const orderDate = order.order_date || order.date || (order.created_at ? order.created_at.split(' ')[0] : 'N/A');
    const orderTotal = parseFloat(order.total || order.total_amount || 0).toFixed(2);
    // Always show "Paid" in order history since orders only appear after successful GCash payment
    const paymentStatus = 'Paid';
    // Normalize status class for CSS (handle variations like "Out for Delivery")
    const statusClass = (order.order_status || order.status || 'pending').toLowerCase().replace(/\s+/g, '-');
    
    wrap.innerHTML = `
      <div class="order-detail-card">
        <div class="detail-header">
          <h3>Order ID: <span class="order-id">${orderId}</span></h3>
          <div class="detail-meta">
            <span>Date: <strong>${orderDate}</strong></span>
            <span>Branch: <strong>${branch}</strong></span>
            <span class="status-badge ${statusClass}">${status}</span>
            <span>Payment: <strong>${paymentStatus}</strong></span>
          </div>
        </div>
        <div class="detail-body">
          <div class="detail-section">
            <h4>Order Items</h4>
            <div class="detail-items">${itemsHtml || '<div style="color:#666;">No items found.</div>'}</div>
          </div>
        </div>
        <div class="detail-footer">
          <div class="detail-total">
            <span>TOTAL:</span>
            <strong>₱ ${orderTotal}</strong>
          </div>
          <div class="detail-actions">
            <button class="btn secondary" id="back-to-list">Back</button>
            <button class="btn primary" id="detail-reorder">Re-order</button>
          </div>
        </div>
      </div>`;
    const back = document.getElementById('back-to-list');
    if (back) back.addEventListener('click', renderOrderHistory);
    const re = document.getElementById('detail-reorder');
    if (re) re.addEventListener('click', ()=> reorderOrder(order));
  }

  async function reorderOrder(order){
    try{
      // Get order ID from the order object (prefer numeric id for database lookup)
      const orderId = order.id || order.order_id;
      if (!orderId) {
        console.error('Order ID not found');
        if (typeof showToast === 'function') {
          showToast('error', 'Reorder Failed', 'Order ID not found');
        } else {
          alert('Order ID not found. Cannot reorder.');
        }
        return;
      }

      // Determine if we're using numeric id or order_id string
      const isNumericId = typeof orderId === 'number' || /^\d+$/.test(String(orderId));
      
      // Fetch full order details from database API
      let orderData = null;
      try {
        if (typeof OrdersAPI !== 'undefined' && OrdersAPI && typeof OrdersAPI.get === 'function') {
          // Use numeric id if available, otherwise use order_id string
          const result = await OrdersAPI.get(orderId, !isNumericId);
          if (result && result.success && result.data) {
            orderData = result.data;
            console.log('✅ Fetched order from database:', orderData);
          } else {
            throw new Error(result?.message || 'Failed to fetch order details');
          }
        } else {
          // Fallback: use order object passed in (should have items already)
          console.warn('OrdersAPI not available, using order object directly');
          orderData = order;
        }
      } catch (apiError) {
        console.error('Failed to fetch order from API:', apiError);
        // Fallback to using the order object passed in
        if (order.items && order.items.length > 0) {
          console.warn('Using order object as fallback');
          orderData = order;
        } else {
          throw new Error('Failed to fetch order details: ' + (apiError.message || 'Unknown error'));
        }
      }

      // Get menu items to match product names and get productId and images
      const menuItems = [];
      try {
        // Try to get menu items from localStorage
        const storedMenu = JSON.parse(localStorage.getItem('jessie_menu') || '[]');
        if (Array.isArray(storedMenu) && storedMenu.length > 0) {
          menuItems.push(...storedMenu);
        }
        
        // Also try to fetch from API if available
        if (typeof ProductsAPI !== 'undefined' && ProductsAPI && typeof ProductsAPI.getAll === 'function') {
          try {
            const productsResult = await ProductsAPI.getAll();
            if (productsResult && productsResult.success && Array.isArray(productsResult.data)) {
              // Merge API products with localStorage products
              productsResult.data.forEach(apiProduct => {
                const exists = menuItems.find(m => m.id === apiProduct.id || m.name === apiProduct.name);
                if (!exists) {
                  menuItems.push(apiProduct);
                }
              });
            }
          } catch (productError) {
            console.warn('Failed to fetch products from API:', productError);
          }
        }
      } catch (menuError) {
        console.warn('Failed to load menu items:', menuError);
      }

      // Helper function to find product by name
      function findProductByName(productName) {
        return menuItems.find(p => 
          p.name === productName || 
          p.product_name === productName ||
          (p.name && productName && p.name.toLowerCase() === productName.toLowerCase())
        );
      }

      // Format order items for cart
      const items = (orderData.items || []).map((item, index) => {
        const productName = item.product_name || item.name || '';
        const product = findProductByName(productName);
        
        return {
          cartId: Date.now() + index + Math.random(),
          productId: product ? (product.id || product.product_id || 0) : 0,
          name: productName,
          img: product ? (product.img || product.image_url || '') : '',
          size: item.size || 'Regular',
          special: item.special || 'None',
          notes: item.notes || '',
          qty: item.quantity || item.qty || 1,
          unitPrice: parseFloat(item.price || 0)
        };
      });

      if (items.length === 0) {
        if (typeof showToast === 'function') {
          showToast('error', 'Reorder Failed', 'No items found in this order');
        } else {
          alert('No items found in this order. Cannot reorder.');
        }
        return;
      }

      // Save to localStorage with user-specific key
      const current = JSON.parse(sessionStorage.getItem('currentUser') || localStorage.getItem('currentUser') || '{}');
      const userKey = encodeURIComponent(((current && (current.email||current.username))||'guest').toLowerCase());
      localStorage.setItem(`saved_cart_${userKey}`, JSON.stringify(items));
      
      // Show success message
      if (typeof showToast === 'function') {
        showToast('success', 'Order Loaded', `Loading ${items.length} item(s) to cart...`);
      }
      
      // Redirect to drinks page
      window.location.href = 'drinks.php';
    } catch(e) { 
      console.error('Reorder failed:', e);
      if (typeof showToast === 'function') {
        showToast('error', 'Reorder Failed', e.message || 'Failed to reorder. Please try again.');
      } else {
        alert('Failed to reorder: ' + (e.message || 'Please try again.'));
      }
    }
  }

  // Logout handler
  const logoutBtn = document.getElementById('logout-link');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
      e.preventDefault();
      if (confirm('Are you sure you want to log out?')) {
        sessionStorage.removeItem('isLoggedIn');
        sessionStorage.removeItem('currentUser');
        window.location.href = 'customer_dashboard.php';
      }
    });
  }

})();
