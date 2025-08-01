# S-Cape Travel AWS Setup Script
# This script sets up AWS resources for federated authentication with Microsoft Entra ID

param(
    [Parameter(Mandatory=$true)]
    [string]$AwsAccountId,
    
    [Parameter(Mandatory=$false)]
    [string]$Region = "eu-west-1",
    
    [Parameter(Mandatory=$false)]
    [string]$BucketName = "scape-travel-docs"
)

Write-Host "🚀 S-Cape Travel AWS Setup Script" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host "AWS Account ID: $AwsAccountId" -ForegroundColor Green
Write-Host "Region: $Region" -ForegroundColor Green
Write-Host "S3 Bucket: $BucketName" -ForegroundColor Green
Write-Host ""

# Configuration from .env
$InternalTenantId = "48a85b75-4e7d-4d8c-9cc6-a72722124be8"
$ExternalTenantId = "37a2c2da-5eec-4680-b380-2c0a72013f67"
$InternalClientId = "756223c3-0313-4195-8540-b03063366f3a"
$ExternalClientId = "2d24e26e-99ee-4232-8921-06b161b65bb5"

# Step 1: Check AWS CLI configuration
Write-Host "🔧 Step 1: Checking AWS CLI configuration..." -ForegroundColor Yellow
try {
    $CallerIdentity = aws sts get-caller-identity --output json | ConvertFrom-Json
    Write-Host "✅ AWS CLI configured for account: $($CallerIdentity.Account)" -ForegroundColor Green
    
    if ($CallerIdentity.Account -ne $AwsAccountId) {
        Write-Host "⚠️  Warning: CLI account ($($CallerIdentity.Account)) differs from provided account ($AwsAccountId)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ AWS CLI not configured or not accessible" -ForegroundColor Red
    Write-Host "Please run: aws configure" -ForegroundColor Yellow
    exit 1
}

# Step 2: Create/verify S3 bucket
Write-Host ""
Write-Host "🪣 Step 2: Setting up S3 bucket..." -ForegroundColor Yellow
try {
    aws s3 ls s3://$BucketName --region $Region 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ S3 bucket '$BucketName' already exists" -ForegroundColor Green
    } else {
        Write-Host "Creating S3 bucket '$BucketName'..." -ForegroundColor Cyan
        aws s3 mb s3://$BucketName --region $Region
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ S3 bucket '$BucketName' created successfully" -ForegroundColor Green
        } else {
            Write-Host "❌ Failed to create S3 bucket" -ForegroundColor Red
            exit 1
        }
    }
} catch {
    Write-Host "❌ Error accessing S3: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Step 3: Create folder structure in S3
Write-Host ""
Write-Host "📁 Step 3: Creating S3 folder structure..." -ForegroundColor Yellow
$Folders = @("customers/", "agents/", "shared/", "templates/")
foreach ($Folder in $Folders) {
    Write-Host "Creating folder: $Folder" -ForegroundColor Cyan
    $TempFile = New-TemporaryFile
    "" | Out-File -FilePath $TempFile.FullName
    aws s3 cp $TempFile.FullName s3://$BucketName/$Folder.gitkeep --region $Region
    Remove-Item $TempFile.FullName
}
Write-Host "✅ S3 folder structure created" -ForegroundColor Green

# Step 4: Create OIDC Identity Providers
Write-Host ""
Write-Host "🔐 Step 4: Creating OIDC Identity Providers..." -ForegroundColor Yellow

# Microsoft's thumbprint for login.microsoftonline.com
$MsftThumbprint = "626D44E704D1CEABE3BF0D53397464AC8080142C"

# Internal tenant OIDC provider (B2B - Agents)
Write-Host "Creating OIDC provider for Internal tenant (B2B)..." -ForegroundColor Cyan
$InternalOidcUrl = "https://login.microsoftonline.com/$InternalTenantId/v2.0"
try {
    aws iam create-open-id-connect-provider `
        --url $InternalOidcUrl `
        --thumbprint-list $MsftThumbprint `
        --client-id-list $InternalClientId `
        --output json 2>$null
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Internal tenant OIDC provider created" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Internal tenant OIDC provider may already exist" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Could not create Internal OIDC provider (may already exist)" -ForegroundColor Yellow
}

# External tenant OIDC provider (B2C - Customers)
Write-Host "Creating OIDC provider for External tenant (B2C)..." -ForegroundColor Cyan
$ExternalOidcUrl = "https://login.microsoftonline.com/$ExternalTenantId/v2.0"
try {
    aws iam create-open-id-connect-provider `
        --url $ExternalOidcUrl `
        --thumbprint-list $MsftThumbprint `
        --client-id-list $ExternalClientId `
        --output json 2>$null
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ External tenant OIDC provider created" -ForegroundColor Green
    } else {
        Write-Host "⚠️  External tenant OIDC provider may already exist" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Could not create External OIDC provider (may already exist)" -ForegroundColor Yellow
}

# Step 5: Create IAM policies
Write-Host ""
Write-Host "📜 Step 5: Creating IAM policies..." -ForegroundColor Yellow

# Customer policy (read-only)
$CustomerPolicy = @{
    Version = "2012-10-17"
    Statement = @(
        @{
            Effect = "Allow"
            Action = @(
                "s3:GetObject",
                "s3:ListBucket"
            )
            Resource = @(
                "arn:aws:s3:::$BucketName",
                "arn:aws:s3:::$BucketName/customers/*",
                "arn:aws:s3:::$BucketName/shared/*"
            )
        }
    )
} | ConvertTo-Json -Depth 5

$CustomerPolicyFile = "customer-policy.json"
$CustomerPolicy | Out-File -FilePath $CustomerPolicyFile -Encoding UTF8

Write-Host "Creating Customer policy..." -ForegroundColor Cyan
try {
    aws iam create-policy `
        --policy-name "SCapeCustomerS3Policy" `
        --policy-document file://$CustomerPolicyFile `
        --description "S-Cape Travel Customer S3 read-only access" `
        --output json
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Customer policy created" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Customer policy may already exist" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Could not create Customer policy (may already exist)" -ForegroundColor Yellow
}

# Agent policy (read-write)
$AgentPolicy = @{
    Version = "2012-10-17"
    Statement = @(
        @{
            Effect = "Allow"
            Action = @(
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            )
            Resource = @(
                "arn:aws:s3:::$BucketName",
                "arn:aws:s3:::$BucketName/*"
            )
        }
    )
} | ConvertTo-Json -Depth 5

$AgentPolicyFile = "agent-policy.json"
$AgentPolicy | Out-File -FilePath $AgentPolicyFile -Encoding UTF8

Write-Host "Creating Agent policy..." -ForegroundColor Cyan
try {
    aws iam create-policy `
        --policy-name "SCapeAgentS3Policy" `
        --policy-document file://$AgentPolicyFile `
        --description "S-Cape Travel Agent S3 full access" `
        --output json
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Agent policy created" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Agent policy may already exist" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Could not create Agent policy (may already exist)" -ForegroundColor Yellow
}

# Step 6: Create IAM roles
Write-Host ""
Write-Host "👥 Step 6: Creating IAM roles..." -ForegroundColor Yellow

# Customer role trust policy
$CustomerTrustPolicy = @{
    Version = "2012-10-17"
    Statement = @(
        @{
            Effect = "Allow"
            Principal = @{
                Federated = "arn:aws:iam::$AwsAccountId`:oidc-provider/login.microsoftonline.com/$ExternalTenantId/v2.0"
            }
            Action = "sts:AssumeRoleWithWebIdentity"
            Condition = @{
                StringEquals = @{
                    "login.microsoftonline.com/$ExternalTenantId/v2.0:aud" = $ExternalClientId
                }
            }
        }
    )
} | ConvertTo-Json -Depth 6

