/* PURPOSE: Cashier dashboard scripts — manage orders, view inventory, and handle branch operations */

// Utility: detect admin view via query param
function isAdminView() {
    try {
        const params = new URLSearchParams(window.location.search);
        return params.get('adminView') === '1' || params.get('adminView') === 'true';
    } catch (_) {
        return false;
    }
}

// Check authentication
function checkCashierAuth() {
    // Allow read-only embed for admin without cashier login
    if (isAdminView()) return true;
    
    // Check if we're already on the cashier dashboard - if so, allow staying on page
    const currentPath = window.location.pathname;
    const isOnCashierPage = currentPath.includes('cashier.php') || currentPath.includes('manual-order.php');
    
    // If already on cashier dashboard page, allow access (don't redirect on refresh)
    if (isOnCashierPage) {
        // Try to restore login state if missing but user is on dashboard
        const isLoggedIn = localStorage.getItem('isLoggedIn');
        const currentUser = localStorage.getItem('currentUser');
        
        if (isLoggedIn === 'true' && currentUser) {
            try {
                const user = JSON.parse(currentUser);
                if (user && user.role === 'cashier') {
                    return true; // Valid cashier, stay on dashboard
                }
            } catch (e) {
                // If parsing fails but we're on dashboard, allow staying
                console.warn('Could not parse user data, but staying on cashier dashboard');
                return true;
            }
        }
        
        // Even if login state is missing, if we're already on dashboard, stay here
        // This prevents redirect loops on refresh
        console.log('On cashier dashboard - allowing access even if login state unclear');
        return true;
    }
    
    // For other pages, check authentication normally
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const currentUser = localStorage.getItem('currentUser');
    
    if (!isLoggedIn || !currentUser) {
        window.location.href = '../cashier/login_cashier.php';
        return false;
    }
    
    try {
        const user = JSON.parse(currentUser);
        if (!user || user.role !== 'cashier') {
            window.location.href = '../cashier/login_cashier.php';
            return false;
        }
        return true;
    } catch (e) {
        window.location.href = '../cashier/login_cashier.php';
        return false;
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    if (!checkCashierAuth()) return;
    // Tag body when embedded in admin to enable wide layout overrides via CSS
    try { if (isAdminView()) { document.body.classList.add('admin-embed'); } } catch(_) {}
    initializeCashierDashboard();
});

// Get cashier branch from current user
function getCashierBranch() {
    // This is the SJDM portal: always scope to SJDM regardless of any lingering user state
    return 'SM San Jose del Monte';
}

// Normalize and match branches robustly (handles variants like "SJDM")
function branchesMatch(orderBranch, cashierBranch) {
    try {
        const a = String(orderBranch || '').toLowerCase().trim();
        const b = String(cashierBranch || '').toLowerCase().trim();
        if (!a || !b) return false;
        if (a === b) return true;
        // Common aliases
        const isA_SJDM = a.includes('san jose del monte') || a.includes('sjdm');
        const isB_SJDM = b.includes('san jose del monte') || b.includes('sjdm');
        if (isA_SJDM && isB_SJDM) return true;
        const isA_Fairview = a.includes('fairview');
        const isB_Fairview = b.includes('fairview');
        if (isA_Fairview && isB_Fairview) return true;
        return false;
    } catch (_) { return false; }
}

