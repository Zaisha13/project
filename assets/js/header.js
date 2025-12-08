// PURPOSE: Shared header behaviors across customer, admin, and cashier portals
// This script enhances the existing header without replacing it

document.addEventListener('DOMContentLoaded', function(){
  try {
    const path = window.location.pathname.split('/').pop() || 'customer_dashboard.php';
    
    // Add active class to current page link
    document.querySelectorAll('.nav-btn, .navbar a').forEach(a => {
      const href = (a.getAttribute('href') || '').split('/').pop();
      if (href === path) {
        a.classList.add('active');
      }
    });

    // Check both sessionStorage (for current session) and localStorage (for backward compatibility)
    const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true' || localStorage.getItem('isLoggedIn') === 'true';
    const rightHeader = document.querySelector('.right-header');
    
    function showAuthBanner(message, redirectUrl, delayMs = 1400) {
      try {
        let banner = document.querySelector('.auth-banner');
        if (!banner) {
          banner = document.createElement('div');
          banner.className = 'auth-banner';
          banner.setAttribute('role', 'status');
          document.body.appendChild(banner);
        }
        banner.innerHTML = `<div>${message}</div><small>You will be redirected shortly.</small>`;
        banner.getBoundingClientRect();
        banner.classList.add('auth-banner--visible');
        banner.style.pointerEvents = 'none';

        const t = setTimeout(() => {
          try {
            if (redirectUrl) window.location.href = redirectUrl;
          } finally {
            banner.classList.remove('auth-banner--visible');
            setTimeout(() => { if (banner && banner.parentNode) banner.parentNode.removeChild(banner); }, 300);
            document.removeEventListener('click', cancelFn, true);
          }
        }, delayMs);

        function cancelFn() {
          try { clearTimeout(t); } catch (e) {}
          try { banner.classList.remove('auth-banner--visible'); } catch (e) {}
          setTimeout(() => { if (banner && banner.parentNode) banner.parentNode.removeChild(banner); }, 300);
          document.removeEventListener('click', cancelFn, true);
        }

        document.addEventListener('click', cancelFn, true);
        return { timeoutId: t, banner, cancel: cancelFn };
      } catch (err) { console.warn('showAuthBanner error', err); }
    }

    if (rightHeader) {
      // Find the profile link and handle authentication
      const profileLink = rightHeader.querySelector('a[href*="profile"]');
      const logoutLink = rightHeader.querySelector('.logout');
      
      if (!isLoggedIn) {
        // Disable profile link for non-logged-in users
        if (profileLink) {
          profileLink.classList.add('disabled');
          profileLink.setAttribute('aria-disabled', 'true');
          profileLink.style.opacity = '0.5';
          profileLink.style.cursor = 'not-allowed';
          profileLink.addEventListener('click', function(e){
            e.preventDefault();
            showAuthBanner('Please log in or register to access your profile.', 'customer_dashboard.php', 1400);
          });
        }
        
        // Change logout link to "Create Account" / Login
        if (logoutLink) {
          logoutLink.textContent = 'Login';
          logoutLink.classList.remove('logout');
          logoutLink.classList.add('login-link');
          logoutLink.href = '#';
          logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof window.showAuthModal !== 'undefined') {
              window.showAuthModal('login');
            } else {
              // Redirect to dashboard where modal is available
              window.location.href = 'customer_dashboard.php';
            }
          });
        }
      } else {
        // User is logged in - enable profile and setup logout
        if (profileLink) {
          profileLink.classList.remove('disabled');
          profileLink.removeAttribute('aria-disabled');
          profileLink.style.opacity = '';
          profileLink.style.cursor = '';
        }
        
        // Get user info and update logout button text
        try {
          const currentUserStr = sessionStorage.getItem('currentUser') || localStorage.getItem('currentUser');
          if (currentUserStr) {
            const currentUser = JSON.parse(currentUserStr);
            const userName = currentUser.name || currentUser.firstname || currentUser.email || 'User';
            // Update logout button to show user is logged in (keep it as "Logout" but ensure it's visible)
            if (logoutLink) {
              logoutLink.textContent = 'Logout';
              logoutLink.classList.add('logout');
              logoutLink.classList.remove('login-link');
            }
          }
        } catch (e) {
          console.warn('Error parsing currentUser:', e);
        }
        
        // Setup logout functionality
        if (logoutLink) {
          logoutLink.addEventListener('click', function(e){
            e.preventDefault();
            if (confirm('Are you sure you want to log out?')) {
              // Remove from both sessionStorage and localStorage
              sessionStorage.removeItem('isLoggedIn');
              sessionStorage.removeItem('currentUser');
              sessionStorage.removeItem('token');
              localStorage.removeItem('isLoggedIn');
              localStorage.removeItem('currentUser');
              localStorage.removeItem('token');
              window.location.href = 'customer_dashboard.php';
            }
          });
        }
      }
    }

    // Add scroll effect to header
    const headerEl = document.querySelector('header');
    if (headerEl) {
      let ticking = false;
      const update = () => {
        const scrolled = window.scrollY > 8;
        headerEl.classList.toggle('scrolled', scrolled);
        ticking = false;
      };
      window.addEventListener('scroll', function onScroll() {
        if (!ticking) {
          window.requestAnimationFrame(update);
          ticking = true;
        }
      }, { passive: true });
      update();
    }
  } catch (err) { console.warn('header.js init error', err); }
});
