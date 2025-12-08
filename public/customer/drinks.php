  <!-- drinks.php -->

  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Jessie Cane | Menu</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/shared.css" />
  <link rel="stylesheet" href="css/drinks.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script defer src="../../assets/js/api-helper.js"></script>
  <script defer src="../../assets/js/header.js"></script>
  <script defer src="js/drinks.js"></script>
  </head>
  <body>
    <!-- PURPOSE: Customer Menu page — header, product grid, cart sidebar, and checkout modals -->
    <!-- HEADER -->
    <header>
      <div class="left-header">
        <img src="../../assets/images/logo.png" id="drink_logo" alt="Jessie Cane Logo">
      </div>
      <div class="right-header">
        <a href="../index.php" class="nav-btn">Portal</a>
        <a href="customer_dashboard.php" class="nav-btn">Home</a>
        <a href="drinks.php" class="nav-btn active">Menu</a>
        <a href="profile.php" class="nav-btn">Profile</a>
        <a href="inquiry.php" class="nav-btn">Inquiry</a>
        <a href="../index.php" class="nav-btn logout">Logout</a>
      </div>
    </header>

  <!-- PURPOSE: Main content — product listing and cart sidebar -->
  <!-- MAIN CONTENT -->
  <main class="main-content">
      <section class="container">
        <h2>Our Menu</h2>
        <div class="products" id="products"></div>
      </section>

  <!-- PURPOSE: Cart sidebar — current cart items, subtotal, and checkout actions -->
  <!-- CART SIDEBAR -->
  <aside class="cart">
        <div class="cart-top">
          <h3>My Cart</h3>
          <button id="clear-order" class="clear-order">🗑 Clear Order</button>
        </div>
        <ul id="cart-items"></ul>

        <div class="cart-bottom">
          <div class="cart-summary">
            <div class="cart-total-text">Total</div>
            <div id="total" class="cart-total-value">₱0</div>
          </div>
          <button id="checkout-btn" class="checkout-btn">Checkout</button>
        </div>
      </aside>
    </main>

    <!-- FOOTER -->
    <footer>
      <p>© 2025 Jessie Cane. All rights reserved.</p>
    </footer>

<!-- PURPOSE: Order modal — collects order options and verifies before adding to cart -->
<!-- ORDER MODAL -->
<div class="modal-backdrop hidden" id="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" id="order-modal">
        <button class="modal-close" id="modal-close" aria-label="Close">&times;</button>
        <div class="modal-body">
            <div class="modal-left">
                <img src="" alt="product image" id="modal-img">
            </div>
            <div class="modal-right">
                <h3 id="modal-name">Product Name</h3>
                <p id="modal-desc">product description</p>

                <div class="option-group">
                    <div class="option-label">Size</div>
          <div class="size-buttons">
            <button class="size-btn active" data-size="Regular">Regular</button>
            <button class="size-btn" data-size="Tall">Tall</button>
          </div>
                </div>

        <div class="option-group qty-group">
          <div class="option-label">Quantity</div>
          <div class="qty-controls">
            <button id="qty-decrease">−</button>
            <!-- Make quantity editable by typing. Use number input so mobile/desktop show numeric keyboard. -->
            <input id="qty-display" type="number" min="1" step="1" value="1" aria-label="Quantity" />
            <button id="qty-increase">+</button>
          </div>
        </div>

                <div class="option-group">
                    <div class="option-label">Special Instruction</div>
                    <div class="special-buttons">
                        <button class="special-btn active" data-special="None">None</button>
                        <button class="special-btn" data-special="No Ice">No Ice (+₱20/cup)</button>
                    </div>
                </div>

                <div class="option-group">
                    <label for="notes" class="option-label">Special Notes (optional)</label>
                    <textarea id="notes" placeholder="e.g. less ice"></textarea>
                </div>

        <div class="modal-actions">
          <div class="price-preview">Price: <span id="modal-price">₱0</span></div>
          <div class="modal-action-buttons">
            <button id="modal-cancel-btn" class="btn-secondary">Cancel</button>
            <button id="add-confirm-btn" class="add-confirm-btn">Place Order</button>
          </div>
        </div>
            </div>
        </div>
    </div>
