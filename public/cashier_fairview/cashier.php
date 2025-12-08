<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Jessie Cane Juicebar Branch Dashboard</title>
  <link rel="stylesheet" href="css/cashier.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/shared.css">
</head>
<body>
  <div class="app">

  <!-- PURPOSE: Sidebar navigation — primary dashboard navigation links and logout control -->
    <aside class="sidebar" aria-label="Main Navigation">
      <div>
        <div class="brand">
          <!-- Logo -->
          <img src="../../assets/images/logo_green.png" alt="Jessie Cane Logo" onerror="this.style.display='none'">
          <div>
            <h1>Jessie Cane Fairview</h1>
            <p style="font-size:12px;" id="branch-name-display">Branch Dashboard</p>
          </div>
        </div>

        <nav class="nav" id="nav">
          <a href="../index.php"><i class="fa-solid fa-arrow-left"></i><span>Back to Portal</span></a>
          <a href="#orders" class="active" data-target="orders"><i class="fa-solid fa-list-check"></i><span>Order Management</span></a>
          <a href="manual-order.php"><i class="fa-solid fa-mug-hot"></i><span>Manual Order (Walk-in)</span></a>
          <a href="#inventory" data-target="inventory"><i class="fa-solid fa-boxes-stacked"></i><span>Inventory Management</span></a>
          <a href="#reports" data-target="reports"><i class="fa-solid fa-chart-line"></i><span>Sales Reporting</span></a>
          <a href="#customers" data-target="customers"><i class="fa-solid fa-people-group"></i><span>Customer Handling</span></a>
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

    <!-- PURPOSE: Main content area — houses topbar, grid of dashboard cards (orders, inventory, reports, etc.) -->
    <main class="main">
      <header class="topbar">
        <div class="page-title">
          <h2>Jessie Cane Juicebar Branch Dashboard</h2>
          <p class="muted">Restricted Access • Manage orders, inventory, and reports</p>
        </div>
      </header>

      <section class="grid">

  <!-- PURPOSE: Orders card — list of incoming orders, actions (approve/cancel), and quick controls -->
  <div class="card col-12" id="orders">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div>
              <h3 style="margin:0">Order Management</h3>
              <p class="muted" style="margin:4px 0 0 0;">Approve/cancel orders, update status.</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <button id="clearOrdersBtn" class="btn btn--outline small" style="background:#fff;" disabled title="This feature is disabled for cashiers">Clear Order List</button>
            </div>
          </div>

          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer Type</th>
                  <th>Customer</th>
                  <th>Drink(s)</th>
                  <th>Size</th>
                  <th>Special Instruction</th>
                  <th>Status</th>
                  <th>Actions</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody id="ordersTbody">
                <tr id="no-orders" style="display:none;">
                  <td colspan="9" class="muted">No orders available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

  <!-- PURPOSE: Inventory card — manage stock in/out and generate branch reports -->
  <div class="card col-6" id="inventory" style="margin-top:6px;">
          <h3>Inventory Management</h3>
          <p class="muted">Stock-in/out, auto/manual updates, branch reports.</p>

          <!-- Ingredients Inventory Update Section -->
          <div style="margin-top:16px; padding:16px; background:var(--bg-light); border-radius:10px; border:1px solid rgba(0,0,0,0.05);">
            <h4 style="margin:0 0 12px 0; font-size:14px; font-weight:700; color:var(--green-700);">Ingredients Inventory Update</h4>
            <form id="ingredientsInventoryForm" style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
              <div style="flex:1; min-width:150px;">
                <label class="muted" style="display:block; margin-bottom:4px; font-size:12px;">Ingredient Name</label>
                <input id="ingredientInput" type="text" placeholder="e.g., Lemon, Yakult, Watermelon..." 
                       style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1); font-size:13px;" required />
              </div>
              <div style="flex:1; min-width:140px;">
                <label class="muted" style="display:block; margin-bottom:4px; font-size:12px;">Status</label>
                <select id="ingredientStatus" 
                       style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1); font-size:13px; cursor:pointer;">
                  <option value="Out of Stock">Out of Stock</option>
                  <option value="Low Stock">Low Stock</option>
                  <option value="Spoiled">Spoiled</option>
                </select>
              </div>
              <div style="flex:0;">
                <button type="submit" class="btn btn--primary small" style="white-space:nowrap;">
                  <i class="fa-solid fa-plus"></i> Add
                </button>
              </div>
            </form>
            <p class="muted" style="margin:8px 0 0 0; font-size:11px;">
              <i class="fa-solid fa-info-circle"></i> Report ingredient inventory issues to notify management
            </p>
          </div>
          <!-- Items Inventory Update Section -->
          <div style="margin-top:16px; padding:16px; background:var(--bg-light); border-radius:10px; border:1px solid rgba(0,0,0,0.05);">
            <h4 style="margin:0 0 12px 0; font-size:14px; font-weight:700; color:var(--green-700);">Items Inventory Update</h4>
            <form id="itemsInventoryForm" style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
              <div style="flex:1; min-width:150px;">
                <label class="muted" style="display:block; margin-bottom:4px; font-size:12px;">Item</label>
                <select id="itemInput" 
                       style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1); font-size:13px; cursor:pointer;" required>
                  <option value="">Select an item...</option>
                  <option value="Cups">Cups</option>
                  <option value="Tissue">Tissue</option>
                  <option value="Straws">Straws</option>
                </select>
              </div>
              <div style="flex:1; min-width:140px;">
                <label class="muted" style="display:block; margin-bottom:4px; font-size:12px;">Status</label>
                <select id="itemStatus" 
                       style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1); font-size:13px; cursor:pointer;">
                  <option value="Out of Stock">Out of Stock</option>
                  <option value="Low Stock">Low Stock</option>
                  <option value="Defective">Defective</option>
                </select>
              </div>
              <div style="flex:0;">
                <button type="submit" class="btn btn--primary small" style="white-space:nowrap;">
                  <i class="fa-solid fa-plus"></i> Add
                </button>
              </div>
            </form>
            <p class="muted" style="margin:8px 0 0 0; font-size:11px;">
              <i class="fa-solid fa-info-circle"></i> Report item inventory issues to notify management
            </p>
          </div>
        </div>

  <!-- Sales Report card -->
  <div class="card col-6" id="reports" style="margin-top:6px;padding:14px 16px;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
            <div>
              <h3 style="margin:0 0 4px 0;">Sales Reporting</h3>
              <p class="muted" style="margin:0;font-size:12px;">Daily summary reports.</p>
            </div>
            <button class="btn btn--primary small" onclick="viewDetailedReport()" style="flex-shrink:0;"><i class="fa-solid fa-chart-pie"></i> View Detailed Report</button>
          </div>

          <div style="margin-top:10px;">
            <div style="font-size:32px;font-weight:800;color:var(--green-700);line-height:1.2;margin-bottom:4px;"><span id="total-sales">₱0.00</span></div>
            <div class="muted" style="font-size:12px;margin-bottom:10px;">Total Sales Today</div>
            <div style="font-size:12px; color:var(--muted);line-height:1.6;">
              Orders: <strong id="total-orders">0</strong>
              &nbsp;•&nbsp; Pending: <strong id="pending-orders">0</strong>
              &nbsp;•&nbsp; Approved: <strong id="approved-orders">0</strong>
              &nbsp;•&nbsp; Out for Delivery: <strong id="ofd-orders">0</strong>
              &nbsp;•&nbsp; Completed: <strong id="completed-orders">0</strong>
              &nbsp;•&nbsp; Cancelled: <strong id="cancelled-orders">0</strong>
            </div>
          </div>
        </div>

  <!-- Full-width Current Inventory Items table (spans both columns) -->
  <div class="card col-12" id="inventory-list" style="margin-top:6px;">
    <h3>Inventory Status Log</h3>
    <div class="table-wrapper" style="margin-top:12px;">
      <table>
        <thead>
          <tr><th>Item</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr>
        </thead>
        <tbody id="inventoryTbody">
          <tr id="no-inventory" style="display:none;">
            <td colspan="4" class="muted">No inventory items logged yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div style="margin-top:12px; display:flex; gap:8px;">
      <button class="btn btn--outline small" onclick="clearInventory()"><i class="fa-solid fa-trash"></i> Clear All</button>
      <button class="btn btn--primary small" onclick="generateInventoryReport()"><i class="fa-solid fa-file-lines"></i> Generate Inventory Report</button>
    </div>
  </div>

  <!-- PURPOSE: Customers card — view basic customer information and quick lookups -->
  <div class="card col-12" id="customers" style="margin-top:6px;">
          <h3>Customer Handling</h3>
          <p class="muted">View customer details per order (walk-in, registered users, and guests).</p>

          <div class="table-wrapper" style="margin-top:12px;">
            <table>
              <thead>
                <tr><th>Order ID</th><th>Name</th><th>Email</th><th>Phone Number</th><th>Customer Type</th></tr>
              </thead>
              <tbody id="customersTbody">
                <tr id="no-customers" style="display:none;">
                  <td colspan="5" class="muted">No customer orders available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </section>
    </main>

  </div>

  <!-- Detailed Report Modal -->
  <div id="detailedReportModal" class="report-modal" style="display: none;">
    <div class="report-modal-content">
      <div class="report-modal-header">
        <h2><i class="fa-solid fa-chart-pie"></i> Detailed Sales Report</h2>
        <button class="report-modal-close" id="closeReportModal">&times;</button>
      </div>
      <div class="report-modal-body" id="reportModalBody">
        <!-- Content will be populated by JavaScript -->
      </div>
      <div class="report-modal-footer">
        <button class="btn btn--primary" onclick="closeDetailedReport()">Close</button>
        <button class="btn btn--outline" onclick="printDetailedReport()"><i class="fa-solid fa-print"></i> Print</button>
      </div>
    </div>
  </div>

  <!-- Inventory Report Modal -->
  <div id="inventoryReportModal" class="report-modal" style="display: none;">
    <div class="report-modal-content">
      <div class="report-modal-header">
        <h2><i class="fa-solid fa-boxes-stacked"></i> Inventory Report</h2>
        <button class="report-modal-close" id="closeInventoryReportModal">&times;</button>
      </div>
      <div class="report-modal-body" id="inventoryReportModalBody">
        <!-- Content will be populated by JavaScript -->
      </div>
      <div class="report-modal-footer">
        <button class="btn btn--primary" onclick="closeInventoryReport()">Close</button>
        <button class="btn btn--outline" onclick="printInventoryReport()"><i class="fa-solid fa-print"></i> Print</button>
      </div>
    </div>
  </div>

  <script src="../../assets/js/api-helper.js"></script>
  <script src="js/cashier.js"></script>
</body>
</html>