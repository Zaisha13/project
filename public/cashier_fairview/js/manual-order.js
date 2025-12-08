/* PURPOSE: Manual Order functionality — walk-in customer order processing */

// Check authentication
function checkCashierAuth() {
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

// Get cashier branch from current user
function getCashierBranch() {
    // This is the Fairview portal: always use Fairview as the branch
    return 'SM Fairview';
}

// Get menu items from storage or defaults
function getMenuItems() {
    try {
        const storedMenu = JSON.parse(localStorage.getItem('jessie_menu') || '[]');
        if (Array.isArray(storedMenu) && storedMenu.length > 0) {
            return storedMenu;
        }
        return DEFAULT_MENU_ITEMS;
    } catch (error) {
        console.error('Error parsing menu items:', error);
        return DEFAULT_MENU_ITEMS;
    }
}

// Default menu items
const DEFAULT_MENU_ITEMS = [
    { id: 1, name: 'Pure Sugarcane', desc: 'Freshly pressed sugarcane juice', priceRegular: 79, priceTall: 109, img: 'pure-sugarcane.png' },
    { id: 2, name: 'Calamansi Cane', desc: 'A zesty twist on classic sugarcane juice', priceRegular: 89, priceTall: 119, img: 'calamansi-cane.png' },
    { id: 3, name: 'Lemon Cane', desc: 'Freshly squeezed lemon combined with sugarcane', priceRegular: 89, priceTall: 119, img: 'lemon-cane.png' },
    { id: 4, name: 'Yakult Cane', desc: 'Sugarcane juice and Yakult', priceRegular: 89, priceTall: 119, img: 'yakult-cane.png' },
    { id: 5, name: 'Calamansi Yakult Cane', desc: 'Calamansi, Yakult, and sugarcane', priceRegular: 99, priceTall: 129, img: 'calamansi-yakult-cane.png' },
    { id: 6, name: 'Lemon Yakult Cane', desc: 'Lemon, Yakult, and sugarcane', priceRegular: 99, priceTall: 129, img: 'lemon-yakult-cane.png' },
    { id: 7, name: 'Lychee Cane', desc: 'Lychee and sugarcane juice', priceRegular: 99, priceTall: 129, img: 'lychee-cane.png' },
    { id: 8, name: 'Orange Cane', desc: 'Orange juice blended with sugarcane', priceRegular: 109, priceTall: 139, img: 'orange-cane.png' },
    { id: 9, name: 'Passion Fruit Cane', desc: 'Passion fruit and sugarcane', priceRegular: 109, priceTall: 139, img: 'passion-fruit-cane.png' },
    { id: 10, name: 'Watermelon Cane', desc: 'Watermelon and sugarcane juice', priceRegular: 109, priceTall: 139, img: 'watermelon-cane.png' },
    { id: 11, name: 'Dragon Fruit Cane', desc: 'Dragon fruit and sugarcane juice', priceRegular: 119, priceTall: 149, img: 'dragon-fruit-cane.png' },
    { id: 12, name: 'Strawberry Yogurt Cane', desc: 'Strawberry yogurt and sugarcane', priceRegular: 119, priceTall: 149, img: 'strawberry-yogurt-cane.png' }
];

// Normalize image path
function normalizeImagePath(src) {
    if (!src) return '';
    if (typeof src !== 'string') return '';
    const s = src.trim();
    if (!s) return '';
    if (s.startsWith('data:') || s.startsWith('http://') || s.startsWith('https://')) {
        return s;
    }
    if (s.startsWith('./') || s.startsWith('../')) {
        return s;
    }
    return `../../assets/images/${s}`;
}

// Initialize page
let cart = [];
let modalState = {};

document.addEventListener('DOMContentLoaded', function() {
    if (!checkCashierAuth()) return;
    
    // Set cashier name
    try {
        const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
        const cashierName = document.getElementById('cashier-name');
        if (cashierName && (currentUser.name || currentUser.username)) {
            cashierName.textContent = currentUser.name || currentUser.username;
        }
    } catch (e) {
        console.error('Error getting cashier name:', e);
    }
    
    // Load menu items
    const menuItems = getMenuItems();
    renderMenu(menuItems);
    
    // Setup logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to log out?')) {
                localStorage.removeItem('isLoggedIn');
                localStorage.removeItem('currentUser');
                window.location.href = '../cashier/login_cashier.php';
            }
        });
    }
    
    // Set branch display to cashier branch
    try {
        const branchDisplay = document.getElementById('branchDisplay');
        if (branchDisplay) {
            branchDisplay.value = getCashierBranch();
        }
    } catch(_){}

    // Setup checkout button
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', openCheckoutModal);
    }

    // Recalculate change when amount received changes
    const amountReceivedEl = document.getElementById('amountReceived');
    if (amountReceivedEl) {
        amountReceivedEl.addEventListener('input', updateTotal);
    }

    // Setup payment method change handler
    const paymentMethodEl = document.getElementById('paymentMethod');
    if (paymentMethodEl) {
        paymentMethodEl.addEventListener('change', handlePaymentMethodChange);
    }

    // Initialize payment method on page load
    handlePaymentMethodChange();
});

