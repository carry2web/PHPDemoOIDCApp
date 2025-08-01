# AWS Credentials Setup for Azure

## 🔑 Missing AWS Credentials

Your application needs AWS credentials to access S3. Add these to Azure Application Settings:

### Option 1: Use Existing AWS User Credentials

If you have AWS CLI configured or an existing IAM user:

1. **Find your credentials:**
   - Check `~/.aws/credentials` file
   - Or use AWS CLI: `aws configure list`
   - Or check AWS Console → IAM → Users → [Your User] → Security credentials

2. **Add to Azure Application Settings:**
   - **Name:** `AWS_ACCESS_KEY_ID`
   - **Value:** `[Your AWS Access Key ID - starts with AKIA...]`
   - **Name:** `AWS_SECRET_ACCESS_KEY`  
   - **Value:** `[Your AWS Secret Access Key - 40 character string]`

### Option 2: Create New IAM User (Recommended)

1. **AWS Console → IAM → Users → Create user**
   - User name: `scape-travel-app`
   - Access type: ✅ Programmatic access

2. **Attach policies:**
   - `AmazonS3FullAccess` (or custom policy for your bucket)
   - OR create custom policy:
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
           },
           {
               "Effect": "Allow",
               "Action": "sts:AssumeRole",
               "Resource": [
                   "arn:aws:iam::955654668431:role/CustomerRole",
                   "arn:aws:iam::955654668431:role/AgentRole"
               ]
           }
       ]
   }
   ```

3. **Copy credentials** from the success page (you won't see them again!)

### Option 3: Use Environment Variables from .env

If you have AWS credentials in your local `.env` file, add them:

```
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
```

## 🚀 After Adding Credentials

1. **Save** Application Settings in Azure Portal
2. **Restart** your App Service
3. **Test** the debug page - should show ✅ for AWS credentials
4. **Test** document upload/download functionality

## 🔒 Security Notes

- Never commit AWS credentials to git
- Use IAM users with minimal required permissions
- Consider using AWS IAM roles for EC2 if moving to AWS later
- Regularly rotate access keys

## 📝 Current Status

Based on your debug output:
- ✅ All authentication variables configured
- ❌ Missing AWS_ACCESS_KEY_ID  
- ❌ Missing AWS_SECRET_ACCESS_KEY
- ✅ Graph API "405 error" is normal (root endpoint doesn't accept GET)

## 🎯 Next Steps

1. Add AWS credentials to Azure Application Settings
2. Restart App Service  
3. Test debug page: `https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/debug_azure.php`
4. Test main application with file upload/download
