# Cross-Tenant Configuration Checklist

## Overview
This checklist ensures proper setup of the multi-tenant S-Cape Travel authentication system using Microsoft Entra ID B2B (internal) and B2C (external) tenants with Graph API integration.

## Prerequisites
- [ ] Two Microsoft Entra ID tenants (Internal B2B + External B2C)
- [ ] Administrative access to both tenants
- [ ] Azure subscription for app registrations
- [ ] AWS account for S3 bucket and IAM roles

## 1. Internal Tenant (B2B) Configuration

### App Registration Setup
- [ ] Create app registration for internal employees/agents
- [ ] Configure **Redirect URIs**: `https://yourapp.azurewebsites.net/callback.php`
- [ ] Enable **ID tokens** and **Access tokens**
- [ ] Note down `Application (client) ID` and `Directory (tenant) ID`
- [ ] Create **Client Secret** and note the value

### App Roles Configuration
- [ ] Go to **App roles** in your app registration
- [ ] Create role with these settings:
  - **Display name**: Admin
  - **Allowed member types**: Users/Groups
  - **Value**: Admin
  - **Description**: Administrative access to agent management

### Authentication Settings
- [ ] Configure **Supported account types**: 
  - "Accounts in this organizational directory only (Single tenant)"
- [ ] Add redirect URI: `https://yourapp.azurewebsites.net/callback.php`
- [ ] Configure **Front-channel logout URL**: `https://yourapp.azurewebsites.net/logout.php`

### User Assignment
- [ ] Go to **Enterprise applications** → Find your app
- [ ] Click **Users and groups**
- [ ] Assign specific users to the **Admin** role
- [ ] Verify role assignments are active

## 2. External Tenant (B2C) Configuration

### B2C Setup
- [ ] Create Azure AD B2C tenant
- [ ] Configure **Identity providers** (Local accounts, Social providers)
- [ ] Create **User flows** for sign-up and sign-in
- [ ] Configure **Custom policies** if needed

### App Registration
- [ ] Register application in B2C tenant
- [ ] Configure **Redirect URIs**: `https://yourapp.azurewebsites.net/callback.php`
- [ ] Enable **ID tokens**
- [ ] Note down `Application (client) ID`
- [ ] Create **Client Secret**

### User Flow Configuration
- [ ] Create or configure user flow
- [ ] Enable required **User attributes** and **Application claims**
- [ ] Test user flow works correctly

## 3. Graph API Configuration

### App Registration for Graph
- [ ] Create dedicated app registration for Graph API access
- [ ] Note down `Application (client) ID`, `Directory (tenant) ID`
- [ ] Create **Client Secret**

### API Permissions
- [ ] Add these **Application permissions**:
  - `Application.ReadWrite.All`
  - `Directory.ReadWrite.All` 
  - `User.ReadWrite.All`
  - `Mail.Send` ← **Critical for email functionality**
- [ ] **Grant admin consent** for all permissions
- [ ] Verify consent status shows "Granted for [tenant]"

### Email Configuration
- [ ] Verify admin email account exists: `admin@yourcompany.com`
- [ ] Ensure mailbox is accessible and active
- [ ] Test email sending permissions

## 4. Azure B2B Collaboration Settings

### External Collaboration
- [ ] In Internal tenant → **External Identities**
- [ ] Configure **External collaboration settings**
- [ ] Set **Guest invite settings**:
  - "Member users and users assigned to specific admin roles can invite guest users"
- [ ] Configure **Collaboration restrictions**:
  - Allow invitations to any domain OR specific domain allowlist

### Cross-Tenant Access
- [ ] Configure **Cross-tenant access settings**
- [ ] Add B2C tenant to allowlist if using domain restrictions
- [ ] Configure **B2B collaboration** settings
- [ ] Test guest user invitation flow

## 5. Application Configuration

