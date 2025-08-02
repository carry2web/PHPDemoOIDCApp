# Enhanced Logout System with Microsoft Graph API Integration

## Overview

The enhanced logout system provides comprehensive session termination with three levels of logout:

1. **Local Logout** - Standard session and cookie cleanup
2. **Graph API Logout** - Microsoft Graph API token revocation  
3. **Azure Platform Logout** - Full Microsoft identity platform logout

## Features

### 1. Local Session Cleanup
- Clears all PHP session variables
- Removes session cookies
- Destroys session completely
- Clears authentication-related cookies

### 2. Microsoft Graph API Integration
- **revokeSignInSessions**: Invalidates all refresh tokens and session cookies
- **invalidateAllRefreshTokens**: Forces reauthentication across all apps
- Requires `User.RevokeSessions.All` permission
- Automatic fallback between methods

### 3. Azure Identity Platform Logout
- Redirects to Microsoft logout endpoint
- Performs single sign-out (SSO)
- Tenant-specific logout URLs for B2B/B2C

## Usage

### Basic Logout
```php
// Standard local logout
header('Location: logout.php');
```

### Enhanced Graph API Logout
```php
// Local + Graph API token revocation
header('Location: logout.php?graph_logout=1');
```

### Complete Logout
```php
// All logout methods
header('Location: logout.php?complete_logout=1');
```

### Azure Platform Logout
```php
// Microsoft identity platform logout
header('Location: logout.php?azure_logout=1');
```

## API Endpoints

### Graph API Methods Used

1. **Revoke Sign-In Sessions**
   ```
   POST https://graph.microsoft.com/v1.0/users/{user-oid}/revokeSignInSessions
   ```
   - Invalidates all refresh tokens for the user
   - Resets signInSessionsValidFromDateTime to current time
   - Forces re-authentication on all applications

2. **Invalidate All Refresh Tokens**
   ```
   POST https://graph.microsoft.com/v1.0/users/{user-oid}/invalidateAllRefreshTokens
   ```
   - Alternative method for token revocation
   - Used as fallback if revokeSignInSessions fails

### Azure Logout Endpoints

**B2B Tenant (Agents)**
```
https://login.microsoftonline.com/{tenant-id}/oauth2/v2.0/logout?post_logout_redirect_uri={redirect-uri}
```

**B2C Tenant (Customers)**
```
https://{tenant-name}.ciamlogin.com/{tenant-name}.onmicrosoft.com/oauth2/v2.0/logout?post_logout_redirect_uri={redirect-uri}
```

## Requirements

### Graph API Permissions
- **User.RevokeSessions.All** (Application or Delegated)
- Admin consent required for application permissions
- Valid access token with sufficient scope

### Session Data Required
- `access_token`: For API authentication
- `userinfo['oid']` or `userinfo['sub']`: User object identifier
- `user_type`: For determining logout endpoint

## Error Handling

The system includes comprehensive error handling:

### Graph API Errors
- **401 Unauthorized**: Token expired or invalid
- **403 Forbidden**: Insufficient permissions
- **Network errors**: CURL timeout or connection issues

### Fallback Strategy
1. Try `revokeSignInSessions` first
2. Fall back to `invalidateAllRefreshTokens`
3. Continue with local logout if both fail
4. Log all attempts for debugging

## Testing

### Test Tools Available
- `tests/test_enhanced_logout.php` - Interactive logout testing
- `tests/session_cleanup.php` - Force cleanup utility
- `tests/test_logout.php` - Logout verification

### Test Scenarios
1. **Standard logout** - Verify session cleanup
2. **Graph API logout** - Test token revocation
3. **Permission errors** - Handle API access denied
4. **Network failures** - Test error handling
5. **Mixed scenarios** - Multiple logout types

## Implementation Notes

### Security Considerations
- Access tokens are cleared before API calls to prevent leakage
- All errors are logged but not exposed to users
- Graceful degradation when Graph API fails

### Performance
- 10-second timeout on Graph API calls
- Non-blocking logout (continues if API fails)
- Minimal user wait time

### Logging
All logout operations are logged with:
- User identification
- Logout method attempted
- Success/failure status
- Error details for debugging

## Configuration

### App Registration Requirements
1. **API Permissions**:
   - User.RevokeSessions.All
   - User.ReadWrite.All (if needed)

2. **Redirect URIs**:
   - Include logout redirect URI
   - Configure for both B2B and B2C tenants

3. **Front-channel Logout** (Optional):
   - Configure front-channel logout URL
   - Enable single sign-out across applications

## Troubleshooting

### Common Issues

1. **Graph API 403 Error**
   - Check User.RevokeSessions.All permission
   - Verify admin consent granted
   - Confirm access token scope

2. **Missing User OID**
   - Ensure 'oid' claim in ID token
   - Check userinfo endpoint response
   - Verify token contains user identifier

3. **Token Expiration**
   - Graph API calls use current access token
   - No refresh attempted during logout
   - Normal behavior for expired sessions

### Debug Information
Check logs in `data/` directory:
- `app-{date}.log` - Application events
- `debug-{date}.log` - Debug information
- `error-{date}.log` - Error details

## Future Enhancements

### Planned Features
1. **Front-channel logout** configuration
2. **Batch logout** for multiple users (admin)
3. **Logout audit trail** for compliance
4. **Background token cleanup** scheduled task

### Integration Options
1. **webhook notifications** for logout events
2. **SIEM integration** for security monitoring
3. **Multi-tenant logout** coordination
