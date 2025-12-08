<?php
// API Configuration
// Build BASE_URL dynamically so it works with different Apache ports (8080/8081)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('BASE_URL', $protocol . '://' . $_SERVER['HTTP_HOST'] . '/project');
define('API_BASE_URL', BASE_URL . '/api/api');

// Database Configuration (can be moved to separate file if needed)
define('DB_HOST', 'localhost');
define('DB_NAME', 'jessiecane');
define('DB_USER', 'root');  // XAMPP default
define('DB_PASS', '');      // XAMPP default (empty password)

// Xendit Configuration
define('XENDIT_API_URL', 'https://api.xendit.co/v2/invoices');
define('XENDIT_SECRET_KEY', 'YOUR_XENDIT_SECRET_KEY_HERE');
define('XENDIT_WEBHOOK_TOKEN', 'YOUR_WEBHOOK_TOKEN_HERE');

// PayMongo Configuration
define('PAYMONGO_SECRET_KEY', 'sk_live_EcNSvL5xN9UeTNseJfJLGLty'); // Live Secret Key
define('PAYMONGO_PUBLIC_KEY', 'pk_live_D8zvMEySLLPxz1KKnbmg96xS'); // Live Public Key
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');
define('PAYMONGO_WEBHOOK_SECRET', 'whsk_mNHzLiYfcA2FMhxkqrh6tEWc');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '327009869774-krq51e5rguin7fq17fmnrjq5quvtasct.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/api/api/auth/google-callback.php');
define('FRONTEND_DOMAIN', BASE_URL . '/public/customer');
?>

