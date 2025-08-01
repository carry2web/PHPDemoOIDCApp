# AWS Setup Guide for S-Cape Travel Cross-Tenant Authentication

## Overview
This guide walks through setting up AWS resources for federated authentication with Microsoft Entra ID, allowing customers and agents to access S3 documents with role-based permissions.

## Architecture Overview
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Customer      │    │   S-Cape App    │    │   AWS S3        │
│   (B2C Login)   │───▶│   PHP App       │───▶│   Documents     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
┌─────────────────┐           │               ┌─────────────────┐
│   Agent         │           │               │   AWS IAM       │
│   (B2B Login)   │───────────┼──────────────▶│   Roles         │
└─────────────────┘           │               └─────────────────┘
                              │
                       ┌─────────────────┐
                       │   Microsoft     │
                       │   Entra ID      │
                       │   (OIDC IdP)    │
                       └─────────────────┘
```

## Current Configuration Status
✅ **SETUP COMPLETE!** Your S-Cape Travel AWS integration is fully configured:
- **S3 Bucket**: `scape-travel-docs` (eu-west-1) ✅ Created with folder structure
- **Customer Role**: `arn:aws:iam::955654668431:role/CustomerRole` ✅ Active
- **Agent Role**: `arn:aws:iam::955654668431:role/AgentRole` ✅ Active
- **OIDC Providers**: Both B2B and B2C tenants configured ✅
- **IAM Security**: Proper limited permissions (admin access removed) ✅

## ⚠️ SECURITY WARNING: Root Access Keys
**NEVER use root access keys for applications!** Root keys have unlimited access to your entire AWS account. Let's create a dedicated IAM user instead.

## Step 1: Create Dedicated IAM User (RECOMMENDED)

### Method 1: AWS Console (Easiest)
1. **Login to AWS Console** with your root account (last time!)
2. **Go to IAM** → **Users** → **Create user**
3. **Step 1 - User details:**
   - **User name**: `scape-travel-service`
   - **Provide user access to the AWS Management Console**: ❌ **UNCHECK** (we only want API access)
   - Click **Next**
4. **Step 2 - Set permissions:**
   - Select **Attach policies directly**
   - Click **Create policy** (opens in new tab)
   - Use the custom policy JSON below
   - Return to user creation and attach your new policy
5. **Step 3 - Review and create:**
   - **Tags** (optional): Add `Project: S-Cape Travel`
   - Click **Create user**
6. **Step 4 - Create access key:**
   - After user is created, click on the user name
   - Go to **Security credentials** tab
   - Click **Create access key**
   - **Select use case**: Choose **"Application running outside AWS"** ⭐ (CORRECT for your PHP app)
   - **Alternative options**:
     - ❌ Command Line Interface (CLI) - for AWS CLI tools only
     - ❌ Local code - for development/testing
     - ❌ Third-party service - for external integrations
   - Check the confirmation box
   - Click **Create access key**
   - **⚠️ IMPORTANT**: Download the CSV or copy the keys - you won't see the secret again!

### Custom IAM Policy for S-Cape Travel
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "S3BucketManagement",
      "Effect": "Allow",
      "Action": [
        "s3:CreateBucket",
        "s3:ListBucket",
        "s3:GetBucketLocation",
        "s3:GetBucketVersioning",
        "s3:PutBucketVersioning",
        "s3:GetBucketAcl",
        "s3:PutBucketAcl",
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject"
      ],
      "Resource": [
        "arn:aws:s3:::scape-travel-docs",
        "arn:aws:s3:::scape-travel-docs/*"
      ]
    },
    {
      "Sid": "IAMForOIDCProviders",
      "Effect": "Allow",
      "Action": [
        "iam:CreateOpenIDConnectProvider",
        "iam:GetOpenIDConnectProvider",
        "iam:ListOpenIDConnectProviders",
        "iam:CreateRole",
        "iam:GetRole",
        "iam:ListRoles",
        "iam:PutRolePolicy",
        "iam:GetRolePolicy",
        "iam:AttachRolePolicy",
        "iam:CreatePolicy",
        "iam:GetPolicy",
        "iam:ListPolicies"
      ],
      "Resource": "*",
      "Condition": {
        "StringLike": {
          "iam:RoleName": ["CustomerRole", "AgentRole"],
          "iam:PolicyName": ["CustomerPolicy", "AgentPolicy"]
        }
      }
    },
    {
      "Sid": "STSForTesting",
      "Effect": "Allow",
      "Action": [
        "sts:GetCallerIdentity",
        "sts:AssumeRole"
      ],
      "Resource": "*"
    }
  ]
}
```

