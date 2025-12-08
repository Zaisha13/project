<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Thank You | Jessie Cane</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/shared.css" />
  <link rel="stylesheet" href="css/customer_dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .thank-you-container {
      min-height: 80vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      text-align: center;
    }
    .thank-you-icon {
      font-size: 80px;
      color: #146B33;
      margin-bottom: 20px;
    }
    .thank-you-title {
      font-size: 36px;
      font-weight: 700;
      color: #2E5D47;
      margin-bottom: 15px;
    }
    .thank-you-message {
      font-size: 18px;
      color: #666;
      margin-bottom: 30px;
      max-width: 600px;
    }
    .order-info {
      background: #f8f5e9;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 30px;
      max-width: 500px;
    }
    .order-info h3 {
      color: #2E5D47;
      margin-bottom: 10px;
    }
    .order-number {
      font-size: 24px;
      font-weight: 700;
      color: #146B33;
    }
    .action-buttons {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .btn {
      padding: 12px 30px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-block;
    }
    .btn-primary {
      background: #146B33;
      color: white;
    }
    .btn-primary:hover {
      background: #0f4f26;
    }
    .btn-secondary {
      background: #f0f0f0;
      color: #2E5D47;
    }
    .btn-secondary:hover {
      background: #e0e0e0;
    }
  </style>
</head>
<body>
  <!-- HEADER -->
  <header>
    <div class="left-header">
      <img src="../../assets/images/logo.png" id="drink_logo" alt="Jessie Cane Logo" onerror="this.style.display='none'">
    </div>
    <div class="right-header">
      <a href="../index.php" class="nav-btn">Portal</a>
      <a href="customer_dashboard.php" class="nav-btn">Home</a>
      <a href="drinks.php" class="nav-btn">Menu</a>
      <a href="profile.php" class="nav-btn">Profile</a>
      <a href="inquiry.php" class="nav-btn">Inquiry</a>
    </div>
  </header>

  <div class="thank-you-container">
    <div class="thank-you-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <h1 class="thank-you-title">Thank You for Your Order!</h1>
    <p class="thank-you-message">
      Your payment has been successfully processed. We've received your order and will prepare it shortly.
    </p>
    
    <?php
    $orderId = $_GET['order'] ?? '';
    if ($orderId):
    ?>
    <div class="order-info">
      <h3>Order Number</h3>
      <div class="order-number"><?php echo htmlspecialchars($orderId); ?></div>
      <p style="margin-top: 15px; color: #666; font-size: 14px;">
        You will receive an email confirmation shortly. You can track your order status in your profile.
      </p>
    </div>
    <?php endif; ?>
    
    <div class="action-buttons">
      <a href="customer_dashboard.php" class="btn btn-primary">
        <i class="fas fa-home"></i> Back to Home
      </a>
      <a href="drinks.php" class="btn btn-secondary">
        <i class="fas fa-shopping-cart"></i> Order More
      </a>
    </div>
  </div>

  <script src="../../assets/js/api-helper.js"></script>
  <script>
    // Clean up cart after successful payment
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('saved_cart_' + encodeURIComponent((JSON.parse(sessionStorage.getItem('currentUser') || '{}').email || '').toLowerCase()));
    }

    // Verify payment status when page loads
    (async function() {
      const urlParams = new URLSearchParams(window.location.search);
      const orderId = urlParams.get('order');
      const invoiceId = urlParams.get('invoice_id');

      if (orderId || invoiceId) {
        try {
          // Wait for API helper to load
          if (typeof getAPIBaseURL === 'undefined') {
            await new Promise(resolve => setTimeout(resolve, 500));
          }

          const apiBaseUrl = (typeof getAPIBaseURL === 'function') 
            ? getAPIBaseURL() 
            : (window.API_BASE_URL || 'http://localhost:8080/project/api/api');
          
          const verifyUrl = apiBaseUrl + '/payments/gcash-verify.php?' + 
            (orderId ? 'order_id=' + encodeURIComponent(orderId) : '');
          
          const response = await fetch(verifyUrl);
          const data = await response.json();
          
          if (data.success && data.data.status === 'Paid') {
            console.log('Payment verified successfully');
            // Payment is confirmed, status updated
          } else {
            console.log('Payment verification:', data);
          }
        } catch (error) {
          console.error('Error verifying payment:', error);
          // Don't show error to user as webhook should handle it
        }
      }
    })();
  </script>
</body>
</html>

