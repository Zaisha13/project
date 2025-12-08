/* PURPOSE: Customer dashboard scripts — render featured drinks, provide
    helper utilities for menu sync, notifications, and debugging tools used
    by the public-facing customer dashboard. */

// Storage Keys
const ADMIN_MENU_KEY = "jessieCaneMenu";
const CUSTOMER_MENU_KEY = "jessie_menu";
const DEFAULT_IMAGE = "pure-sugarcane.png"; // Fallback image

const DEFAULT_MENU_ITEMS = [
    { id: 1, name: 'Pure Sugarcane', desc: 'Freshly pressed sugarcane juice in its purest form — naturally sweet, refreshing, and energizing with no added sugar or preservatives.', priceRegular: 79, priceTall: 109, img: 'pure-sugarcane.png' },
    { id: 2, name: 'Calamansi Cane', desc: 'The perfect balance of tangy calamansi and sweet sugarcane juice — zesty, refreshing, and bursting with vitamin C.', priceRegular: 89, priceTall: 119, img: 'calamansi-cane.png' },
    { id: 3, name: 'Lemon Cane', desc: 'A zesty blend of fresh lemon and sugarcane juice — bright, citrusy, and perfectly balanced for a refreshing boost.', priceRegular: 89, priceTall: 119, img: 'lemon-cane.png' },
    { id: 4, name: 'Yakult Cane', desc: 'A creamy fusion of probiotic-rich Yakult and sweet sugarcane juice — delicious, gut-friendly, and uniquely satisfying.', priceRegular: 89, priceTall: 119, img: 'yakult-cane.png' },
    { id: 5, name: 'Calamansi-Yakult Cane', desc: 'Three-way harmony: tart calamansi, creamy Yakult, and sweet sugarcane create an unforgettable flavor combo.', priceRegular: 99, priceTall: 129, img: 'calamansi-yakult-cane.png' },
    { id: 6, name: 'Lemon-Yakult Cane', desc: 'Creamy lemon meets probiotic Yakult and sugarcane — tangy, smooth, and irresistibly refreshing.', priceRegular: 99, priceTall: 129, img: 'lemon-yakult-cane.png' },
    { id: 7, name: 'Lychee Cane', desc: 'Exotic lychee and sugarcane juice combine for a tropical, floral sweetness that\'s both exotic and refreshing.', priceRegular: 99, priceTall: 129, img: 'lychee-cane.png' },
    { id: 8, name: 'Orange Cane', desc: 'Sweet citrus meets sugarcane for a vibrant, vitamin-packed drink that\'s both tangy and naturally sweet.', priceRegular: 109, priceTall: 139, img: 'orange-cane.png' },
    { id: 9, name: 'Passion Fruit Cane', desc: 'Tropical passion fruit and sugarcane juice blend into a tangy, exotic drink loaded with flavor and natural sweetness.', priceRegular: 109, priceTall: 139, img: 'passion-fruit-cane.png' },
    { id: 10, name: 'Watermelon Cane', desc: 'A hydrating fusion of freshly pressed watermelon and sugarcane juice, offering a light, cooling sweetness that\'s perfect for hot days.', priceRegular: 109, priceTall: 139, img: 'watermelon-cane.png' },
    { id: 11, name: 'Dragon Fruit Cane', desc: 'A vibrant blend of dragon fruit and pure sugarcane juice — visually stunning, naturally sweet, and loaded with antioxidants.', priceRegular: 119, priceTall: 149, img: 'dragon-fruit-cane.png' },
    { id: 12, name: 'Strawberry Yogurt Cane', desc: 'Creamy strawberry yogurt meets sweet sugarcane for a smooth, fruity, and indulgent drink that\'s both refreshing and satisfying.', priceRegular: 119, priceTall: 149, img: 'strawberry-yogurt-cane.png' }
];