$CustomerTrustFile = "customer-trust-policy.json"
$CustomerTrustPolicy | Out-File -FilePath $CustomerTrustFile -Encoding UTF8

Write-Host "Creating CustomerRole..." -ForegroundColor Cyan
try {
    aws iam create-role `
        --role-name "CustomerRole" `
        --assume-role-policy-document file://$CustomerTrustFile `
        --description "S-Cape Travel Customer role for S3 access" `
        --output json
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ CustomerRole created" -ForegroundColor Green
        
        # Attach policy to role
        aws iam attach-role-policy `
            --role-name "CustomerRole" `
            --policy-arn "arn:aws:iam::$AwsAccountId`:policy/SCapeCustomerS3Policy"
        Write-Host "✅ Customer policy attached to role" -ForegroundColor Green
    } else {
        Write-Host "⚠️  CustomerRole may already exist" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Could not create CustomerRole (may already exist)" -ForegroundColor Yellow
}

# Agent role trust policy
$AgentTrustPolicy = @{
    Version = "2012-10-17"
    Statement = @(
        @{
            Effect = "Allow"
            Principal = @{
                Federated = "arn:aws:iam::$AwsAccountId`:oidc-provider/login.microsoftonline.com/$InternalTenantId/v2.0"
            }
            Action = "sts:AssumeRoleWithWebIdentity"
            Condition = @{
                StringEquals = @{
                    "login.microsoftonline.com/$InternalTenantId/v2.0:aud" = $InternalClientId
                }
            }
        }
    )
} | ConvertTo-Json -Depth 6