### Method 2: AWS CLI (if you have temporary access)
```bash
# Create the IAM user
aws iam create-user --user-name scape-travel-service

# Create and attach the policy
aws iam create-policy --policy-name ScapeTravelPolicy --policy-document file://scape-travel-policy.json

# Attach policy to user (replace ACCOUNT-ID with your account)
aws iam attach-user-policy --user-name scape-travel-service --policy-arn arn:aws:iam::ACCOUNT-ID:policy/ScapeTravelPolicy

# Create access keys
aws iam create-access-key --user-name scape-travel-service
```

## Step 2: Configure AWS CLI with New IAM User

Once you have your IAM user access keys:

## Step 2: Configure AWS CLI with New IAM User

Once you have your IAM user access keys:

```powershell
# Configure AWS CLI with new IAM user credentials
aws configure

# When prompted, enter:
# AWS Access Key ID: [Your new IAM user access key]
# AWS Secret Access Key: [Your new IAM user secret key]
# Default region name: [Your preferred region, e.g., eu-west-1]
# Default output format: json
```

### Alternative: Environment Variables Method
```powershell
# Set environment variables (temporary for this session)
$env:AWS_ACCESS_KEY_ID = "YOUR-IAM-USER-ACCESS-KEY"
$env:AWS_SECRET_ACCESS_KEY = "YOUR-IAM-USER-SECRET-KEY"
$env:AWS_DEFAULT_REGION = "eu-west-1"
```

### Alternative: AWS Credentials File
Create file at `~/.aws/credentials`:
```ini
[default]
aws_access_key_id = YOUR-IAM-USER-ACCESS-KEY
aws_secret_access_key = YOUR-IAM-USER-SECRET-KEY

[scape-travel]
aws_access_key_id = YOUR-IAM-USER-ACCESS-KEY
aws_secret_access_key = YOUR-IAM-USER-SECRET-KEY
```

And `~/.aws/config`:
```ini
[default]
region = eu-west-1
output = json

[profile scape-travel]
region = eu-west-1
output = json
```

## Step 3: Verify New IAM User Setup

### Test Basic Access
```bash
# Test IAM user credentials
aws sts get-caller-identity

# Should return something like:
# {
#   "UserId": "AIDACKCEVSQ6C2EXAMPLE",
#   "Account": "123456789012",
#   "Arn": "arn:aws:iam::123456789012:user/scape-travel-service"
# }
```

### Test S3 Access
```bash
# Test S3 permissions
aws s3 ls --region eu-west-1

# Test bucket creation (if bucket doesn't exist)
aws s3 mb s3://scape-travel-docs --region eu-west-1
```

## Step 4: Get Your AWS Account Information
```bash
# Method 1: AWS CLI
aws sts get-caller-identity --query Account --output text

# Method 2: AWS Console
# Go to AWS Console → Top right corner → Account dropdown
```

### Verify Current S3 Bucket
```bash
# Check if bucket exists
aws s3 ls s3://scape-travel-docs --region eu-west-1

# If bucket doesn't exist, create it
aws s3 mb s3://scape-travel-docs --region eu-west-1
```

## Step 2: Create OIDC Identity Provider

