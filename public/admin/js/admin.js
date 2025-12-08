/* PURPOSE: Admin dashboard client logic — menu management, reports, modals,
   and admin-only interactions. */

// Authentication check - redirect to login if not authenticated
function checkAdminAuth() {
    // Check if we're already on the admin dashboard - if so, allow staying on page
    const currentPath = window.location.pathname;
    const isOnAdminPage = currentPath.includes('admin.php') || currentPath.includes('admin.html');
    
    // If already on admin dashboard page, allow access (don't redirect on refresh)
    if (isOnAdminPage) {
        // Try to restore login state if missing but user is on dashboard
        const isLoggedIn = localStorage.getItem('isLoggedIn');
        const currentUser = localStorage.getItem('currentUser');
        
        if (isLoggedIn === 'true' && currentUser) {
            try {
                const user = JSON.parse(currentUser);
                if (user && user.role === 'admin') {
                    return; // Valid admin, stay on dashboard
                }
            } catch (e) {
                // If parsing fails but we're on dashboard, allow staying
                console.warn('Could not parse user data, but staying on admin dashboard');
                return;
            }
        }
        
        // Even if login state is missing, if we're already on dashboard, stay here
        // This prevents redirect loops on refresh
        console.log('On admin dashboard - allowing access even if login state unclear');
        return;
    }
    
    // For other pages, check authentication normally
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const currentUser = localStorage.getItem('currentUser');
    
    if (!isLoggedIn || !currentUser) {
        window.location.href = 'login_admin.php';
        return;
    }
    
    try {
        const user = JSON.parse(currentUser);
        if (!user || user.role !== 'admin') {
            window.location.href = 'login_admin.php';
            return;
        }
    } catch (e) {
        window.location.href = 'login_admin.php';
        return;
    }
}

// Check authentication before initializing dashboard
checkAdminAuth();

document.addEventListener('DOMContentLoaded', function() {
    initializeAdminDashboard();
});

function showPopup(type, options = {}) {

    hidePopup();
    const popup = document.createElement('div');
    popup.className = 'popup-overlay';
    const inner = document.createElement('div');
    inner.className = `popup popup-${type || 'info'}`;

    if (options.title) {
        const h = document.createElement('h3');
        h.textContent = options.title;
        inner.appendChild(h);
    }
    if (options.message) {
        const p = document.createElement('p');
        p.textContent = options.message;
        inner.appendChild(p);
    }
    if (options.customContent) {
        const wrapper = document.createElement('div');
        if (typeof options.customContent === 'string') wrapper.innerHTML = options.customContent; else wrapper.appendChild(options.customContent);
        inner.appendChild(wrapper);
    }

    const actionsContainer = document.createElement('div');
    actionsContainer.className = 'popup-actions';
    if (Array.isArray(options.actions)) {
        options.actions.forEach(action => {
            const btn = document.createElement('button');
            btn.className = `btn btn-${action.type || 'primary'}`;
            btn.textContent = action.text || 'Action';
            btn.addEventListener('click', (e) => {
                try { if (typeof action.handler === 'function') action.handler(e); } catch (err) { console.error(err); }
            });
            actionsContainer.appendChild(btn);
        });
    } else {
        const btn = document.createElement('button');
        btn.className = 'btn btn-primary';
        btn.textContent = 'Close';
        btn.addEventListener('click', hidePopup);
        actionsContainer.appendChild(btn);
    }

    inner.appendChild(actionsContainer);
    popup.appendChild(inner);
    document.body.appendChild(popup);

    if (options.backdrop !== false) {
        popup.addEventListener('click', function(e){ if (e.target === popup) hidePopup(); });
    }

    setTimeout(()=>{
        const firstInput = popup.querySelector('input, button, select, textarea');
        if (firstInput) firstInput.focus();
    }, 50);
}

function hidePopup(){
    const p = document.querySelector('.popup-overlay');
    if (p) p.remove();
}

function showToast(type='info', title='', message=''){
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = title ? `<strong>${title}</strong><div>${message}</div>` : (message || title || '');
    document.body.appendChild(t);
    setTimeout(()=> t.classList.add('visible'), 20);
    setTimeout(()=> { t.classList.remove('visible'); setTimeout(()=> t.remove(), 350); }, 3000);
}

