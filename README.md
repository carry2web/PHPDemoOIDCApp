# PHP OIDC Demo

A minimal PHP project demonstrating OpenID Connect authentication using the Jumbojett/openid-connect-php library.

## Files
- `index.php`: Login page
- `callback.php`: Handles OIDC callback
- `dashboard.php`: Shows user claims and ID token after login

## Setup
1. Install dependencies:
   ```shell
   composer require jumbojett/openid-connect-php
   ```
2. Configure your OIDC provider, client ID, and secret in all PHP files.
3. Run with a local PHP server:
   ```shell
   php -S localhost:80
   ```

## Usage
- Visit `http://localhost/index.php` and log in.
- After authentication, view user details on the dashboard.