### Configure Microsoft Entra ID as Identity Provider
```bash
# Create OIDC Identity Provider for your Internal tenant
aws iam create-open-id-connect-provider \
    --url https://login.microsoftonline.com/48a85b75-4e7d-4d8c-9cc6-a72722124be8/v2.0 \
    --thumbprint-list 626D44E704D1CEABE3BF0D53397464AC8080142C \
    --client-id-list 756223c3-0313-4195-8540-b03063366f3a

# Create OIDC Identity Provider for your External tenant  
aws iam create-open-id-connect-provider \
    --url https://login.microsoftonline.com/37a2c2da-5eec-4680-b380-2c0a72013f67/v2.0 \
    --thumbprint-list 626D44E704D1CEABE3BF0D53397464AC8080142C \
    --client-id-list 2d24e26e-99ee-4232-8921-06b161b65bb5
```

## Step 3: Create IAM Roles

### Customer Role (Read-Only Access)
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": {
        "Federated": "arn:aws:iam::YOUR-ACCOUNT-ID:oidc-provider/login.microsoftonline.com/37a2c2da-5eec-4680-b380-2c0a72013f67/v2.0"
      },
      "Action": "sts:AssumeRoleWithWebIdentity",
      "Condition": {
        "StringEquals": {
          "login.microsoftonline.com/37a2c2da-5eec-4680-b380-2c0a72013f67/v2.0:aud": "2d24e26e-99ee-4232-8921-06b161b65bb5",
          "login.microsoftonline.com/37a2c2da-5eec-4680-b380-2c0a72013f67/v2.0:sub": "customer-identifier"
        }
      }
    }
  ]
}
```

### Agent Role (Read-Write Access)
```json
{
  "Version": "2012-10-17", 
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": {
        "Federated": "arn:aws:iam::YOUR-ACCOUNT-ID:oidc-provider/login.microsoftonline.com/48a85b75-4e7d-4d8c-9cc6-a72722124be8/v2.0"
      },
      "Action": "sts:AssumeRoleWithWebIdentity",
      "Condition": {
        "StringEquals": {
          "login.microsoftonline.com/48a85b75-4e7d-4d8c-9cc6-a72722124be8/v2.0:aud": "756223c3-0313-4195-8540-b03063366f3a",
          "login.microsoftonline.com/48a85b75-4e7d-4d8c-9cc6-a72722124be8/v2.0:sub": "agent-identifier"
        }
      }
    }
  ]
}
```

## Step 4: Create IAM Policies

### Customer Policy (S3 Read-Only)
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
        "arn:aws:s3:::scape-travel-docs",
        "arn:aws:s3:::scape-travel-docs/customers/*"
      ]
    }
  ]
}
```

### Agent Policy (S3 Read-Write)
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow", 
      "Action": [
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::scape-travel-docs",
        "arn:aws:s3:::scape-travel-docs/*"
      ]
    }
  ]
}
```

## Step 5: Automated Setup Script

Let me create an automated setup script for you:

### PowerShell Setup Script
```powershell
# AWS PowerShell Setup Script
param(
    [Parameter(Mandatory=$true)]
    [string]$AwsAccountId
)

Write-Host "Setting up AWS resources for S-Cape Travel..." -ForegroundColor Green

# Set variables
$BucketName = "scape-travel-docs"
$Region = "eu-west-1"
$InternalTenantId = "48a85b75-4e7d-4d8c-9cc6-a72722124be8"
$ExternalTenantId = "37a2c2da-5eec-4680-b380-2c0a72013f67"
$InternalClientId = "756223c3-0313-4195-8540-b03063366f3a"
$ExternalClientId = "2d24e26e-99ee-4232-8921-06b161b65bb5"

# Check if bucket exists
Write-Host "Checking S3 bucket..." -ForegroundColor Yellow
try {
    aws s3 ls s3://$BucketName --region $Region
    Write-Host "✅ Bucket exists" -ForegroundColor Green
} catch {
    Write-Host "Creating S3 bucket..." -ForegroundColor Yellow
    aws s3 mb s3://$BucketName --region $Region
}

# Create OIDC providers
Write-Host "Creating OIDC Identity Providers..." -ForegroundColor Yellow