</div>

    <div id="checkout-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Checkout</h2>
            <span class="close" onclick="closeCheckout()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="checkout-items"></div>
            <div class="checkout-summary">
                <div class="summary-row">
                    <span id="summary-label">Total:</span>
                    <span id="checkout-subtotal">₱0.00</span>
                </div>
                <div class="summary-row" id="checkout-tax-row" style="display:none;">
                    <span>Tax (8.5%):</span>
                    <span id="checkout-tax">₱0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="checkout-total">₱0.00</span>
                </div>
            </div>
            <div class="customer-info">
                <h3>Customer Information</h3>
                <label for="customer-name" class="field-label">Your Name *</label>
                <input type="text" id="customer-name" placeholder="Enter your full name" required>
                <label for="branch-select" class="field-label">Select Branch *</label>
                <select id="branch-select" required>
                    <option value="">Choose a branch</option>
                    <option value="SM San Jose del Monte">SM San Jose del Monte</option>
                    <option value="SM Fairview">SM Fairview</option>
                </select>
                <label for="customer-notes" class="field-label">Special Instructions (optional)</label>
                <textarea id="customer-notes" placeholder="Any additional instructions for your order..." rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeCheckout()" class="btn-secondary">Cancel</button>
            <button onclick="placeOrder()" class="btn-primary">Place Order</button>
        </div>
    </div>
</div>



  <!-- PURPOSE: Login modal — shown on checkout if user is not authenticated -->
  <!-- LOGIN MODAL (shown on Checkout when not logged in) -->
  <div id="login-modal" class="modal-backdrop hidden" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" id="login-dialog" style="max-width:560px; position:relative;">
      <button class="modal-close" id="login-close" aria-label="Close">&times;</button>
      <div class="modal-body" style="align-items:flex-start;">
        <div class="left-panel">
          <div class="headline">LOGIN</div>
          <div class="sub">You need to login before ordering a drink</div>
        </div>
        <div class="right-panel">
          <form id="inline-login-form" class="auth-form" onsubmit="return false;">
            <div class="form-field"><input id="inline-username" placeholder="Email or Username" /></div>
            <div class="form-field"><input id="inline-password" placeholder="Password" type="password" /></div>
            <div class="actions" style="margin-top:12px;">
              <button id="login-cancel" class="btn-secondary">Cancel</button>
              <button id="login-submit" class="btn-primary">Log In</button>
            </div>
            <div style="margin-top:12px;text-align:center;color:#546b5a;">or</div>
            <div style="margin-top:10px;text-align:center;"><button type="button" class="btn-link" onclick="window.location.href='customer_dashboard.php'">Register here</button></div>
            <div style="margin-top:16px;text-align:center;">
              <button id="guest-checkout" class="btn-guest">Continue as Guest</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- QR Code Modal for PayMongo -->
  <div class="modal-backdrop hidden" id="qrCodeModal" aria-hidden="true">
    <div class="qr-modal" role="dialog" aria-modal="true">
      <button class="qr-modal-close" id="qr-close" aria-label="Close">&times;</button>
      <div class="qr-modal-header">
        <div class="qr-icon-wrapper">
          <i class="fa-solid fa-qrcode"></i>
        </div>
        <h2>Scan QR Code to Pay</h2>
        <p class="qr-subtitle">Scan this QR code with your GCash app to complete payment</p>
      </div>
      <div class="qr-modal-body">
        <div id="qrcode-container" class="qr-code-wrapper">
          <div id="qrcode" class="qr-code-display"></div>
        </div>
        <div class="qr-order-info">
          <div class="qr-info-row">
            <span class="qr-info-label">Order ID:</span>
            <span class="qr-info-value" id="qr-order-id">-</span>
          </div>
          <div class="qr-info-row">
            <span class="qr-info-label">Amount:</span>
            <span class="qr-info-value" id="qr-amount">₱0.00</span>
          </div>
        </div>
        <a id="qr-payment-link" href="#" target="_blank" class="qr-pay-button">
          <i class="fa-solid fa-external-link"></i>
          <span>Or Pay Online</span>
        </a>
        <div class="qr-info-box">
          <i class="fa-solid fa-info-circle"></i>
          <p>Payment will be automatically verified. You can close this window after scanning.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- QR Code Library -->
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  </body>
  </html>