$AgentTrustFile = "agent-trust-policy.json"
$AgentTrustPolicy | Out-File -FilePath $AgentTrustFile -Encoding UTF8

Write-Host "Creating AgentRole..." -ForegroundColor Cyan
try {
    aws iam create-role `
        --role-name "AgentRole" `
        --assume-role-policy-document file://$AgentTrustFile `
        --description "S-Cape Travel Agent role for S3 access" `
        --output json
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ AgentRole created" -ForegroundColor Green
        
        # Attach policy to role
        aws iam attach-role-policy `
            --role-name "AgentRole" `
            --policy-arn "arn:aws:iam::$AwsAccountId`:policy/SCapeAgentS3Policy"
        Write-Host "✅ Agent policy attached to role" -ForegroundColor Green
    } else {
        Write-Host "⚠️  AgentRole may already exist" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Could not create AgentRole (may already exist)" -ForegroundColor Yellow
}

# Step 7: Update .env file
Write-Host ""
Write-Host "📝 Step 7: Updating .env file with AWS Account ID..." -ForegroundColor Yellow

if (Test-Path ".env") {
    $EnvContent = Get-Content ".env" -Raw
    $EnvContent = $EnvContent -replace "AWS_ROLE_CUSTOMER='arn:aws:iam::ACCOUNT:role/CustomerRole'", "AWS_ROLE_CUSTOMER='arn:aws:iam::$AwsAccountId`:role/CustomerRole'"
    $EnvContent = $EnvContent -replace "AWS_ROLE_AGENT='arn:aws:iam::ACCOUNT:role/AgentRole'", "AWS_ROLE_AGENT='arn:aws:iam::$AwsAccountId`:role/AgentRole'"
    Set-Content ".env" $EnvContent -Encoding UTF8
    Write-Host "✅ .env file updated with AWS Account ID" -ForegroundColor Green
} else {
    Write-Host "⚠️  .env file not found in current directory" -ForegroundColor Yellow
}

# Step 8: Clean up temporary files
Write-Host ""
Write-Host "🧹 Step 8: Cleaning up temporary files..." -ForegroundColor Yellow
$TempFiles = @($CustomerPolicyFile, $AgentPolicyFile, $CustomerTrustFile, $AgentTrustFile)
foreach ($File in $TempFiles) {
    if (Test-Path $File) {
        Remove-Item $File
        Write-Host "Removed: $File" -ForegroundColor Gray
    }
}
Write-Host "✅ Cleanup completed" -ForegroundColor Green

# Step 9: Summary and next steps
Write-Host ""
Write-Host "🎉 AWS Setup Complete!" -ForegroundColor Green
Write-Host "=====================" -ForegroundColor Green
Write-Host ""
Write-Host "Created Resources:" -ForegroundColor Cyan
Write-Host "• S3 Bucket: $BucketName" -ForegroundColor White
Write-Host "• OIDC Providers: Internal + External tenants" -ForegroundColor White
Write-Host "• IAM Policies: SCapeCustomerS3Policy, SCapeAgentS3Policy" -ForegroundColor White
Write-Host "• IAM Roles: CustomerRole, AgentRole" -ForegroundColor White
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Test your PHP application with federated authentication" -ForegroundColor White
Write-Host "2. Upload test documents to S3 bucket" -ForegroundColor White
Write-Host "3. Verify role-based access works correctly" -ForegroundColor White
Write-Host "4. Run the cross-tenant diagnostic tool: admin/cross_tenant_check.php" -ForegroundColor White
Write-Host ""
Write-Host "Updated .env variables:" -ForegroundColor Cyan
Write-Host "AWS_ROLE_CUSTOMER='arn:aws:iam::$AwsAccountId`:role/CustomerRole'" -ForegroundColor Gray
Write-Host "AWS_ROLE_AGENT='arn:aws:iam::$AwsAccountId`:role/AgentRole'" -ForegroundColor Gray