# Internal tenant OIDC provider
aws iam create-open-id-connect-provider `
    --url "https://login.microsoftonline.com/$InternalTenantId/v2.0" `
    --thumbprint-list "626D44E704D1CEABE3BF0D53397464AC8080142C" `
    --client-id-list $InternalClientId

# External tenant OIDC provider  
aws iam create-open-id-connect-provider `
    --url "https://login.microsoftonline.com/$ExternalTenantId/v2.0" `
    --thumbprint-list "626D44E704D1CEABE3BF0D53397464AC8080142C" `
    --client-id-list $ExternalClientId

Write-Host "✅ OIDC providers created" -ForegroundColor Green

# Update .env file with account ID
Write-Host "Updating .env file with AWS Account ID..." -ForegroundColor Yellow
$EnvContent = Get-Content ".env" -Raw
$EnvContent = $EnvContent -replace "AWS_ROLE_CUSTOMER='arn:aws:iam::ACCOUNT:role/CustomerRole'", "AWS_ROLE_CUSTOMER='arn:aws:iam::$AwsAccountId:role/CustomerRole'"
$EnvContent = $EnvContent -replace "AWS_ROLE_AGENT='arn:aws:iam::ACCOUNT:role/AgentRole'", "AWS_ROLE_AGENT='arn:aws:iam::$AwsAccountId:role/AgentRole'"
Set-Content ".env" $EnvContent

Write-Host "✅ Environment file updated" -ForegroundColor Green
Write-Host "Next: Create IAM roles manually in AWS Console or continue with CLI commands" -ForegroundColor Cyan
```

## Step 6: Manual AWS Console Setup

### Create S3 Bucket
1. Go to **S3 Console**
2. Click **Create bucket**
3. **Bucket name**: `scape-travel-docs`
4. **Region**: `Europe (Ireland) eu-west-1`
5. Configure permissions as needed
6. Click **Create bucket**

### Create OIDC Identity Providers
1. Go to **IAM Console** → **Identity providers**
2. Click **Add provider**
3. **Provider type**: OpenID Connect
4. **Provider URL**: `https://login.microsoftonline.com/48a85b75-4e7d-4d8c-9cc6-a72722124be8/v2.0`
5. **Audience**: `756223c3-0313-4195-8540-b03063366f3a`
6. **Thumbprint**: `626D44E704D1CEABE3BF0D53397464AC8080142C`
7. Repeat for external tenant

### Create IAM Roles
1. Go to **IAM Console** → **Roles**
2. Click **Create role**
3. **Trusted entity type**: Web identity
4. **Identity provider**: Select your OIDC provider
5. **Audience**: Your client ID
6. Add appropriate policies
7. **Role name**: `CustomerRole` or `AgentRole`

## Testing & Validation

### Test S3 Access
```bash
# Test bucket access
aws s3 ls s3://scape-travel-docs --region eu-west-1

# Upload test file
echo "Test document" > test.txt
aws s3 cp test.txt s3://scape-travel-docs/test.txt

# Verify upload
aws s3 ls s3://scape-travel-docs/
```

### Test Role Assumption
Use your PHP application to test federated authentication and role assumption.

## Security Considerations

### Bucket Policy Example
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "CustomerReadAccess",
      "Effect": "Allow",
      "Principal": {
        "AWS": "arn:aws:iam::YOUR-ACCOUNT-ID:role/CustomerRole"
      },
      "Action": [
        "s3:GetObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::scape-travel-docs",
        "arn:aws:s3:::scape-travel-docs/customers/*"
      ]
    },
    {
      "Sid": "AgentFullAccess",
      "Effect": "Allow",
      "Principal": {
        "AWS": "arn:aws:iam::YOUR-ACCOUNT-ID:role/AgentRole"
      },
      "Action": [
        "s3:*"
      ],
      "Resource": [
        "arn:aws:s3:::scape-travel-docs",
        "arn:aws:s3:::scape-travel-docs/*"
      ]
    }
  ]
}
```

## Next Steps
1. **Get your AWS Account ID**
2. **Run the setup script** with your account ID
3. **Create IAM roles** with federated trust policies
4. **Test federated authentication** through your PHP app
5. **Validate S3 access** with different user types

Would you like me to help you get started with any specific step?
