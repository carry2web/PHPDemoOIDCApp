# PHP OIDC Demo

A minimal PHP application demonstrating OpenID Connect authentication using Microsoft Entra ID for external users, based on the [jumbojett/openid-connect-php](https://github.com/jumbojett/OpenID-Connect-PHP) library.

## Files

- `index.php`: Login page
- `callback.php`: Handles the OIDC authentication callback
- `dashboard.php`: Displays user claims and ID token after login
- `.env.example`: Sample environment configuration

## Installation

1. Install dependencies using Composer:

   ```bash
   composer require jumbojett/openid-connect-php
   ```

2. Copy the example `.env` file and fill in your configuration:

   ```bash
   cp .env.example .env
   ```

   Required fields:

   - `TENANT_ID`
   - `CLIENT_ID`
   - `CLIENT_SECRET`
   - `REDIRECT_URI` (see below)

3. All PHP files use `parse_ini_file()` to read the `.env` file, as shown in `callback.php`.

---

## Running the App

This application requires a **public HTTPS URL**, since Microsoft Entra ID does **not allow `http://localhost`** as a valid redirect URI. You have two options:

---

### 🔹 Option 1: Use ngrok (for local testing)

1. Start the PHP built-in server:

   ```bash
   php -S localhost:8000
   ```

2. Start ngrok (you must have ngrok installed and authenticated):

   ```bash
   ngrok http 8000
   ```

3. Copy the HTTPS forwarding address, for example:

   ```
   https://fancy-subdomain.ngrok.io
   ```

4. Set this as your `REDIRECT_URI` in the `.env` file:

   ```
   REDIRECT_URI=https://fancy-subdomain.ngrok.io/callback.php
   ```

5. Make sure to register **this exact URI** in your App Registration in the Azure Portal.

---

### 🔹 Option 2: Deploy to Azure Free Web App

1. Create a PHP Web App in Azure (Linux + PHP stack recommended).
2. Deploy your files using FTP, VS Code deploy, or GitHub Actions.
3. Upload the `.env` file with your credentials and config.
4. Use your Web App's domain in the `REDIRECT_URI`, e.g.:

   ```
   REDIRECT_URI=https://yourapp.azurewebsites.net/callback.php
   ```

5. Add this exact redirect URI to your App Registration in the Azure Portal.

---

## Usage

1. Visit the login page:

   - via ngrok: `https://fancy-subdomain.ngrok.io/index.php`
   - via Azure: `https://yourapp.azurewebsites.net/index.php`

2. You will be redirected to sign in using Microsoft Entra ID.

3. Upon successful login, the user info (claims) will be shown on `dashboard.php`.

---

## Notes

- Sessions are safely stored using a dedicated session directory (`lib/oidc.php` handles this).
- You can extend this demo to authorize by roles, groups, or email domains as needed.

## Reference

- For a very complete and extensive demo check out this repo:
https://github.com/microsoft/woodgrove-groceries