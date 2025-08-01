# 🚀 S-Cape Travel OIDC Deployment Checklist

## Pre-Deployment Setup

### ✅ Microsoft Entra ID Configuration

#### Internal Tenant (B2B - scapetravel)
- [ ] **Get Tenant ID**: Azure Portal → Entra ID → Overview → Tenant ID
- [ ] **Create App Registration**: 
  - Name: `S-Cape Travel Internal App`
  - Account types: `Accounts in this organizational directory only`
  - Redirect URI: `https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/callback.php`
- [ ] **Copy Client ID** → Update `INTERNAL_CLIENT_ID` in .env
- [ ] **Create Client Secret** → Update `INTERNAL_CLIENT_SECRET` in .env
- [ ] **Configure API Permissions**: `openid`, `profile`, `email`, `User.Read`

#### External Tenant (B2C - scapecustomers) ✅ Already Done
- [x] Client ID: `2d24e26e-99ee-4232-8921-06b161b65bb5`
- [x] Tenant ID: `37a2c2da-5eec-4680-b380-2c0a72013f67`

#### Microsoft Graph App Registration
- [ ] **Create in External Tenant**: Name `S-Cape Travel Graph Manager`
- [ ] **Copy Client ID** → Update `GRAPH_CLIENT_ID` in .env
- [ ] **Create Client Secret** → Update `GRAPH_CLIENT_SECRET` in .env
- [ ] **Grant API Permissions**:
  - [ ] `User.ReadWrite.All` (Application)
  - [ ] `User.Invite.All` (Application)  
  - [ ] `Directory.ReadWrite.All` (Application)
- [ ] **Grant Admin Consent** for all permissions

### ✅ AWS Configuration

#### S3 Bucket Setup
- [ ] **Create S3 Bucket**: `scape-travel-docs` in `eu-west-1`
- [ ] **Create Folder Structure**:
  ```bash
  aws s3api put-object --bucket scape-travel-docs --key customers/
  aws s3api put-object --bucket scape-travel-docs --key agents/
  aws s3api put-object --bucket scape-travel-docs --key employees/
  ```
- [ ] **Upload Sample Documents** to each folder

#### IAM Roles Setup
- [ ] **Create Customer Role**:
  - Name: `CustomerRole`
  - Policy: S3 access to `customers/*` only
  - Trust Policy: OIDC provider for External Tenant
- [ ] **Create Agent Role**:
  - Name: `AgentRole` 
  - Policy: S3 access to `customers/*` and `agents/*`
  - Trust Policy: OIDC provider for Internal Tenant
- [ ] **Copy Role ARNs** → Update `AWS_ROLE_CUSTOMER` and `AWS_ROLE_AGENT` in .env
- [ ] **Get AWS Account ID** → Update ARNs in .env

#### OIDC Identity Providers (if using JWT tokens)
- [ ] **Create OIDC Provider** for External Tenant
- [ ] **Create OIDC Provider** for Internal Tenant

### ✅ Environment Configuration

#### Update .env File
- [ ] `INTERNAL_CLIENT_ID` - From Internal App Registration
- [ ] `INTERNAL_CLIENT_SECRET` - From Internal App Registration  
- [ ] `INTERNAL_TENANT_ID` - From Internal Tenant
- [ ] `GRAPH_CLIENT_ID` - From Graph App Registration
- [ ] `GRAPH_CLIENT_SECRET` - From Graph App Registration
- [ ] `AWS_ROLE_CUSTOMER` - Customer IAM Role ARN
- [ ] `AWS_ROLE_AGENT` - Agent IAM Role ARN
- [ ] `ADMIN_PASSWORD` - Strong admin password for admin panel

## Deployment Steps

### ✅ File Preparation
- [x] **Clean unused files**: Removed debug files, old registration files
- [x] **Library files ready**: oidc.php, aws_helper.php, graph_helper.php, config_helper.php
- [x] **Core files ready**: index.php, callback.php, dashboard.php, etc.
- [x] **Admin files ready**: admin/agents.php, admin/config_check.php

