# AWS IAM User Creation - Step by Step Guide (2025)

## 🎯 Goal: Create `scape-travel-service` IAM User

### Step 1: Navigate to IAM
1. Login to [AWS Console](https://console.aws.amazon.com)
2. Search for "IAM" in the top search bar
3. Click **IAM** service

### Step 2: Create User
1. In IAM Dashboard, click **Users** (left sidebar)
2. Click **Create user** (blue button)

### Step 3: User Details Page
```
┌─────────────────────────────────────────┐
│ User details                            │
├─────────────────────────────────────────┤
│ User name: scape-travel-service         │
│                                         │
│ ☐ Provide user access to the AWS       │
│   Management Console - optional        │
│   ↳ LEAVE THIS UNCHECKED!              │
│                                         │
│ [Next] ────────────────────────────────→│
└─────────────────────────────────────────┘
```

### Step 4: Set Permissions Page
1. Select **Attach policies directly**
2. Click **Create policy** (opens new tab)
3. **In the new tab:**
   - Click **JSON** tab
   - Delete existing content
   - Paste the custom policy (see below)
   - Click **Next**
   - **Policy name**: `ScapeTravelPolicy`
   - **Description**: `Policy for S-Cape Travel cross-tenant authentication`
   - Click **Create policy**
4. **Return to user creation tab:**
   - Click refresh button (🔄) next to search
   - Search for `ScapeTravelPolicy`
   - ✅ Check the box next to your policy
   - Click **Next**

### Step 5: Review and Create
1. Review settings:
   - User name: `scape-travel-service`
   - Permissions: `ScapeTravelPolicy`
2. **Tags** (optional): 
   - Key: `Project`, Value: `S-Cape Travel`
3. Click **Create user**

### Step 6: Create Access Key (CRITICAL!)
1. After user creation, click on the user name `scape-travel-service`
2. Click **Security credentials** tab
3. Scroll down to **Access keys** section
4. Click **Create access key**
5. **Select use case**: Choose **"Application running outside AWS"** ⭐
   ```
   ┌─────────────────────────────────────────┐
   │ Select your use case:                   │
   │                                         │
   │ ⚪ Command Line Interface (CLI)         │
   │ ⚪ Local code                           │
   │ 🔘 Application running outside AWS      │ ← SELECT THIS
   │ ⚪ Third-party service                  │
   │ ⚪ Other                                │
   └─────────────────────────────────────────┘
   ```
6. ✅ Check: "I understand the above recommendation..."
7. Click **Next**
8. **Description** (optional): `S-Cape Travel PHP app on Azure Web Apps`
9. Click **Create access key**
10. **⚠️ CRITICAL STEP**: 
    - **Download .csv file** OR
    - **Copy both keys** to a secure location
    - You will NEVER see the Secret Access Key again!

## Custom Policy JSON

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
      "Resource": "*"
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

## What You Should Have Now
- ✅ IAM User: `scape-travel-service`
- ✅ Custom Policy: `ScapeTravelPolicy` attached
- ✅ Access Key ID and Secret Access Key downloaded
- ❌ NO Console access (more secure)

## Next Steps
1. Configure AWS CLI with your new keys:
   ```
   aws configure
   ```
2. Test the setup:
   ```
   aws sts get-caller-identity
   ```
   Should show: `"Arn": "arn:aws:iam::123456789012:user/scape-travel-service"`

3. Run the S-Cape Travel setup script:
   ```
   .\aws-setup.ps1 -AwsAccountId $(aws sts get-caller-identity --query Account --output text)
   ```

## Common Issues

### Issue: "No policies found"
- **Solution**: Make sure you clicked the refresh button (🔄) after creating the policy

### Issue: "Access denied" when testing
- **Solution**: Verify the policy JSON was pasted correctly and user has the policy attached

### Issue: "InvalidUserType.NotSupported"
- **Solution**: Make sure you UNCHECKED console access during user creation

## Security Notes
- ✅ This user has minimal permissions (only what S-Cape Travel needs)
- ✅ No console access reduces attack surface
- ✅ Can be easily revoked or rotated
- ❌ Never share these keys or commit them to version control

Ready to test your new IAM user? Use `aws configure` with your new keys!