// Global sync function that can be called from anywhere
if (typeof window.syncFromAdmin === 'undefined') {
    window.syncFromAdmin = function() {
        try {
            const adminMenu = JSON.parse(localStorage.getItem('jessieCaneMenu') || '[]');
            const customerMenu = JSON.parse(localStorage.getItem('jessie_menu') || '[]');
            
            // If admin menu has items, convert and save them
            if (adminMenu.length > 0) {
                const converted = adminMenu.map((item, index) => {
                    return {
                        id: item.id || index + 1,
                        name: item.name,
                        desc: item.description || item.desc || '',
                        priceRegular: parseFloat(item.priceRegular || item.priceSmall || 0),
                        priceTall: parseFloat(item.priceTall || item.priceMedium || item.priceLarge || 0),
                        img: item.image || item.img || DEFAULT_IMAGE
                    };
                });
                localStorage.setItem('jessie_menu', JSON.stringify(converted));
                return true;
            }
            return false;
        } catch (err) {
            console.error('Error syncing from admin:', err);
            return false;
        }
    };
}

// Get menu items from localStorage (synced from admin)
function getMenuItems() {
    try {
        if (typeof window.syncFromAdmin === 'function') window.syncFromAdmin();

        let storedMenu = JSON.parse(localStorage.getItem(CUSTOMER_MENU_KEY) || '[]');
        
        // If still empty, force initialization
        if (!Array.isArray(storedMenu) || storedMenu.length === 0) {
            console.log('⚠️ Dashboard menu is empty, initializing defaults...');
            storedMenu = DEFAULT_MENU_ITEMS.map(i => Object.assign({}, i));
            localStorage.setItem(CUSTOMER_MENU_KEY, JSON.stringify(storedMenu));
            console.log('✅ Default menu forced initialization in dashboard');
        }

        const migrated = Array.isArray(storedMenu) ? storedMenu.map(item => {
            if (!item) return null;
            if (typeof item.priceRegular === 'number' && typeof item.priceTall === 'number') return item;

            const ps = parseFloat(item.priceSmall) || 0;
            const pm = parseFloat(item.priceMedium);
            const pl = parseFloat(item.priceLarge);
            // Small -> Regular, Medium -> Tall, Large -> Tall (medium preferred)
            const priceRegular = !isNaN(ps) ? ps : 0;
            const priceTall = (!isNaN(pm) && pm > 0) ? pm : ( (!isNaN(pl) && pl > 0) ? pl : priceRegular );

            return Object.assign({}, item, { priceRegular: Number(priceRegular), priceTall: Number(priceTall) });
        }).filter(Boolean) : [];

        const validItems = migrated.filter(item => item && item.name && item.name.trim() !== '' && item.name !== 'JC' && !item.name.includes('Default') && typeof item.priceRegular === 'number' && item.priceRegular > 0);
        return validItems;
    } catch (err) {
        console.error('Error parsing customer menu items:', err);
        return [];
    }

    // initialize text rotation for the newly rendered featured cards
    try { initFeaturedTextRotation(container, { interval: 4500 }); } catch (err) { console.warn('initFeaturedTextRotation error', err); }
}

// Normalize image path function
function normalizeImagePath(src) {
    if (!src) return '';
    try {
        if (typeof src !== 'string') return '';
        const s = src.trim();
        if (!s) return '';
        // If already has full path, return as-is
        if (s.startsWith('data:') || s.startsWith('http://') || s.startsWith('https://') || s.startsWith('../../assets/images/')) {
            return s;
        }
        // If already starts with ./ or ../, return as-is
        if (s.startsWith('./') || s.startsWith('../')) {
            return s;
        }
        // Otherwise, add the full path
        return '../../assets/images/' + s;
    } catch (err) {
        console.error('Error normalizing image path:', err);
        return '';
    }
}