### ✅ Azure Web Apps Deployment
- [ ] **Package files** (zip without node_modules if any)
- [ ] **Upload to Azure Web Apps**
- [ ] **Upload .env file securely** (consider Azure Key Vault for production)
- [ ] **Verify file permissions**
- [ ] **Test basic PHP functionality**

### ✅ Post-Deployment Testing

#### Configuration Validation
- [ ] **Visit**: `https://yoursite.azurewebsites.net/admin/config_check.php`
- [ ] **Admin login works** (password: value from ADMIN_PASSWORD)
- [ ] **All configuration checks pass**:
  - [ ] Environment variables loaded
  - [ ] Microsoft Graph connectivity
  - [ ] AWS credentials valid
  - [ ] File permissions correct

#### Customer Flow Testing
- [ ] **Visit main site**: Homepage loads correctly
- [ ] **Customer Registration**:
  - [ ] Click "New Customer Registration"
  - [ ] Enter test email address
  - [ ] Account creation succeeds
  - [ ] Check email for password reset link
- [ ] **Customer Login**:
  - [ ] Set password via email link
  - [ ] Login with customer credentials
  - [ ] Dashboard shows customer role
  - [ ] Can access customer documents from S3

#### Agent Flow Testing  
- [ ] **Agent Application**:
  - [ ] Click "Apply as Agent"
  - [ ] Submit complete application
  - [ ] Application appears in admin panel
- [ ] **Admin Approval**:
  - [ ] Login to admin panel: `/admin/agents.php`
  - [ ] Review and approve agent application
  - [ ] B2B invitation sent successfully
- [ ] **Agent Login**:
  - [ ] Check agent email for B2B invitation
  - [ ] Accept B2B invitation
  - [ ] Login via "Agent/Employee Login"
  - [ ] Dashboard shows agent role
  - [ ] Can access both agent and customer documents

#### Security Testing
- [ ] **Session management**: Sessions expire correctly
- [ ] **Admin panel**: Only accessible with correct password
- [ ] **Role-based access**: Customers can't access agent documents
- [ ] **Error handling**: Graceful error pages, no sensitive data exposed
- [ ] **Logging**: Security events logged correctly

## Production Readiness

### ✅ Security Hardening
- [ ] **Change admin password** from default
- [ ] **Set DEBUG='false'** in production
- [ ] **Review all client secrets** are secure
- [ ] **Enable HTTPS only**
- [ ] **Set up monitoring** for failed logins
- [ ] **Regular backup** of agent applications data

### ✅ Documentation
- [x] **Setup guide created**: SETUP_GUIDE.md
- [x] **Deployment checklist**: This file
- [x] **Environment template**: .env.example
- [ ] **User guide** for customers and agents
- [ ] **Admin guide** for managing applications

### ✅ Monitoring & Maintenance
- [ ] **Monitor application logs**
- [ ] **Monitor authentication failures**
- [ ] **Regular review** of agent applications
- [ ] **Update dependencies** regularly
- [ ] **Backup strategy** for application data

## 🎯 Success Criteria

Your deployment is successful when:

1. ✅ **Configuration check passes** - All services connected
2. ✅ **Customer flow works** - Registration → Login → Document Access
3. ✅ **Agent flow works** - Application → Approval → B2B Invitation → Login → Document Access  
4. ✅ **Admin panel works** - Secure login and agent management
5. ✅ **Security logging active** - All events tracked
6. ✅ **No exposed secrets** - All credentials secure

## 🆘 Troubleshooting

**Common Issues:**
- **Configuration check fails**: Check .env file values and permissions
- **Graph API errors**: Verify app permissions and admin consent
- **AWS access denied**: Check IAM role policies and trust relationships
- **Authentication loops**: Verify redirect URIs match exactly
- **Session issues**: Check PHP session configuration on Azure

**Debug Tools:**
- Configuration check page: `/admin/config_check.php`
- Dashboard test page: `/dashboard-test.php` (if needed)
- Enable DEBUG mode temporarily for detailed error messages

## 📞 Support

For issues with:
- **Microsoft Identity**: Check Azure Portal app registrations and permissions
- **AWS**: Verify IAM roles and S3 bucket policies  
- **Application**: Review logs and configuration check results

**Following Microsoft Woodgrove Security Patterns** 🛡️