// Render menu items with prices
function renderMenu(menuItems) {
    const menuGrid = document.getElementById('menuGrid');
    if (!menuGrid) return;
    
    menuGrid.innerHTML = menuItems.map(item => {
        const imagePath = normalizeImagePath(item.img);
        const priceRegular = parseFloat(item.priceRegular) || 0;
        const priceTall = parseFloat(item.priceTall) || priceRegular;
        return `
            <div class="menu-item" onclick="openCustomizationModal(${item.id})">
                <img src="${imagePath}" alt="${item.name}" onerror="this.src='../../assets/images/logo.png'">
                <div class="menu-item-name">${item.name}</div>
                <div class="menu-item-prices">
                    <span class="price-tag">R: ₱${priceRegular.toFixed(0)}</span>
                    <span class="price-tag">T: ₱${priceTall.toFixed(0)}</span>
                </div>
            </div>
        `;
    }).join('');
}

// Open customization modal
function openCustomizationModal(productId) {
    const menuItems = getMenuItems();
    const product = menuItems.find(p => p.id === productId);
    
    if (!product) {
        alert('Product not found');
        return;
    }
    
    // Set modal state
    modalState = {
        productId: productId,
        size: 'Regular',
        qty: 1,
        special: 'None',
        notes: ''
    };
    
    // Update modal content
    const modalImg = document.getElementById('modal-drink-image');
    const modalName = document.getElementById('modal-drink-name');
    const modalQty = document.getElementById('modalQty');
    
    if (modalImg) modalImg.src = normalizeImagePath(product.img);
    if (modalName) modalName.textContent = product.name;
    if (modalQty) modalQty.value = 1;
    
    updateModalPrice();
    
    // Show modal
    const modal = document.getElementById('customizationModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

// Close customization modal
function closeCustomizationModal() {
    const modal = document.getElementById('customizationModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Select size
function selectSize(size) {
    modalState.size = size;
    updateModalPrice();
    
    // Update button states
    document.querySelectorAll('.size-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.size === size) {
            btn.classList.add('active');
        }
    });
}

// Select special
function selectSpecial(special) {
    modalState.special = special;
    updateModalPrice();
    
    // Update button states
    document.querySelectorAll('.special-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.special === special) {
            btn.classList.add('active');
        }
    });
}

// Increase quantity
function increaseQty() {
    const qty = parseInt(document.getElementById('modalQty').value) || 1;
    document.getElementById('modalQty').value = qty + 1;
    modalState.qty = qty + 1;
    updateModalPrice();
}

// Decrease quantity
function decreaseQty() {
    const qty = parseInt(document.getElementById('modalQty').value) || 1;
    if (qty > 1) {
        document.getElementById('modalQty').value = qty - 1;
        modalState.qty = qty - 1;
        updateModalPrice();
    }
}

// Update quantity
function updateQty(value) {
    const qty = parseInt(value) || 1;
    modalState.qty = qty;
    updateModalPrice();
}

// Update modal price
function updateModalPrice() {
    const menuItems = getMenuItems();
    const product = menuItems.find(p => p.id === modalState.productId);
    
    if (!product) {
        document.getElementById('modalPrice').textContent = '₱0.00';
        return;
    }
    
    // Get base price based on size
    let basePrice = 0;
    if (modalState.size === 'Regular') {
        basePrice = product.priceRegular || 0;
    } else if (modalState.size === 'Tall') {
        basePrice = product.priceTall || product.priceRegular || 0;
    }
    
    // Add special instruction price
    if (modalState.special === 'No Ice') {
        basePrice += 20;
    }
    
    const totalPrice = basePrice * modalState.qty;
    
    document.getElementById('modalPrice').textContent = `₱${totalPrice.toFixed(2)}`;
}

