<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manual Order - Walk-in Customer</title>
  <link rel="stylesheet" href="css/cashier.css">
  <link rel="stylesheet" href="css/manual-order.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="manual-order-page">
  <div class="app">

  <!-- PURPOSE: Sidebar navigation — primary dashboard navigation links and logout control -->
    <aside class="sidebar" aria-label="Main Navigation">
      <div>
        <div class="brand">
          <!-- Logo -->
          <img src="../../assets/images/logo_green.png" alt="Jessie Cane Logo" onerror="this.style.display='none'">
          <div>
            <h1>Jessie Cane SJDM</h1>
            <p style="font-size:12px;">Manual Order</p>
          </div>
        </div>

        <nav class="nav" id="nav">
          <a href="../index.php"><i class="fa-solid fa-arrow-left"></i><span>Back to Portal</span></a>
          <a href="cashier.php#orders"><i class="fa-solid fa-list-check"></i><span>Order Management</span></a>
          <a href="manual-order.php" class="active"><i class="fa-solid fa-mug-hot"></i><span>Manual Order (Walk-in)</span></a>
          <a href="cashier.php#inventory"><i class="fa-solid fa-boxes-stacked"></i><span>Inventory Management</span></a>
          <a href="cashier.php#reports"><i class="fa-solid fa-chart-line"></i><span>Sales Reporting</span></a>
          <a href="cashier.php#customers"><i class="fa-solid fa-people-group"></i><span>Customer Handling</span></a>
        </nav>
      </div>

      <div class="bottom">
        <div style="display:flex;gap:10px;align-items:center;flex-direction:column;">
          <div style="font-size:12px;color:var(--muted);text-align:center;">
            Signed in as <strong style="color:var(--green-700)" id="cashier-name">Cashier User</strong>
          </div>
          <button class="logout-btn" id="logoutBtn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
          </button>
        </div>
      </div>
    </aside>

    <!-- PURPOSE: Main content area — manual order interface for walk-in customers -->
    <main class="main">
      <header class="topbar">
        <div class="page-title">
          <h2>Manual Order - Walk-in Customer</h2>
          <p class="muted">Take orders for customers in-store</p>
        </div>
        <div class="right">
          <button class="btn btn--primary" onclick="clearCart()"><i class="fa-solid fa-trash"></i> Clear Cart</button>
        </div>
      </header>

      <div class="manual-order-container">
        <!-- Menu Section -->
        <div class="menu-section">
          <h3><i class="fa-solid fa-list"></i> Select Drinks</h3>
          <div class="menu-grid" id="menuGrid">
            <!-- Menu items will be populated by JavaScript -->
          </div>
        </div>

        <!-- Cart Section -->
        <div class="cart-section">
          <div class="cart-header">
            <h3><i class="fa-solid fa-cart-shopping"></i> Order Cart</h3>
            <span class="cart-count" id="cartCount">0 items</span>
          </div>

          <div class="cart-items" id="cartItems">
            <div class="empty-cart">
              <i class="fa-solid fa-cart-plus"></i>
              <p>No items in cart</p>
            </div>
          </div>

          <div class="cart-summary">
            <div class="summary-row total">
              <span>Total:</span>
              <span id="total">₱0.00</span>
            </div>
          </div>

          <button class="btn btn--primary btn-checkout" id="checkoutBtn" disabled>
            <i class="fa-solid fa-check"></i> Place Order
          </button>
        </div>
      </div>
    </main>

  </div>

  <!-- Customization Modal -->
  <div class="modal-backdrop" id="customizationModal" style="display: none;">
    <div class="modal-content modal-large">
      <div class="modal-header">
        <h3 id="modal-drink-name">Customize Drink</h3>
        <button class="modal-close" onclick="closeCustomizationModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="customization-layout">
          <div class="customization-left">
            <img id="modal-drink-image" src="" alt="Drink image" style="width: 100%; border-radius: 8px;">
          </div>
          <div class="customization-right">
            <div class="form-group">
              <label>Size *</label>
              <div class="size-buttons">
                <button class="size-btn active" data-size="Regular" onclick="selectSize('Regular')">Regular</button>
                <button class="size-btn" data-size="Tall" onclick="selectSize('Tall')">Tall</button>
              </div>
            </div>
            <div class="form-group">
              <label>Quantity *</label>
              <div class="qty-controls">
                <button class="qty-btn" onclick="decreaseQty()">-</button>
                <input type="number" id="modalQty" min="1" value="1" onchange="updateQty(this.value)">
                <button class="qty-btn" onclick="increaseQty()">+</button>
              </div>
            </div>
            <div class="form-group">
              <label>Special Instruction</label>
              <div class="special-buttons">
                <button class="special-btn active" data-special="None" onclick="selectSpecial('None')">None</button>
                <button class="special-btn" data-special="No Ice" onclick="selectSpecial('No Ice')">No Ice (+₱20)</button>
              </div>
            </div>
            <div class="form-group">
              <label for="modalNotes">Special Notes</label>
              <textarea id="modalNotes" rows="3" placeholder="Any additional instructions..."></textarea>
            </div>
            <div class="modal-price-preview">
              Price: <span id="modalPrice">₱0.00</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn--outline" onclick="closeCustomizationModal()">Cancel</button>
        <button class="btn btn--primary" onclick="confirmAddToCart()">
          <i class="fa-solid fa-check"></i> Add to Cart
        </button>
      </div>
    </div>
  </div>

  <!-- Checkout Modal -->
  <div class="modal-backdrop" id="checkoutModal" style="display: none;">
    <div class="modal-content modal-large">
      <div class="modal-header">
        <h3><i class="fa-solid fa-receipt"></i> Complete Order</h3>
        <button class="modal-close" onclick="closeCheckoutModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="customerForm">
          <div class="form-group">
            <label for="customerName">Customer Name *</label>
            <input type="text" id="customerName" placeholder="Enter customer name" required>
          </div>
          <div class="form-group">
            <label>Branch</label>
            <input type="text" id="branchDisplay" readonly style="background:#f5f5f5;">
          </div>
          <div class="form-group">
            <label for="paymentMethod">Payment Method *</label>
            <select id="paymentMethod" required onchange="handlePaymentMethodChange()">
              <option value="cash">Cash</option>
              <option value="gcash">GCash</option>
            </select>
          </div>
          <div class="form-group" id="cashFieldsGroup">
            <label for="amountReceived">Amount Received (Cash) *</label>
            <input type="number" id="amountReceived" min="0" step="0.01" placeholder="Enter cash received from customer">
          </div>
          <div class="form-group" id="changeFieldsGroup">
            <label for="changeAmount">Change</label>
            <input type="text" id="changeAmount" readonly style="background:#f5f5f5;" placeholder="₱0.00">
          </div>
          <div class="form-group">
            <label for="specialNotes">Special Notes</label>
            <textarea id="specialNotes" rows="3" placeholder="Any special instructions..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn--outline" onclick="closeCheckoutModal()">Cancel</button>
        <button class="btn btn--primary" onclick="placeManualOrder()">
          <i class="fa-solid fa-check"></i> Confirm Order
        </button>
      </div>
    </div>
  </div>

  <!-- API helper (for saving orders to backend) -->
  <!-- Note: api-config.js is NOT included here because it sets wrong path (/api/api instead of /php) -->
  <!-- api-helper.js handles dynamic URL detection automatically -->
  <script src="../../assets/js/api-helper.js"></script>
  <script src="js/manual-order.js"></script>
</body>
</html>

