# S-Cape Travel OIDC Setup Guide
*Following Microsoft Woodgrove Security Patterns*

## 🔧 Required Configuration IDs and Setup

### 1. Microsoft Entra ID Setup

#### A. Internal Tenant (B2B - scapetravel)
**Purpose**: S-Cape employees + B2B guest agents

**Steps to get IDs:**
1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to **Microsoft Entra ID** > **Overview**
3. Copy **Tenant ID** → Use for `INTERNAL_TENANT_ID`

**App Registration for Internal Tenant:**
1. **Microsoft Entra ID** > **App registrations** > **New registration**
2. **Name**: `S-Cape Travel Internal App`
3. **Supported account types**: `Accounts in this organizational directory only`
4. **Redirect URI**: `Web` → `https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/callback.php`
5. After creation, copy:
   - **Application (client) ID** → `INTERNAL_CLIENT_ID`
   - Go to **Certificates & secrets** > **New client secret** → Copy value → `INTERNAL_CLIENT_SECRET`

**Required Permissions:**
- `openid`
- `profile` 
- `email`
- `User.Read`

#### B. External Tenant (B2C - scapecustomers) ✅ Already Configured
**Current IDs in .env:**
- `EXTERNAL_CLIENT_ID='2d24e26e-99ee-4232-8921-06b161b65bb5'` ✅
- `EXTERNAL_TENANT_ID='37a2c2da-5eec-4680-b380-2c0a72013f67'` ✅

#### C. Microsoft Graph App Registration
**Purpose**: Create B2C users and send B2B invitations

**Steps:**
1. In **External Tenant** (scapecustomers), create new app registration:
2. **Name**: `S-Cape Travel Graph Manager`
3. **Supported account types**: `Accounts in this organizational directory only`
4. **No redirect URI needed**
5. Copy:
   - **Application (client) ID** → `GRAPH_CLIENT_ID`
   - Create client secret → `GRAPH_CLIENT_SECRET`

**Required API Permissions:**
- **Microsoft Graph Application Permissions**:
  - `User.ReadWrite.All` (create B2C users)
  - `User.Invite.All` (send B2B invitations)
  - `Directory.ReadWrite.All` (manage users)

**Grant Admin Consent** for all permissions!

### 2. AWS Setup Guide

#### A. Create S3 Bucket
```bash
aws s3 mb s3://scape-travel-docs --region eu-west-1
```

#### B. Create S3 Folder Structure
```bash
# Create folders for role-based access
aws s3api put-object --bucket scape-travel-docs --key customers/ --region eu-west-1
aws s3api put-object --bucket scape-travel-docs --key agents/ --region eu-west-1
aws s3api put-object --bucket scape-travel-docs --key employees/ --region eu-west-1
```

#### C. Upload Sample Documents
```bash
# Sample customer documents
echo "Customer Travel Itinerary - Sample" > customer-sample.pdf
aws s3 cp customer-sample.pdf s3://scape-travel-docs/customers/

# Sample agent documents  
echo "Agent Commission Structure - Sample" > agent-sample.pdf
aws s3 cp agent-sample.pdf s3://scape-travel-docs/agents/

# Sample employee documents
echo "Employee Handbook - Sample" > employee-sample.pdf
aws s3 cp employee-sample.pdf s3://scape-travel-docs/employees/
```

#### D. Create IAM Roles

