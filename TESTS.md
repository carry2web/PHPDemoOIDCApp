# TESTS.md - Authentication Testing Guide

## Overview

This document describes the comprehensive test suite for the PHP OpenID Connect (OIDC) authentication system. The test suite validates configuration, authentication flows, role determination, and security settings for both customer (B2C/External ID) and agent (B2B/Organizational) authentication scenarios.

## Test Infrastructure

### Test Files Structure

```
tests/
├── complete_test.php           # ⭐ Main comprehensive test suite
├── quick_test.php             # Fast configuration validation
├── auth_flow_test.php         # Authentication flow analysis
├── test_runner.php            # Web-based test interface
├── AuthenticationTestSuite.php # Full test framework
├── agent_auth_tests.php       # B2B agent-specific tests
├── email_provider_tests.php   # Email provider validation
├── run_tests.php             # CLI test runner
├── test_simple_auth.php      # Simple authentication tests
├── test_simple.php           # Basic functionality tests
├── test_s3_integration.php   # AWS S3 integration tests
├── test_role_access.php      # Role-based access tests
├── test_logger.php           # Logging system tests
├── security_test.php         # Security validation tests
├── minimal_test.php          # Minimal configuration tests
├── azure_test.php            # Azure-specific tests
├── dashboard-test.php        # Dashboard functionality tests
├── debug_test.php            # Debug functionality tests
└── debug/                    # Debug and diagnostic files
    ├── debug.php             # Main debug interface
    ├── debug_azure.php       # Azure debugging
    ├── debug_callback.php    # Callback debugging
    ├── debug_entra_roles.php # Entra ID role debugging
    ├── debug_env.php         # Environment debugging
    ├── debug_oidc_config.php # OIDC configuration debugging
    ├── debug_wrapper.php     # Debug wrapper utilities
    ├── emergency_debug.php   # Emergency debugging tools
    ├── index_debug.php       # Index page debugging
    ├── simple_callback_debug.php    # Simple callback debugging
    └── simple_dashboard_debug.php   # Simple dashboard debugging
```

## Running Specific Test Categories

### Core Authentication Tests
```bash
# Main comprehensive suite
php tests/complete_test.php

# Basic configuration validation  
php tests/quick_test.php

# Authentication flow analysis
php tests/auth_flow_test.php
```

### Component-Specific Tests
```bash
# Role-based access control
php tests/test_role_access.php

# AWS S3 integration
php tests/test_s3_integration.php

# Security validation
php tests/security_test.php

# Logger functionality
php tests/test_logger.php

# Simple authentication flows
php tests/test_simple_auth.php
```

### Azure and B2B Tests
```bash
# Azure-specific functionality
php tests/azure_test.php

# B2B agent authentication
php tests/agent_auth_tests.php

# Dashboard integration
php tests/dashboard-test.php
```

### Debug and Diagnostic Tools
```bash
# Main debug interface
php tests/debug/debug.php

# OIDC configuration debugging
php tests/debug/debug_oidc_config.php

# Environment debugging
php tests/debug/debug_env.php

# Emergency debugging tools
php tests/debug/emergency_debug.php
```

### 1. Quick Validation (Recommended First Step)

```bash
php tests/complete_test.php
```

**What it tests:**
- Configuration loading and validation
- OIDC client creation for both user types
- Role determination logic
- URL and security settings
- Authentication URL generation

**Expected output:**
```
🔐 Complete OIDC Authentication Test Suite
==========================================

1. Configuration Tests
✅ Configuration Loading
✅ B2C Config  
✅ B2B Config
✅ App Config

2. OIDC Client Tests  
✅ Customer Client
✅ Agent Client
✅ Customer Authority
✅ Agent Authority

[... more tests ...]

📊 Test Summary
Total Tests: 12
Passed: ✅ 12
Failed: ❌ 0
Success Rate: 100.0%

🎉 ALL TESTS PASSED!
```

### 2. Configuration-Only Tests

```bash
php tests/quick_test.php
```

**Use when:**
- Setting up the system for the first time
- Debugging configuration issues
- Verifying environment variables

### 3. Authentication Flow Analysis

```bash
php tests/auth_flow_test.php
```

