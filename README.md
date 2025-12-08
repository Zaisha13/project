# Jessie Cane Juice Bar - Backend API

Complete PHP backend API for Jessie Cane Juice Bar web application.

## Setup Instructions

### 1. Database Setup

1. Open phpMyAdmin (usually at `http://localhost:8080/phpmyadmin`)
2. Import the SQL file: Execute `database-setup.sql` from the project root
3. The database `jessie_cane_db` will be created with all necessary tables and sample data

### 2. XAMPP Configuration

1. Copy the entire `jessie-cane-api` folder to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\jessie-cane-api\
   ```
   
2. Update database credentials in `config/database.php` if needed:
   ```php
   private $host = "localhost";
   private $db_name = "jessie_cane_db";
   private $username = "root";
   private $password = "";
   ```

### 3. API Configuration

#### Xendit Payment Integration

1. Register for a Xendit account at https://www.xendit.co/
2. Get your API key from the dashboard
3. Update `api/payments/xendit-create.php`:
   ```php
   define('XENDIT_SECRET_KEY', 'YOUR_XENDIT_SECRET_KEY_HERE');
   ```
4. Update webhook token in `api/payments/xendit-webhook.php`:
   ```php
   define('XENDIT_WEBHOOK_TOKEN', 'YOUR_WEBHOOK_TOKEN_HERE');
   ```

#### Google Authentication

1. Create a Google Cloud project at https://console.cloud.google.com/
2. Enable Google Identity Platform API
3. Create OAuth 2.0 credentials
4. Update `api/auth/google-auth.php` and `api/auth/google-callback.php`:
   ```php
   define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
   define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
   ```
5. Install Google API client (optional, required for Google auth):
   ```bash
   composer require google/apiclient
   ```

### 4. Access the API

Base URL: `http://localhost:8080/jessie-cane-api/api/`

## API Endpoints

### Authentication
- `POST /api/auth/login.php` - User login
- `POST /api/auth/register.php` - User registration
- `POST /api/auth/logout.php` - User logout
- `GET /api/auth/google-auth.php` - Get Google auth URL
- `GET /api/auth/google-callback.php` - Google OAuth callback

### Products
- `GET /api/products/get-all.php` - Get all products
- `GET /api/products/get.php?id={id}` - Get single product
- `POST /api/products/create.php` - Create product (admin)
- `PUT /api/products/update.php` - Update product (admin)
- `DELETE /api/products/delete.php?id={id}` - Delete product (admin)

### Orders
- `POST /api/orders/create.php` - Create new order
- `GET /api/orders/get-all.php` - Get all orders (with optional filters)
- `GET /api/orders/get.php?id={id}` - Get single order
- `PUT /api/orders/update-status.php` - Update order status

### Payments
- `POST /api/payments/xendit-create.php` - Create Xendit GCash payment
- `POST /api/payments/xendit-webhook.php` - Xendit webhook handler
- `GET /api/payments/gcash-verify.php` - Verify payment status

### Users
- `GET /api/users/profile.php?user_id={id}` - Get user profile
- `PUT /api/users/update.php` - Update user profile
- `GET /api/users/inquiries.php` - Get event inquiries
- `POST /api/users/inquiries.php` - Submit event inquiry

## Default Credentials

- **Admin Username:** `admin`
- **Admin Password:** `admin123`
- **Admin Email:** `admin@gmail.com`

## Testing

Test the API endpoints using:
- Postman
- cURL
- Your frontend application

Example login request:
```bash
curl -X POST http://localhost:8080/jessie-cane-api/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gmail.com","password":"admin123"}'
```

## Notes

- All endpoints return JSON responses
- CORS is enabled for all origins (update for production)
- Database passwords and API keys should be stored in environment variables in production
- Ensure PHP extensions are enabled: PDO, PDO_MySQL, OpenSSL, JSON, CURL, mbstring