// Confirm add to cart
function confirmAddToCart() {
    const menuItems = getMenuItems();
    const product = menuItems.find(p => p.id === modalState.productId);
    
    if (!product) {
        alert('Product not found');
        return;
    }
    
    // Get notes from textarea
    const notesTextarea = document.getElementById('modalNotes');
    if (notesTextarea) {
        modalState.notes = notesTextarea.value.trim();
    }
    
    // Calculate price
    let basePrice = 0;
    if (modalState.size === 'Regular') {
        basePrice = product.priceRegular || 0;
    } else if (modalState.size === 'Tall') {
        basePrice = product.priceTall || product.priceRegular || 0;
    }
    
    if (modalState.special === 'No Ice') {
        basePrice += 20;
    }
    
    const price = basePrice;
    
    // Check if item already exists in cart
    const existingItem = cart.find(item => 
        item.productId === modalState.productId && 
        item.size === modalState.size && 
        item.special === modalState.special &&
        item.notes === modalState.notes
    );
    
    if (existingItem) {
        existingItem.qty += modalState.qty;
    } else {
        cart.push({
            productId: modalState.productId,
            name: product.name,
            size: modalState.size,
            special: modalState.special,
            notes: modalState.notes,
            price: price,
            qty: modalState.qty,
            img: product.img
        });
    }
    
    closeCustomizationModal();
    updateCartUI();
}

// Update cart UI
function updateCartUI() {
    renderCart();
    updateTotal();
    
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.disabled = cart.length === 0;
    }
    
    const cartCount = document.getElementById('cartCount');
    const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
    if (cartCount) {
        cartCount.textContent = `${totalItems} item${totalItems !== 1 ? 's' : ''}`;
    }
    
}

// Render cart items
function renderCart() {
    const cartItems = document.getElementById('cartItems');
    if (!cartItems) return;
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart">
                <i class="fa-solid fa-cart-plus"></i>
                <p>No items in cart</p>
            </div>
        `;
        return;
    }
    
    cartItems.innerHTML = cart.map((item, index) => {
        const imagePath = normalizeImagePath(item.img);
        const itemTotal = item.qty * item.price;
        
        return `
            <div class="cart-item">
                <img class="cart-item-image" src="${imagePath}" alt="${item.name}" onerror="this.src='../../assets/images/logo.png'">
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-size">${item.size}${item.special !== 'None' ? ' • ' + item.special : ''}</div>
                </div>
                <div class="cart-item-controls">
                    <button class="qty-btn" onclick="updateQuantity(${index}, -1)">-</button>
                    <span class="qty-display">${item.qty}</span>
                    <button class="qty-btn" onclick="updateQuantity(${index}, 1)">+</button>
                </div>
                <div class="cart-item-price">₱${itemTotal.toFixed(2)}</div>
                <button class="cart-item-remove" onclick="removeFromCart(${index})">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
    }).join('');
}

// Update quantity
function updateQuantity(index, change) {
    if (index < 0 || index >= cart.length) return;
    
    cart[index].qty += change;
    
    if (cart[index].qty <= 0) {
        cart.splice(index, 1);
    }
    
    updateCartUI();
}

// Remove from cart
function removeFromCart(index) {
    if (index < 0 || index >= cart.length) return;
    cart.splice(index, 1);
    updateCartUI();
}

// Clear cart
function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCartUI();
    }
}