**Provides:**
- Exact Microsoft authority URLs being used
- Generated authentication URLs
- Role determination test scenarios
- Manual testing checklist

### 4. Web-Based Testing

1. Ensure PHP server is running: `php -S localhost:80`
2. Open: http://localhost/tests/test_runner.php
3. Use the visual interface to run tests and view results

### 5. Debug Tools

For troubleshooting specific issues, use the debug tools in `tests/debug/`:

```bash
# Main debug interface
php tests/debug/debug.php

# Azure-specific debugging
php tests/debug/debug_azure.php

# OIDC configuration debugging
php tests/debug/debug_oidc_config.php

# Entra ID role debugging
php tests/debug/debug_entra_roles.php

# Environment variable debugging
php tests/debug/debug_env.php
```

**Web-based debug tools:**
- http://localhost/tests/debug/debug.php - Main debug interface
- http://localhost/tests/debug/index_debug.php - Index page debugging
- http://localhost/tests/debug/simple_callback_debug.php - Callback debugging
- http://localhost/tests/debug/simple_dashboard_debug.php - Dashboard debugging

## Test Categories

### Configuration Tests

Validates all required configuration parameters:

- **B2C Configuration**: External ID tenant settings
  - `tenant_id`, `client_id`, `client_secret`, `tenant_name`
- **B2B Configuration**: Organizational tenant settings  
  - `tenant_id`, `client_id`, `client_secret`
- **App Configuration**: Application settings
  - `redirect_uri` validation and format checking

### OIDC Client Tests

Verifies proper client initialization:

- **Customer Client**: External ID tenant client creation
  - Authority: `https://{tenant}.ciamlogin.com/{tenant}.onmicrosoft.com/v2.0`
  - Scopes: `openid`, `profile`, `email`
- **Agent Client**: Organizational tenant client creation
  - Authority: `https://login.microsoftonline.com/{tenant_id}/v2.0`
  - Scopes: `openid`, `profile`, `email`, `https://graph.microsoft.com/User.Read`

### Role Determination Tests

Tests the role assignment logic for different user scenarios:

| User Type | Claims | Expected Role |
|-----------|---------|---------------|
| Customer | External email | `customer` |
| Agent (Employee) | `userType: Member` | `agent` |
| Agent (Guest) | `userType: Guest` | `agent` |
| Agent (Admin) | `roles: ['Admin']` | `admin` |

### Security Tests

Validates security configurations:

- Redirect URI format and validation
- Session security settings
- Azure Web Apps compatibility
- HTTPS enforcement settings

### Integration Tests

Tests integration with external services:

- **AWS S3 Integration** (`test_s3_integration.php`): Document storage and retrieval
- **Azure Services** (`azure_test.php`): Azure-specific functionality
- **Email Providers** (`email_provider_tests.php`): Email notification systems
- **Dashboard Integration** (`dashboard-test.php`): Dashboard functionality

### Component Tests

Tests individual system components:

- **Logger Tests** (`test_logger.php`): Logging system functionality
- **Simple Auth** (`test_simple_auth.php`): Basic authentication without complexity
- **Role Access** (`test_role_access.php`): Role-based access control
- **Minimal Config** (`minimal_test.php`): Minimal configuration requirements

## Manual Testing Procedures

### Customer Authentication Flow

1. **Start Test**: http://localhost/index.php?user_type=customer
2. **Expected Redirect**: Microsoft External ID login page
   - URL should contain: `scapecustomers.ciamlogin.com`
3. **After Login**: Redirected to dashboard with customer role
4. **Verify Session**: Check that `$_SESSION['user_type'] === 'customer'`

### Agent Authentication Flow

1. **Start Test**: http://localhost/index.php?user_type=agent  
2. **Expected Redirect**: Microsoft organizational login page
   - URL should contain: `login.microsoftonline.com`
3. **After Login**: Redirected to dashboard with agent role
4. **Verify Session**: Check that `$_SESSION['user_type'] === 'agent'`

### Role-Based Access Testing

Test different user scenarios:

#### Employee Agent
- Email: `employee@scape.com.au`
- Expected: `user_role = 'agent'`, `is_scape_employee = true`

#### Guest Agent (B2B)
- Email: `partner@external-company.com` 
- Expected: `user_role = 'agent'`, `is_guest_agent = true`