// Show notification function
function showToast(type, title, message) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <strong>${title}</strong>
        <span>${message}</span>
    `;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Render featured drinks
function renderFeaturedDrinks() {
    const allMenuItems = getMenuItems();
    const container = document.getElementById("featured-drinks");
    const emptyState = document.getElementById("empty-featured");

    // Clear container first
    container.innerHTML = '';

    if (!allMenuItems || allMenuItems.length === 0) {
        emptyState.style.display = 'block';
        container.appendChild(emptyState);
        console.log("📭 No menu items to display in featured section");
        return;
    }

    emptyState.style.display = 'none';

    const slots = Math.min(3, allMenuItems.length);

    // helper to build an element for a given drink object
    function buildSlotElement(drink) {
        const el = document.createElement('div');
        el.className = 'product featured-slot';

        // Normalize image path
        const imgSrc = drink && drink.img ? normalizeImagePath(drink.img) : '';
        const imageHtml = imgSrc && !imgSrc.includes('JC') ? `<img src="${imgSrc}" alt="${drink.name}" class="drink-image">` : '<div class="no-image">🍹</div>';
        const nameHtml = `<h3>${drink && drink.name ? drink.name : ''}</h3>`;
                const descHtml = `<div class="description">${drink && drink.desc ? drink.desc : ''}</div>`;
                const pricesHtml = `<div class="prices" aria-hidden="true"><div class="price-option">Regular: ₱${Number(drink && drink.priceRegular || 0).toFixed(2)}</div><div class="price-option">Tall: ₱${Number((drink && drink.priceTall) || (drink && drink.priceRegular) || 0).toFixed(2)}</div></div>`;

        el.innerHTML = `${imageHtml}${nameHtml}${descHtml}${pricesHtml}<button class="view-btn" onclick="location.href='drinks.php'">View Menu</button>`;
        return el;
    }

    // pick a random set of unique items for the slots
    function pickRandomSet() {
        const pool = [...allMenuItems];
        const result = [];
        while (result.length < slots && pool.length > 0) {
            const idx = Math.floor(Math.random() * pool.length);
            result.push(pool.splice(idx, 1)[0]);
        }
        return result;
    }

    // create initial fixed slots (elements) to preserve layout
    const initialSet = pickRandomSet();
    initialSet.forEach(drink => container.appendChild(buildSlotElement(drink)));
    // ensure we always have the number of slots
    while (container.children.length < slots) container.appendChild(buildSlotElement({}));

    // per-card text rotation removed - descriptions and prices are static

    // rotation timer (random next set each tick) - use named function so we can pause/resume reliably
    const interval = 6000;
    if (container._rotTimer) clearInterval(container._rotTimer);

    let previousIds = initialSet.map(d => d && d.id ? d.id : (d && d.name) || JSON.stringify(d));

    function rotateOnce() {
        // choose a new set that's not identical to previous
        let attempts = 0;
        let next = pickRandomSet();
        const nextIds = next.map(d => d && d.id ? d.id : (d && d.name) || JSON.stringify(d));
        while (arraysEqual(nextIds, previousIds) && attempts < 8) {
            next = pickRandomSet();
            attempts++;
        }

        const slotEls = Array.from(container.querySelectorAll('.featured-slot'));

            // Replace content of each slot immediately (no whole-card transition)
            slotEls.forEach((oldSlot, i) => {
                const drink = next[i] || {};
                // build new inner HTML and replace directly
                try {
                                        const newInner = (function(d){
                                                var imageHtml = (d && d.img && !d.img.includes('JC')) ? ('<img src="' + (d.img || '') + '" alt="' + (d.name || '') + '" class="drink-image">') : ('<div class="no-image">🍹</div>');
                                                var nameHtml = '<h3>' + (d && d.name ? d.name : '') + '</h3>';
                                                var descHtml = '<div class="description">' + (d && d.desc ? d.desc : '') + '</div>';
                                                var pricesHtml = '<div class="prices" aria-hidden="true"><div class="price-option">Regular: ₱' + Number(d && d.priceRegular || 0).toFixed(2) + '</div><div class="price-option">Tall: ₱' + Number((d && d.priceTall) || (d && d.priceRegular) || 0).toFixed(2) + '</div></div>';
                                                return imageHtml + nameHtml + descHtml + pricesHtml + '<button class="view-btn" onclick="location.href=\'drinks.php\'">View Menu</button>';
                                        })(drink);

                    oldSlot.innerHTML = newInner;
                } catch (err) {
                    console.warn('Error replacing slot content', err);
                }
            });

        previousIds = nextIds;
    }

    container._rotTimer = setInterval(rotateOnce, interval);

    // pause rotation on hover for accessibility
    container.addEventListener('mouseenter', () => { if (container._rotTimer) { clearInterval(container._rotTimer); container._rotTimer = null; } });
    container.addEventListener('mouseleave', () => { if (!container._rotTimer) container._rotTimer = setInterval(rotateOnce, interval); });

    function arraysEqual(a, b) {
        if (!a || !b || a.length !== b.length) return false;
        for (let i = 0; i < a.length; i++) if (a[i] !== b[i]) return false;
        return true;
    }
}

// Per-card text rotation removed; descriptions are now static.

// Helper function to shuffle array (for random featured items)
function shuffleArray(array) {
    const newArray = [...array];
    for (let i = newArray.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
    }
    return newArray;
}

// Render announcements
function renderAnnouncements() {
    const container = document.getElementById('announcements-container');
    const section = document.getElementById('announcements-section');
    if (!container) return;
    
    try {
        const announcements = JSON.parse(localStorage.getItem('jessieCaneAnnouncements') || '[]');
        
        if (announcements.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:#666;padding:20px;">No announcements at this time.</p>';
            return;
        }
        
        container.innerHTML = announcements.map(announcement => {
            return `
                <div class="announcement-card">
                    <h3>${announcement.title || 'Announcement'}</h3>
                    <p>${announcement.content || ''}</p>
                    <div class="announcement-date">Posted on: ${announcement.date || 'N/A'}</div>
                </div>
            `;
        }).join('');
    } catch (err) {
        console.error('Error rendering announcements:', err);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    // Public page: allow visiting the dashboard without being logged in.
    // Authentication guarded pages (like profile.html) still perform checks.
    // Keep a debug trace for convenience.
    const isLoggedIn = sessionStorage.getItem("isLoggedIn");
    console.log("customer_dashboard: isLoggedIn=", isLoggedIn);

    // Debug: Check what's in localStorage
    console.log("=== CUSTOMER DASHBOARD DEBUG INFO ===");
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key.includes('menu') || key.includes('Menu')) {
            const data = JSON.parse(localStorage.getItem(key) || "[]");
            console.log(`${key}: ${data.length} items`, data);
        }
    }

    // Initialize event slideshow
    initEventSlideshow();

    // Initial render
    renderFeaturedDrinks();
    renderAnnouncements();

    // Logout functionality
    const logoutBtn = document.querySelector(".logout");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            
            if (confirm('Are you sure you want to log out?')) {
                sessionStorage.removeItem("isLoggedIn");
                sessionStorage.removeItem("currentUser");
                setTimeout(() => {
                    window.location.href = "customer_dashboard.php";
                }, 500);
            }
        });
    }

});

// Simple branded popup used by auth.js on this page (fallback if drinks.js isn't loaded)
if (typeof window.showPopup === 'undefined') {
    function showPopup(type, options = {}) {
        const overlay = document.createElement('div');
        overlay.className = 'popup-overlay';
        const modal = document.createElement('div');
        modal.className = `popup popup-${type||'info'}`;
        const header = document.createElement('div');
        header.className = 'popup-header';
        const h = document.createElement('h3');
        h.textContent = options.title || (type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notice');
        header.appendChild(h);
        const body = document.createElement('div');
        body.className = 'popup-body';
        if (options.message) {
            const p = document.createElement('p');
            p.textContent = options.message;
            body.appendChild(p);
        }
        const actions = document.createElement('div');
        actions.className = 'popup-actions';
        const buttons = Array.isArray(options.actions) && options.actions.length ? options.actions : [{ text: 'OK', type: 'primary', handler: hidePopup }];
        buttons.forEach(a => {
            const btn = document.createElement('button');
            btn.className = `btn btn-${a.type||'primary'}`;
            btn.textContent = a.text||'OK';
            btn.addEventListener('click', (e)=>{ e.stopPropagation(); try{ (a.handler||hidePopup)(); }catch(_){} });
            actions.appendChild(btn);
        });
        modal.appendChild(header); modal.appendChild(body); modal.appendChild(actions);
        overlay.appendChild(modal);
        overlay.addEventListener('click', (e)=>{ if (e.target === overlay) hidePopup(); });
        document.body.appendChild(overlay);
    }
    function hidePopup(){ const p = document.querySelector('.popup-overlay'); if (p) p.remove(); }
    window.showPopup = showPopup; window.hidePopup = hidePopup;
}

// Debug function to check sync status
function checkSyncStatus() {
    const adminMenu = JSON.parse(localStorage.getItem(ADMIN_MENU_KEY) || "[]");
    const customerMenu = JSON.parse(localStorage.getItem(CUSTOMER_MENU_KEY) || "[]");
    
    console.log("=== SYNC STATUS ===");
    console.log("Admin menu items:", adminMenu);
    console.log("Customer menu items:", customerMenu);
    
    const featuredContainer = document.getElementById("featured-drinks");
    const featuredCount = featuredContainer.querySelectorAll('.product').length;
    
    alert(`Admin: ${adminMenu.length} items\nCustomer: ${customerMenu.length} items\nFeatured: ${featuredCount} items showing\nCheck console for details.`);
}

// Manual sync function
function manualSync() {
    const adminMenu = JSON.parse(localStorage.getItem(ADMIN_MENU_KEY) || "[]");
    
    if (adminMenu.length === 0) {
        alert("No items in admin menu to sync!");
        return;
    }
    
    console.log("🔄 Manual sync triggered...", adminMenu);
    
    // Convert and save to customer format
    const customerMenu = adminMenu.map((item, index) => {
        const imageMap = {
            "Pure Sugarcane": "pure-sugarcane.png",
            "Calamansi Cane": "calamansi-cane.png", 
            "Lemon Cane": "lemon-cane.png",
            "Yakult Cane": "yakult-cane.png",
            "Calamansi-Yakult Cane": "calamansi-yakult-cane.png",
            "Lemon-Yakult Cane": "lemon-yakult-cane.png",
            "Lychee Cane": "lychee-cane.png",
            "Orange Cane": "orange-cane.png",
            "Passion Fruit Cane": "passion-fruit-cane.png",
            "Watermelon Cane": "watermelon-cane.png",
            "Dragon Fruit Cane": "dragon-fruit-cane.png",
            "Strawberry Yogurt Cane": "strawberry-yogurt-cane.png"
        };
        
        const imageFilename = item.image || imageMap[item.name] || DEFAULT_IMAGE;
        
        return {
            id: index + 1,
            name: item.name,
            desc: item.description,
            priceRegular: parseFloat(item.priceRegular) || parseFloat(item.priceSmall) || 0,
            priceTall: parseFloat(item.priceTall) || parseFloat(item.priceMedium) || parseFloat(item.priceLarge) || parseFloat(item.priceSmall) || 0,
            img: imageFilename
        };
    });
    
    localStorage.setItem(CUSTOMER_MENU_KEY, JSON.stringify(customerMenu));
    console.log(`✅ Manual sync completed: ${customerMenu.length} items`, customerMenu);
    
    // Immediately re-render featured drinks
    renderFeaturedDrinks();
    
    alert(`Synced ${customerMenu.length} items to customer menu! Featured drinks updated.`);
}

// Force refresh function
function forceRefresh() {
    localStorage.removeItem(CUSTOMER_MENU_KEY);
    console.log("🔄 Force refresh - cleared customer menu cache");
    renderFeaturedDrinks();
    alert("Customer menu cache cleared! Re-syncing from admin...");
}

// Emergency cleanup function
function emergencyCleanup() {
    if (confirm('This will clear ALL menu data. Are you sure?')) {
        localStorage.removeItem(ADMIN_MENU_KEY);
        localStorage.removeItem(CUSTOMER_MENU_KEY);
        console.log("✅ Emergency cleanup completed");
        renderFeaturedDrinks();
        alert('All menu data cleared! Featured drinks section updated.');
    }
}

// Debug function to check what's in localStorage
function debugMenuData() {
    console.log("=== DEBUG MENU DATA ===");
    console.log("Admin Menu (jessieCaneMenu):", JSON.parse(localStorage.getItem(ADMIN_MENU_KEY) || "[]"));
    console.log("Customer Menu (jessie_menu):", JSON.parse(localStorage.getItem(CUSTOMER_MENU_KEY) || "[]"));
    
    const adminCount = JSON.parse(localStorage.getItem(ADMIN_MENU_KEY) || "[]").length;
    const customerCount = JSON.parse(localStorage.getItem(CUSTOMER_MENU_KEY) || "[]").length;
    const featuredCount = document.querySelectorAll('.product').length;
    
    alert(`Admin: ${adminCount} items\nCustomer: ${customerCount} items\nFeatured Showing: ${featuredCount} items\nCheck console for details.`);
}

// Add CSS for the featured drinks
const style = document.createElement('style');
style.textContent = `
    .no-image {
        width: 120px;
        height: 120px;
        background: #f8f5e9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        margin: 0 auto 15px;
        border: 4px solid #146B33;
    }
    
    .drink-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: block;
        border: 4px solid #146B33;
    }
    
    .prices {
        margin: 15px 0;
    }
    
    .price-option {
        background: #FFD966;
        padding: 8px 15px;
        border-radius: 20px;
        margin: 5px 0;
        font-weight: bold;
        color: #146B33;
    }
    
    .description {
        color: #666;
        margin: 10px 0;
        line-height: 1.5;
        min-height: 40px;
    }
    
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        z-index: 1000;
        max-width: 300px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .toast-success {
        background-color: #4CAF50;
    }
    
    .toast strong {
        display: block;
        margin-bottom: 5px;
    }
    /* Description style (static) */
    .description { color: #666; margin: 10px 0; line-height: 1.5; min-height: 40px; }
`;
document.head.appendChild(style);

// Event Slideshow Function
function initEventSlideshow() {
    const slides = document.querySelectorAll('.event-slide');
    const indicators = document.querySelectorAll('.indicator');
    let currentSlide = 0;
    const totalSlides = slides.length;
    let slideInterval;

    function showSlide(index) {
        // Remove active class from all slides and indicators
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));

        // Add active class to current slide and indicator
        if (slides[index]) {
            slides[index].classList.add('active');
        }
        if (indicators[index]) {
            indicators[index].classList.add('active');
        }
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    function startSlideshow() {
        slideInterval = setInterval(nextSlide, 4000); // Change slide every 4 seconds
    }

    function stopSlideshow() {
        clearInterval(slideInterval);
    }

    // Initialize first slide
    if (slides.length > 0) {
        showSlide(0);
        startSlideshow();
    }

    // Pause on hover
    const slideshow = document.querySelector('.event-slideshow');
    if (slideshow) {
        slideshow.addEventListener('mouseenter', stopSlideshow);
        slideshow.addEventListener('mouseleave', startSlideshow);
    }

    // Indicator click handlers
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
            stopSlideshow();
            // Restart after a delay
            setTimeout(startSlideshow, 6000);
        });
    });
}

// Log initialization
console.log("✅ customer_dashboard.js loaded successfully");
console.log("Available debug commands:");
console.log("- checkSyncStatus() - Check sync status");
console.log("- manualSync() - Force manual sync and update featured drinks");
console.log("- forceRefresh() - Clear cache and refresh");
console.log("- debugMenuData() - Debug menu data");
console.log("- emergencyCleanup() - Clear all menu data");