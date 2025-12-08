<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jessie Cane Juicebar</title>
  <link rel="stylesheet" href="../assets/css/shared.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #fffde9 0%, #f0f9e8 100%);
      min-height: 100vh;
      overflow-x: hidden;
    }
    
    /* Header */
    .main-header {
      background: linear-gradient(135deg, #146B33 0%, #1a8a42 100%);
      padding: 15px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 20px rgba(20, 107, 51, 0.3);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    
    .logo-section {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .logo-section img {
      height: 50px;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }
    
    .logo-section h1 {
      color: #fff;
      font-size: 24px;
      font-weight: 800;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    }
    
    .header-nav {
      display: flex;
      gap: 15px;
    }
    
    .header-nav a {
      color: #fff;
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 25px;
      font-weight: 600;
      transition: all 0.3s ease;
      font-size: 14px;
    }
    
    .header-nav a:hover {
      background: rgba(255,255,255,0.2);
      transform: translateY(-2px);
    }
    
    .header-nav a.btn-login {
      background: #fff;
      color: #146B33;
    }
    
    .header-nav a.btn-login:hover {
      background: #f0f9e8;
      box-shadow: 0 4px 15px rgba(255,255,255,0.3);
    }
    
    /* Hero Section */
    .hero {
      position: relative;
      height: 500px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      overflow: hidden;
    }
    
    .hero-bg {
      position: absolute;
      inset: 0;
      background: url('../assets/images/BANNER.png') center/cover no-repeat;
      filter: brightness(0.7);
    }
    
    .hero-content {
      position: relative;
      z-index: 2;
      color: #fff;
      max-width: 800px;
      padding: 40px;
    }
    
    .hero h2 {
      font-size: 56px;
      font-weight: 900;
      margin-bottom: 20px;
      text-shadow: 3px 3px 6px rgba(0,0,0,0.4);
      line-height: 1.1;
    }
    
    .hero p {
      font-size: 20px;
      margin-bottom: 30px;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.4);
    }
    
    .hero-btn {
      display: inline-block;
      background: linear-gradient(135deg, #146B33, #1a8a42);
      color: #fff;
      padding: 15px 40px;
      border-radius: 50px;
      font-size: 18px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.3s ease;
      box-shadow: 0 6px 20px rgba(20, 107, 51, 0.4);
    }
    
    .hero-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(20, 107, 51, 0.5);
    }
    
    /* Portal Cards Section */
    .portals-section {
      padding: 80px 40px;
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .section-title {
      text-align: center;
      margin-bottom: 50px;
    }
    
    .section-title h2 {
      font-size: 36px;
      color: #146B33;
      margin-bottom: 10px;
    }
    
    .section-title p {
      color: #666;
      font-size: 16px;
    }
    
    .portal-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
    }
    
    .portal-card {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      transition: all 0.4s ease;
      text-decoration: none;
      display: block;
    }
    
    .portal-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 50px rgba(0,0,0,0.15);
    }
    
    .portal-card-header {
      padding: 40px 30px;
      text-align: center;
      color: #fff;
    }
    
    .portal-card-header.customer {
      background: linear-gradient(135deg, #146B33, #1a8a42);
    }
    
    .portal-card-header.cashier {
      background: linear-gradient(135deg, #6B21A8, #A855F7);
    }
    
    .portal-card-header.admin {
      background: linear-gradient(135deg, #8B4513, #A0522D);
    }
    
    .portal-card-header i {
      font-size: 48px;
      margin-bottom: 15px;
      display: block;
    }
    
    .portal-card-header h3 {
      font-size: 24px;
      font-weight: 700;
      margin: 0;
    }
    
    .portal-card-body {
      padding: 30px;
    }
    
    .portal-card-body p {
      color: #666;
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 20px;
    }
    
    .portal-card-body ul {
      list-style: none;
      margin-bottom: 25px;
    }
    
    .portal-card-body ul li {
      padding: 8px 0;
      color: #444;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .portal-card-body ul li i {
      color: #146B33;
      font-size: 12px;
    }
    
    .portal-btn {
      display: block;
      text-align: center;
      padding: 14px 30px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 15px;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .portal-btn.customer {
      background: linear-gradient(135deg, #146B33, #1a8a42);
      color: #fff;
    }
    
    .portal-btn.cashier {
      background: linear-gradient(135deg, #6B21A8, #A855F7);
      color: #fff;
    }
    
    .portal-btn.admin {
      background: linear-gradient(135deg, #8B4513, #A0522D);
      color: #fff;
    }
    
    .portal-btn:hover {
      opacity: 0.9;
      transform: scale(1.02);
    }
    
    /* Features Section */
    .features-section {
      background: #fff;
      padding: 80px 40px;
    }
    
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 40px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .feature-item {
      text-align: center;
      padding: 30px;
    }
    
    .feature-item i {
      font-size: 48px;
      color: #146B33;
      margin-bottom: 20px;
    }
    
    .feature-item h4 {
      font-size: 20px;
      color: #333;
      margin-bottom: 10px;
    }
    
    .feature-item p {
      color: #666;
      font-size: 14px;
      line-height: 1.6;
    }
    
    /* Footer */
    .main-footer {
      background: linear-gradient(135deg, #146B33, #0d4a23);
      color: #fff;
      padding: 40px;
      text-align: center;
    }
    
    .main-footer p {
      opacity: 0.9;
      font-size: 14px;
    }
    
    .footer-links {
      margin-top: 20px;
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
    }
    
    .footer-links a {
      color: #fff;
      text-decoration: none;
      font-size: 14px;
      opacity: 0.8;
      transition: opacity 0.3s ease;
    }
    
    .footer-links a:hover {
      opacity: 1;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .main-header {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
      }
      
      .hero h2 {
        font-size: 36px;
      }
      
      .hero p {
        font-size: 16px;
      }
      
      .portals-section {
        padding: 50px 20px;
      }
      
      .section-title h2 {
        font-size: 28px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="main-header">
    <div class="logo-section">
      <img src="../assets/images/logo.png" alt="Jessie Cane Logo" onerror="this.style.display='none'">
      <h1>Jessie Cane Juicebar</h1>
    </div>
    <nav class="header-nav">
      <a href="customer/customer_dashboard.php">Home</a>
      <a href="customer/drinks.php">Menu</a>
      <a href="customer/inquiry.php">Contact</a>
      <a href="customer/customer_dashboard.php" class="btn-login"><i class="fas fa-user"></i> Customer Login</a>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content">
      <h2>Fresh Sugarcane Drinks</h2>
      <p>Experience nature's purest sweetness with our refreshing sugarcane beverages</p>
      <a href="customer/drinks.php" class="hero-btn"><i class="fas fa-glass-water"></i> Order Now</a>
    </div>
  </section>

  <!-- Portal Selection -->
  <section class="portals-section">
    <div class="section-title">
      <h2>Select Your Portal</h2>
      <p>Choose the appropriate portal based on your role</p>
    </div>
    
    <div class="portal-grid">
      <!-- Customer Portal -->
      <a href="customer/customer_dashboard.php" class="portal-card">
        <div class="portal-card-header customer">
          <i class="fas fa-user"></i>
          <h3>Customer Portal</h3>
        </div>
        <div class="portal-card-body">
          <p>Browse our menu, place orders, and enjoy refreshing sugarcane drinks delivered to your location.</p>
          <ul>
            <li><i class="fas fa-check"></i> Browse drink menu</li>
            <li><i class="fas fa-check"></i> Place orders online</li>
            <li><i class="fas fa-check"></i> Track order status</li>
            <li><i class="fas fa-check"></i> Manage your profile</li>
            <li><i class="fas fa-check"></i> Book events & inquiries</li>
          </ul>
          <span class="portal-btn customer"><i class="fas fa-arrow-right"></i> Enter Customer Portal</span>
        </div>
      </a>

      <!-- Cashier Portal -->
      <a href="cashier/login_cashier.php" class="portal-card">
        <div class="portal-card-header cashier">
          <i class="fas fa-cash-register"></i>
          <h3>Cashier Portal</h3>
        </div>
        <div class="portal-card-body">
          <p>Access the unified cashier interface to manage transactions for your assigned branch.</p>
          <ul>
            <li><i class="fas fa-check"></i> Process customer orders</li>
            <li><i class="fas fa-check"></i> Handle GCash payments</li>
            <li><i class="fas fa-check"></i> Create manual orders</li>
            <li><i class="fas fa-check"></i> View daily transactions</li>
            <li><i class="fas fa-check"></i> Multi-branch support</li>
          </ul>
          <span class="portal-btn cashier"><i class="fas fa-arrow-right"></i> Enter Cashier Portal</span>
        </div>
      </a>

      <!-- Admin Portal -->
      <a href="admin/login_admin.php" class="portal-card">
        <div class="portal-card-header admin">
          <i class="fas fa-user-shield"></i>
          <h3>Admin Portal</h3>
        </div>
        <div class="portal-card-body">
          <p>Full administrative access to manage the entire Jessie Cane system and operations.</p>
          <ul>
            <li><i class="fas fa-check"></i> Manage all branches</li>
            <li><i class="fas fa-check"></i> User management</li>
            <li><i class="fas fa-check"></i> Product management</li>
            <li><i class="fas fa-check"></i> Sales reports & analytics</li>
            <li><i class="fas fa-check"></i> System configuration</li>
          </ul>
          <span class="portal-btn admin"><i class="fas fa-arrow-right"></i> Enter Admin Portal</span>
        </div>
      </a>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section">
    <div class="section-title">
      <h2>Why Choose Jessie Cane?</h2>
      <p>We bring you the freshest sugarcane drinks with exceptional service</p>
    </div>
    
    <div class="features-grid">
      <div class="feature-item">
        <i class="fas fa-leaf"></i>
        <h4>100% Natural</h4>
        <p>Our drinks are made from fresh, naturally harvested sugarcane with no artificial additives.</p>
      </div>
      <div class="feature-item">
        <i class="fas fa-bolt"></i>
        <h4>Instant Energy</h4>
        <p>Get a natural energy boost with our refreshing and revitalizing sugarcane beverages.</p>
      </div>
      <div class="feature-item">
        <i class="fas fa-mobile-alt"></i>
        <h4>Easy Ordering</h4>
        <p>Order online and pay conveniently through GCash for a seamless experience.</p>
      </div>
      <div class="feature-item">
        <i class="fas fa-store"></i>
        <h4>Multiple Branches</h4>
        <p>Visit us at SM Fairview or SM San Jose del Monte for fresh drinks anytime.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="main-footer">
    <p>&copy; 2025 Jessie Cane Juicebar. All rights reserved.</p>
    <div class="footer-links">
      <a href="customer/customer_dashboard.php">Home</a>
      <a href="customer/drinks.php">Menu</a>
      <a href="customer/inquiry.php">Contact Us</a>
      <a href="customer/profile.php">My Account</a>
    </div>
  </footer>
</body>
</html>

