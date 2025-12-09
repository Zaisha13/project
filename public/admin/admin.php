<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Jessie Cane - Admin Dashboard</title>
  <link rel="stylesheet" href="css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
</head>
<body>
  <div class="app">

    <!-- PURPOSE: Sidebar navigation — primary dashboard navigation links and logout control -->
    <aside class="sidebar" id="admin-sidebar" aria-label="Main Navigation">
      <div>
        <div class="brand">
          <!-- Logo -->
          <img src="../../assets/images/logo_green.png" alt="Jessie Cane Logo" onerror="this.style.display='none'">
          <div>
            <h1>Jessie Cane <span class="admin-accent">Admin</span></h1>
            <p style="font-size:12px;">Administrative Dashboard</p>
          </div>
        </div>

        <nav class="nav" id="nav">
          <a href="../index.php"><i class="fa-solid fa-arrow-left"></i><span>Back to Portal</span></a>
          <a href="#" class="active" data-section="dashboard"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
          <a href="#" data-section="menu-management"><i class="fa-solid fa-utensils"></i><span>Menu Management</span></a>
          <a href="#" data-section="sales-report"><i class="fa-solid fa-chart-line"></i><span>Sales Report</span></a>
          <a href="#" data-section="profile"><i class="fa-solid fa-user"></i><span>Profile</span></a>
        </nav>
      </div>

      <div class="bottom">
        <div style="display:flex;gap:10px;align-items:center;flex-direction:column;">
          <div style="font-size:12px;color:var(--muted);text-align:center;">
            Signed in as <strong style="color:var(--green-700)" id="admin-name">Admin User</strong>
          </div>
          <button class="logout-btn" id="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
          </button>
        </div>
      </div>
    </aside>

    <!-- PURPOSE: Main content area — houses topbar, grid of dashboard cards (orders, inventory, reports, etc.) -->
    <main class="main">
      <header class="topbar">
        <div class="page-title">
          <h2 id="page-title">Dashboard</h2>
          <p class="muted">Admin Portal • Manage menu, reports, and system overview</p>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
        </div>
      </header>

      <section class="grid">

        <!-- Dashboard Section -->
        <div class="content-section active" id="dashboard">
          <div class="card col-12" style="margin-bottom:24px;">
            <div class="mb-6 p-4" style="background:#fef3c7;border-left:4px solid #f59e0b;color:#92400e;border-radius:8px;padding:14px 16px;">
              <p style="font-weight:bold;margin:0 0 4px 0;">Admin Dashboard</p>
              <p style="margin:0;font-size:13px;">Post announcements and view system overview</p>
            </div>
            
            <div class="grid" style="margin-top:12px;gap:18px;">
              <!-- Announcement Form -->
              <div class="card col-6">
                <h3>Post Announcement</h3>
                <form id="announcement-form" style="margin-top:12px;">
                  <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Announcement Title:</label>
                    <input type="text" id="announcement-title" placeholder="Enter announcement title" 
                      style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);">
                  </div>
                  <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Announcement Content:</label>
                    <textarea id="announcement-content" rows="4" placeholder="Enter announcement content" 
                      style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);resize:vertical;"></textarea>
                  </div>
                  <button type="submit" class="btn btn--primary" style="width:100%;">
                    <i class="fa-solid fa-bullhorn"></i> Post Announcement
                  </button>
                </form>
              </div>
              
              <!-- Recent Announcements -->
              <div class="card col-6">
                <h3>Recent Announcements</h3>
                <div id="announcements-list" style="margin-top:12px;max-height:400px;overflow-y:auto;">
                  <!-- Announcements will be populated here -->
                </div>
              </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid" style="margin-top:18px;gap:18px;">
              <div class="card col-4 stat-card stat--sjdm" id="sm-sjdm-card">
                <h3>Today's Order SM SJDM</h3>
                <div style="font-size:36px;font-weight:800;" id="sm-sjdm-count">0</div>
              </div>
              <div class="card col-4 stat-card stat--fairview" id="sm-fairview-card">
                <h3>Today's Order SM Fairview</h3>
                <div style="font-size:36px;font-weight:800;" id="sm-fairview-count">0</div>
              </div>
              <div class="card col-4 stat-card stat--total" id="total-orders-card">
                <h3>Today's Total Orders</h3>
                <div style="font-size:36px;font-weight:800;" id="total-orders-count">0</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Menu Management Section -->
        <div class="content-section" id="menu-management" style="display:none;">
          <div class="card col-12">
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
              <button id="add-item-btn" class="btn btn--primary">
                <i class="fa-solid fa-plus-circle"></i>
                Add New Item
              </button>
              
              <button id="edit-menu-btn" class="btn btn--outline">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Current Menu
              </button>
            </div>

            <div style="margin-bottom:24px;">
              <h3 style="border-bottom:2px solid var(--bg-light);padding-bottom:8px;">Current Menu Items View</h3>
              
              <div id="menu-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;margin-top:18px;">
                <!-- Menu items will be dynamically inserted here -->
              </div>
              
              <p id="no-menu-message" style="text-align:center;color:var(--muted);margin-top:24px;font-size:14px;display:none;">
                No menu items available yet. Please click "Add New Item" to begin!
              </p>
            </div>
          </div>
        </div>

        <!-- Profile Management Section -->
        <div class="content-section" id="profile" style="display:none;">
          <div class="card col-12">
            <div class="mb-6 p-4" style="background:#d1fae5;border-left:4px solid #10b981;color:#065f46;border-radius:8px;padding:14px 16px;">
              <p style="font-weight:bold;margin:0 0 4px 0;">Profile Management</p>
              <p style="margin:0;font-size:13px;">Update your admin account details</p>
            </div>
            
            <div class="grid" style="gap:18px;">
              <!-- Target account selector -->
              <div class="card col-12">
                <h3>Target Account</h3>
                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-top:12px;">
                  <div style="min-width:240px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Select Account</label>
                    <select id="account-target" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);cursor:pointer;">
                      <option value="admin">Administrator</option>
                      <option value="cashier_fairview">Cashier - SM Fairview</option>
                      <option value="cashier_sjdm">Cashier - SM San Jose del Monte</option>
                    </select>
                  </div>
                  <div style="flex:1;min-width:260px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Current Email</label>
                    <input type="email" id="current-email" readonly
                      style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);background:#f5f5f5;">
                  </div>
                </div>
              </div>
              <!-- Change Email -->
              <div class="card col-6">
                <h3>Change Email</h3>
                <form id="change-email-form" style="margin-top:12px;">
                  <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">New Email:</label>
                    <input type="email" id="new-email" placeholder="Enter new email" required
                      style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);">
                  </div>
                  <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Confirm New Email:</label>
                    <input type="email" id="confirm-email" placeholder="Confirm new email" required
                      style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);">
                  </div>
                  <button type="submit" class="btn btn--primary" style="width:100%;">
                    <i class="fa-solid fa-envelope"></i> Update Email
                  </button>
                </form>
              </div>
              
              <!-- Change Password -->
              <div class="card col-6">
                <h3>Change Password</h3>
                <form id="change-password-form" style="margin-top:12px;">
                  <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Current Password:</label>
                    <div style="position:relative;">
                      <input type="password" id="current-password" placeholder="Enter current password" required
                        style="width:100%;padding:10px 40px 10px 10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);">
                      <button type="button" id="toggle-admin-current-password" aria-label="Show/Hide password" 
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:transparent;border:none;color:var(--green-700);cursor:pointer;font-size:16px;">
                        <i class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">New Password:</label>
                    <div style="position:relative;">
                      <input type="password" id="new-password" placeholder="Enter new password" required
                        style="width:100%;padding:10px 40px 10px 10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);">
                      <button type="button" id="toggle-admin-new-password" aria-label="Show/Hide password" 
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:transparent;border:none;color:var(--green-700);cursor:pointer;font-size:16px;">
                        <i class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Confirm New Password:</label>
                    <div style="position:relative;">
                      <input type="password" id="confirm-password" placeholder="Confirm new password" required
                        style="width:100%;padding:10px 40px 10px 10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);">
                      <button type="button" id="toggle-admin-confirm-password" aria-label="Show/Hide password" 
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:transparent;border:none;color:var(--green-700);cursor:pointer;font-size:16px;">
                        <i class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                  </div>
                  <button type="submit" class="btn btn--primary" style="width:100%;">
                    <i class="fa-solid fa-lock"></i> Update Password
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Sales Report Section -->
        <div class="content-section" id="sales-report" style="display:none;">
          <div class="card col-12">
            <div class="mb-6 p-4" style="background:#e0e7ff;border-left:4px solid #6366f1;color:#3730a3;border-radius:8px;padding:14px 16px;">
              <p style="font-weight:bold;margin:0 0 4px 0;">Sales Report</p>
              <p style="margin:0;font-size:13px;">Consolidated and per-branch sales analytics</p>
            </div>

            <!-- Tabs -->
            <div class="card col-12" style="padding:0;">
              <div id="sales-tabs" style="display:flex;gap:8px;border-bottom:2px solid var(--bg-light);padding:8px 8px 0 8px;flex-wrap:wrap;">
                <button class="btn btn--outline small sales-tab active" data-tab="tab-consolidated" title="Overall view with live cashier dashboard">
                  <i class="fa-solid fa-layer-group"></i> Consolidated
                </button>
                <button class="btn btn--outline small sales-tab" data-tab="tab-daily">
                  <i class="fa-solid fa-calendar-day"></i> Daily
                </button>
                <button class="btn btn--outline small sales-tab" data-tab="tab-weekly">
                  <i class="fa-solid fa-calendar-week"></i> Weekly
                </button>
                <button class="btn btn--outline small sales-tab" data-tab="tab-monthly">
                  <i class="fa-solid fa-calendar-days"></i> Monthly
                </button>
                <button class="btn btn--outline small sales-tab" data-tab="tab-walkin">
                  <i class="fa-solid fa-person-walking"></i> Walk-in
                </button>
                <button class="btn btn--outline small sales-tab" data-tab="tab-online">
                  <i class="fa-solid fa-globe"></i> Online Customers
                </button>
                <button class="btn btn--outline small sales-tab" data-tab="tab-best-sellers">
                  <i class="fa-solid fa-chart-column"></i> Best Sellers
                </button>
              </div>

              <!-- Tab Panels -->
              <div id="tab-panels" style="padding:12px;">
                <!-- Consolidated Panel (existing cashier dashboard embed) -->
                <div id="tab-consolidated" class="sales-tab-panel" style="display:block;">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:12px;flex-wrap:wrap;">
                    <h3 style="margin:0;">Cashier Dashboard (Consolidated)</h3>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                      <div style="position:relative;display:flex;align-items:center;">
                        <i class="fa-solid fa-search" style="position:absolute;left:12px;color:var(--muted);font-size:14px;pointer-events:none;z-index:1;"></i>
                        <input type="text" id="consolidated-search-input" placeholder="Search by Order ID or Customer..." 
                          style="padding:10px 38px 10px 38px;border-radius:10px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);width:280px;min-width:200px;background:#fff;transition:border-color .2s ease,box-shadow .2s ease;">
                        <button id="clear-search-btn" type="button" style="position:absolute;right:8px;background:none;border:none;color:var(--muted);cursor:pointer;padding:4px;display:none;z-index:2;font-size:14px;" title="Clear search">
                          <i class="fa-solid fa-times-circle"></i>
                        </button>
                      </div>
                      <button id="refresh-cashier-dashboard-btn" class="btn btn--outline" title="Refresh dashboard" style="display:inline-flex;align-items:center;gap:8px;">
                        <i class="fa-solid fa-rotate-right"></i>
                        Refresh
                      </button>
                    </div>
              </div>
              <iframe id="cashier-dashboard-iframe" src="../cashier_fairview/cashier.php?adminView=1" title="Cashier Dashboard (Consolidated)" 
                    style="width:calc(100% + 36px);height:70vh;border:0;border-radius:12px;box-shadow:var(--shadow);margin-left:-18px;"></iframe>
              <p style="font-size:12px;color:var(--muted);margin-top:8px;">
                    <i class="fa-solid fa-info-circle"></i> Click refresh to load the latest data.
                  </p>
                </div>

                <!-- Daily Sales Panel -->
                <div id="tab-daily" class="sales-tab-panel" style="display:none;">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                    <h3 style="margin:0;">Daily Sales (Completed Orders Only)</h3>
                    <div style="display:flex;gap:8px;align-items:center;">
                      <input type="date" id="daily-date-input" style="padding:8px 10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);font-size:13px;" />
                      <button id="refresh-daily-btn" class="btn btn--outline" title="Refresh daily report"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                    </div>
                  </div>
                  <div class="card col-12">
                    <h4 style="margin:0 0 8px 0;">SM San Jose del Monte</h4>
                    <div id="daily-table-sjdm" class="table-wrapper"></div>
                    <div id="daily-total-sjdm" style="margin-top:8px;font-weight:700;color:var(--green-700);"></div>
                  </div>
                  <div class="card col-12" style="margin-top:16px;">
                    <h4 style="margin:0 0 8px 0;">SM Fairview</h4>
                    <div id="daily-table-fairview" class="table-wrapper"></div>
                    <div id="daily-total-fairview" style="margin-top:8px;font-weight:700;color:var(--green-700);"></div>
                  </div>
                  <div class="card col-12" style="margin-top:16px;">
                    <h4 style="margin:0 0 8px 0;">Consolidated</h4>
                    <div id="daily-table-consolidated" class="table-wrapper"></div>
                  </div>
                  <div class="card col-12" style="background:#f9fafb;">
                    <h4 style="margin:0 0 8px 0;">Consolidated Total</h4>
                    <div id="daily-total-consolidated" style="font-weight:800;color:var(--green-700);"></div>
                  </div>
                </div>

                <!-- Weekly Panel -->
                <div id="tab-weekly" class="sales-tab-panel" style="display:none;">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                    <h3 style="margin:0;">Weekly Sales (Completed)</h3>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                      <button id="refresh-weekly-btn" class="btn btn--outline" title="Refresh report"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                    </div>
                  </div>
                  <div class="card col-12" style="background:#f9fafb;">
                    <h4 style="margin:0 0 8px 0;">This Week</h4>
                    <div class="card col-12"><h5 style="margin:0 0 8px 0;">SM San Jose del Monte</h5><div id="weekly-table-sjdm"></div><div id="weekly-total-sjdm" style="margin-top:8px;font-weight:700;color:var(--green-700);"></div></div>
                    <div class="card col-12" style="margin-top:16px;"><h5 style="margin:0 0 8px 0;">SM Fairview</h5><div id="weekly-table-fairview"></div><div id="weekly-total-fairview" style="margin-top:8px;font-weight:700;color:var(--green-700);"></div></div>
                    <div class="card col-12" style="margin-top:16px;"><h5 style="margin:0 0 8px 0;">Consolidated</h5><div id="weekly-table-consolidated"></div><div id="weekly-total-consolidated" style="margin-top:8px;font-weight:800;color:var(--green-700);"></div></div>
                  </div>
                </div>

                <!-- Monthly Panel -->
                <div id="tab-monthly" class="sales-tab-panel" style="display:none;">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                    <h3 style="margin:0;">Monthly Sales (Completed)</h3>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                      <button id="refresh-monthly-btn" class="btn btn--outline" title="Refresh report"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                    </div>
                  </div>
                  <div class="card col-12" style="background:#f9fafb;">
                    <h4 style="margin:0 0 8px 0;">This Month</h4>
                    <div class="card col-12"><h5 style="margin:0 0 8px 0;">SM San Jose del Monte</h5><div id="monthly-table-sjdm"></div><div id="monthly-total-sjdm" style="margin-top:8px;font-weight:700;color:var(--green-700);"></div></div>
                    <div class="card col-12" style="margin-top:16px;"><h5 style="margin:0 0 8px 0;">SM Fairview</h5><div id="monthly-table-fairview"></div><div id="monthly-total-fairview" style="margin-top:8px;font-weight:700;color:var(--green-700);"></div></div>
                    <div class="card col-12" style="margin-top:16px;"><h5 style="margin:0 0 8px 0;">Consolidated</h5><div id="monthly-table-consolidated"></div><div id="monthly-total-consolidated" style="margin-top:8px;font-weight:800;color:var(--green-700);"></div></div>
                  </div>
                </div>

                <!-- Walk-in Panel -->
                <div id="tab-walkin" class="sales-tab-panel" style="display:none;">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                    <h3 style="margin:0;">Walk-in Orders</h3>
                    <button id="refresh-walkin-btn" class="btn btn--outline" title="Refresh">
                      <i class="fa-solid fa-rotate-right"></i> Refresh
                    </button>
                  </div>
                  <div class="card col-12"><h4 style="margin:0 0 8px 0;">All Walk-in Orders</h4><div id="walkin-table"></div></div>
                </div>

                <!-- Online Customers Panel -->
                <div id="tab-online" class="sales-tab-panel" style="display:none;">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                    <h3 style="margin:0;">Online Customers</h3>
                    <button id="refresh-online-btn" class="btn btn--outline" title="Refresh">
                      <i class="fa-solid fa-rotate-right"></i> Refresh
                    </button>
                  </div>
                  <div class="card col-12"><h4 style="margin:0 0 8px 0;">All Online Orders</h4><div id="online-table"></div></div>
                </div>

                <!-- Best Sellers Panel -->
                <div id="tab-best-sellers" class="sales-tab-panel" style="display:none;">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                    <h3 style="margin:0;">Best Selling Drinks</h3>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                      <button id="refresh-best-sellers-btn" class="btn btn--outline" title="Refresh best sellers"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                    </div>
                  </div>
                  <div class="grid" style="gap:18px;">
                    <div class="card col-6">
                      <h4 style="margin:0 0 8px 0;">Top Items (By Quantity)</h4>
                      <div id="best-sellers-table"></div>
                    </div>
                    <div class="card col-6">
                      <h4 style="margin:0 0 8px 0;">Chart</h4>
                      <canvas id="best-sellers-chart" width="400" height="260" style="background:#fff;border:1px solid rgba(0,0,0,0.06);border-radius:8px;position:relative;"></canvas>
                      <div id="best-sellers-legend" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:10px;font-size:12px;color:#111827;"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </section>
    </main>

  </div>

  <!-- Menu Modal -->
  <div id="menu-modal" class="modal" style="display:none;">
    <div class="modal-content">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:2px solid var(--bg-light);padding-bottom:16px;">
        <h2 style="margin:0;color:var(--green-700);font-size:24px;" id="modal-title">Menu Item</h2>
        <button id="close-modal-btn" style="background:none;border:none;font-size:32px;color:var(--green-700);cursor:pointer;line-height:1;">&times;</button>
      </div>
      
      <form id="add-item-form" data-editing-id="-1" style="margin-bottom:24px;">
        <input type="hidden" id="item-image-data" value="">

        <input type="text" id="item-name" placeholder="Item Name (e.g., Lychee Cane)" required
          style="width:100%;padding:12px;margin-bottom:12px;border-radius:10px;border:1px solid rgba(0,0,0,0.1);font-size:14px;font-family:var(--font-primary);">
        
        <textarea id="item-description" placeholder="Description (e.g., Tropical sweetness meets cane juice)" required
          style="width:100%;padding:12px;margin-bottom:12px;border-radius:10px;border:1px solid rgba(0,0,0,0.1);font-size:14px;font-family:var(--font-primary);resize:vertical;" rows="3"></textarea>
        
        <!-- Price Inputs for Each Size -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div>
            <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Regular Size Price (₱):</label>
            <input type="number" id="item-price-regular" placeholder="0.00" step="0.01" min="0" required
              style="width:100%;padding:12px;border-radius:10px;border:1px solid rgba(0,0,0,0.1);font-size:14px;font-family:var(--font-primary);">
          </div>
          <div>
            <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;">Tall Size Price (₱):</label>
            <input type="number" id="item-price-tall" placeholder="0.00" step="0.01" min="0" required
              style="width:100%;padding:12px;border-radius:10px;border:1px solid rgba(0,0,0,0.1);font-size:14px;font-family:var(--font-primary);">
          </div>
        </div>
        
        <label style="display:block;margin-bottom:4px;font-size:13px;color:var(--green-700);font-weight:600;" id="image-label">Upload Item Photo:</label>
        <input type="file" id="item-image" accept="image/*"
          style="width:100%;padding:10px;margin-bottom:12px;border-radius:10px;border:1px solid rgba(0,0,0,0.1);font-size:13px;font-family:var(--font-primary);cursor:pointer;">
        
        <button type="submit" id="save-item-btn" class="btn btn--primary" style="width:100%;margin-bottom:12px;">
          <i class="fa-solid fa-save"></i> Save Item
        </button>

        <button type="button" id="delete-item-on-edit-btn" class="btn btn--outline" style="width:100%;background:#ef4444;color:#fff;border-color:#dc2626;display:none;">
          <i class="fa-solid fa-trash-can"></i> Delete This Item
        </button>
      </form>

      <div id="current-menu-container" style="background:var(--bg-light);padding:18px;border-radius:16px;border:2px solid var(--green-600);display:none;">
        <h3 style="margin:0 0 16px 0;color:var(--green-700);font-size:18px;">Current Menu List</h3>
        <ul id="menu-list" style="list-style:none;padding:0;margin:0;">
          <!-- Menu items for editing will be populated here -->
        </ul>
      </div>
    </div>
  </div>

  <!-- Order Details Modal -->
  <div id="order-details-modal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:800px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:2px solid var(--bg-light);padding-bottom:16px;">
        <h2 style="margin:0;color:var(--green-700);font-size:24px;" id="order-modal-title">Order Details</h2>
        <button id="close-order-modal-btn" style="background:none;border:none;font-size:32px;color:var(--green-700);cursor:pointer;line-height:1;">&times;</button>
      </div>
      
      <div id="order-details-content">
        <!-- Order details will be populated here -->
      </div>
    </div>
  </div>

  <script src="../../assets/js/api-helper.js"></script>
  <script>
    // Add db_connect path for API calls
    const API_BASE = '';
  </script>
  <script src="js/admin.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</body>
</html>
