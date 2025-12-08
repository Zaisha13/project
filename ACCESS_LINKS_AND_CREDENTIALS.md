# Jessie Cane - Access Links and Credentials

## Base URL
- **Local Development:** `http://localhost/project/` or `http://localhost:8080/project/`
- **Note:** Adjust the port number (8080) if your XAMPP uses a different port

## ⚡ Quick Access - Unified Cashier Portal
**Recommended:** Use the unified cashier portal for all branches
- **Login:** `http://localhost/project/public/cashier/login_cashier.php`
- Supports both SM Fairview and SM San Jose del Monte branches
- Automatically redirects to the correct branch dashboard after login

---

## 🏠 Customer Portal

### Homepage
```
http://localhost/project/public/index.php
```
or
```
http://localhost/project/public/customer/customer_dashboard.php
```

### Login/Registration
- **Login Page:** `http://localhost/project/public/login.php`
- **Register Page:** `http://localhost/project/public/register.php`
- **Customer Dashboard:** `http://localhost/project/public/customer/customer_dashboard.php`

### Credentials
- **Note:** Customers must register their own accounts. No default customer credentials are provided.
- Customers can also use **Google OAuth** for authentication.

---

## 👨‍💼 Admin Portal

### Homepage
```
http://localhost/project/public/admin/admin.php
```

### Login Page
```
http://localhost/project/public/admin/login_admin.php
```

### Credentials
- **Email:** `admin@jessiecane.com` (or `admin@gmail.com` or `admin`)
- **Password:** `admin123`

---

## 💰 Unified Cashier Portal (Recommended)

### Login Page
```
http://localhost/project/public/cashier/login_cashier.php
```

### Credentials
The unified portal supports both branches. Use the appropriate credentials based on your branch:

#### SM Fairview Branch
- **Email:** `cashierfairview@gmail.com`
- **Password:** `cashierfairview`
- **After login:** Redirects to `http://localhost/project/public/cashier_fairview/cashier.php`

#### SM San Jose del Monte Branch
- **Email:** `cashiersjdm@gmail.com`
- **Password:** `cashiersjdm`
- **After login:** Redirects to `http://localhost/project/public/cashier_sjdm/cashier.php`

### Features
- Single login portal for all branches
- Automatic branch detection and redirection
- Multi-branch support

---

## 💰 Branch-Specific Cashier Portals (Alternative)

### SM Fairview - Direct Access
- **Homepage:** `http://localhost/project/public/cashier_fairview/cashier.php`
- **Login:** `http://localhost/project/public/cashier_fairview/login_cashier.php`
- **Email:** `cashierfairview@gmail.com`
- **Password:** `cashierfairview`

### SM San Jose del Monte - Direct Access
- **Homepage:** `http://localhost/project/public/cashier_sjdm/cashier.php`
- **Login:** `http://localhost/project/public/cashier_sjdm/login_cashier.php`
- **Email:** `cashiersjdm@gmail.com`
- **Password:** `cashiersjdm`

---

## 📋 Quick Reference Table

| Portal | Homepage | Login Page | Email | Password |
|--------|----------|------------|-------|----------|
| **Customer** | `/public/index.php` | `/public/login.php` | Register required | Register required |
| **Admin** | `/public/admin/admin.php` | `/public/admin/login_admin.php` | `admin@jessiecane.com` | `admin123` |
| **Cashier (Unified)** | Auto-redirects to branch | `/public/cashier/login_cashier.php` | `cashierfairview@gmail.com` or `cashiersjdm@gmail.com` | `cashierfairview` or `cashiersjdm` |
| **Cashier (Fairview)** | `/public/cashier_fairview/cashier.php` | `/public/cashier_fairview/login_cashier.php` | `cashierfairview@gmail.com` | `cashierfairview` |
| **Cashier (SJDM)** | `/public/cashier_sjdm/cashier.php` | `/public/cashier_sjdm/login_cashier.php` | `cashiersjdm@gmail.com` | `cashiersjdm` |

---

## 🔗 Full URLs (Example with localhost:8080)

### Customer
- **Homepage:** `http://localhost:8080/project/public/index.php`
- **Login:** `http://localhost:8080/project/public/login.php`
- **Register:** `http://localhost:8080/project/public/register.php`
- **Dashboard:** `http://localhost:8080/project/public/customer/customer_dashboard.php`

### Admin
- **Homepage:** `http://localhost:8080/project/public/admin/admin.php`
- **Login:** `http://localhost:8080/project/public/admin/login_admin.php`

### Cashier - Unified Portal (Recommended)
- **Login:** `http://localhost:8080/project/public/cashier/login_cashier.php`
- **SM Fairview Credentials:** `cashierfairview@gmail.com` / `cashierfairview`
- **SM SJDM Credentials:** `cashiersjdm@gmail.com` / `cashiersjdm`

### Cashier - Branch-Specific (Alternative)
- **SM Fairview Homepage:** `http://localhost:8080/project/public/cashier_fairview/cashier.php`
- **SM Fairview Login:** `http://localhost:8080/project/public/cashier_fairview/login_cashier.php`
- **SM SJDM Homepage:** `http://localhost:8080/project/public/cashier_sjdm/cashier.php`
- **SM SJDM Login:** `http://localhost:8080/project/public/cashier_sjdm/login_cashier.php`

---

## 📝 Notes

1. **Port Configuration:** If your XAMPP runs on a different port (not 8080), replace `8080` with your actual port number in the URLs above.

2. **Customer Registration:** Customers need to create their own accounts. There are no default customer credentials.

3. **Google OAuth:** Customers can also authenticate using Google OAuth if configured.

4. **Database:** Ensure your MySQL database is running and the `jessiecane` database is properly set up.

5. **Security:** These are development credentials. Change all passwords in production!

---

## 🚀 Quick Start

1. Start XAMPP (Apache and MySQL)
2. Navigate to `http://localhost/project/public/index.php` (or with your port)
3. Choose your portal from the homepage
4. Use the credentials above to log in

---

**Last Updated:** 2025-01-27