#### Admin User
- Claims contain admin roles
- Expected: `user_role = 'admin'`

## Troubleshooting Test Failures

### Configuration Failures

**Symptom**: `❌ Configuration Loading`
**Solution**: 
1. Check `.env` file exists and is readable
2. Verify all required environment variables are set
3. Run: `php -r "print_r(get_app_config());"`

### OIDC Client Failures

**Symptom**: `❌ Customer Client` or `❌ Agent Client`
**Solution**:
1. Verify tenant IDs are valid GUIDs
2. Check client IDs and secrets are correct
3. Ensure internet connectivity for metadata retrieval

### Authority URL Issues

**Symptom**: Wrong authority URLs generated
**Solution**:
1. Verify B2C `tenant_name` matches your External ID tenant
2. Check B2B `tenant_id` matches your organizational tenant
3. Confirm URL formats in test output

### Role Determination Failures

**Symptom**: Incorrect roles assigned
**Solution**:
1. Check claims being received from Microsoft
2. Verify role mapping logic in `determineUserRole()` function
3. Test with known user accounts

## Expected Test Results

### Successful Configuration
```
✅ Configuration Loading
✅ B2C Config - B2C tenant configured  
✅ B2B Config - B2B tenant configured
✅ App Config - App configuration present
```

### Successful OIDC Clients
```
✅ Customer Client - Customer OIDC client created
✅ Agent Client - Agent OIDC client created
✅ Customer Authority - External ID authority: https://scapecustomers.ciamlogin.com/...
✅ Agent Authority - Organizational authority: https://login.microsoftonline.com/...
```

### Successful Role Tests
```
✅ Role: customer -> customer - Got: customer
✅ Role: agent -> agent - Got: agent  
✅ Role: agent -> agent - Got: agent
✅ Role: agent -> admin - Got: admin
```

## Integration with Development Workflow

### Pre-Deployment Testing

Before deploying to Azure:

1. Run complete test suite: `php tests/complete_test.php`
2. Verify 100% pass rate
3. Test manual authentication flows
4. Check role assignments work correctly

### Continuous Testing

Add to your deployment pipeline:

```yaml
# In .github/workflows/deploy.yml
- name: Run Authentication Tests
  run: php tests/complete_test.php
```

### Local Development

During development:

1. Run `php tests/quick_test.php` after configuration changes
2. Use `php tests/auth_flow_test.php` to verify URLs
3. Test in browser using http://localhost/tests/test_runner.php

## Debugging Failed Tests

### Enable Detailed Logging

Set in your `.env`:
```
APP_DEBUG=true
LOG_LEVEL=debug
```

### Check Authentication Logs

View detailed logs:
```bash
php view_logs.php
```

Or check log files in `data/` directory:
- `app-{date}.log` - Application logs
- `debug-{date}.log` - Debug information  
- `error-{date}.log` - Error logs

### Common Issues and Solutions

| Issue | Symptom | Solution |
|-------|---------|----------|
| Invalid tenant configuration | Client creation fails | Verify tenant IDs in `.env` |
| Network connectivity | Metadata retrieval fails | Check internet connection |
| Wrong redirect URI | Authentication loop | Verify callback.php URL |
| Session issues | User not authenticated | Check session configuration |
| Role assignment problems | Wrong dashboard access | Review role determination logic |

## Test Coverage

The test suite covers:

- ✅ **Configuration**: All required settings validated
- ✅ **Authentication**: Both B2C and B2B flows tested  
- ✅ **Authorization**: Role-based access control verified
- ✅ **Security**: Session and redirect URI validation
- ✅ **Integration**: Dashboard and logout flow testing
- ✅ **Error Handling**: Invalid scenarios and edge cases

## Success Criteria

Your authentication system is ready for production when:

1. **All automated tests pass** (100% success rate)
2. **Manual authentication flows work** for both user types
3. **Role assignment functions correctly** for all user scenarios
4. **Session management works** (login, dashboard, logout)
5. **Security settings are enforced** (HTTPS, secure sessions)

---

**Last Updated**: August 2, 2025  
**Version**: 1.0  
**Author**: OIDC Authentication System