### Environment Variables (.env)
```bash
# Internal Tenant (B2B) - Employees/Agents
B2B_CLIENT_ID=your-internal-client-id
B2B_CLIENT_SECRET=your-internal-client-secret
B2B_TENANT_ID=your-internal-tenant-id

# External Tenant (B2C) - Customers  
B2C_CLIENT_ID=your-b2c-client-id
B2C_CLIENT_SECRET=your-b2c-client-secret
B2C_TENANT_ID=your-b2c-tenant-id
B2C_USER_FLOW=B2C_1_signupsignin

# Graph API Configuration
GRAPH_CLIENT_ID=your-graph-client-id
GRAPH_CLIENT_SECRET=your-graph-client-secret
GRAPH_TENANT_ID=your-graph-tenant-id

# Email Configuration (Graph API)
EMAIL_METHOD=graph
ADMIN_EMAIL=admin@yourcompany.com
SMTP_PASSWORD=not-needed-using-graph-api

# AWS Configuration
AWS_BUCKET=s-cape-travel-documents
AWS_REGION=us-east-1
AWS_CUSTOMER_ROLE_ARN=arn:aws:iam::YOUR-ACCOUNT-ID:role/S-CapeCustomerRole
AWS_AGENT_ROLE_ARN=arn:aws:iam::YOUR-ACCOUNT-ID:role/S-CapeAgentRole
```

### Config File Validation
- [ ] Run: `php admin/cross_tenant_check.php`
- [ ] Verify all configuration items show ✅
- [ ] Fix any ❌ failed items
- [ ] Test Graph API token acquisition

## 6. AWS Integration

### S3 Bucket Setup
- [ ] Create S3 bucket: `s-cape-travel-documents`
- [ ] Configure bucket permissions
- [ ] Set up appropriate bucket policies

### IAM Roles
- [ ] Create `S-CapeCustomerRole` with S3 read permissions
- [ ] Create `S-CapeAgentRole` with S3 read/write permissions  
- [ ] Configure trust relationships for web identity federation
- [ ] Replace `ACCOUNT` with actual AWS Account ID in role ARNs

## 7. Testing & Validation

### Authentication Flow Testing
- [ ] Test customer registration flow (B2C)
- [ ] Test customer login (B2C)
- [ ] Test agent/employee login (B2B)
- [ ] Test admin role verification
- [ ] Test logout functionality

### Email System Testing
- [ ] Test Graph API token acquisition
- [ ] Test email sending functionality
- [ ] Verify admin notifications work
- [ ] Check email templates render correctly

### Cross-Tenant Integration
- [ ] Test B2B guest invitations work
- [ ] Verify external users can access appropriate resources
- [ ] Test role-based access control
- [ ] Validate AWS federated access

## 8. Production Deployment

### Azure Web Apps Configuration
- [ ] Deploy to Azure Web Apps (Linux PHP 8.2)
- [ ] Configure environment variables in Azure
- [ ] Set up custom domain if needed
- [ ] Configure SSL certificates

### Monitoring & Logging
- [ ] Verify enhanced logging works in Azure environment
- [ ] Set up log monitoring and alerts
- [ ] Test error handling and logging
- [ ] Monitor authentication flows

### Security Review
- [ ] Verify no passwords in source code
- [ ] Confirm client secrets are properly secured
- [ ] Review HTTPS configuration
- [ ] Validate CORS settings if applicable

## 9. Documentation & Maintenance

### Documentation
- [ ] Document tenant IDs and app registration details
- [ ] Create user guides for admin panel
- [ ] Document troubleshooting procedures
- [ ] Update deployment guides

### Ongoing Maintenance
- [ ] Set up client secret expiration monitoring
- [ ] Plan for certificate renewals
- [ ] Monitor API permission changes
- [ ] Regular security reviews

## Troubleshooting

### Common Issues
1. **Graph API 403 Errors**: Check API permissions and admin consent
2. **Authentication Failures**: Verify redirect URIs match exactly
3. **Email Not Sending**: Ensure Mail.Send permission granted
4. **Role Assignment Issues**: Check enterprise application user assignments
5. **B2B Collaboration**: Verify external collaboration settings

### Diagnostic Tools
- Use `admin/cross_tenant_check.php` for configuration validation
- Check Azure AD sign-in logs for authentication issues
- Monitor application logs for Graph API errors
- Use Azure Application Insights for detailed monitoring

## Success Criteria
- [ ] All items in this checklist completed ✅
- [ ] Cross-tenant diagnostic tool shows all green ✅  
- [ ] Authentication flows tested successfully ✅
- [ ] Email notifications working ✅
- [ ] Admin panel accessible with role-based access ✅
- [ ] AWS integration functional ✅

When all criteria are met, your cross-tenant configuration is production-ready! 🚀
