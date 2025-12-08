# Google OAuth Setup Guide

This guide will help you set up Google OAuth authentication for the Jessie Cane Juice Bar application.

## Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click on the project dropdown at the top
3. Click "New Project"
4. Enter a project name (e.g., "Jessie Cane OAuth")
5. Click "Create"

## Step 2: Enable Google+ API

1. In your Google Cloud project, go to **APIs & Services** > **Library**
2. Search for "Google+ API" or "Google Identity Platform"
3. Click on it and click **Enable**

## Step 3: Create OAuth 2.0 Credentials

1. Go to **APIs & Services** > **Credentials**
2. Click **+ CREATE CREDENTIALS** > **OAuth client ID**
3. If prompted, configure the OAuth consent screen:
   - Choose **External** (unless you have a Google Workspace)
   - Fill in the required information:
     - App name: "Jessie Cane Juice Bar"
     - User support email: Your email
     - Developer contact information: Your email
   - Click **Save and Continue**
   - Skip scopes (click **Save and Continue**)
   - Add test users if needed (click **Save and Continue**)
   - Click **Back to Dashboard**

4. Create OAuth Client ID:
   - Application type: **Web application**
   - Name: "Jessie Cane Web Client"
   - Authorized JavaScript origins:
     - `http://localhost` (or your domain)
     - `http://localhost:8080` (if using XAMPP on port 8080)
   - Authorized redirect URIs:
     - `http://localhost/project/api/api/auth/google-callback.php`
     - `http://localhost:8080/project/api/api/auth/google-callback.php` (if using port 8080)
     - Add your production domain when deploying
   - Click **Create**

5. Copy your credentials:
   - **Client ID** (looks like: `123456789-abcdefghijklmnop.apps.googleusercontent.com`)
   - **Client Secret** (looks like: `GOCSPX-abcdefghijklmnopqrstuvwxyz`)

## Step 4: Configure the Application

1. Open `api/config.php` in your project
2. Replace the placeholder values:

```php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET_HERE');
```

With your actual credentials:

```php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '123456789-abcdefghijklmnop.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-abcdefghijklmnopqrstuvwxyz');
```

3. Save the file

## Step 5: Verify Redirect URI

The redirect URI is automatically generated from your `BASE_URL` in `api/config.php`. Make sure it matches exactly what you added in Google Cloud Console.

To check your redirect URI:
1. Open your browser's developer console
2. Try to log in with Google
3. Check the console logs - it will show the redirect URI that needs to be added to Google Cloud Console

The redirect URI should be in the format:
- `http://localhost/project/api/api/auth/google-callback.php` (default port 80)
- `http://localhost:8080/project/api/api/auth/google-callback.php` (port 8080)

## Step 6: Test the Integration

1. Start your XAMPP server (Apache)
2. Navigate to your application
3. Click "Continue with Google" button
4. You should be redirected to Google's login page
5. After logging in, you'll be redirected back to your application

## Troubleshooting

### Error: "Failed to initiate Google login: Server returned non-JSON response"

This usually means:
- Google OAuth credentials are not set in `api/config.php`
- The credentials are incorrect
- Check the browser console and server logs for more details

### Error: "redirect_uri_mismatch"

This means the redirect URI in your code doesn't match what's configured in Google Cloud Console:
1. Check the redirect URI shown in the browser console
2. Go to Google Cloud Console > Credentials > Your OAuth Client
3. Add the exact redirect URI shown in the console
4. Make sure there are no trailing slashes or extra characters

### Error: "invalid_client"

This means your Client ID or Client Secret is incorrect:
1. Double-check the values in `api/config.php`
2. Make sure there are no extra spaces or quotes
3. Regenerate the credentials in Google Cloud Console if needed

## Security Notes

- **Never commit** `api/config.php` with real credentials to version control
- Use environment variables in production
- Keep your Client Secret secure
- Regularly rotate your OAuth credentials
- Use HTTPS in production (Google requires HTTPS for production redirect URIs)

## Production Deployment

When deploying to production:

1. Update the redirect URI in Google Cloud Console to your production domain
2. Update `BASE_URL` in `api/config.php` to your production URL
3. Ensure your production server uses HTTPS
4. Update authorized JavaScript origins in Google Cloud Console

Example production redirect URI:
```
https://yourdomain.com/api/api/auth/google-callback.php
```