// Handle payment method change
function handlePaymentMethodChange() {
    const paymentMethod = document.getElementById('paymentMethod');
    const cashFieldsGroup = document.getElementById('cashFieldsGroup');
    const changeFieldsGroup = document.getElementById('changeFieldsGroup');
    const amountReceivedEl = document.getElementById('amountReceived');
    const changeAmountEl = document.getElementById('changeAmount');
    
    if (!paymentMethod) return;
    
    const selectedMethod = paymentMethod.value;
    
    if (selectedMethod === 'cash') {
        // Show and enable cash fields
        if (cashFieldsGroup) cashFieldsGroup.style.display = 'block';
        if (changeFieldsGroup) changeFieldsGroup.style.display = 'block';
        if (amountReceivedEl) {
            amountReceivedEl.required = true;
            amountReceivedEl.removeAttribute('disabled');
            amountReceivedEl.removeAttribute('readonly');
        }
        if (changeAmountEl) {
            changeAmountEl.removeAttribute('disabled');
        }
    } else if (selectedMethod === 'gcash') {
        // Hide and disable cash fields - block all interaction
        if (cashFieldsGroup) cashFieldsGroup.style.display = 'none';
        if (changeFieldsGroup) changeFieldsGroup.style.display = 'none';
        if (amountReceivedEl) {
            amountReceivedEl.required = false;
            amountReceivedEl.value = '';
            amountReceivedEl.setAttribute('disabled', 'disabled');
            amountReceivedEl.setAttribute('readonly', 'readonly');
        }
        if (changeAmountEl) {
            changeAmountEl.value = '₱0.00';
            changeAmountEl.setAttribute('disabled', 'disabled');
        }
    }
}

// Update total
function updateTotal() {
    const totalEl = document.getElementById('total');
    const amountReceivedEl = document.getElementById('amountReceived');
    const changeAmountEl = document.getElementById('changeAmount');
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    
    if (totalEl) totalEl.textContent = `₱${total.toFixed(2)}`;

    // If amount received is filled and payment method is cash, auto-compute change
    if (amountReceivedEl && changeAmountEl) {
        const paymentMethodEl = document.getElementById('paymentMethod');
        const paymentMethod = paymentMethodEl ? paymentMethodEl.value : 'cash';
        
        // Only calculate change if payment method is cash and field is not disabled
        if (paymentMethod === 'cash' && !amountReceivedEl.disabled) {
            const received = parseFloat(amountReceivedEl.value) || 0;
            const change = Math.max(0, received - total);
            changeAmountEl.value = `₱${change.toFixed(2)}`;
        } else {
            // For GCash, keep change at 0
            changeAmountEl.value = '₱0.00';
        }
    }
}

// Open checkout modal
function openCheckoutModal() {
    if (cart.length === 0) {
        alert('Cart is empty');
        return;
    }
    
    const modal = document.getElementById('checkoutModal');
    if (modal) {
        modal.style.display = 'flex';
        // Reset payment method to cash
        const paymentMethodEl = document.getElementById('paymentMethod');
        if (paymentMethodEl) paymentMethodEl.value = 'cash';
        handlePaymentMethodChange();
        // Reset amount fields and compute initial change
        const amountReceivedEl = document.getElementById('amountReceived');
        const changeAmountEl = document.getElementById('changeAmount');
        if (amountReceivedEl) amountReceivedEl.value = '';
        if (changeAmountEl) changeAmountEl.value = '₱0.00';
        updateTotal();
        
        // Add event listener for amount received changes
        if (amountReceivedEl) {
            amountReceivedEl.removeEventListener('input', updateTotal);
            amountReceivedEl.addEventListener('input', updateTotal);
        }
    }
}

// Close checkout modal
function closeCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    if (modal) {
        modal.style.display = 'none';
        // Reset form
        document.getElementById('customerForm').reset();
        // Refill branch display
        const branchDisplay = document.getElementById('branchDisplay');
        if (branchDisplay) branchDisplay.value = getCashierBranch();
    }
}


// Generate order ID
function generateOrderId() {
    try {
        const orders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
        let maxNum = 0;
        orders.forEach(o => {
            if (!o || !o.id) return;
            const id = String(o.id).trim();
            if (/^\d+$/.test(id)) {
                maxNum = Math.max(maxNum, parseInt(id, 10));
                return;
            }
            const m = id.match(/(\d+)$/);
            if (m) maxNum = Math.max(maxNum, parseInt(m[1], 10));
        });
        const next = maxNum + 1;
        return 'ORD-' + String(next).padStart(3, '0');
    } catch (err) {
        return 'ORD-' + String(Date.now()).slice(-6);
    }
}