function initializeCashierDashboard() {
    console.log('Cashier dashboard initializing...');
    
    // Get cashier branch (admin view aggregates all branches)
    const cashierBranch = getCashierBranch();
    console.log('Cashier branch:', cashierBranch);
    
    // Get DOM elements
    const logoutBtn = document.getElementById('logoutBtn');
    const navLinks = document.querySelectorAll('.nav a');
    const ordersTbody = document.getElementById('ordersTbody');
    const clearOrdersBtn = document.getElementById('clearOrdersBtn');
    const noOrdersRow = document.getElementById('no-orders');
    const cashierName = document.getElementById('cashier-name');
    
    // Set cashier name and branch display
    try {
        const adminView = isAdminView();
        const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
        if (adminView) {
            cashierName.textContent = 'Admin View';
            // Hide sidebar navigation entirely in admin embed view
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) sidebar.style.display = 'none';
            // Expand main area to full width if sidebar is hidden
            const main = document.querySelector('.main');
            if (main) main.style.marginLeft = '0';
        } else if (currentUser.name || currentUser.username) {
            cashierName.textContent = currentUser.name || currentUser.username;
        }
        // Update branch name display in sidebar
        const branchNameDisplay = document.getElementById('branch-name-display');
        if (branchNameDisplay) {
            branchNameDisplay.textContent = adminView ? 'All Branches' : (cashierBranch || 'SM San Jose del Monte');
        }
        // Update page title to show branch
        const pageTitle = document.querySelector('.page-title h2');
        if (pageTitle) {
            pageTitle.textContent = adminView
                ? 'Jessie Cane Juicebar - Admin (All Branches)'
                : `Jessie Cane Juicebar - ${cashierBranch} Dashboard`;
        }
    } catch (e) {
        console.error('Error getting cashier name:', e);
    }
    
    // Navigation handling
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = this.getAttribute('data-target');
            const href = this.getAttribute('href');
            
            // Check if this is a manual order link or other external page - allow navigation
            if (href && (href.includes('manual-order.php') || href.includes('manual-order.html') || href.includes('../index.php'))) {
                // Allow navigation to manual order page or back to portal
                // Don't prevent default, let the browser handle the navigation
                return true;
            }
            
            if (target) {
                // In-page navigation - prevent default and handle via JavaScript
                e.preventDefault();
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
                // Handle navigation
                handleNavigation(target);
            } else if (href && href.startsWith('#')) {
                // Hash-based navigation - allow default behavior
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
                // Allow default navigation
            } else if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                // External links or other pages - allow default navigation
                return true;
            }
            // If href is a full path (like manual-order.html), allow default link behavior
            // No preventDefault means the link will navigate normally
        });
    });
    
    // Logout handler (hide in admin view)
    if (logoutBtn) {
        if (isAdminView()) {
            logoutBtn.style.display = 'none';
        } else {
            logoutBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to log out?')) {
                    localStorage.removeItem('isLoggedIn');
                    localStorage.removeItem('currentUser');
                    window.location.href = '../cashier/login_cashier.php';
                }
            });
        }
    }
    
    // Clear orders handler
    if (clearOrdersBtn) {
        if (isAdminView()) {
            // Enable for admin embedded view and clear ALL orders across branches
            clearOrdersBtn.disabled = false;
            clearOrdersBtn.style.opacity = '';
            clearOrdersBtn.style.cursor = '';
            clearOrdersBtn.title = 'Clear all orders across branches';
            clearOrdersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showConfirmModal({
                    title: 'Clear All Orders',
                    message: 'This will permanently remove ALL orders across ALL branches. This action cannot be undone.',
                    confirmText: 'Yes, Clear All',
                    cancelText: 'Cancel',
                    onConfirm: async function(){
                        try {
                            localStorage.setItem('jessie_orders', JSON.stringify([]));
                            await renderOrders();
                            renderCustomers();
                            updateSalesTotals();
                            showToastBanner('success', 'All orders cleared successfully.');
                        } catch (err) {
                            console.error('Error clearing orders:', err);
                            showToastBanner('error', 'Error clearing orders.');
                        }
                    }
                });
            });
        } else {
            // Disabled for cashier runtime
            clearOrdersBtn.disabled = true;
            clearOrdersBtn.style.opacity = '0.5';
            clearOrdersBtn.style.cursor = 'not-allowed';
            // Remove any existing click handler
            clearOrdersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                alert('Clearing orders is disabled for cashiers. Please contact an administrator if you need to clear orders.');
            });
        }
    }
    
    // Ingredients inventory form handler (disabled in admin view)
    const ingredientsInventoryForm = document.getElementById('ingredientsInventoryForm');
    if (ingredientsInventoryForm) {
        if (isAdminView()) {
            ingredientsInventoryForm.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
            ingredientsInventoryForm.addEventListener('submit', e => e.preventDefault());
        } else {
            ingredientsInventoryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const ingredientInput = document.getElementById('ingredientInput');
                const ingredientStatus = document.getElementById('ingredientStatus');
                const itemName = ingredientInput.value.trim();
                const status = ingredientStatus.value;
                if (itemName) {
                    addInventoryItem(itemName, status, cashierBranch);
                    ingredientInput.value = '';
                    renderInventory();
                }
            });
        }
    }

    // Items inventory form handler (disabled in admin view)
    const itemsInventoryForm = document.getElementById('itemsInventoryForm');
    if (itemsInventoryForm) {
        if (isAdminView()) {
            itemsInventoryForm.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
            itemsInventoryForm.addEventListener('submit', e => e.preventDefault());
        } else {
            itemsInventoryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const itemInput = document.getElementById('itemInput');
                const itemStatus = document.getElementById('itemStatus');
                const itemName = itemInput.value.trim();
                const status = itemStatus.value;
                if (itemName) {
                    addInventoryItem(itemName, status, cashierBranch);
                    itemInput.value = '';
                    renderInventory();
                }
            });
        }
    }
    
    // Initial render
    renderOrders(); // async function, but we don't await it here to avoid blocking
    renderCustomers();
    renderInventory();
    updateSalesTotals();
    
    // Auto-refresh disabled in admin embed view to avoid interrupting browsing
    if (!isAdminView()) {
        setInterval(async () => {
            await renderOrders();
            renderCustomers();
            renderInventory();
            updateSalesTotals();
        }, 3000); // Refresh every 3 seconds (cashier portal only)
    }
    
    // Setup modal close handlers
    const closeBtn = document.getElementById('closeReportModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeDetailedReport);
    }
    
    // Close modal when clicking outside
    const modal = document.getElementById('detailedReportModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeDetailedReport();
            }
        });
    }
    
    // Setup inventory report modal close handlers
    const closeInventoryBtn = document.getElementById('closeInventoryReportModal');
    if (closeInventoryBtn) {
        closeInventoryBtn.addEventListener('click', closeInventoryReport);
    }
    
    // Close inventory modal when clicking outside
    const inventoryModal = document.getElementById('inventoryReportModal');
    if (inventoryModal) {
        inventoryModal.addEventListener('click', function(e) {
            if (e.target === inventoryModal) {
                closeInventoryReport();
            }
        });
    }
}

function handleNavigation(target) {
    console.log('Navigating to:', target);
    
    // Find the target element by ID
    const targetElement = document.getElementById(target);
    if (targetElement) {
        // Smooth scroll to the target element
        targetElement.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
        
        // Update URL hash without triggering scroll again
        window.history.pushState(null, '', `#${target}`);
    }
}

// Calculate and update sales totals (only for cashier's branch)
function updateSalesTotals() {
    try {
        const adminView = isAdminView();
        const cashierBranch = getCashierBranch();
        const allOrders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
        
        // Filter orders by branch (admin view aggregates all)
        const branchOrders = adminView ? allOrders : allOrders.filter(order => branchesMatch(order.branch, cashierBranch));
        
        const today = new Date().toDateString();
        
        // Filter today's orders for this branch
        const todayOrders = branchOrders.filter(order => {
            // Handle both timestamp (number) and date (string) formats
            let orderDate;
            if (order.timestamp) {
                orderDate = new Date(order.timestamp).toDateString();
            } else if (order.date) {
                orderDate = new Date(order.date).toDateString();
            } else {
                return false;
            }
            return orderDate === today;
        });
        
        // Calculate total sales for today - COMPLETED orders only
        let totalSales = 0;
        todayOrders.forEach(order => {
            const orderStatus = (order.status || '').toLowerCase();
            const orderTotal = parseFloat(order.total || 0);
            if (orderStatus === 'completed') {
                totalSales += orderTotal;
            }
        });
        
    // Count orders (branch-specific unless admin)
    const totalOrders = branchOrders.length;
    const pendingOrders = branchOrders.filter(o => 
            o.status === 'Pending' || o.status === 'pending'
        ).length;
    const approvedOrders = branchOrders.filter(o => 
            o.status === 'Approved' || o.status === 'approved'
        ).length;
    const ofdOrders = branchOrders.filter(o => 
            o.status === 'Out for Delivery' || o.status === 'out for delivery'
        ).length;
    const completedOrders = branchOrders.filter(o => 
            o.status === 'Completed' || o.status === 'completed'
        ).length;
    const cancelledOrders = branchOrders.filter(o => 
            o.status === 'Cancelled' || o.status === 'cancelled'
        ).length;
        
        // Update UI
        const totalSalesEl = document.getElementById('total-sales');
        const totalOrdersEl = document.getElementById('total-orders');
        const pendingOrdersEl = document.getElementById('pending-orders');
    const approvedOrdersEl = document.getElementById('approved-orders');
    const ofdOrdersEl = document.getElementById('ofd-orders');
    const completedOrdersEl = document.getElementById('completed-orders');
    const cancelledOrdersEl = document.getElementById('cancelled-orders');
        
        if (totalSalesEl) totalSalesEl.textContent = '₱' + totalSales.toFixed(2);
        if (totalOrdersEl) totalOrdersEl.textContent = totalOrders;
        if (pendingOrdersEl) pendingOrdersEl.textContent = pendingOrders;
    if (approvedOrdersEl) approvedOrdersEl.textContent = approvedOrders;
    if (ofdOrdersEl) ofdOrdersEl.textContent = ofdOrders;
    if (completedOrdersEl) completedOrdersEl.textContent = completedOrders;
    if (cancelledOrdersEl) cancelledOrdersEl.textContent = cancelledOrders;
    } catch (error) {
        console.error('Error updating sales totals:', error);
    }
}