**Customer Role:**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:GetObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::scape-travel-docs/customers/*",
        "arn:aws:s3:::scape-travel-docs"
      ],
      "Condition": {
        "StringLike": {
          "s3:prefix": ["customers/"]
        }
      }
    }
  ]
}
```

**Agent Role:**
```json
{
  "Version": "2012-10-17", 
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:GetObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::scape-travel-docs/agents/*",
        "arn:aws:s3:::scape-travel-docs/customers/*",
        "arn:aws:s3:::scape-travel-docs"
      ],
      "Condition": {
        "StringLike": {
          "s3:prefix": ["agents/", "customers/"]
        }
      }
    }
  ]
}
```

**Trust Policy for Both Roles:**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": {
        "Federated": "arn:aws:iam::YOUR-ACCOUNT-ID:oidc-provider/login.microsoftonline.com/YOUR-TENANT-ID"
      },
      "Action": "sts:AssumeRoleWithWebIdentity",
      "Condition": {
        "StringEquals": {
          "login.microsoftonline.com/YOUR-TENANT-ID:aud": "YOUR-CLIENT-ID"
        }
      }
    }
  ]
}
```

**Get Role ARNs:**
- Customer Role ARN → `AWS_ROLE_CUSTOMER`
- Agent Role ARN → `AWS_ROLE_AGENT`

### 3. Required Environment Variables

**Update your .env file with these IDs:**

```env
# Internal Tenant (B2B scapetravel) - NEED TO GET THESE
INTERNAL_CLIENT_ID='your-internal-client-id-here'
INTERNAL_CLIENT_SECRET='your-internal-client-secret-here'  
INTERNAL_TENANT_ID='your-scapetravel-tenant-id-here'

# External Tenant (B2C scapecustomers) - ✅ ALREADY SET
EXTERNAL_CLIENT_ID='your-external-client-id-here'
EXTERNAL_CLIENT_SECRET='your-external-client-secret-here'
EXTERNAL_TENANT_ID='your-external-tenant-id-here'

# Microsoft Graph Settings - NEED TO GET THESE
GRAPH_CLIENT_ID='your-graph-app-client-id-here'
GRAPH_CLIENT_SECRET='your-graph-app-client-secret-here'
GRAPH_TENANT_ID='37a2c2da-5eec-4680-b380-2c0a72013f67'

# AWS Settings - NEED TO GET THESE
AWS_REGION='eu-west-1'
AWS_S3_BUCKET='scape-travel-docs'
AWS_ROLE_CUSTOMER='arn:aws:iam::YOUR-ACCOUNT-ID:role/CustomerRole'
AWS_ROLE_AGENT='arn:aws:iam::YOUR-ACCOUNT-ID:role/AgentRole'
```

## 🚀 Deployment Steps

### 1. Web Deploy to Azure
1. Package your PHP files
2. Upload to Azure Web Apps
3. Ensure .env file is uploaded securely
4. Test the configuration check page: `/admin/config_check.php`

### 2. Test Flow

**Customer Flow:**
1. Go to main site → "New Customer Registration"
2. Enter email → Account created instantly
3. Check email for password reset link
4. Set password and login
5. Access customer documents

**Agent Flow:**
1. Go to main site → "Apply as Agent"
2. Submit application
3. Admin approves via `/admin/agents.php`
4. Agent receives B2B invitation email
5. Accept invitation
6. Login via "Agent/Employee Login"
7. Access agent documents

## 🔐 Security Checklist

- [ ] All client secrets are secure
- [ ] Admin password is strong
- [ ] Microsoft Graph permissions granted
- [ ] AWS IAM roles configured
- [ ] S3 bucket policy set correctly
- [ ] HTTPS redirect URI configured
- [ ] All configuration validated via config check page

## 📋 File Structure (After Cleanup)

**Core Files:**
- `index.php` - Main login page
- `callback.php` - OIDC callback handler
- `dashboard.php` - User dashboard
- `register_customer.php` - Customer registration
- `apply_agent.php` - Agent application
- `logout.php` - Logout handler
- `download.php` - File download handler

**Admin Files:**
- `admin/agents.php` - Agent management
- `admin/config_check.php` - Configuration validation

**Library Files:**
- `lib/oidc.php` - OIDC authentication
- `lib/aws_helper.php` - AWS integration
- `lib/graph_helper.php` - Microsoft Graph API
- `lib/config_helper.php` - Configuration management

**Removed Files:**
- ❌ `testenv.php` (unused)
- ❌ `envtest.php` (unused) 
- ❌ `debug.php` (unused)
- ❌ `get_token.php` (unused)
- ❌ `register_agent.php` (replaced with apply_agent.php)
- ❌ `lib/s3_helper.php` (merged into aws_helper.php)

## 🎨 Branding Suggestions (Optional)

Consider customizing the Woodgrove references:
- Replace "Woodgrove" with "S-Cape Security Standards" 
- Update color scheme in `style.css`
- Add S-Cape Travel logo
- Customize success/error messages
- Update email templates

This follows Microsoft's recommended enterprise patterns while being specific to S-Cape Travel.