// Place manual order
async function placeManualOrder() {
    const customerName = document.getElementById('customerName').value.trim();
    const customerPhoneEl = document.getElementById('customerPhone');
    const customerPhone = customerPhoneEl ? customerPhoneEl.value.trim() : 'N/A';
    const branch = getCashierBranch();
    const specialNotes = document.getElementById('specialNotes').value.trim();
    const amountReceivedInput = document.getElementById('amountReceived');
    const amountReceived = amountReceivedInput ? parseFloat(amountReceivedInput.value) || 0 : 0;
    
    if (!customerName) {
        alert('Please enter customer name');
        return;
    }
    
    if (cart.length === 0) {
        alert('Cart is empty');
        return;
    }
    
    try {
        const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const paymentMethodEl = document.getElementById('paymentMethod');
        const paymentMethod = paymentMethodEl ? paymentMethodEl.value : 'cash';

        // Validate cash payment only if payment method is cash
        let change = 0;
        if (paymentMethod === 'cash') {
            if (!amountReceived || amountReceived < total) {
                alert('Amount received is less than total. Please enter a valid cash amount.');
                if (amountReceivedInput) amountReceivedInput.focus();
                return;
            }
            change = amountReceived - total;
        } else {
            // For GCash, set amount received and change to 0
            change = 0;
        }

        // Build payload for backend API (php/create-order.php)
        const payload = {
            items: cart.map(item => ({
                name: item.name,
                size: item.size,
                special: item.special,
                notes: item.notes,
                qty: item.qty,
                price: item.price
            })),
            customer_name: customerName,
            customer_username: '',
            customer_email: 'N/A',
            customer_phone: customerPhone || 'N/A',
            customer_notes: specialNotes,
            branch: branch,
            subtotal: total,
            tax: 0,
            total: total,
            amount_received: paymentMethod === 'cash' ? amountReceived : 0,
            change: change,
            payment_method: paymentMethod,
            order_type: 'Walk-in',
            user_id: null
        };

        // Call backend to create order
        let apiResult = null;
        if (typeof OrdersAPI !== 'undefined' && OrdersAPI && typeof OrdersAPI.create === 'function') {
            try {
                apiResult = await OrdersAPI.create(payload);
                console.log('Order saved to backend:', apiResult);
            } catch (apiError) {
                console.error('API Error:', apiError);
                // Check if it's a network error
                if (apiError.message && apiError.message.includes('Failed to fetch')) {
                    console.warn('Network error - API may be unreachable. Order will be saved to localStorage only.');
                    // Continue with localStorage save even if API fails
                } else {
                    throw apiError; // Re-throw other errors
                }
            }
        } else {
            console.warn('OrdersAPI not available, skipping backend save');
            if (typeof window !== 'undefined' && window.API_BASE_URL) {
                console.warn('API_BASE_URL:', window.API_BASE_URL);
            }
        }

        // Still keep localStorage in sync for dashboards/admin that read jessie_orders
        const orders = JSON.parse(localStorage.getItem('jessie_orders') || '[]');
        const now = new Date();
        const fallbackId = generateOrderId();

        const localOrder = {
            id: (apiResult && apiResult.data && apiResult.data.order_id) || fallbackId,
            order_db_id: (apiResult && apiResult.data && apiResult.data.order_db_id) || null,
            customerName: customerName,
            customerUsername: '',
            customerEmail: 'N/A',
            customerPhone: customerPhone || 'N/A',
            customerNotes: specialNotes,
            branch: branch,
            items: payload.items,
            subtotal: total,
            tax: 0,
            total: total.toFixed(2),
            amountReceived: paymentMethod === 'cash' ? amountReceived : 0,
            change: change,
            paymentMethod: paymentMethod,
            status: 'Pending',
            timestamp: Date.now(),
            date: now.toLocaleDateString(),
            time: now.toLocaleTimeString(),
            orderType: 'Walk-in',
            isGuest: true
        };

        orders.push(localOrder);
        localStorage.setItem('jessie_orders', JSON.stringify(orders));
        
        // Clear cart
        cart = [];
        updateCartUI();
        closeCheckoutModal();
        
        alert(`Order ${localOrder.id} placed successfully!`);
        
        // Redirect back to dashboard
        setTimeout(() => {
            window.location.href = 'cashier.php#orders';
        }, 1500);
    } catch (error) {
        console.error('Error saving order:', error);
        const message = (error && error.message) ? error.message : 'Unknown error';
        alert('Error saving order: ' + message);
    }
}

console.log('Manual order page loaded');