// Sync orders from database to localStorage
async function syncOrdersFromDatabase() {
    try {
        // Try to fetch from the API endpoint
        if (typeof OrdersAPI !== 'undefined' && OrdersAPI && typeof OrdersAPI.getAll === 'function') {
            const cashierBranch = getCashierBranch();
            const adminView = isAdminView();
            
            // Build filters for API call
            const filters = {};
            if (!adminView && cashierBranch) {
                filters.branch = cashierBranch;
            }
            
            const result = await OrdersAPI.getAll(filters);
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
                
                // Replace localStorage with database orders (database is source of truth)
                // This ensures deleted orders are removed from localStorage
                const orderMap = new Map();
                
                // Add database orders (database is the source of truth)
                convertedOrders.forEach(order => {
                    const key = order.order_db_id || order.id;
                    if (key) orderMap.set(String(key), order);
                });
                
                // Save database orders to localStorage (removes deleted orders)
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

// Render orders table (filtered by branch)
async function renderOrders() {
    const ordersTbody = document.getElementById('ordersTbody');
    const noOrdersRow = document.getElementById('no-orders');
    const adminView = isAdminView();
    const cashierBranch = getCashierBranch();
    
    if (!ordersTbody) return;
    
    try {
        // Sync orders from database first
        const allOrders = await syncOrdersFromDatabase();
        
        // Filter orders by cashier's branch (admin view shows all)
        const branchOrders = adminView ? allOrders : allOrders.filter(order => branchesMatch(order.branch, cashierBranch));
        console.log(adminView
            ? `Admin view: showing all orders (${branchOrders.length}/${allOrders.length})`
            : `Orders for ${cashierBranch}: ${branchOrders.length} out of ${allOrders.length} total orders`);
        
        if (branchOrders.length === 0) {
            ordersTbody.innerHTML = '';
            if (noOrdersRow) {
                noOrdersRow.style.display = '';
                noOrdersRow.textContent = adminView ? 'No orders available.' : `No orders available for ${cashierBranch}.`;
            }
            return;
        }
        
        if (noOrdersRow) noOrdersRow.style.display = 'none';
        
        // Sort orders by timestamp or date (newest first)
        const sortedOrders = branchOrders.sort((a, b) => {
            const timeA = a.timestamp || (a.date ? new Date(a.date + ' ' + (a.time || '')).getTime() : 0) || 0;
            const timeB = b.timestamp || (b.date ? new Date(b.date + ' ' + (b.time || '')).getTime() : 0) || 0;
            return timeB - timeA;
        });
        
        ordersTbody.innerHTML = sortedOrders.map((order, index) => {
            const items = order.items || [];
            
            // Build detailed items display
            let itemsDisplay = '';
            if (items.length === 0) {
                itemsDisplay = 'N/A';
            } else {
                itemsDisplay = items.map((item, idx) => {
                    const qty = item.qty || 1;
                    const price = parseFloat(item.price || 0);
                    const itemTotal = qty * price;
                    return `${qty}x ${item.name} (${item.size || 'Regular'})${item.special && item.special !== 'None' ? ' - ' + item.special : ''} - ₱${itemTotal.toFixed(2)}`;
                }).join('<br>');
            }
            
            const sizes = items.map(item => `${item.qty || 1}x ${item.size || 'N/A'}`).join(', ') || 'N/A';
            const specialInstructions = items.map(item => {
                const notes = item.notes || '';
                return notes ? notes : 'None';
            }).filter(n => n !== 'None').join('; ') || 'None';
            
            // Normalize status (handle both 'pending' and 'Pending')
            const status = order.status === 'pending' ? 'Pending' : (order.status || 'Pending');
            let statusColor = '#f59e0b'; // Default: Pending (orange)
            if (status === 'Approved' || status === 'approved') {
                statusColor = '#3b82f6'; // Blue
            } else if (status === 'Cancelled' || status === 'cancelled') {
                statusColor = '#ef4444'; // Red
            } else if (status === 'Out for Delivery' || status === 'out for delivery') {
                statusColor = '#6366f1'; // Indigo
            } else if (status === 'Completed' || status === 'completed') {
                statusColor = '#10b981'; // Green (for completed/paid orders)
            }
            
            // Use unified function to determine customer type and badge color
            const typeInfo = getCustomerTypeInfo(order);
            const customerType = typeInfo.customerType;
            const badgeColor = typeInfo.badgeColor;
            const orderType = typeInfo.orderType; // Use for status dropdown logic
            
            // Show customer contact info if available
            const customerInfo = order.customerName || order.customer || 'Guest';
            const customerEmail = order.customerEmail ? ` (${order.customerEmail})` : '';
            const customerPhone = order.customerPhone && order.customerPhone !== 'N/A' ? ` - ${order.customerPhone}` : '';
            
            return `
                <tr>
                    <td><strong>${order.id || 'N/A'}</strong><br><small style="color: #666;">${order.date || ''} ${order.time || ''}</small></td>
                    <td><span style="background: ${badgeColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; white-space: nowrap; display: inline-block;">${customerType}</span>${adminView && order.branch ? `<br><small class="muted">${order.branch}</small>` : ''}</td>
                    <td>${customerInfo}${customerEmail}${customerPhone}</td>
                    <td style="font-size: 12px; line-height: 1.4;">${itemsDisplay}</td>
                    <td style="font-size: 12px;">${sizes}</td>
                    <td style="font-size: 12px; max-width: 200px;">${specialInstructions.substring(0, 50)}${specialInstructions.length > 50 ? '...' : ''}</td>
                    <td><span style="background: ${statusColor}; color: white; border-radius: 6px; white-space: nowrap; display: inline-block;">${status}</span></td>
                    <td style="display: flex; gap: 4px; flex-wrap: wrap;">
                        ${orderType === 'Digital' ? `
                            <select class="status-dropdown" data-order-id="${order.id}" data-order-type="Digital" onchange="updateOrderStatus('${order.id}', this.value)">
                                <option value="Pending" ${status === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Approved" ${status === 'Approved' ? 'selected' : ''}>Approved</option>
                                <option value="Out for Delivery" ${status === 'Out for Delivery' ? 'selected' : ''}>Out for Delivery</option>
                                <option value="Completed" ${status === 'Completed' ? 'selected' : ''}>Completed</option>
                                <option value="Cancelled" ${status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        ` : `
                            <select class="status-dropdown" data-order-id="${order.id}" data-order-type="Walk-in" onchange="updateOrderStatus('${order.id}', this.value)">
                                <option value="Pending" ${status === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Completed" ${status === 'Completed' ? 'selected' : ''}>Completed</option>
                            </select>
                        `}
                    </td>
                    <td><strong style="color: #2E5D47;">₱${Number(order.total || 0).toFixed(2)}</strong></td>
                </tr>
            `;
        }).join('');
        // Disable status controls in admin view (read-only)
        if (isAdminView()) {
            const dropdowns = document.querySelectorAll('.status-dropdown');
            dropdowns.forEach(dd => { dd.disabled = true; dd.title = 'Read-only in admin view'; });
        }
        
    } catch (error) {
        console.error('Error rendering orders:', error);
        ordersTbody.innerHTML = '<tr><td colspan="9" style="color: red;">Error loading orders</td></tr>';
    }
}

// Unified function to determine customer type and badge color
function getCustomerTypeInfo(order) {
    // Get registered users for lookup
    const registeredUsers = JSON.parse(localStorage.getItem('jessie_users') || '[]');
    const unifiedData = JSON.parse(localStorage.getItem('jessie_users_unified') || '{}');
    const unifiedCustomers = unifiedData.customers || [];
    
    // Determine order type first (for internal logic)
    let orderType = order.orderType;
    if (!orderType) {
        // Fallback: if order has no orderType, determine from properties
        // Walk-in orders typically have no customerEmail or have empty customerUsername with isGuest
        if (order.isGuest === true && (!order.customerEmail || order.customerEmail === 'N/A') && !order.customerUsername) {
            orderType = 'Walk-in';
        } else {
            orderType = 'Digital'; // Assume Digital if it has customer info
        }
    }
    
    // Determine customer type based on order type
    let customerType = 'Walk-in'; // Default
    const isGuest = order.isGuest === true || order.isGuest === 'true';
    
    if (orderType === 'Walk-in') {
        customerType = 'Walk-in';
    } else if (orderType === 'Digital') {
        // Digital orders come from online portal
        // Registered User: has username and isGuest is explicitly false or undefined (not true)
        if (order.customerUsername && order.customerUsername.trim() && !isGuest) {
            customerType = 'Registered User';
        } else if (isGuest) {
            customerType = 'Guest';
        } else {
            // Fallback: check if user exists in registry
            const customerEmail = order.customerEmail || order.customer;
            const customerUsername = order.customerUsername;
            
            if (customerEmail && customerEmail !== 'N/A') {
                const foundUser = registeredUsers.find(user => {
                    return (user.email === customerEmail || user.username === customerUsername) && user.role === 'customer';
                }) || unifiedCustomers.find(user => {
                    return user.email === customerEmail || user.username === customerUsername;
                });
                
                if (foundUser) {
                    customerType = 'Registered User';
                } else {
                    customerType = 'Guest';
                }
            } else {
                customerType = 'Guest';
            }
        }
    }
    
    // Assign badge colors for each customer type
    let badgeColor;
    switch (customerType) {
        case 'Walk-in':
            badgeColor = '#f59e0b'; // Orange/Amber
            break;
        case 'Registered User':
            badgeColor = '#3b82f6'; // Blue
            break;
        case 'Guest':
            badgeColor = '#8b5cf6'; // Purple/Violet
            break;
        default:
            badgeColor = '#146B33'; // Default green
    }
    
    return {
        customerType: customerType,
        badgeColor: badgeColor,
        orderType: orderType // Return orderType for status dropdown logic
    };
}

// Render customers table (filtered by branch)
function renderCustomers() {
    const customersTbody = document.getElementById('customersTbody');
    const noCustomersRow = document.getElementById('no-customers');
    const adminView = isAdminView();
    const cashierBranch = getCashierBranch();
    
    if (!customersTbody) return;
    
    try {
        const allOrders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
        
        // Filter orders by cashier's branch (admin view shows all)
        const branchOrders = adminView ? allOrders : allOrders.filter(order => branchesMatch(order.branch, cashierBranch));
        
        if (branchOrders.length === 0) {
            customersTbody.innerHTML = '';
            if (noCustomersRow) {
                noCustomersRow.style.display = '';
                noCustomersRow.textContent = adminView ? 'No customer orders available.' : `No customer orders available for ${cashierBranch}.`;
            }
            return;
        }
        
        if (noCustomersRow) noCustomersRow.style.display = 'none';
        
        // Get registered users to lookup phone numbers from both structures
        const registeredUsers = JSON.parse(localStorage.getItem('jessie_users') || '[]');
        const unifiedData = JSON.parse(localStorage.getItem('jessie_users_unified') || '{}');
        const unifiedCustomers = unifiedData.customers || [];
        
        // Sort orders by timestamp or date (newest first)
        const sortedOrders = branchOrders.sort((a, b) => {
            const timeA = a.timestamp || (a.date ? new Date(a.date + ' ' + (a.time || '')).getTime() : 0) || 0;
            const timeB = b.timestamp || (b.date ? new Date(b.date + ' ' + (b.time || '')).getTime() : 0) || 0;
            return timeB - timeA;
        });
        
        customersTbody.innerHTML = sortedOrders.map((order) => {
            const orderId = order.id || 'N/A';
            const name = order.customerName || 'Guest';
            const email = order.customerEmail || 'N/A';
            
            // Use unified function to determine customer type and badge color
            const customerInfo = getCustomerTypeInfo(order);
            const customerType = customerInfo.customerType;
            const badgeColor = customerInfo.badgeColor;
            
            // First check if order has customerPhone
            let phone = 'N/A';
            if (order.customerPhone && order.customerPhone.trim() && order.customerPhone !== 'N/A') {
                phone = order.customerPhone;
            } else {
                // If no phone in order, look it up from registered users by email or username
                const customerEmail = order.customerEmail || order.customer;
                const customerUsername = order.customerUsername;
                
                // First check the old structure (jessie_users)
                let registeredUser = registeredUsers.find(user => {
                    return (user.email === customerEmail || user.username === customerUsername) && user.role === 'customer';
                });
                
                // If not found, check the unified structure
                if (!registeredUser) {
                    registeredUser = unifiedCustomers.find(user => {
                        return user.email === customerEmail || user.username === customerUsername;
                    });
                }
                
                if (registeredUser && registeredUser.phone) {
                    phone = registeredUser.phone;
                }
            }
            
            return `
                <tr>
                    <td><strong>${orderId}</strong>${adminView && order.branch ? `<br><small class="muted">${order.branch}</small>` : ''}</td>
                    <td>${name}</td>
                    <td>${email}</td>
                    <td>${phone}</td>
                    <td><span style="background: ${badgeColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${customerType}</span></td>
                </tr>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Error rendering customers:', error);
        customersTbody.innerHTML = '<tr><td colspan="5" style="color: red;">Error loading customers</td></tr>';
    }
}

// Update order status (for both online and walk-in orders)
async function updateOrderStatus(orderId, newStatus) {
    try {
        const cashierBranch = getCashierBranch();
        const orders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
        
        // First, try to find the order by ID (case-insensitive string comparison)
        let orderIndex = orders.findIndex(o => {
            const oId = String(o.id || '').trim();
            const searchId = String(orderId || '').trim();
            return oId === searchId || oId.toLowerCase() === searchId.toLowerCase();
        });
        
        // If not found, try finding by order_db_id
        if (orderIndex === -1) {
            orderIndex = orders.findIndex(o => {
                const dbId = String(o.order_db_id || o.dbId || '').trim();
                const searchId = String(orderId || '').trim();
                return dbId === searchId;
            });
        }
        
        if (orderIndex === -1) {
            console.error('Order not found:', {
                orderId: orderId,
                totalOrders: orders.length,
                orderIds: orders.map(o => ({ id: o.id, order_db_id: o.order_db_id, branch: o.branch }))
            });
            alert(`Order ${orderId} not found. Please refresh the page and try again.`);
            return;
        }
        
        const order = orders[orderIndex];
        
        // Check branch after finding the order
        if (!branchesMatch(order.branch, cashierBranch)) {
            console.warn('Order branch mismatch:', {
                orderId: orderId,
                orderBranch: order.branch,
                cashierBranch: cashierBranch
            });
            alert(`Order ${orderId} does not belong to your branch (${cashierBranch}).`);
            return;
        }
        const orderType = order.orderType || (order.isGuest ? 'Walk-in' : 'Digital');
        
        // Validate status based on order type
        let validStatuses;
        if (orderType === 'Walk-in') {
            // Walk-in orders can only be Pending or Completed
            validStatuses = ['Pending', 'Completed'];
        } else {
            // Digital/online orders can have all statuses
            validStatuses = ['Pending', 'Approved', 'Out for Delivery', 'Completed', 'Cancelled'];
        }
        
        if (!validStatuses.includes(newStatus)) {
            console.error('Invalid status for order type:', newStatus, orderType);
            alert(`Invalid status for ${orderType} orders. Valid statuses: ${validStatuses.join(', ')}`);
            // Reset dropdown to current status
            const dropdown = document.querySelector(`select[data-order-id="${orderId}"]`);
            if (dropdown) dropdown.value = order.status || 'Pending';
            return;
        }
        
        // Confirm cancellation (only for digital orders)
        if (newStatus === 'Cancelled' && orderType === 'Digital') {
            if (!confirm('Are you sure you want to cancel this order?')) {
                // Reset dropdown to current status
                const dropdown = document.querySelector(`select[data-order-id="${orderId}"]`);
                if (dropdown) dropdown.value = order.status || 'Pending';
                return;
            }
        }
        
        // Update order status locally first
        orders[orderIndex].status = newStatus;
        
        // Add timestamp based on status
        const now = new Date().toISOString();
        if (newStatus === 'Approved') {
            orders[orderIndex].approvedAt = now;
            orders[orderIndex].approvedBy = cashierBranch;
        } else if (newStatus === 'Cancelled') {
            orders[orderIndex].cancelledAt = now;
            orders[orderIndex].cancelledBy = cashierBranch;
        } else if (newStatus === 'Out for Delivery') {
            orders[orderIndex].outForDeliveryAt = now;
        } else if (newStatus === 'Completed') {
            orders[orderIndex].completedAt = now;
            orders[orderIndex].completedBy = cashierBranch;
        }
        
        // Save to localStorage
        localStorage.setItem('jessie_orders', JSON.stringify(orders));
        console.log(`Order ${orderId} status updated to:`, newStatus);

        // Also update status in backend database
        try {
            const localOrder = orders[orderIndex];
            const backendId = localOrder.order_db_id || localOrder.dbId || null;
            const orderIdString = localOrder.id || orderId; // Use order_id string as fallback
            
            if (typeof OrdersAPI !== 'undefined' && OrdersAPI && typeof OrdersAPI.updateStatus === 'function') {
                // Try with backendId first (numeric ID), then fallback to order_id string
                const idToUse = backendId || orderIdString;
                if (idToUse) {
                    await OrdersAPI.updateStatus(idToUse, newStatus, null);
                    console.log('Backend order status updated via API for id:', idToUse);
                } else {
                    console.warn('No order ID available for backend update');
                }
            } else {
                console.warn('OrdersAPI not available, status updated in localStorage only');
            }
        } catch (apiErr) {
            console.error('Failed to update backend order status:', apiErr);
            // Don't block the UI update - localStorage update already succeeded
            // Show a warning but don't fail the entire operation
            console.warn('Status updated in localStorage, but backend update failed. Error:', apiErr.message || apiErr);
        }
        
        // Refresh views
        await renderOrders();
        updateSalesTotals();
        
        // Show success message
        const statusMessages = {
            'Pending': 'Order set to pending',
            'Approved': 'Order approved successfully',
            'Out for Delivery': 'Order marked as out for delivery',
            'Completed': 'Order completed successfully',
            'Cancelled': 'Order cancelled successfully'
        };
        alert(statusMessages[newStatus] || 'Order status updated');
        
    } catch (error) {
        console.error('Error updating order status:', error);
        alert('Error updating order status');
        // Reset dropdown on error
        const dropdown = document.querySelector(`select[data-order-id="${orderId}"]`);
        if (dropdown) {
            const orders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
            const order = orders.find(o => o.id === orderId);
            if (order) dropdown.value = order.status || 'Pending';
        }
    }
}

// Stock management functions
function stockIn(itemName) {
    console.log('Stock in:', itemName);
    alert(`Stock in functionality for ${itemName} - coming soon`);
}

function stockOut(itemName) {
    console.log('Stock out:', itemName);
    alert(`Stock out functionality for ${itemName} - coming soon`);
}

// Inventory management functions
const INVENTORY_STORAGE_KEY = 'jessie_inventory';

function addInventoryItem(itemName, status, branch) {
    try {
        const allInventory = JSON.parse(localStorage.getItem(INVENTORY_STORAGE_KEY) || '[]');
        
        // Check if item already exists for this branch
        const existingIndex = allInventory.findIndex(item => 
            item.name.toLowerCase() === itemName.toLowerCase() && 
            item.branch === branch
        );
        
        const now = new Date();
        const timestamp = now.toISOString();
        const formattedDate = now.toLocaleString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        if (existingIndex !== -1) {
            // Update existing item
            allInventory[existingIndex].timestamp = timestamp;
            allInventory[existingIndex].lastUpdated = formattedDate;
            allInventory[existingIndex].status = status;
            alert(`${itemName} inventory status updated to "${status}"!`);
        } else {
            // Add new item
            const newItem = {
                id: Date.now().toString(),
                name: itemName,
                branch: branch,
                status: status,
                timestamp: timestamp,
                lastUpdated: formattedDate
            };
            allInventory.push(newItem);
            alert(`${itemName} added with status: "${status}"!`);
        }
        
        localStorage.setItem(INVENTORY_STORAGE_KEY, JSON.stringify(allInventory));
    } catch (error) {
        console.error('Error adding inventory item:', error);
        alert('Error adding inventory item');
    }
}

function removeInventoryItem(itemId) {
    try {
        const allInventory = JSON.parse(localStorage.getItem(INVENTORY_STORAGE_KEY) || '[]');
        const filteredInventory = allInventory.filter(item => item.id !== itemId);
        localStorage.setItem(INVENTORY_STORAGE_KEY, JSON.stringify(filteredInventory));
        renderInventory();
    } catch (error) {
        console.error('Error removing inventory item:', error);
        alert('Error removing inventory item');
    }
}

function clearInventory() {
    const cashierBranch = getCashierBranch();
    const adminView = isAdminView();
    const message = adminView 
        ? 'Are you sure you want to clear ALL inventory logs across ALL branches? This action cannot be undone.'
        : `Are you sure you want to clear all inventory items for ${cashierBranch}? This action cannot be undone.`;
    if (!confirm(message)) return;
    try {
        if (adminView) {
            // Admin consolidated clear: remove every inventory entry regardless of branch
            localStorage.setItem(INVENTORY_STORAGE_KEY, JSON.stringify([]));
            renderInventory();
            alert('All inventory items across ALL branches cleared successfully!');
        } else {
            // Branch-only clear
            const allInventory = JSON.parse(localStorage.getItem(INVENTORY_STORAGE_KEY) || '[]');
            const filteredInventory = allInventory.filter(item => item.branch !== cashierBranch);
            localStorage.setItem(INVENTORY_STORAGE_KEY, JSON.stringify(filteredInventory));
            renderInventory();
            alert(`All inventory items for ${cashierBranch} cleared successfully!`);
        }
    } catch (error) {
        console.error('Error clearing inventory:', error);
        alert('Error clearing inventory');
    }
}

function renderInventory() {
    const inventoryTbody = document.getElementById('inventoryTbody');
    const noInventoryRow = document.getElementById('no-inventory');
    const cashierBranch = getCashierBranch();
    const adminView = isAdminView();
    // Update table header to include Branch in admin consolidated view
    try {
        const headerRow = document.querySelector('#inventory-list thead tr');
        if (headerRow) {
            if (adminView) {
                headerRow.innerHTML = '<th>Item</th><th>Status</th><th>Branch</th><th>Last Updated</th>';
            } else {
                headerRow.innerHTML = '<th>Item</th><th>Status</th><th>Last Updated</th><th>Actions</th>';
            }
        }
    } catch(_) {}
    
    if (!inventoryTbody) return;
    
    try {
        const allInventory = JSON.parse(localStorage.getItem(INVENTORY_STORAGE_KEY) || '[]');
        
        // Filter inventory by cashier's branch (admin view shows all)
        const branchInventory = adminView ? allInventory : allInventory.filter(item => item.branch === cashierBranch);
        
        if (branchInventory.length === 0) {
            inventoryTbody.innerHTML = '';
            if (noInventoryRow) {
                noInventoryRow.style.display = '';
                noInventoryRow.textContent = `No inventory items logged for ${cashierBranch}.`;
            }
            return;
        }
        
        if (noInventoryRow) noInventoryRow.style.display = 'none';
        
        // Sort by most recent first
        const sortedInventory = branchInventory.sort((a, b) => {
            return new Date(b.timestamp) - new Date(a.timestamp);
        });
        
        // Function to get status badge color
        const getStatusColor = (status) => {
            switch(status) {
                case 'Out of Stock':
                    return { bg: '#ef4444', icon: 'fa-box-open' }; // Red
                case 'Low Stock':
                    return { bg: '#f59e0b', icon: 'fa-exclamation-triangle' }; // Orange
                case 'Spoiled':
                    return { bg: '#991b1b', icon: 'fa-ban' }; // Dark red
                default:
                    return { bg: '#6b7280', icon: 'fa-info-circle' }; // Gray
            }
        };
        
        inventoryTbody.innerHTML = sortedInventory.map(item => {
            const statusInfo = getStatusColor(item.status);
            const branchCell = adminView ? `<td>${item.branch || 'N/A'}</td>` : '';
            const actionsCell = adminView ? '' : `<td>
                        <button class="btn btn--outline small" onclick=\"removeInventoryItem('${item.id}')\" title=\"Remove Item\">\n                            <i class=\"fa-solid fa-trash\"></i> Remove\n                        </button>\n                    </td>`;
            return `
                <tr>
                    <td><strong>${item.name}</strong></td>
                    <td>
                        <span style="background: ${statusInfo.bg}; color: white; border-radius: 6px; white-space: nowrap; display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: bold;">
                            <i class="fa-solid ${statusInfo.icon}" style="margin-right: 4px;"></i>${item.status}
                        </span>
                    </td>
                    ${branchCell}
                    <td style="font-size: 12px; color: var(--muted);">${item.lastUpdated}</td>
                    ${actionsCell}
                </tr>
            `;
        }).join('');
        // Disable inventory action buttons in admin view (read-only)
        if (adminView) {
            const invButtons = document.querySelectorAll('#inventory tbody .btn');
            invButtons.forEach(b => { b.disabled = true; b.style.pointerEvents = 'none'; b.title = 'Read-only in admin view'; });
        }
        
    } catch (error) {
        console.error('Error rendering inventory:', error);
        inventoryTbody.innerHTML = '<tr><td colspan="4" style="color: red;">Error loading inventory</td></tr>';
    }
}

// View detailed report (for cashier's branch)
function viewDetailedReport() {
    try {
        const adminView = isAdminView();
        const cashierBranch = getCashierBranch();
        const allOrders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
        const branchOrders = adminView ? allOrders : allOrders.filter(order => order.branch === cashierBranch);
        const today = new Date().toDateString();
        
        const todayOrders = branchOrders.filter(order => {
            let orderDate;
            if (order.timestamp) {
                orderDate = new Date(order.timestamp).toDateString();
            } else if (order.date) {
                orderDate = new Date(order.date).toDateString();
            } else {
                return false;
            }
            return orderDate === today;
        });
        
        if (todayOrders.length === 0) {
            alert(adminView ? 'No orders today.' : `No orders today for ${cashierBranch}.`);
            return;
        }
        
        // Calculate statistics
        const totalOrders = todayOrders.length;
        const approvedCount = todayOrders.filter(o => {
            const s = (o.status || '').toLowerCase();
            return s === 'approved';
        }).length;
        const completedCount = todayOrders.filter(o => {
            const s = (o.status || '').toLowerCase();
            return s === 'completed';
        }).length;
        const pendingCount = todayOrders.filter(o => {
            const s = (o.status || '').toLowerCase();
            return s === 'pending';
        }).length;
        const cancelledCount = todayOrders.filter(o => {
            const s = (o.status || '').toLowerCase();
            return s === 'cancelled';
        }).length;
        
        // Calculate sales totals - compute COMPLETED orders only
        let totalSales = 0; // Total sales (completed only)
        let completedSales = 0;
        let approvedSales = 0;
        let pendingSales = 0;
        let cancelledSales = 0;
        let outForDeliverySales = 0;

        todayOrders.forEach(order => {
            const orderTotal = parseFloat(order.total || 0);
            // Get status - use the EXACT same logic as the count calculation above
            const statusLower = (order.status || order.order_status || '').toString().trim().toLowerCase();

            // Count sales by status
            if (statusLower === 'completed') {
                completedSales += orderTotal;
                totalSales += orderTotal;
            } else if (statusLower === 'approved') {
                approvedSales += orderTotal;
            } else if (statusLower === 'pending') {
                pendingSales += orderTotal;
            } else if (statusLower === 'out for delivery' || statusLower === 'outfordelivery') {
                outForDeliverySales += orderTotal;
            } else if (statusLower === 'cancelled') {
                cancelledSales += orderTotal;
            }
        });
        
        // Build HTML content
        const modalBody = document.getElementById('reportModalBody');
        const formattedDate = new Date().toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        modalBody.innerHTML = `
            <div style="margin-bottom: 20px; color: var(--muted); font-size: 13px;">
                <strong>Branch:</strong> ${adminView ? 'All Branches' : cashierBranch}<br>
                <strong>Date:</strong> ${formattedDate}
            </div>
            
            <div class="report-summary">
                <div class="report-summary-card">
                    <h4>Total Orders</h4>
                    <p class="value">${totalOrders}</p>
                </div>
                <div class="report-summary-card approved">
                    <h4>Approved</h4>
                    <p class="value">${approvedCount}</p>
                </div>
                <div class="report-summary-card completed">
                    <h4>Completed</h4>
                    <p class="value">${completedCount}</p>
                </div>
                <div class="report-summary-card pending">
                    <h4>Pending</h4>
                    <p class="value">${pendingCount}</p>
                </div>
                <div class="report-summary-card cancelled">
                    <h4>Cancelled</h4>
                    <p class="value">${cancelledCount}</p>
                </div>
            </div>
            
            <div class="report-section">
                <h3><i class="fa-solid fa-list"></i> Order Breakdown</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${todayOrders.map(order => {
                            const itemsCount = (order.items || []).reduce((sum, item) => sum + (item.qty || 1), 0);
                            const status = order.status === 'pending' ? 'Pending' : (order.status || 'Pending');
                            let statusColor = '#f59e0b'; // Default: Pending (orange)
                            if (status === 'Approved' || status === 'approved') {
                                statusColor = '#3b82f6'; // Blue
                            } else if (status === 'Cancelled' || status === 'cancelled') {
                                statusColor = '#ef4444'; // Red
                            } else if (status === 'Out for Delivery' || status === 'out for delivery') {
                                statusColor = '#6366f1'; // Indigo
                            } else if (status === 'Completed' || status === 'completed') {
                                statusColor = '#10b981'; // Green (for completed/paid orders)
                            }
                            return `
                                <tr>
                                    <td><strong>${order.id || 'N/A'}</strong>${adminView && order.branch ? `<br><small class="muted">${order.branch}</small>` : ''}</td>
                                    <td>${order.customerName || 'Guest'}</td>
                                    <td>${itemsCount} item(s)</td>
                                    <td><span style="background: ${statusColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">${status}</span></td>
                                    <td><strong>₱${parseFloat(order.total || 0).toFixed(2)}</strong></td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="report-section">
                <h3><i class="fa-solid fa-chart-line"></i> Sales Summary</h3>
                <table class="report-table">
                    <tr>
                        <td><strong>Total Sales (Completed Orders Only)</strong></td>
                        <td style="text-align: right;"><strong>₱${totalSales.toFixed(2)}</strong></td>
                    </tr>
                    <tr>
                        <td>Approved Orders Sales</td>
                        <td style="text-align: right; color: #10b981;">₱${approvedSales.toFixed(2)}</td>
                    </tr>
                    ${outForDeliverySales > 0 ? `
                    <tr>
                        <td>Out for Delivery Orders Sales</td>
                        <td style="text-align: right; color: #3b82f6;">₱${outForDeliverySales.toFixed(2)}</td>
                    </tr>
                    ` : ''}
                    ${completedSales > 0 ? `
                    <tr>
                        <td>Completed Orders Sales</td>
                        <td style="text-align: right; color: #10b981;">₱${completedSales.toFixed(2)}</td>
                    </tr>
                    ` : ''}
                    <tr>
                        <td>Pending Orders Sales</td>
                        <td style="text-align: right; color: #f59e0b;">₱${pendingSales.toFixed(2)}</td>
                    </tr>
                    ${cancelledSales > 0 ? `
                    <tr>
                        <td>Cancelled Orders Sales</td>
                        <td style="text-align: right; color: #ef4444;">₱${cancelledSales.toFixed(2)}</td>
                    </tr>
                    ` : ''}
                </table>
            </div>
            
            <div class="report-total" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                <div style="display: flex; justify-content: space-between; width: 100%;">
                    <span class="report-total-label">Total Sales Today (Excludes Cancelled)</span>
                    <span class="report-total-amount">₱${totalSales.toFixed(2)}</span>
                </div>
                <div style="font-size: 14px; font-weight: 600; color: rgba(255, 255, 255, 0.9);">
                    Cancelled Orders: ${cancelledCount} (not included in total)
                </div>
            </div>
        `;
        
        // Show modal
        const modal = document.getElementById('detailedReportModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
    } catch (error) {
        console.error('Error generating report:', error);
        alert('Error generating report');
    }
}

// Close detailed report modal
function closeDetailedReport() {
    const modal = document.getElementById('detailedReportModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Print detailed report
function printDetailedReport() {
    const modalBody = document.getElementById('reportModalBody');
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Detailed Sales Report</title>
                <style>
                    body { font-family: 'Century Gothic', Arial, sans-serif; padding: 20px; }
                    h1 { color: #246b46; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                    th { background: #f5f1e6; color: #246b46; }
                    .total { font-size: 24px; font-weight: bold; color: #246b46; }
                </style>
            </head>
            <body>
                ${modalBody.innerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Generate inventory report (for cashier's branch)
function generateInventoryReport() {
    try {
        const adminView = isAdminView();
        const cashierBranch = getCashierBranch();
        const allInventory = JSON.parse(localStorage.getItem(INVENTORY_STORAGE_KEY) || '[]');
        
        // Filter inventory by cashier's branch (admin view shows all)
        const branchInventory = adminView ? allInventory : allInventory.filter(item => item.branch === cashierBranch);
        
        if (branchInventory.length === 0) {
            alert(adminView ? 'No inventory items logged.' : `No inventory items logged for ${cashierBranch}.`);
            return;
        }
        
        // Count items by status
        const statusCounts = {
            'Out of Stock': 0,
            'Low Stock': 0,
            'Spoiled': 0
        };
        
        branchInventory.forEach(item => {
            if (statusCounts.hasOwnProperty(item.status)) {
                statusCounts[item.status]++;
            }
        });
        
        // Function to get status badge color
        const getStatusColor = (status) => {
            switch(status) {
                case 'Out of Stock':
                    return { bg: '#ef4444', icon: 'fa-box-open' };
                case 'Low Stock':
                    return { bg: '#f59e0b', icon: 'fa-exclamation-triangle' };
                case 'Spoiled':
                    return { bg: '#991b1b', icon: 'fa-ban' };
                default:
                    return { bg: '#6b7280', icon: 'fa-info-circle' };
            }
        };
        
        // Build HTML content
        const modalBody = document.getElementById('inventoryReportModalBody');
        const formattedDate = new Date().toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        modalBody.innerHTML = `
            <div style="margin-bottom: 20px; color: var(--muted); font-size: 13px;">
                <strong>Branch:</strong> ${adminView ? 'All Branches' : cashierBranch}<br>
                <strong>Date:</strong> ${formattedDate}
            </div>
            
            <div class="report-summary">
                <div class="report-summary-card">
                    <h4>Total Items</h4>
                    <p class="value">${branchInventory.length}</p>
                </div>
                <div class="report-summary-card" style="border-left-color: #ef4444;">
                    <h4>Out of Stock</h4>
                    <p class="value" style="color: #ef4444;">${statusCounts['Out of Stock']}</p>
                </div>
                <div class="report-summary-card" style="border-left-color: #f59e0b;">
                    <h4>Low Stock</h4>
                    <p class="value" style="color: #f59e0b;">${statusCounts['Low Stock']}</p>
                </div>
                <div class="report-summary-card" style="border-left-color: #991b1b;">
                    <h4>Spoiled</h4>
                    <p class="value" style="color: #991b1b;">${statusCounts['Spoiled']}</p>
                </div>
            </div>
            
            <div class="report-section">
                <h3><i class="fa-solid fa-list"></i> Inventory Details</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${branchInventory.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp)).map(item => {
                            const statusInfo = getStatusColor(item.status);
                            return `
                                <tr>
                                    <td><strong>${item.name}</strong></td>
                                    <td>
                                        <span style="background: ${statusInfo.bg}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">
                                            <i class="fa-solid ${statusInfo.icon}" style="margin-right: 4px;"></i>${item.status}
                                        </span>
                                    </td>
                                    <td>${item.lastUpdated}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="report-total">
                <span class="report-total-label">Total Inventory Issues</span>
                <span class="report-total-amount">${branchInventory.length}</span>
            </div>
        `;
        
        // Show modal
        const modal = document.getElementById('inventoryReportModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
    } catch (error) {
        console.error('Error generating inventory report:', error);
        alert('Error generating inventory report');
    }
}

// Close inventory report modal
function closeInventoryReport() {
    const modal = document.getElementById('inventoryReportModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Print inventory report
function printInventoryReport() {
    const modalBody = document.getElementById('inventoryReportModalBody');
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Inventory Report</title>
                <style>
                    body { font-family: 'Century Gothic', Arial, sans-serif; padding: 20px; }
                    h1 { color: #246b46; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                    th { background: #f5f1e6; color: #246b46; }
                    .total { font-size: 24px; font-weight: bold; color: #246b46; }
                </style>
            </head>
            <body>
                ${modalBody.innerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Make functions globally available
window.updateOrderStatus = updateOrderStatus;
window.stockIn = stockIn;
window.stockOut = stockOut;
window.addInventoryItem = addInventoryItem;
window.removeInventoryItem = removeInventoryItem;
window.clearInventory = clearInventory;
window.renderInventory = renderInventory;
window.viewDetailedReport = viewDetailedReport;
window.closeDetailedReport = closeDetailedReport;
window.printDetailedReport = printDetailedReport;
window.generateInventoryReport = generateInventoryReport;
window.closeInventoryReport = closeInventoryReport;
window.printInventoryReport = printInventoryReport;

// --- Styled confirmation modal for destructive actions ---
function showConfirmModal(options) {
    try {
        const opts = options || {};
        const overlay = document.createElement('div');
        overlay.className = 'confirm-overlay';

        const modal = document.createElement('div');
        modal.className = 'confirm-modal';

        const icon = document.createElement('div');
        icon.className = 'confirm-icon';
        icon.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';

        const title = document.createElement('h3');
        title.textContent = opts.title || 'Confirm Action';

        const message = document.createElement('p');
        message.textContent = opts.message || 'Are you sure you want to proceed?';

        const actions = document.createElement('div');
        actions.className = 'confirm-actions';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'btn btn--outline small';
        cancelBtn.textContent = opts.cancelText || 'Cancel';
        cancelBtn.addEventListener('click', function(){ document.body.removeChild(overlay); });

        const confirmBtn = document.createElement('button');
        confirmBtn.className = 'btn btn--primary small';
        confirmBtn.textContent = opts.confirmText || 'Confirm';
        confirmBtn.addEventListener('click', function(){
            try { if (typeof opts.onConfirm === 'function') opts.onConfirm(); } finally { if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay); }
        });

        actions.appendChild(cancelBtn);
        actions.appendChild(confirmBtn);
        modal.appendChild(icon);
        modal.appendChild(title);
        modal.appendChild(message);
        modal.appendChild(actions);
        overlay.appendChild(modal);
        overlay.addEventListener('click', function(e){ if (e.target === overlay) document.body.removeChild(overlay); });
        document.body.appendChild(overlay);
    } catch (e) {
        // Fallback to native confirm
        if (options && typeof options.onConfirm === 'function' && confirm(options.message || 'Proceed?')) {
            options.onConfirm();
        }
    }
}

// Lightweight toast banner
function showToastBanner(type, text){
    const t = document.createElement('div');
    t.className = 'toast-banner ' + (type || 'info');
    t.textContent = text || '';
    document.body.appendChild(t);
    setTimeout(()=> t.classList.add('visible'), 20);
    setTimeout(()=> { t.classList.remove('visible'); setTimeout(()=> t.remove(), 300); }, 2500);
}

console.log('Cashier dashboard loaded');