function initializeAdminDashboard() {
    // DOM Elements
    const sidebar = document.getElementById('admin-sidebar');
    const pageTitle = document.getElementById('page-title');
    const adminName = document.getElementById('admin-name');
    const logoutBtn = document.getElementById('logout-btn');
    const sidebarLinks = document.querySelectorAll('.nav a[data-section]');
    const contentSections = document.querySelectorAll('.content-section');
    
    // Menu Management Elements
    const addItemBtn = document.getElementById('add-item-btn');
    const editMenuBtn = document.getElementById('edit-menu-btn');
    const modalTitle = document.getElementById('modal-title');
    const menuModal = document.getElementById('menu-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const addItemForm = document.getElementById('add-item-form');
    const menuContainer = document.getElementById('current-menu-container');
    
    const itemNameInput = document.getElementById('item-name');
    const itemDescInput = document.getElementById('item-description');
    const itemImageInput = document.getElementById('item-image');
    const itemImageDataInput = document.getElementById('item-image-data'); 
    const saveItemBtn = document.getElementById('save-item-btn');
    const deleteItemOnEditBtn = document.getElementById('delete-item-on-edit-btn');

    const menuList = document.getElementById('menu-list');
    const menuGrid = document.getElementById('menu-grid');
    const noMenuMessage = document.getElementById('no-menu-message');

    let _lastFocused = null;
    function modalKeyHandler(e){ if (e.key === 'Escape') closeMenuModal(); }
    function openMenuModal(){
        _lastFocused = document.activeElement;
        if (menuModal) {
            menuModal.style.display = 'flex';
            // Ensure modal content is visible
            const modalContent = menuModal.querySelector('.modal-content');
            if (modalContent) modalContent.style.display = 'block';
        }
        if (itemNameInput) setTimeout(()=> itemNameInput.focus(), 50);
        document.addEventListener('keydown', modalKeyHandler);
    }
    function closeMenuModal(){
        if (menuModal) menuModal.style.display = 'none';
        document.removeEventListener('keydown', modalKeyHandler);
        try { if (_lastFocused && typeof _lastFocused.focus === 'function') _lastFocused.focus(); } catch(e){}
    }
    
    const announcementForm = document.getElementById('announcement-form');
    const announcementsList = document.getElementById('announcements-list');
    
    const changeEmailForm = document.getElementById('change-email-form');
    const changePasswordForm = document.getElementById('change-password-form');
    const accountTargetSelect = document.getElementById('account-target');
    const MENU_STORAGE_KEY = "jessieCaneMenu";
    const ANNOUNCEMENTS_STORAGE_KEY = "jessieCaneAnnouncements";
    const CUSTOMER_MENU_KEY = "jessie_menu";
    const ACCOUNTS_STORAGE_KEY = "jessie_accounts";
    const ADMIN_CREDENTIALS_KEY = 'jessie_admin_credentials';
    const CASHIER_FAIRVIEW_KEY = 'jessie_cashier_fairview_credentials';
    const CASHIER_SJDM_KEY = 'jessie_cashier_sjdm_credentials';
                
    const DEFAULT_IMAGE = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23FFD966'/><text x='50' y='55' font-size='30' fill='%23146B33' text-anchor='middle'>JC</text></svg>";

    const DEFAULT_MENU_ITEMS = [
        { id: 1, name: 'Pure Sugarcane', description: 'Freshly pressed sugarcane juice in its purest form — naturally sweet, refreshing, and energizing with no added sugar or preservatives.', priceRegular: 79, priceTall: 109, img: '../../assets/images/pure-sugarcane.png' },
        { id: 2, name: 'Calamansi Cane', description: 'A zesty twist on classic sugarcane juice, blended with the tangy freshness of calamansi for a perfectly balanced sweet and citrusy drink.', priceRegular: 89, priceTall: 119, img: '../../assets/images/calamansi-cane.png' },
        { id: 3, name: 'Lemon Cane', description: 'Freshly squeezed lemon combined with pure sugarcane juice, creating a crisp and revitalizing drink that awakens your senses.', priceRegular: 89, priceTall: 119, img: '../../assets/images/lemon-cane.png' },
        { id: 4, name: 'Yakult Cane', description: 'A delightful mix of sugarcane juice and Yakult — smooth, creamy, and packed with probiotics for a unique sweet-tangy flavor.', priceRegular: 89, priceTall: 119, img: '../../assets/images/yakult-cane.png' },
        { id: 5, name: 'Calamansi Yakult Cane', description: 'A refreshing blend of calamansi, Yakult, and sugarcane juice — the perfect harmony of sweet, sour, and creamy goodness.', priceRegular: 99, priceTall: 129, img: '../../assets/images/calamansi-yakult-cane.png' },
        { id: 6, name: 'Lemon Yakult Cane', description: 'Experience a fusion of lemon\'s zesty tang with Yakult\'s smooth creaminess, all complemented by naturally sweet sugarcane.', priceRegular: 99, priceTall: 129, img: '../../assets/images/lemon-yakult-cane.png' },
        { id: 7, name: 'Lychee Cane', description: 'A fragrant and fruity treat made with the exotic sweetness of lychee and the crisp freshness of sugarcane juice.', priceRegular: 99, priceTall: 129, img: '../../assets/images/lychee-cane.png' },
        { id: 8, name: 'Orange Cane', description: 'Fresh orange juice blended with pure sugarcane extract for a bright, citrusy burst of sunshine in every sip.', priceRegular: 109, priceTall: 139, img: '../../assets/images/orange-cane.png' },
        { id: 9, name: 'Passion Fruit Cane', description: 'A tropical blend of tangy passion fruit and naturally sweet sugarcane — vibrant, juicy, and irresistibly refreshing.', priceRegular: 109, priceTall: 139, img: '../../assets/images/passion-fruit-cane.png' },
        { id: 10, name: 'Watermelon Cane', description: 'A hydrating fusion of freshly pressed watermelon and sugarcane juice, offering a light, cooling sweetness that\'s perfect for hot days.', priceRegular: 109, priceTall: 139, img: '../../assets/images/watermelon-cane.png' },
        { id: 11, name: 'Dragon Fruit Cane', description: 'A vibrant blend of dragon fruit and pure sugarcane juice — visually stunning, naturally sweet, and loaded with antioxidants.', priceRegular: 119, priceTall: 149, img: '../../assets/images/dragon-fruit-cane.png' },
        { id: 12, name: 'Strawberry Yogurt Cane', description: 'Creamy strawberry yogurt meets sweet sugarcane for a smooth, fruity, and indulgent drink that\'s both refreshing and satisfying.', priceRegular: 119, priceTall: 149, img: '../../assets/images/strawberry-yogurt-cane.png' }
    ];

    function resolveImagePath(path) {
        if (!path) return DEFAULT_IMAGE; 
        if (path.startsWith('data:') || path.startsWith('http://') || path.startsWith('https://') || path.startsWith('../../assets/images/')) return path;
        if (path.startsWith('../')) return path;
        // If just a filename, add the path
        return '../../assets/images/' + path;
    }

    const sessionUser = JSON.parse(localStorage.getItem("currentUser") || "{}");
    if (sessionUser.name && adminName) {
        adminName.textContent = sessionUser.name;
    }

    // Order stats elements
    const smSjdmCard = document.getElementById('sm-sjdm-card');
    const smFairviewCard = document.getElementById('sm-fairview-card');
    const totalOrdersCard = document.getElementById('total-orders-card');
    const smSjdmCount = document.getElementById('sm-sjdm-count');
    const smFairviewCount = document.getElementById('sm-fairview-count');
    const totalOrdersCount = document.getElementById('total-orders-count');
    const orderDetailsModal = document.getElementById('order-details-modal');
    const orderModalTitle = document.getElementById('order-modal-title');
    const orderDetailsContent = document.getElementById('order-details-content');
    const closeOrderModalBtn = document.getElementById('close-order-modal-btn');

    // Fetch orders from database and sync to localStorage
    async function syncOrdersFromDatabase() {
        try {
            // Try to fetch from the API endpoint
            if (typeof OrdersAPI !== 'undefined' && OrdersAPI && typeof OrdersAPI.getAll === 'function') {
                const result = await OrdersAPI.getAll();
                if (result && result.success && Array.isArray(result.data)) {
                    // Convert database orders to localStorage format
                    const convertedOrders = result.data.map(dbOrder => {
                        // Parse order_date if it's a string
                        let orderDate = dbOrder.order_date || dbOrder.date || new Date().toLocaleDateString();
                        let orderTime = dbOrder.order_time || '';
                        let timestamp = Date.now();
                        
                        // If order_date is a timestamp, convert it
                        if (typeof dbOrder.timestamp === 'number') {
                            timestamp = dbOrder.timestamp * 1000; // Convert to milliseconds if in seconds
                            const date = new Date(timestamp);
                            orderDate = date.toLocaleDateString();
                            orderTime = date.toLocaleTimeString();
                        } else if (dbOrder.order_date && typeof dbOrder.order_date === 'string') {
                            // Try to parse date string (format: m/d/Y or Y-m-d)
                            let parsed;
                            if (dbOrder.order_date.includes('/')) {
                                // Format: m/d/Y
                                const parts = dbOrder.order_date.split('/');
                                if (parts.length === 3) {
                                    parsed = new Date(parseInt(parts[2]), parseInt(parts[0]) - 1, parseInt(parts[1]));
                                }
                            } else {
                                parsed = new Date(dbOrder.order_date);
                            }
                            if (parsed && !isNaN(parsed.getTime())) {
                                timestamp = parsed.getTime();
                                orderDate = parsed.toLocaleDateString();
                                orderTime = parsed.toLocaleTimeString();
                            }
                        }
                        
                        // Parse items if they're in a different format
                        let items = dbOrder.items || [];
                        if (typeof items === 'string') {
                            try {
                                items = JSON.parse(items);
                            } catch (e) {
                                items = [];
                            }
                        }
                        
                        // Convert database item format to frontend format
                        // Database uses: product_name, quantity
                        // Frontend expects: name, qty
                        const convertedItems = items.map(dbItem => ({
                            name: dbItem.product_name || dbItem.name || 'Unknown Item',
                            qty: dbItem.quantity || dbItem.qty || 1,
                            size: dbItem.size || 'Regular',
                            special: dbItem.special || 'None',
                            notes: dbItem.notes || '',
                            price: parseFloat(dbItem.price || 0),
                            unitPrice: parseFloat(dbItem.price || 0)
                        }));
                        
                        return {
                            id: dbOrder.order_id || dbOrder.id || '',
                            order_db_id: dbOrder.id || dbOrder.order_db_id || null,
                            customerName: dbOrder.customer_name || '',
                            customerUsername: dbOrder.customer_username || '',
                            customerEmail: dbOrder.customer_email || 'N/A',
                            customerPhone: dbOrder.customer_phone || 'N/A',
                            customerNotes: dbOrder.customer_notes || '',
                            branch: dbOrder.branch || '',
                            items: convertedItems,
                            subtotal: parseFloat(dbOrder.subtotal || 0),
                            tax: parseFloat(dbOrder.tax || 0),
                            total: parseFloat(dbOrder.total || dbOrder.total_amount || 0),
                            amountReceived: parseFloat(dbOrder.amount_received || 0),
                            change: parseFloat(dbOrder.change_amount || 0),
                            status: dbOrder.order_status || dbOrder.status || 'Pending',
                            payment_status: dbOrder.payment_status || 'Pending',
                            timestamp: timestamp,
                            date: orderDate,
                            time: orderTime,
                            orderType: dbOrder.order_type || 'Digital',
                            isGuest: dbOrder.is_guest === 1 || dbOrder.is_guest === true || dbOrder.is_guest === '1',
                            payment_method: dbOrder.payment_method || 'cash'
                        };
                    });
                    
                    // Merge with existing localStorage orders (keep unique by order_db_id or id)
                    const existingOrders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
                    const orderMap = new Map();
                    
                    // First add existing orders
                    existingOrders.forEach(order => {
                        const key = order.order_db_id || order.id;
                        if (key) orderMap.set(String(key), order);
                    });
                    
                    // Then add/update with database orders (database takes precedence)
                    convertedOrders.forEach(order => {
                        const key = order.order_db_id || order.id;
                        if (key) orderMap.set(String(key), order);
                    });
                    
                    // Save merged orders back to localStorage
                    const mergedOrders = Array.from(orderMap.values());
                    localStorage.setItem('jessie_orders', JSON.stringify(mergedOrders));
                    
                    console.log(`✅ Synced ${convertedOrders.length} orders from database to localStorage (total: ${mergedOrders.length})`);
                    return mergedOrders;
                } else {
                    console.warn('OrdersAPI.getAll() returned invalid format:', result);
                }
            } else {
                console.warn('OrdersAPI not available, using localStorage only');
            }
        } catch (err) {
            console.error('Error syncing orders from database:', err);
        }
        
        // Fallback to localStorage if API fails
        try {
            return JSON.parse(localStorage.getItem('jessie_orders') || '[]');
        } catch (e) {
            return [];
        }
    }

    // Calculate and display order stats from database (synced to localStorage)
    async function updateOrderStats() {
        try {
            // Sync orders from database first
            const orders = await syncOrdersFromDatabase();
            const today = new Date().toDateString();

            const todayOrders = orders.filter(order => {
                const orderDate = new Date(order.timestamp || new Date(order.date)).toDateString();
                return orderDate === today;
            });

            const sjdmOrders = todayOrders.filter(order => {
                const branch = (order.branch || '').toLowerCase();
                return branch.includes('sjdm') || branch.includes('san jose') || branch.includes('monte');
            });
            const fairviewOrders = todayOrders.filter(order => {
                const branch = (order.branch || '').toLowerCase();
                return branch.includes('fairview');
            });

            // Show number of orders for each card
            if (smSjdmCount) smSjdmCount.textContent = sjdmOrders.length;
            if (smFairviewCount) smFairviewCount.textContent = fairviewOrders.length;
            if (totalOrdersCount) totalOrdersCount.textContent = todayOrders.length;
        } catch (err) {
            console.error('Error updating order stats:', err);
        }
    }

    // Helper to get today's orders (from synced localStorage)
    async function getTodayOrders() {
        // Sync from database first
        const orders = await syncOrdersFromDatabase();
        const today = new Date().toDateString();
        return orders.filter(order => {
            const orderDate = new Date(order.timestamp || new Date(order.date)).toDateString();
            return orderDate === today;
        });
    }

    // Helper to count items in an order (supports multiple data formats)
    function getOrderItemCount(order) {
        // If items array exists, count the total quantity
        if (order.items && Array.isArray(order.items)) {
            return order.items.reduce((sum, item) => {
                const qty = parseInt(item.qty) || parseInt(item.quantity) || parseInt(item.count) || 1;
                return sum + qty;
            }, 0);
        }
        // Fallback: check for total_items
        if (order.total_items) {
            return parseInt(order.total_items) || 0;
        }
        return 0;
    }

    // Open order details modal
    function openOrderDetailsModal(title, orders) {
        if (!orderDetailsModal) return;
        
        if (orderModalTitle) orderModalTitle.textContent = title;
        if (orderDetailsContent) {
            if (orders.length === 0) {
                orderDetailsContent.innerHTML = '<p style="text-align:center;color:var(--muted);padding:24px;">No orders available.</p>';
            } else {
                // Sort orders by newest first (descending by timestamp)
                const sortedOrders = [...orders].sort((a, b) => {
                    const timeA = a.timestamp || (a.date ? new Date(a.date).getTime() : 0) || 0;
                    const timeB = b.timestamp || (b.date ? new Date(b.date).getTime() : 0) || 0;
                    return timeB - timeA; // Descending order (newest first)
                });
                
                orderDetailsContent.innerHTML = `
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead>
                                <tr>
                                    <th style="padding:12px 10px;border:1px solid #f0f0f0;background:transparent;color:var(--muted);font-weight:700;font-size:12px;text-transform:uppercase;min-width:120px;width:120px;">Order ID</th>
                                    <th style="padding:12px 10px;border:1px solid #f0f0f0;background:transparent;color:var(--muted);font-weight:700;font-size:12px;text-transform:uppercase;">Customer Name</th>
                                    <th style="padding:12px 10px;border:1px solid #f0f0f0;background:transparent;color:var(--muted);font-weight:700;font-size:12px;text-transform:uppercase;">Customer Type</th>
                                    <th style="padding:12px 10px;border:1px solid #f0f0f0;background:transparent;color:var(--muted);font-weight:700;font-size:12px;text-transform:uppercase;">Items</th>
                                    <th style="padding:12px 10px;border:1px solid #f0f0f0;background:transparent;color:var(--muted);font-weight:700;font-size:12px;text-transform:uppercase;">Total Price</th>
                                    <th style="padding:12px 10px;border:1px solid #f0f0f0;background:transparent;color:var(--muted);font-weight:700;font-size:12px;text-transform:uppercase;">Status</th>
                                    <th style="padding:12px 10px;border:1px solid #f0f0f0;background:transparent;color:var(--muted);font-weight:700;font-size:12px;text-transform:uppercase;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${sortedOrders.map(order => {
                                    const itemCount = getOrderItemCount(order);
                                    return `
                                    <tr style="border-bottom:1px solid #f0f0f0;">
                                        <td style="padding:12px 10px;border:1px solid #f0f0f0;color:#2a2a2a;">${order.id || 'N/A'}</td>
                                        <td style="padding:12px 10px;border:1px solid #f0f0f0;color:#2a2a2a;">${order.customerName || 'Guest'}</td>
                                        <td style="padding:12px 10px;border:1px solid #f0f0f0;color:#2a2a2a;">${getCustomerType(order)}</td>
                                        <td style="padding:12px 10px;border:1px solid #f0f0f0;color:#2a2a2a;">${itemCount} item(s)</td>
                                        <td style="padding:12px 10px;border:1px solid #f0f0f0;color:#2a2a2a;">₱${Number(order.total || 0).toFixed(2)}</td>
                                        <td style="padding:12px 10px;border:1px solid #f0f0f0;color:#2a2a2a;">
                                          <span style="
                                            background: ${String(order.status||'').toLowerCase()==='completed' ? '#10b981' : (String(order.status||'').toLowerCase()==='cancelled' ? '#ef4444' : (String(order.status||'').toLowerCase().includes('delivery') || String(order.status||'').toLowerCase()==='out for delivery' ? '#6366f1' : '#f59e0b'))};
                                            color: #fff; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing:.2px; white-space: nowrap;">
                                            ${formatStatus(order.status || 'Pending')}
                                          </span>
                                        </td>
                                        <td style="padding:12px 10px;border:1px solid #f0f0f0;color:#2a2a2a;">${order.date || 'N/A'}</td>
                                    </tr>
                                `}).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }
        }
        
        orderDetailsModal.style.display = 'flex';
    }

    // Close order details modal
    function closeOrderDetailsModal() {
        if (orderDetailsModal) orderDetailsModal.style.display = 'none';
    }

    // Add click handlers for order cards
    if (smSjdmCard) {
        smSjdmCard.addEventListener('click', async () => {
            const todayOrders = await getTodayOrders();
            // Filter orders by SJDM branch
            const smSjdmOrders = todayOrders.filter(order => {
                const branch = (order.branch || '').toLowerCase();
                return branch.includes('sjdm') || branch.includes('san jose') || branch.includes('monte');
            });
            openOrderDetailsModal('Today\'s Orders - SM SJDM', smSjdmOrders);
        });
    }

    if (smFairviewCard) {
        smFairviewCard.addEventListener('click', async () => {
            const todayOrders = await getTodayOrders();
            // Filter orders by Fairview branch
            const smFairviewOrders = todayOrders.filter(order => {
                const branch = (order.branch || '').toLowerCase();
                return branch.includes('fairview');
            });
            openOrderDetailsModal('Today\'s Orders - SM Fairview', smFairviewOrders);
        });
    }

    if (totalOrdersCard) {
        totalOrdersCard.addEventListener('click', async () => {
            const todayOrders = await getTodayOrders();
            openOrderDetailsModal('Today\'s Total Orders', todayOrders);
        });
    }

    if (closeOrderModalBtn) {
        closeOrderModalBtn.addEventListener('click', closeOrderDetailsModal);
    }

    // Close modal when clicking outside
    if (orderDetailsModal) {
        orderDetailsModal.addEventListener('click', (e) => {
            if (e.target === orderDetailsModal) {
                closeOrderDetailsModal();
            }
        });
    }

    // --- SYNC MENU TO CUSTOMER DASHBOARD ---
    function syncMenuToCustomerDashboard() {
        const adminMenu = JSON.parse(localStorage.getItem(MENU_STORAGE_KEY) || "[]");

        const merged = DEFAULT_MENU_ITEMS.slice().map((item, idx) => Object.assign({}, item, { id: item.id || idx + 1 }));

        const byId = {};
        const byName = {};
        merged.forEach(it => {
            if (it.id) byId[it.id] = it;
            if (it.name) byName[(it.name || '').toLowerCase()] = it;
        });

        let maxId = merged.reduce((m, it) => Math.max(m, (it.id || 0)), 0);

        (Array.isArray(adminMenu) ? adminMenu : []).forEach(aItem => {
            const rawImage = aItem.image || aItem.img || DEFAULT_IMAGE;
            const finalImage = resolveImagePath(rawImage);
            const migratedRegular = (typeof aItem.priceRegular === 'number') ? aItem.priceRegular : (parseFloat(aItem.priceRegular) || parseFloat(aItem.priceSmall) || 0);
            const migratedTall = (typeof aItem.priceTall === 'number') ? aItem.priceTall : (parseFloat(aItem.priceTall) || parseFloat(aItem.priceMedium) || parseFloat(aItem.priceLarge) || migratedRegular);

            const normalized = {
                name: aItem.name || 'Unnamed',
                desc: aItem.description || aItem.desc || aItem.description || '',
                priceRegular: Number(migratedRegular || 0),
                priceTall: Number(migratedTall || migratedRegular || 0),
                img: finalImage,
                image: finalImage // Also store in image field for compatibility
            };

            if (aItem.id && byId[aItem.id]) {
                const existing = byId[aItem.id];
                Object.assign(existing, normalized);
            } else if (aItem.id) {
                normalized.id = aItem.id;
                merged.push(normalized);
                byId[normalized.id] = normalized;
                byName[(normalized.name||'').toLowerCase()] = normalized;
                maxId = Math.max(maxId, normalized.id || 0);
            } else if (aItem.name && byName[(aItem.name||'').toLowerCase()]) {
                const existing = byName[(aItem.name||'').toLowerCase()];
                Object.assign(existing, normalized);
            } else {
                maxId++;
                normalized.id = maxId;
                merged.push(normalized);
                byId[normalized.id] = normalized;
                byName[(normalized.name||'').toLowerCase()] = normalized;
            }
        });

        const finalForCustomer = merged.map(it => {
            if (!it.id) {
                maxId++;
                it.id = maxId;
            }
            return it;
        });

        localStorage.setItem(MENU_STORAGE_KEY, JSON.stringify(finalForCustomer));
        localStorage.setItem(CUSTOMER_MENU_KEY, JSON.stringify(finalForCustomer));

        showToast('success','Menu updated', `Menu updated and synced to customer dashboard. ${finalForCustomer.length} items available.`);
    }

    function updateMenuAndSync() {
        renderMainGrid();
        renderModalList();
        syncMenuToCustomerDashboard();
    }

    logoutBtn.addEventListener('click', () => {
        showPopup('warning', {
            title: 'Confirm Logout',
            message: 'Are you sure you want to log out?',
            actions: [
                { text: 'Cancel', type: 'secondary', handler: hidePopup },
                                 { text: 'Log Out', type: 'primary', handler: () => { localStorage.removeItem('isLoggedIn'); localStorage.removeItem('currentUser'); window.location.href = 'login_admin.php'; } }
            ]
        });
    });

    // --- NAVIGATION LOGIC ---
    function switchSection(sectionId) {
        sidebarLinks.forEach(link => {
            if (link.getAttribute('data-section') === sectionId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        contentSections.forEach(section => {
            if (section.id === sectionId) {
                section.style.display = 'block';
                section.classList.add('active');
            } else {
                section.style.display = 'none';
                section.classList.remove('active');
            }
        });

        const sectionTitles = {
            'dashboard': 'Dashboard',
            'menu-management': 'Menu Management',
            'profile': 'Profile Management',
            'sales-report': 'Sales Report'
        };
        if (pageTitle) {
            pageTitle.textContent = sectionTitles[sectionId];
        }

        // No auto-refresh; manual refresh only
        if (sectionId === 'menu-management') {
            renderMainGrid();
        } else if (sectionId === 'profile') {
            loadAdminProfile();
        }
    }

    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.getAttribute('data-section');
            if (sectionId) {
                switchSection(sectionId);
            }
        });
    });

    // --- DASHBOARD FUNCTIONALITY ---
    if (announcementForm) {
        announcementForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const title = document.getElementById('announcement-title').value;
            const content = document.getElementById('announcement-content').value;
            
            if (title && content) {
                const announcement = {
                    title,
                    content,
                    date: new Date().toLocaleDateString()
                };

                const announcements = JSON.parse(localStorage.getItem(ANNOUNCEMENTS_STORAGE_KEY) || '[]');
                announcements.unshift(announcement);
                localStorage.setItem(ANNOUNCEMENTS_STORAGE_KEY, JSON.stringify(announcements));

                renderAnnouncements();
                showToast('success','Success','Announcement posted successfully!');
                announcementForm.reset();
            } else {
                showToast('error','Missing fields','Please fill in both title and content.');
            }
        });
    }

    function renderAnnouncements() {
        if (!announcementsList) return;
        
        const announcements = JSON.parse(localStorage.getItem(ANNOUNCEMENTS_STORAGE_KEY) || '[]');
        announcementsList.innerHTML = '';
        
        if (announcements.length === 0) {
            announcementsList.innerHTML = '<p style="text-align:center;color:var(--muted);padding:24px;">No announcements yet.</p>';
        } else {
            announcementsList.innerHTML = '';
            announcements.forEach((announcement, index) => {
                const announcementEl = document.createElement('div');
                announcementEl.style.cssText = 'background:#fff;padding:16px;border-radius:10px;border:1px solid rgba(0,0,0,0.1);margin-bottom:12px;position:relative;';
                
                // Create content div
                const contentDiv = document.createElement('div');
                contentDiv.style.cssText = 'display:flex;justify-content:space-between;align-items:flex-start;gap:12px;';
                
                // Create text content
                const textDiv = document.createElement('div');
                textDiv.style.flex = '1';
                const titleEl = document.createElement('h4');
                titleEl.style.cssText = 'font-weight:700;font-size:16px;color:var(--green-700);margin:0 0 8px 0;';
                titleEl.textContent = announcement.title;
                const contentEl = document.createElement('p');
                contentEl.style.cssText = 'color:#2a2a2a;margin:0 0 8px 0;font-size:14px;';
                contentEl.textContent = announcement.content;
                const dateEl = document.createElement('p');
                dateEl.style.cssText = 'color:var(--muted);font-size:12px;margin:0;';
                dateEl.textContent = `Posted on: ${announcement.date}`;
                
                textDiv.appendChild(titleEl);
                textDiv.appendChild(contentEl);
                textDiv.appendChild(dateEl);
                
                // Create delete button
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'delete-announcement-btn';
                deleteBtn.setAttribute('data-index', index);
                deleteBtn.style.cssText = 'background:none;border:none;color:#ef4444;cursor:pointer;padding:8px;border-radius:4px;transition:all 0.2s;flex-shrink:0;';
                deleteBtn.title = 'Remove announcement';
                deleteBtn.innerHTML = '<i class="fa-solid fa-trash-can" style="font-size:18px;"></i>';
                deleteBtn.addEventListener('mouseenter', function() { this.style.background = '#fee2e2'; });
                deleteBtn.addEventListener('mouseleave', function() { this.style.background = 'none'; });
                deleteBtn.addEventListener('click', (e) => {
                    deleteAnnouncement(index);
                });
                
                contentDiv.appendChild(textDiv);
                contentDiv.appendChild(deleteBtn);
                announcementEl.appendChild(contentDiv);
                announcementsList.appendChild(announcementEl);
            });
        }
    }
    
    function deleteAnnouncement(index) {
        const announcements = JSON.parse(localStorage.getItem(ANNOUNCEMENTS_STORAGE_KEY) || '[]');
        
        if (index < 0 || index >= announcements.length) {
            showToast('error', 'Error', 'Announcement not found.');
            return;
        }
        
        const announcementToDelete = announcements[index];
        
        showPopup('warning', {
            title: 'Remove Announcement',
            message: `Are you sure you want to remove "${announcementToDelete.title}"? This will also remove it from the customer dashboard.`,
            actions: [
                { text: 'Cancel', type: 'secondary', handler: hidePopup },
                { text: 'Remove', type: 'primary', handler: () => {
                    announcements.splice(index, 1);
                    localStorage.setItem(ANNOUNCEMENTS_STORAGE_KEY, JSON.stringify(announcements));
                    renderAnnouncements();
                    showToast('success', 'Removed', 'Announcement removed successfully. The customer dashboard will be updated.');
                    hidePopup();
                } }
            ]
        });
    }

    // --- PROFILE MANAGEMENT FUNCTIONALITY ---
    function getCredentialsFor(target){
        if (target === 'cashier_fairview') {
            return JSON.parse(localStorage.getItem(CASHIER_FAIRVIEW_KEY) || '{"email":"cashierfairview@gmail.com","password":"cashierfairview"}');
        } else if (target === 'cashier_sjdm') {
            return JSON.parse(localStorage.getItem(CASHIER_SJDM_KEY) || '{"email":"cashiersjdm@gmail.com","password":"cashiersjdm"}');
        }
        return JSON.parse(localStorage.getItem(ADMIN_CREDENTIALS_KEY) || '{"email":"admin@gmail.com","password":"admin123"}');
    }

    function setCredentialsFor(target, creds){
        const data = { email: (creds.email||'').trim(), password: creds.password||'' };
        if (target === 'cashier_fairview') {
            localStorage.setItem(CASHIER_FAIRVIEW_KEY, JSON.stringify(data));
        } else if (target === 'cashier_sjdm') {
            localStorage.setItem(CASHIER_SJDM_KEY, JSON.stringify(data));
        } else {
            localStorage.setItem(ADMIN_CREDENTIALS_KEY, JSON.stringify(data));
        }
    }

    async function updateCredentialsInDatabase(target, email, password) {
        try {
            const response = await fetch('../../api/api/admin/update-credentials.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include', // Include cookies for session
                body: JSON.stringify({
                    target: target,
                    email: email || null,
                    password: password || null
                })
            });
            
            const responseText = await response.text();
            
            if (!response.ok) {
                console.error('API Error Response:', responseText);
                try {
                    const errorJson = JSON.parse(responseText);
                    return { success: false, message: errorJson.message || `Server error: ${response.status}` };
                } catch (e) {
                    return { success: false, message: `Server error: ${response.status} - ${responseText}` };
                }
            }
            
            try {
                const result = JSON.parse(responseText);
                console.log('Update credentials result:', result);
                
                // Handle the response structure from sendResponse (success, message, data)
                if (result.success !== undefined) {
                    return {
                        success: result.success,
                        message: result.message || 'Update completed',
                        data: result.data || {}
                    };
                }
                return result;
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError, 'Response text:', responseText);
                return { success: false, message: 'Invalid response from server: ' + responseText.substring(0, 100) };
            }
        } catch (error) {
            console.error('Error updating credentials:', error);
            return { success: false, message: 'Failed to update credentials in database: ' + error.message };
        }
    }

    function loadAdminProfile() {
        const currentEmailInput = document.getElementById('current-email');
        const target = (accountTargetSelect && accountTargetSelect.value) || 'admin';
        
        // Get credentials - this will use defaults if not set
        const creds = getCredentialsFor(target);
        
        // Display the credentials (defaults or user-updated values)
        if (currentEmailInput) currentEmailInput.value = creds.email || '';
        const currentPwdInput = document.getElementById('current-password');
        if (currentPwdInput) currentPwdInput.value = creds.password || '';
    }

    if (accountTargetSelect) {
        accountTargetSelect.addEventListener('change', loadAdminProfile);
    }

    if (changeEmailForm) {
        changeEmailForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const newEmail = document.getElementById('new-email').value.trim();
            const confirmEmail = document.getElementById('confirm-email').value.trim();
            
            if (!newEmail || !confirmEmail) {
                showToast('error','Missing fields','Please fill in all fields.');
                return;
            }
            
            if (newEmail !== confirmEmail) {
                showToast('error','Mismatch','Email addresses do not match.');
                return;
            }
            
            const target = (accountTargetSelect && accountTargetSelect.value) || 'admin';
            const existing = getCredentialsFor(target);
            
            // Update in localStorage first (always works)
            existing.email = newEmail;
            setCredentialsFor(target, existing);
            
            // Try to update in database (optional - won't fail if it doesn't work)
            try {
                const result = await updateCredentialsInDatabase(target, newEmail, null);
                if (result && result.success === true) {
                    showToast('success','Updated','Email updated successfully! (Saved to database)');
                } else {
                    showToast('success','Updated','Email updated successfully! (Saved locally)');
                }
            } catch (error) {
                // Database update failed, but localStorage update succeeded
                showToast('success','Updated','Email updated successfully! (Saved locally)');
            }
            
            changeEmailForm.reset();
            loadAdminProfile();
        });
    }

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                showToast('error','Missing fields','Please fill in all fields.');
                return;
            }
            
            const target = (accountTargetSelect && accountTargetSelect.value) || 'admin';
            const existing = getCredentialsFor(target);
            if ((existing.password || '') !== currentPassword) {
                showToast('error','Invalid','Current password is incorrect.');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showToast('error','Mismatch','New passwords do not match.');
                return;
            }
            
            if (newPassword.length < 6) {
                showToast('error','Too short','Password must be at least 6 characters.');
                return;
            }
            
            // Update in localStorage first (always works)
            existing.password = newPassword;
            setCredentialsFor(target, existing);
            
            // Try to update in database (optional - won't fail if it doesn't work)
            try {
                const result = await updateCredentialsInDatabase(target, null, newPassword);
                if (result && result.success === true) {
                    showToast('success','Updated','Password updated successfully! You can now login with the new password. (Saved to database)');
                } else {
                    showToast('success','Updated','Password updated successfully! You can now login with the new password. (Saved locally)');
                }
            } catch (error) {
                // Database update failed, but localStorage update succeeded
                showToast('success','Updated','Password updated successfully! You can now login with the new password. (Saved locally)');
            }
            
            changePasswordForm.reset();
            loadAdminProfile();
        });
    }

    // Toggle visibility for current password (admin)
    (function setupAdminPasswordToggle(){
        function bindToggle(btnId, inputId){
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            if (!btn || !input) return;
            btn.addEventListener('click', function(){
                const toType = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', toType);
                const icon = btn.querySelector('i');
                if (toType === 'password') { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
                else { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
            });
        }
        bindToggle('toggle-admin-current-password','current-password');
        bindToggle('toggle-admin-new-password','new-password');
        bindToggle('toggle-admin-confirm-password','confirm-password');
    })();


    // --- MENU MANAGEMENT FUNCTIONALITY ---
    const renderMainGrid = () => {
        if (!menuGrid) return;
        
        const stored = JSON.parse(localStorage.getItem(MENU_STORAGE_KEY) || '[]');
        const menuItems = (Array.isArray(stored) && stored.length > 0) ? stored : DEFAULT_MENU_ITEMS;
        menuGrid.innerHTML = '';
        
        if (menuItems.length === 0) {
            if (noMenuMessage) noMenuMessage.style.display = 'block';
        } else {
            if (noMenuMessage) noMenuMessage.style.display = 'none';
            menuItems.forEach(item => {
                const card = document.createElement('div');
                    const imageUrl = resolveImagePath(item.img || item.image || DEFAULT_IMAGE);
                
                card.className = 'menu-card';

                const priceRegular = parseFloat(item.priceRegular||item.priceSmall||0).toFixed(2);
                const priceTall = parseFloat(item.priceTall||item.priceMedium||item.priceLarge||item.priceSmall||0).toFixed(2);

                card.innerHTML = `
                    <div class="menu-card__image">
                        <img src="${imageUrl}" alt="${item.name}">
                    </div>
                    <div class="menu-card__body">
                        <h3 class="menu-card__title">${item.name}</h3>
                        <p class="menu-card__desc">${item.description}</p>
                        <div class="menu-card__prices">
                            <div class="price-row"><span class="label">Regular</span><span class="value">₱${priceRegular}</span></div>
                            <div class="price-row"><span class="label">Tall</span><span class="value">₱${priceTall}</span></div>
                        </div>
                    </div>
                `;
                menuGrid.appendChild(card);
            });
        }
    };

    const renderModalList = () => {
        if (!menuList) return;
        const stored = JSON.parse(localStorage.getItem(MENU_STORAGE_KEY) || '[]');
        const menuItems = (Array.isArray(stored) && stored.length > 0) ? stored : DEFAULT_MENU_ITEMS.slice();
        menuList.innerHTML = '';
        
        if (menuItems.length === 0) {
            menuList.innerHTML = '<p class="text-center text-primary-dark/80 pt-2 pb-1">No items have been added to the menu yet.</p>';
        } else {
            menuItems.forEach((item, index) => {
                const li = document.createElement('li');
                const imageUrl = item.img || item.image || DEFAULT_IMAGE;
                
                li.innerHTML = `
                    <div style="display:flex;align-items:center;flex:1;min-width:0;">
                        <img src="${imageUrl}" alt="${item.name}" style="width:40px;height:40px;object-fit:cover;border-radius:50%;margin-right:12px;border:1px solid var(--green-700);flex-shrink:0;">
                        <div style="flex:1;min-width:0;">
                            <h3 style="font-weight:700;font-size:14px;color:var(--green-700);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.name}</h3>
                            <p style="color:var(--muted);font-size:13px;margin:0;">₱${parseFloat(item.priceRegular||item.priceSmall||0).toFixed(2)}</p>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                        <button class="btn btn--primary small edit-item-btn" data-id="${item.id}">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                        <button class="btn btn--outline small remove-item-btn" style="background:#ef4444;color:#fff;border-color:#dc2626;" data-id="${item.id}">
                            <i class="fa-solid fa-trash-can"></i> Del
                        </button>
                    </div>
                `;
                menuList.appendChild(li);
            });
        }
    };

    const handleEdit = (id) => {
        const menuItems = JSON.parse(localStorage.getItem(MENU_STORAGE_KEY) || '[]');
        const item = menuItems.find(mi => Number(mi.id) === Number(id));
        const index = menuItems.findIndex(mi => Number(mi.id) === Number(id));

        modalTitle.textContent = "Edit Menu Item";
        addItemForm.style.display = 'block';
        menuContainer.style.display = 'none';

        itemNameInput.value = item.name;
        itemDescInput.value = item.description;
        document.getElementById('item-price-regular').value = item.priceRegular || item.priceSmall || '';
        document.getElementById('item-price-tall').value = item.priceTall || item.priceMedium || item.priceLarge || '';
        itemImageDataInput.value = item.image || item.img || ''; 
        itemImageInput.value = '';

        addItemForm.setAttribute('data-editing-id', id);
        saveItemBtn.innerHTML = '<i class="fa-solid fa-save mr-2"></i> Update Item';
        deleteItemOnEditBtn.style.display = 'block';
        deleteItemOnEditBtn.setAttribute('data-id', id);
        
        menuModal.style.display = 'flex';
    }

    const handleDelete = (id) => {
        const menuItems = JSON.parse(localStorage.getItem(MENU_STORAGE_KEY) || '[]');
        const index = menuItems.findIndex(mi => Number(mi.id) === Number(id));
        if (index < 0) return;

        const removedName = menuItems[index].name;

        showPopup('warning', {
            title: 'Delete Item',
            message: `Are you sure you want to delete the item: "${removedName}"?`,
            actions: [
                { text: 'Cancel', type: 'secondary', handler: hidePopup },
                { text: 'Delete', type: 'primary', handler: () => {
                    menuItems.splice(index, 1);
                    localStorage.setItem(MENU_STORAGE_KEY, JSON.stringify(menuItems));
                    closeMenuModal();
                    showToast('success','Removed', `"${removedName}" removed successfully.`);
                    updateMenuAndSync();
                } }
            ]
        });
    }

    const handleFormSubmit = (e) => {
        e.preventDefault();
        
        const editingId = addItemForm.getAttribute('data-editing-id');
        const isEditing = editingId && editingId !== '-1';
        
        const menuItems = JSON.parse(localStorage.getItem(MENU_STORAGE_KEY) || '[]');
        let existingImage = itemImageDataInput.value; 

        const finalizeSave = (newImageData = existingImage) => {
            const newItem = {
                name: itemNameInput.value.trim(),
                description: itemDescInput.value.trim(),
                priceRegular: parseFloat(document.getElementById('item-price-regular').value),
                priceTall: parseFloat(document.getElementById('item-price-tall').value),
                image: newImageData
            };

            if (newItem.priceRegular <= 0 || newItem.priceTall <= 0) {
                showToast('error','Invalid prices','Please enter positive prices for Regular and Tall');
                return;
            }
            
            if (isEditing) {
                showPopup('warning', {
                    title: 'Apply Changes',
                    message: 'Are you sure you want to apply changes?',
                    actions: [
                        { text: 'Cancel', type: 'secondary', handler: hidePopup },
                        { text: 'Apply', type: 'primary', handler: () => {
                            newItem.img = newItem.image || '';
                            const editIndex = menuItems.findIndex(mi => String(mi.id) === String(editingId));
                            if (editIndex === -1) {
                                showToast('error','Not found','Failed to locate the item to update. It may have been removed.');
                                return;
                            }
                            newItem.id = menuItems[editIndex].id || editingId;
                            menuItems[editIndex] = newItem;
                            localStorage.setItem(MENU_STORAGE_KEY, JSON.stringify(menuItems));
                            showToast('success','Updated', `"${newItem.name}" updated successfully!`);

                            addItemForm.setAttribute('data-editing-id', '-1');
                            closeMenuModal();
                            updateMenuAndSync();
                        } }
                    ]
                });
                return; 
            } else {    
              
                newItem.img = newItem.image || '';
                const existingIds = (menuItems || []).map(i => Number(i.id || 0)).filter(Boolean);
                const nextId = existingIds.length ? Math.max(...existingIds) + 1 : 1;
                newItem.id = nextId;
                menuItems.push(newItem);
                localStorage.setItem(MENU_STORAGE_KEY, JSON.stringify(menuItems));

                showToast('success','Added', `"${newItem.name}" added successfully!`);
                addItemForm.reset();
                itemImageDataInput.value = '';
                updateMenuAndSync();
            }
        };

        const file = itemImageInput.files[0];
        if (file) {
           
            if (file.size > 500 * 1024) {
                showToast('error','Image too large','Image file is too large. Please use an image under 500KB.');
                return;
            }
            const reader = new FileReader();
            reader.onloadend = () => {
                finalizeSave(reader.result);
            };
            reader.readAsDataURL(file);
        } else {
            finalizeSave();
        }
    };



    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => {
            modalTitle.textContent = "Add New Item";
            addItemForm.style.display = 'block';
            menuContainer.style.display = 'none';
                addItemForm.reset(); 
            addItemForm.setAttribute('data-editing-id', '-1');
            saveItemBtn.innerHTML = '<i class="fa-solid fa-save mr-2"></i> Save Item';
            deleteItemOnEditBtn.style.display = 'none';
            itemImageDataInput.value = '';
            openMenuModal();
        });
    }

    if (editMenuBtn) {
        editMenuBtn.addEventListener('click', () => {
            modalTitle.textContent = "Edit Current Menu";
            addItemForm.style.display = 'none';
            menuContainer.style.display = 'block';
            renderModalList();
            openMenuModal();
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            closeMenuModal();
            renderMainGrid();
        });
    }

    window.onclick = (event) => {
        if (event.target == menuModal) {
            menuModal.style.display = 'none';
            renderMainGrid(); 
        }
    };

    if (addItemForm) {
        addItemForm.addEventListener('submit', handleFormSubmit);
    }

    if (menuList) {
        menuList.addEventListener('click', (e) => {
            const deleteButton = e.target.closest('.remove-item-btn');
            const editButton = e.target.closest('.edit-item-btn');

            if (deleteButton) {
                const id = deleteButton.getAttribute('data-id');
                handleDelete(id);
            } else if (editButton) {
                const id = editButton.getAttribute('data-id');
                handleEdit(id);
            }
        });
    }

    // Event listener for the delete button on the Edit form
    if (deleteItemOnEditBtn) {
        deleteItemOnEditBtn.addEventListener('click', () => {
            const id = deleteItemOnEditBtn.getAttribute('data-id');
            handleDelete(id);
        });
    }

    // Initialize default menu - always reset to defaults
    function initializeDefaultMenu() {
        // Force reset to canonical defaults to replace any old incorrect menu items
        const seeded = DEFAULT_MENU_ITEMS.slice().map((it, idx) => Object.assign({ id: it.id || idx + 1 }, it));
        localStorage.setItem(MENU_STORAGE_KEY, JSON.stringify(seeded));
        // Also sync to customer menu
        localStorage.setItem(CUSTOMER_MENU_KEY, JSON.stringify(seeded));
        console.log('✅ Admin: Default menu initialized and synced to customer');
    }

    // Initial setup
    function initializeDefaultData() {
        initializeDefaultMenu();
        
        const accounts = JSON.parse(localStorage.getItem(ACCOUNTS_STORAGE_KEY) || '[]');
        if (accounts.length === 0) {
            accounts.push(
                { username: 'cashier1', password: 'password', role: 'Cashier' },
                { username: 'customer1', password: 'password', role: 'Customer' }
            );
            localStorage.setItem(ACCOUNTS_STORAGE_KEY, JSON.stringify(accounts));
        }
        // Seed default credentials if missing or if they contain old incorrect defaults
        const adminCreds = localStorage.getItem(ADMIN_CREDENTIALS_KEY);
        if (!adminCreds || adminCreds.includes('admin@jessiecane.com')) {
            localStorage.setItem(ADMIN_CREDENTIALS_KEY, JSON.stringify({ email: 'admin@gmail.com', password: 'admin123' }));
        }
        
        const fairviewCreds = localStorage.getItem(CASHIER_FAIRVIEW_KEY);
        if (!fairviewCreds || fairviewCreds.includes('cashier.fairview@jessiecane.com')) {
            localStorage.setItem(CASHIER_FAIRVIEW_KEY, JSON.stringify({ email: 'cashierfairview@gmail.com', password: 'cashierfairview' }));
        }
        
        const sjdmCreds = localStorage.getItem(CASHIER_SJDM_KEY);
        if (!sjdmCreds || sjdmCreds.includes('cashier.sjdm@jessiecane.com')) {
            localStorage.setItem(CASHIER_SJDM_KEY, JSON.stringify({ email: 'cashiersjdm@gmail.com', password: 'cashiersjdm' }));
        }
        
        const announcements = JSON.parse(localStorage.getItem(ANNOUNCEMENTS_STORAGE_KEY) || '[]');
        if (announcements.length === 0) {
            announcements.push(
                { 
                    title: 'Welcome to Jessie Cane!', 
                    content: 'We\'re excited to have you as part of our team. Please familiarize yourself with the system.',
                    date: new Date().toLocaleDateString()
                }
            );
            localStorage.setItem(ANNOUNCEMENTS_STORAGE_KEY, JSON.stringify(announcements));
        }
    }

    // Manual refresh for cashier dashboard iframe
    function manualRefreshCashierIframe() {
        const iframe = document.getElementById('cashier-dashboard-iframe');
        if (!iframe) return;
        try {
            const url = new URL(iframe.src, window.location.href);
            url.searchParams.set('t', Date.now());
            iframe.src = url.toString();
        } catch (e) {
            console.log('Iframe refresh note:', e);
        }
    }
    const refreshBtn = document.getElementById('refresh-cashier-dashboard-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function(){
            manualRefreshCashierIframe();
        });
    }
    
    // --- SALES REPORT TABS & RENDERING ---
    function setupSalesReportTabs(){
        const tabsContainer = document.getElementById('sales-tabs');
        if (!tabsContainer) return;
        const tabButtons = tabsContainer.querySelectorAll('.sales-tab');
        const panels = document.querySelectorAll('.sales-tab-panel');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', async () => {
                const target = btn.getAttribute('data-tab');
                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                panels.forEach(p => p.style.display = (p.id === target) ? 'block' : 'none');

                // Lazy render per tab (all are async now)
                try {
                    if (target === 'tab-daily') await renderDailySales();
                    if (target === 'tab-weekly') await renderWeekly();
                    if (target === 'tab-monthly') await renderMonthly();
                    if (target === 'tab-walkin') await renderWalkin();
                    if (target === 'tab-online') await renderOnline();
                    if (target === 'tab-best-sellers') await renderBestSellers();
                } catch (error) {
                    console.error('Error rendering tab:', error);
                }
            });
        });

        const refreshDaily = document.getElementById('refresh-daily-btn');
        if (refreshDaily) refreshDaily.addEventListener('click', () => renderDailySales());
        const refreshW = document.getElementById('refresh-weekly-btn');
        if (refreshW) refreshW.addEventListener('click', () => renderWeekly());
        const refreshM = document.getElementById('refresh-monthly-btn');
        if (refreshM) refreshM.addEventListener('click', () => renderMonthly());
        const refreshBest = document.getElementById('refresh-best-sellers-btn');
        if (refreshBest) refreshBest.addEventListener('click', () => renderBestSellers());
        const refreshWalkin = document.getElementById('refresh-walkin-btn');
        if (refreshWalkin) refreshWalkin.addEventListener('click', () => renderWalkin());
        const refreshOnline = document.getElementById('refresh-online-btn');
        if (refreshOnline) refreshOnline.addEventListener('click', () => renderOnline());

        // Default date for daily = today
        const dailyDateInput = document.getElementById('daily-date-input');
        if (dailyDateInput) {
            const tzOffset = (new Date()).getTimezoneOffset() * 60000;
            const todayISO = new Date(Date.now() - tzOffset).toISOString().slice(0,10);
            dailyDateInput.value = todayISO;
            dailyDateInput.addEventListener('change', () => renderDailySales());
            // Enhance with Flatpickr if available for a professional calendar UI
            if (window.flatpickr) {
                try {
                    window.flatpickr(dailyDateInput, {
                        dateFormat: 'Y-m-d',
                        defaultDate: todayISO,
                        allowInput: true,
                        onChange: function(){ renderDailySales().catch(console.error); },
                        disableMobile: true
                    });
                } catch(_) {}
            }
        }
    }

    // Fetch orders from database API
    async function fetchOrdersFromAPI(action = 'all', params = {}) {
        try {
            let url = `api_sales.php?action=${action}`;
            if (params.date) url += `&date=${params.date}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                return { orders: data.data || [], totals: data.totals || {} };
            }
            return { orders: [], totals: {} };
        } catch (e) {
            console.error('API fetch error:', e);
            return { orders: [], totals: {} };
        }
    }

    async function getAllOrders(){
        // Sync from database first, then return from localStorage
        try { 
            await syncOrdersFromDatabase();
            return JSON.parse(localStorage.getItem('jessie_orders') || '[]'); 
        } catch(_) { 
            // Fallback to localStorage only if sync fails
            try { return JSON.parse(localStorage.getItem('jessie_orders') || '[]'); } catch(_) { return []; }
        }
    }
    
    // Synchronous version for backwards compatibility (uses cached localStorage)
    function getAllOrdersSync(){
        try { return JSON.parse(localStorage.getItem('jessie_orders') || '[]'); } catch(_) { return []; }
    }
    function isCompleted(order){ try { return String(order && order.status || '').toLowerCase() === 'completed'; } catch(_) { return false; } }
    function notCompleted(order){ return !isCompleted(order); }
    function toPhp(n){ return '₱' + Number(n||0).toFixed(2); }
    function orderDate(order){
        try {
            const d = order.timestamp ? new Date(order.timestamp) : (order.date ? new Date(order.date) : null);
            return d;
        } catch(_) { return null; }
    }
    function sameDay(a,b){ return a && b && a.toDateString() === b.toDateString(); }
    function startOfWeek(d){ // Monday as start
        const dt = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const day = (dt.getDay() + 6) % 7; // 0..6 with Mon=0
        dt.setDate(dt.getDate() - day);
        dt.setHours(0,0,0,0);
        return dt;
    }
    function endOfWeek(d){ const s = startOfWeek(d); const e = new Date(s); e.setDate(s.getDate()+6); e.setHours(23,59,59,999); return e; }
    function startOfMonth(d){ const s = new Date(d.getFullYear(), d.getMonth(), 1); s.setHours(0,0,0,0); return s; }
    function endOfMonth(d){ const e = new Date(d.getFullYear(), d.getMonth()+1, 0); e.setHours(23,59,59,999); return e; }
    function inRange(date, start, end){ return date && date >= start && date <= end; }

    function normalizeBranch(value){
        try { return String(value || '').toLowerCase().replace(/\s+/g,' ').trim(); } catch(_) { return ''; }
    }
    function branchOf(order){ return normalizeBranch(order && order.branch); }
    function isSJDM(order){
        const b = branchOf(order);
        return b.includes('san jose del monte') || b.includes('sjdm');
    }
    function isFairview(order){
        const b = branchOf(order);
        return b.includes('fairview');
    }

    function renderOrdersTable(containerId, orders){
        const container = document.getElementById(containerId);
        if (!container) return;
        if (!orders || orders.length === 0){
            container.innerHTML = '<p class="muted" style="padding:12px;">No orders.</p>';
            return;
        }
        // Sort orders by newest first (descending by timestamp)
        const sortedOrders = [...orders].sort((a, b) => {
            const timeA = a.timestamp || (a.date ? new Date(a.date).getTime() : 0) || 0;
            const timeB = b.timestamp || (b.date ? new Date(b.date).getTime() : 0) || 0;
            return timeB - timeA; // Descending order (newest first)
        });
        
        const branchClass = /sjdm/i.test(containerId) ? 'branch-sjdm' : (/fairview/i.test(containerId) ? 'branch-fairview' : 'branch-consolidated');
        const rows = sortedOrders.map(o => {
            const itemsCount = (o.items||[]).reduce((s,it)=> s + (Number(it.qty)||1), 0);
            const d = orderDate(o);
            const dateStr = d ? d.toLocaleString() : (o.date || '');
            const customer = o.customerName || o.customerUsername || 'Guest';
            const ctype = getCustomerType(o);
            return `<tr>
                <td>${o.id||''}</td>
                <td>${customer}</td>
                <td>${ctype}</td>
                <td>${itemsCount} item(s)</td>
                <td>${toPhp(o.total||0)}</td>
                <td>${dateStr}</td>
            </tr>`;
        }).join('');
        container.innerHTML = `
            <div class="table-wrapper ${branchClass}">
              <table class="report-table">
                <thead>
                  <tr>
                    <th style="min-width:120px;width:120px;">Order ID</th>
                    <th>Customer</th>
                    <th>Customer Type</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>${rows}</tbody>
              </table>
            </div>`;
    }

    function getCustomerType(order){
        const t = String(order && order.orderType || '').toLowerCase();
        if (t === 'walk-in' || t === 'walkin') return 'Walk-in';
        return (order && order.isGuest) ? 'Guest' : 'Registered User';
    }
    
    // Format status text for better readability
    function formatStatus(status) {
        if (!status) return 'Pending';
        const statusLower = String(status).toLowerCase();
        if (statusLower === 'out for delivery' || statusLower === 'out-for-delivery') {
            return 'Delivering';
        }
        // Capitalize first letter of each word
        return String(status).split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ).join(' ');
    }

    function renderOrdersTableWithCustomerType(containerId, orders){
        const container = document.getElementById(containerId);
        if (!container) return;
        if (!orders || orders.length === 0){
            container.innerHTML = '<p class="muted" style="padding:12px;">No orders.</p>';
            return;
        }
        // Sort orders by newest first (descending by timestamp)
        const sortedOrders = [...orders].sort((a, b) => {
            const timeA = a.timestamp || (a.date ? new Date(a.date).getTime() : 0) || 0;
            const timeB = b.timestamp || (b.date ? new Date(b.date).getTime() : 0) || 0;
            return timeB - timeA; // Descending order (newest first)
        });
        
        const branchClass = /sjdm/i.test(containerId) ? 'branch-sjdm' : (/fairview/i.test(containerId) ? 'branch-fairview' : 'branch-consolidated');

        const showBranch = /consolidated/i.test(containerId);
        const branchName = (o) => {
            if (isSJDM(o)) return 'SM San Jose del Monte';
            if (isFairview(o)) return 'SM Fairview';
            return 'Other/Unknown';
        };

        const rows = sortedOrders.map(o => {
            const itemsCount = (o.items||[]).reduce((s,it)=> s + (Number(it.qty)||1), 0);
            const d = orderDate(o);
            const dateStr = d ? d.toLocaleString() : (o.date || '');
            const customer = o.customerName || o.customerUsername || 'Guest';
            const ctype = getCustomerType(o);
            return `<tr>
                <td>${o.id||''}</td>
                <td>${customer}</td>
                <td>${ctype}</td>
                ${showBranch ? `<td>${branchName(o)}</td>` : ''}
                <td>${itemsCount} item(s)</td>
                <td>${toPhp(o.total||0)}</td>
                <td>${dateStr}</td>
            </tr>`;
        }).join('');
        container.innerHTML = `
            <div class="table-wrapper ${branchClass}">
              <table class="report-table">
                <thead>
                  <tr>
                    <th style="min-width:120px;width:120px;">Order ID</th>
                    <th>Customer</th>
                    <th>Customer Type</th>
                    ${showBranch ? '<th>Branch</th>' : ''}
                    <th>Items</th>
                    <th>Total</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>${rows}</tbody>
              </table>
            </div>`;
    }

    function sumTotals(orders){ return orders.reduce((s,o)=> s + Number(o.total||0), 0); }
    
    // Sum totals for database orders
    function sumTotalsDB(orders){ return orders.reduce((s,o)=> s + Number(o.total_amount||0), 0); }
    
    // Render orders table from database data
    function renderOrdersTableFromDB(containerId, orders, showBranch = false){
        const container = document.getElementById(containerId);
        if (!container) return;
        if (!orders || orders.length === 0){
            container.innerHTML = '<p class="muted" style="padding:12px;">No orders found.</p>';
            return;
        }
        // Sort orders by newest first (descending by timestamp or order_date)
        const sortedOrders = [...orders].sort((a, b) => {
            const timeA = a.timestamp || (a.order_date ? new Date(a.order_date).getTime() : 0) || (a.created_at ? new Date(a.created_at).getTime() : 0) || 0;
            const timeB = b.timestamp || (b.order_date ? new Date(b.order_date).getTime() : 0) || (b.created_at ? new Date(b.created_at).getTime() : 0) || 0;
            return timeB - timeA; // Descending order (newest first)
        });
        
        const branchClass = /sjdm/i.test(containerId) ? 'branch-sjdm' : (/fairview/i.test(containerId) ? 'branch-fairview' : 'branch-consolidated');

        const rows = sortedOrders.map(o => {
            const itemsCount = parseInt(o.total_items) || 0;
            const d = o.order_date ? new Date(o.order_date) : null;
            const dateStr = d ? d.toLocaleString() : '';
            const customer = o.firstname && o.lastname ? `${o.firstname} ${o.lastname}` : (o.user_id ? 'Registered User' : 'Guest');
            const ctype = o.order_type === 'dine-in' ? 'Walk-in' : (o.user_id ? 'Registered User' : 'Guest');
            const branchName = o.branch_name || 'Unknown';
            
            return `<tr>
                <td>${o.order_number || o.order_id || ''}</td>
                <td>${customer}</td>
                <td>${ctype}</td>
                ${showBranch ? `<td>${branchName}</td>` : ''}
                <td>${itemsCount} item(s)</td>
                <td>${toPhp(o.total_amount||0)}</td>
                <td>${dateStr}</td>
            </tr>`;
        }).join('');
        
        container.innerHTML = `
            <div class="table-wrapper ${branchClass}">
              <table class="report-table">
                <thead>
                  <tr>
                    <th style="min-width:120px;width:120px;">Order ID</th>
                    <th>Customer</th>
                    <th>Type</th>
                    ${showBranch ? '<th>Branch</th>' : ''}
                    <th>Items</th>
                    <th>Total</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>${rows}</tbody>
              </table>
            </div>`;
    }

    async function renderDailySales(){
        try {
            const dailyDateInput = document.getElementById('daily-date-input');
            const selected = dailyDateInput && dailyDateInput.value
                ? new Date(dailyDateInput.value + 'T00:00:00')
                : new Date();

            const orders = (await getAllOrders()).filter(isCompleted);
            const todays = orders.filter(o => {
                const d = orderDate(o);
                return d && sameDay(d, selected);
            });

            const sjdm = todays.filter(isSJDM);
            const fair = todays.filter(isFairview);

            renderOrdersTable('daily-table-sjdm', sjdm);
            renderOrdersTable('daily-table-fairview', fair);
            renderOrdersTableWithCustomerType('daily-table-consolidated', todays);

            const tSJDM = document.getElementById('daily-total-sjdm');
            if (tSJDM) tSJDM.textContent = `Total: ${toPhp(sumTotals(sjdm))}`;
            const tFair = document.getElementById('daily-total-fairview');
            if (tFair) tFair.textContent = `Total: ${toPhp(sumTotals(fair))}`;
            const tCons = document.getElementById('daily-total-consolidated');
            if (tCons) tCons.textContent = `All Branches Total: ${toPhp(sumTotals(todays))}`;
        } catch(e){ console.error('renderDailySales error', e); }
    }

    async function renderWeekly(){
        try {
            const now = new Date();
            const wStart = startOfWeek(now), wEnd = endOfWeek(now);
            const orders = (await getAllOrders()).filter(isCompleted);

            const week = orders.filter(o => {
                const d = orderDate(o);
                return inRange(d, wStart, wEnd);
            });
            const weekSJDM = week.filter(isSJDM);
            const weekFair = week.filter(isFairview);

            renderOrdersTable('weekly-table-sjdm', weekSJDM);
            renderOrdersTable('weekly-table-fairview', weekFair);
            renderOrdersTableWithCustomerType('weekly-table-consolidated', week);

            const wSJ = document.getElementById('weekly-total-sjdm');
            if (wSJ) wSJ.textContent = `Total: ${toPhp(sumTotals(weekSJDM))}`;
            const wF = document.getElementById('weekly-total-fairview');
            if (wF) wF.textContent = `Total: ${toPhp(sumTotals(weekFair))}`;
            const wC = document.getElementById('weekly-total-consolidated');
            if (wC) wC.textContent = `All Branches Total: ${toPhp(sumTotals(week))}`;
        } catch(e){ console.error('renderWeekly error', e); }
    }

    async function renderMonthly(){
        try {
            const now = new Date();
            const mStart = startOfMonth(now), mEnd = endOfMonth(now);
            const orders = (await getAllOrders()).filter(isCompleted);

            const month = orders.filter(o => {
                const d = orderDate(o);
                return inRange(d, mStart, mEnd);
            });
            const monthSJDM = month.filter(isSJDM);
            const monthFair = month.filter(isFairview);

            renderOrdersTable('monthly-table-sjdm', monthSJDM);
            renderOrdersTable('monthly-table-fairview', monthFair);
            renderOrdersTableWithCustomerType('monthly-table-consolidated', month);

            const mSJ = document.getElementById('monthly-total-sjdm');
            if (mSJ) mSJ.textContent = `Total: ${toPhp(sumTotals(monthSJDM))}`;
            const mF = document.getElementById('monthly-total-fairview');
            if (mF) mF.textContent = `Total: ${toPhp(sumTotals(monthFair))}`;
            const mC = document.getElementById('monthly-total-consolidated');
            if (mC) mC.textContent = `All Branches Total: ${toPhp(sumTotals(month))}`;
        } catch(e){ console.error('renderMonthly error', e); }
    }

    async function renderBestSellers(){
        try {
            const orders = (await getAllOrders()).filter(isCompleted);
            const countsByDrink = {};

            orders.forEach(o => {
                (o.items||[]).forEach(it => {
                    const rawName = String(it.name||'').trim();
                    if (!rawName) return;
                    const key = rawName.toLowerCase();
                    const qty = Number(it.qty)||1;
                    if (!countsByDrink[key]) countsByDrink[key] = { label: rawName, qty: 0 };
                    countsByDrink[key].qty += qty;
                });
            });

            const ranked = Object.values(countsByDrink).sort((a,b)=> b.qty - a.qty).slice(0,10);
            const tableContainer = document.getElementById('best-sellers-table');
            if (tableContainer){
                if (ranked.length === 0) {
                    tableContainer.innerHTML = '<p class="muted" style="padding:12px;">No orders.</p>';
                } else {
                    tableContainer.innerHTML = `
                        <div class="table-wrapper">
                          <table class="report-table">
                            <thead><tr><th>#</th><th>Drink</th><th>Qty Sold</th></tr></thead>
                            <tbody>
                              ${ranked.map((r,idx)=> `<tr><td>${idx+1}</td><td>${r.label}</td><td>${r.qty}</td></tr>`).join('')}
                            </tbody>
                          </table>
                        </div>`;
                }
            }
            const canvas = document.getElementById('best-sellers-chart');
            if (canvas && canvas.getContext){
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0,0,canvas.width, canvas.height);
                const w = canvas.width, h = canvas.height;
                const total = ranked.reduce((s,r)=> s + r.qty, 0) || 1;
                let startAngle = -Math.PI/2;
                // Flavor-based color mapping for clearer distinction
                function colorForDrink(name){
                    const n = String(name||'').toLowerCase();
                    if (/lemon(?!\s*yakult)/.test(n)) return '#F6C000'; // Lemon = bright yellow
                    if (/lemon\s*yakult/.test(n)) return '#FFD166';       // Lemon Yakult = soft amber
                    if (/calamansi\s*yakult/.test(n)) return '#A3E635';   // Calamansi Yakult = lime yellow-green
                    if (/calamansi/.test(n)) return '#86EFAC';             // Calamansi = light lime
                    if (/yakult/.test(n)) return '#FF9F68';                 // Yakult = peach
                    if (/orange/.test(n)) return '#FB923C';                 // Orange = orange
                    if (/dragon\s*fruit/.test(n)) return '#C026D3';         // Dragon Fruit = magenta purple
                    if (/lychee/.test(n)) return '#F472B6';                  // Lychee = pink
                    if (/watermelon/.test(n)) return '#EF4444';              // Watermelon = red
                    if (/passion/.test(n)) return '#8B5CF6';                 // Passion Fruit = violet
                    if (/pure\s*sugarcane|sugarcane/.test(n)) return '#16A34A'; // Pure cane = green
                    if (/strawberry/.test(n)) return '#EC4899';             // Strawberry = pink
                    return '';
                }
                const fallbackPalette = ['#0EA5E9','#22C55E','#F59E0B','#8B5CF6','#EF4444','#14B8A6','#6366F1','#F97316','#10B981','#E11D48'];

                const cx = w/2, cy = h/2, radius = Math.min(w,h)/2 - 10;
                const slices = [];
                ranked.forEach((r,i)=>{
                    const val = r.qty;
                    const sliceRad = (val/total) * Math.PI * 2;
                    const endAngle = startAngle + sliceRad;
                    ctx.beginPath();
                    ctx.moveTo(cx,cy);
                    ctx.arc(cx,cy, radius, startAngle, endAngle);
                    ctx.closePath();
                    const mapped = colorForDrink(r.label);
                    const fill = mapped || fallbackPalette[i % fallbackPalette.length];
                    ctx.fillStyle = fill;
                    ctx.fill();
                    slices.push({start:startAngle,end:endAngle,color:fill,label:r.label,qty:val,percent:((val/total)*100)});
                    startAngle = endAngle;
                });

                // Build legend below the chart (not overlaying)
                const legend = document.getElementById('best-sellers-legend');
                if (legend) {
                    legend.innerHTML = slices.map(s => `<span style="display:inline-flex;align-items:center;gap:6px;margin-right:10px;"><span style="width:10px;height:10px;background:${s.color};display:inline-block;border-radius:2px;"></span>${s.label} (${s.qty})</span>`).join('');
                }

                // Hover tooltip
                let tooltip = document.getElementById('best-sellers-tooltip');
                if (!tooltip) {
                    tooltip = document.createElement('div');
                    tooltip.id = 'best-sellers-tooltip';
                    tooltip.style.position = 'absolute';
                    tooltip.style.pointerEvents = 'none';
                    tooltip.style.background = 'rgba(17,24,39,0.9)';
                    tooltip.style.color = '#fff';
                    tooltip.style.padding = '6px 8px';
                    tooltip.style.borderRadius = '6px';
                    tooltip.style.fontSize = '12px';
                    tooltip.style.display = 'none';
                    canvas.parentElement.style.position = 'relative';
                    canvas.parentElement.appendChild(tooltip);
                }

                function showTooltip(x,y,text){
                    tooltip.textContent = text;
                    tooltip.style.left = (x + 10) + 'px';
                    tooltip.style.top = (y + 10) + 'px';
                    tooltip.style.display = 'block';
                }
                function hideTooltip(){ tooltip.style.display = 'none'; }

                // Remove previous listeners if any
                if (canvas._hoverHandler) canvas.removeEventListener('mousemove', canvas._hoverHandler);
                if (canvas._leaveHandler) canvas.removeEventListener('mouseleave', canvas._leaveHandler);

                canvas._hoverHandler = function(e){
                    const rect = canvas.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const dx = x - cx, dy = y - cy;
                    const dist = Math.sqrt(dx*dx + dy*dy);
                    if (dist > radius) { hideTooltip(); return; }
                    let ang = Math.atan2(dy, dx);
                    if (ang < -Math.PI/2) ang += Math.PI*2; // normalize to match start
                    // Convert to same basis as startAngle (-PI/2)
                    // Map angle to [ -PI/2, 3PI/2 )
                    // Find slice containing angle
                    const found = slices.find(s => ang >= s.start && ang < s.end);
                    if (found){
                        showTooltip(x,y, `${found.label}: ${found.qty} (${found.percent.toFixed(1)}%)`);
                    } else { hideTooltip(); }
                };
                canvas._leaveHandler = function(){ hideTooltip(); };
                canvas.addEventListener('mousemove', canvas._hoverHandler);
                canvas.addEventListener('mouseleave', canvas._leaveHandler);
            }
        } catch(e){ console.error('renderBestSellers error', e); }
    }

    async function renderWalkin(){
        try {
            const orders = (await getAllOrders()).filter(o => {
                const t = String(o.orderType||'').toLowerCase();
                return (t === 'walk-in' || t === 'walkin' || t === 'manual' || t.includes('walk')) && isCompleted(o);
            });
            const containerId = 'walkin-table';
            const container = document.getElementById(containerId);
            if (!container) return;
            if (orders.length === 0){ container.innerHTML = '<p class="muted" style="padding:12px;">No walk-in orders.</p>'; return; }
            const rows = orders.map(o => {
                const items = (o.items||[]);
                const itemsCount = items.reduce((s,it)=> s + (Number(it.qty)||1), 0);
                const drinks = items.map(it=> it.name).join(', ');
                const sizes = items.map(it=> it.size).join(', ');
                return `<tr>
                    <td>${o.id||''}</td>
                    <td>${o.customerName||'Walk-in Customer'}</td>
                    <td>${drinks}</td>
                    <td>${sizes}</td>
                    <td>${itemsCount}</td>
                    <td>${toPhp(o.total||0)}</td>
                </tr>`;
            }).join('');
            container.innerHTML = `
              <div class="table-wrapper">
                <table class="report-table">
                  <thead><tr><th style=\"min-width:120px;width:120px;\">Order ID</th><th>Customer</th><th>Drinks</th><th>Size</th><th>Items</th><th>Total per order</th></tr></thead>
                  <tbody>${rows}</tbody>
                </table>
              </div>`;
        } catch(e){ console.error('renderWalkin error', e); }
    }

    async function renderOnline(){
        try {
            const orders = (await getAllOrders()).filter(o => {
                const t = String(o.orderType||'').toLowerCase();
                return (t === 'digital' || t === 'online') && isCompleted(o);
            });
            const container = document.getElementById('online-table');
            if (!container) return;
            if (orders.length === 0){ container.innerHTML = '<p class="muted" style="padding:12px;">No online orders.</p>'; return; }
            const rows = orders.map(o => {
                const items = (o.items||[]);
                const itemsCount = items.reduce((s,it)=> s + (Number(it.qty)||1), 0);
                const drinks = items.map(it=> it.name).join(', ');
                const sizes = items.map(it=> it.size).join(', ');
                const ctype = o.isGuest ? 'Guest' : 'Registered User';
                const customer = o.customerName || o.customerUsername || 'Guest';
                return `<tr>
                    <td>${o.id||''}</td>
                    <td>${customer}</td>
                    <td>${ctype}</td>
                    <td>${drinks}</td>
                    <td>${sizes}</td>
                    <td>${itemsCount}</td>
                    <td>${toPhp(o.total||0)}</td>
                </tr>`;
            }).join('');
            container.innerHTML = `
              <div class="table-wrapper">
                <table class="report-table">
                  <thead><tr><th style=\"min-width:120px;width:120px;\">Order ID</th><th>Customer</th><th>Customer Type</th><th>Drinks</th><th>Size</th><th>Items</th><th>Total per order</th></tr></thead>
                  <tbody>${rows}</tbody>
                </table>
              </div>`;
        } catch(e){ console.error('renderOnline error', e); }
    }

    // Initialize Sales tabs after DOM is ready
    setupSalesReportTabs();

    initializeDefaultData();
    renderAnnouncements();
    renderMainGrid();
    
    // Update order stats
    updateOrderStats();
    
    // Optional: update order stats periodically (kept lightweight)
    // setInterval(updateOrderStats, 10000);
    
    switchSection('dashboard');
    // Note: Inventory logs are visible inside the embedded cashier dashboard in the Sales Report (Consolidated) tab

}
