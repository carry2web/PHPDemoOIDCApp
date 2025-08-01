# AWS Security Checklist for S-Cape Travel

## 🚨 IMMEDIATE ACTION REQUIRED: Root Access Key Security

### Current Situation
- ❌ You're using root access keys (DANGEROUS!)
- ⚠️ Root keys have unlimited access to your entire AWS account
- 🎯 We need to create a dedicated IAM user immediately

## Step-by-Step Security Migration

### Phase 1: Create IAM User (Do This First!)

1. **Login to AWS Console** as root user
2. **Go to IAM** → **Users** → **Create user**
3. **User details:**
   - Username: `scape-travel-service`
   - Access type: ✅ Programmatic access only
   - Console access: ❌ Not needed

4. **Create Custom Policy** (click "Create policy" → JSON tab):
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "ScapeTravelS3Access",
      "Effect": "Allow",
      "Action": [
        "s3:CreateBucket",
        "s3:ListBucket",
        "s3:GetBucketLocation",
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject",
        "s3:GetBucketVersioning",
        "s3:PutBucketVersioning"
      ],
      "Resource": [
        "arn:aws:s3:::scape-travel-docs",
        "arn:aws:s3:::scape-travel-docs/*"
      ]
    },
    {
      "Sid": "ScapeTravelIAMAccess",
      "Effect": "Allow",
      "Action": [
        "iam:CreateOpenIDConnectProvider",
        "iam:GetOpenIDConnectProvider",
        "iam:ListOpenIDConnectProviders",
        "iam:CreateRole",
        "iam:GetRole",
        "iam:PutRolePolicy",
        "iam:AttachRolePolicy",
        "iam:CreatePolicy",
        "iam:GetPolicy"
      ],
      "Resource": "*"
    },
    {
      "Sid": "STSAccess",
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

5. **Policy name**: `ScapeTravelPolicy`
6. **Attach policy** to your new user
7. **⚠️ CRITICAL**: Download the CSV file with access keys - you'll never see the secret again!

### Phase 2: Test New IAM User

```powershell
# Configure AWS CLI with new IAM user
aws configure

# Test the new credentials
aws sts get-caller-identity

# Expected output:
# {
#   "UserId": "AIDAXXXXXXXXXXXXXXXXX",
#   "Account": "123456789012", 
#   "Arn": "arn:aws:iam::123456789012:user/scape-travel-service"
# }
```

### Phase 3: Run S-Cape Travel Setup

```powershell
# Get your AWS Account ID
$AccountId = aws sts get-caller-identity --query Account --output text

# Run the setup script with proper IAM user
.\aws-setup.ps1 -AwsAccountId $AccountId -Region "eu-west-1"
```

### Phase 4: Secure Your Root Account

1. **Delete root access keys** immediately after testing IAM user:
   - AWS Console → Security Credentials → Access keys → Delete
2. **Enable MFA** on root account:
   - AWS Console → Security Credentials → Multi-factor authentication
3. **Store root credentials** in a secure password manager
4. **Only use root account** for billing and account closure

## Security Best Practices Checklist

### ✅ Immediate Actions
- [ ] Create IAM user `scape-travel-service`
- [ ] Attach limited-scope policy
- [ ] Generate new access keys for IAM user
- [ ] Test IAM user can run `aws sts get-caller-identity`
- [ ] Delete root access keys
- [ ] Enable MFA on root account

### ✅ Ongoing Security
- [ ] Rotate IAM user keys every 90 days
- [ ] Monitor AWS CloudTrail for unusual activity
- [ ] Set up billing alerts
- [ ] Review IAM permissions monthly
- [ ] Use AWS Config for compliance monitoring

### ✅ Project-Specific Security
- [ ] S3 bucket uses restrictive bucket policies
- [ ] IAM roles use principle of least privilege
- [ ] OIDC providers only accept your specific client IDs
- [ ] Customer role can only read customer documents
- [ ] Agent role has limited write access

## Emergency Procedures

### If Root Keys Are Compromised
1. **Immediately disable** root access keys in AWS Console
2. **Check CloudTrail** for unauthorized activity
3. **Review all AWS resources** for unexpected changes
4. **Contact AWS Support** if needed
5. **Generate new IAM user keys**

### If IAM User Keys Are Compromised
1. **Disable/delete** the IAM user's access keys
2. **Generate new access keys** for the IAM user
3. **Update your application** with new keys
4. **Review CloudTrail** for unauthorized activity

## Next Steps After Securing AWS

1. **Run**: `.\aws-setup.ps1 -AwsAccountId YOUR-ACCOUNT-ID`
2. **Test**: Authentication flows in your PHP app
3. **Monitor**: CloudTrail logs for the first few days
4. **Document**: Store IAM user credentials securely

---

## 🎯 Quick Commands Reference

```powershell
# Check current identity
aws sts get-caller-identity

# List S3 buckets (test permissions)
aws s3 ls

# Get account ID
aws sts get-caller-identity --query Account --output text

# Run S-Cape Travel setup
.\aws-setup.ps1 -AwsAccountId $(aws sts get-caller-identity --query Account --output text)
```

**Remember**: Never commit AWS credentials to version control!
