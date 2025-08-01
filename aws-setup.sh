#!/bin/bash
# AWS Setup Script for S-Cape Travel
# Run this script to set up AWS resources

set -e

# Configuration
BUCKET_NAME="scape-travel-docs"
REGION="eu-west-1"
ACCOUNT_ID="YOUR-ACCOUNT-ID-HERE"  # Replace with your AWS Account ID

echo "🚀 Setting up AWS resources for S-Cape Travel..."

# 1. Create S3 Bucket
echo "📁 Creating S3 bucket: $BUCKET_NAME"
aws s3 mb s3://$BUCKET_NAME --region $REGION || echo "Bucket may already exist"

# 2. Create folder structure
echo "📂 Creating folder structure..."
aws s3api put-object --bucket $BUCKET_NAME --key customers/ --region $REGION
aws s3api put-object --bucket $BUCKET_NAME --key agents/ --region $REGION  
aws s3api put-object --bucket $BUCKET_NAME --key employees/ --region $REGION

# 3. Upload sample documents
echo "📄 Creating and uploading sample documents..."

# Customer sample
cat > customer-sample.pdf << 'EOF'
# Customer Travel Package Information

## Welcome to S-Cape Travel!

### Your Premium Travel Package Includes:
- Luxury accommodation bookings
- Private transportation arrangements  
- Exclusive tour guides
- 24/7 concierge service
- Travel insurance coverage

### Important Documents:
- Flight confirmations
- Hotel vouchers
- Local contact information
- Emergency procedures

For any questions, contact our customer service team.

**This is a sample customer document.**
EOF

# Agent sample
cat > agent-sample.pdf << 'EOF'
# S-Cape Travel Agent Resources

## Commission Structure
- Luxury packages: 15% commission
- Standard packages: 12% commission  
- Group bookings: 18% commission
- Repeat customers: +2% bonus

## Marketing Materials Available:
- Brochures and flyers
- Digital marketing assets
- Website integration tools
- Customer testimonials

## Agent Portal Features:
- Real-time booking system
- Commission tracking
- Customer management
- Support resources

**This is a sample agent document.**
EOF

# Employee sample  
cat > employee-sample.pdf << 'EOF'
# S-Cape Travel Employee Handbook

## Company Policies
- Working hours and flexibility
- Remote work guidelines
- Professional development
- Benefits and compensation

## Internal Resources:
- Employee directory
- IT support procedures
- HR policies and forms
- Training materials

## Contact Information:
- HR Department
- IT Support
- Management team
- Emergency contacts

**This is a sample employee document.**
EOF

# Upload samples
aws s3 cp customer-sample.pdf s3://$BUCKET_NAME/customers/
aws s3 cp agent-sample.pdf s3://$BUCKET_NAME/agents/
aws s3 cp employee-sample.pdf s3://$BUCKET_NAME/employees/

# 4. Create IAM policies
echo "🔐 Creating IAM policies..."

# Customer policy
cat > customer-policy.json << EOF
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
        "arn:aws:s3:::$BUCKET_NAME/customers/*",
        "arn:aws:s3:::$BUCKET_NAME"
      ],
      "Condition": {
        "StringLike": {
          "s3:prefix": ["customers/"]
        }
      }
    }
  ]
}
EOF

# Agent policy  
cat > agent-policy.json << EOF
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
        "arn:aws:s3:::$BUCKET_NAME/agents/*",
        "arn:aws:s3:::$BUCKET_NAME/customers/*", 
        "arn:aws:s3:::$BUCKET_NAME"
      ],
      "Condition": {
        "StringLike": {
          "s3:prefix": ["agents/", "customers/"]
        }
      }
    }
  ]
}
EOF

# Trust policy template
cat > trust-policy.json << EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": {
        "Federated": "arn:aws:iam::$ACCOUNT_ID:oidc-provider/login.microsoftonline.com/TENANT-ID"
      },
      "Action": "sts:AssumeRoleWithWebIdentity",
      "Condition": {
        "StringEquals": {
          "login.microsoftonline.com/TENANT-ID:aud": "CLIENT-ID"
        }
      }
    }
  ]
}
EOF

# 5. Create IAM policies
aws iam create-policy \
  --policy-name ScapeCustomerS3Policy \
  --policy-document file://customer-policy.json \
  --description "S3 access for S-Cape Travel customers" || echo "Policy may already exist"

aws iam create-policy \
  --policy-name ScapeAgentS3Policy \
  --policy-document file://agent-policy.json \
  --description "S3 access for S-Cape Travel agents" || echo "Policy may already exist"

# 6. Create IAM roles (you'll need to update trust policies with real tenant/client IDs)
echo "📋 Creating IAM roles..."
echo "⚠️  Note: You'll need to update trust policies with real Tenant IDs and Client IDs"

aws iam create-role \
  --role-name CustomerRole \
  --assume-role-policy-document file://trust-policy.json \
  --description "Role for S-Cape Travel customers" || echo "Role may already exist"

aws iam create-role \
  --role-name AgentRole \
  --assume-role-policy-document file://trust-policy.json \
  --description "Role for S-Cape Travel agents" || echo "Role may already exist"

# 7. Attach policies to roles
aws iam attach-role-policy \
  --role-name CustomerRole \
  --policy-arn arn:aws:iam::$ACCOUNT_ID:policy/ScapeCustomerS3Policy

aws iam attach-role-policy \
  --role-name AgentRole \
  --policy-arn arn:aws:iam::$ACCOUNT_ID:policy/ScapeAgentS3Policy

# 8. Clean up temporary files
rm -f customer-sample.pdf agent-sample.pdf employee-sample.pdf
rm -f customer-policy.json agent-policy.json trust-policy.json

# 9. Display results
echo ""
echo "✅ AWS Setup Complete!"
echo ""
echo "📋 Update your .env file with these values:"
echo "AWS_S3_BUCKET='$BUCKET_NAME'"
echo "AWS_ROLE_CUSTOMER='arn:aws:iam::$ACCOUNT_ID:role/CustomerRole'"
echo "AWS_ROLE_AGENT='arn:aws:iam::$ACCOUNT_ID:role/AgentRole'"
echo ""
echo "⚠️  Next Steps:"
echo "1. Replace 'YOUR-ACCOUNT-ID-HERE' with your actual AWS Account ID in this script"
echo "2. Update trust policies with real Microsoft Tenant IDs and Client IDs"
echo "3. Create OIDC identity providers for Microsoft tenants (if using JWT tokens)"
echo "4. Test S3 access with your application"
echo ""
echo "🔗 Useful Commands:"
echo "Get AWS Account ID: aws sts get-caller-identity --query Account --output text"
echo "List S3 objects: aws s3 ls s3://$BUCKET_NAME --recursive"
echo "Test role assumption: aws sts assume-role --role-arn arn:aws:iam::$ACCOUNT_ID:role/CustomerRole --role-session-name test"
