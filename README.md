# S-Cape Travel - Cross-Tenant Authentication Platform

A comprehensive enterprise application demonstrating advanced Microsoft Entra ID B2B/B2C cross-tenant authentication with AWS S3 integration and document management capabilities.

## 🌟 Features

### 🔐 Multi-Tenant Authentication
- **B2B Internal Tenant**: S-Cape employees and partner agents
- **B2C External Tenant**: Customer registration and authentication  
- **Cross-tenant user switching**: Seamless role-based access
- **Microsoft Graph API integration**: User management and email notifications

### 📁 Document Management
- **AWS S3 integration**: Secure document storage with federated access
- **Role-based permissions**: Customer vs Agent document access
- **Upload/download functionality**: Multi-file support with progress tracking
- **Document security**: IAM role-based access control

### 🔧 Enterprise Features
- **Admin portal**: Agent approval workflows and cross-tenant management
- **Email notifications**: Graph API-powered email system
- **Comprehensive logging**: Azure-compatible structured logging
- **Debug tools**: Advanced diagnostics and monitoring
- **CI/CD workflows**: GitHub Actions automated deployment

## 🏗️ Architecture

### Authentication Flow
```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Customers     │    │   S-Cape App     │    │   Employees &   │
│   (B2C Tenant)  │◄──►│  (Hub Platform)  │◄──►│  Agents (B2B)   │
│ scapecustomers  │    │                  │    │ S-Cape Partners │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │   AWS S3 with    │
                    │ Federated Access │
                    │  Customer/Agent  │
                    │      Roles       │
                    └──────────────────┘
```

### Technology Stack
- **Backend**: PHP 8.2 with modern OOP architecture
- **Authentication**: Microsoft Entra ID B2B/B2C with OpenID Connect
- **Cloud Storage**: AWS S3 with IAM federated access
- **Email**: Microsoft Graph API
- **Hosting**: Azure Web Apps (Linux)
- **CI/CD**: GitHub Actions

## 📁 Project Structure

```
📦 S-Cape Travel Application
├── 🔑 Authentication & Core
│   ├── index.php              # Multi-tenant login hub
│   ├── callback.php           # OIDC authentication handler
│   ├── dashboard.php          # User dashboard with role detection
│   └── logout.php             # Secure logout with tenant cleanup
├── 📋 User Management
│   ├── apply_agent.php        # Agent application form
│   ├── register_customer.php  # Customer registration
│   └── documents.php          # Document management interface
├── 🛠️ Admin Portal
│   └── admin/
│       ├── agents.php         # Agent approval workflow
│       ├── config_check.php   # System configuration validator
│       └── cross_tenant_check.php # Cross-tenant diagnostics
├── 📚 Core Libraries
│   └── lib/
│       ├── config_helper.php  # Multi-tenant configuration
│       ├── oidc_simple.php    # Enhanced OIDC client
│       ├── aws_helper.php     # AWS S3 federated access
│       ├── document_manager.php # Document operations
│       ├── email_helper.php   # Graph API email service
│       ├── graph_helper.php   # Microsoft Graph integration
│       └── logger.php         # Structured logging system
├── 🔧 DevOps & Debugging
│   ├── debug_azure.php        # Comprehensive Azure diagnostics
│   ├── error_catcher.php      # Global error handler
│   ├── env_setup.php          # Environment setup helper
│   └── .github/workflows/     # CI/CD automation
└── 📖 Documentation
    ├── AWS_SETUP_GUIDE.md     # AWS infrastructure setup
    ├── DEPLOYMENT_CHECKLIST.md # Production deployment guide
    ├── CROSS_TENANT_CHECKLIST.md # Multi-tenant configuration
    └── aws_credentials_helper.md # AWS credentials guide
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Microsoft Entra ID B2B and B2C tenants
- AWS account with S3 bucket
- Azure Web Apps (for production)

### Local Development Setup

1. **Clone and install dependencies:**
   ```bash
   git clone https://github.com/carry2web/PHPDemoOIDCApp.git
   cd PHPDemoOIDCApp
   composer install
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your tenant and AWS credentials
   ```

3. **Start development server:**
   ```bash
   php -S localhost:8000
   ```

4. **Access via HTTPS tunnel (required for OIDC):**
   ```bash
   # Using ngrok
   ngrok http 8000
   # Update REDIRECT_URI in .env with ngrok URL
   ```

## 🌐 Production Deployment

### Azure Web Apps Deployment

The application is configured for automatic deployment via GitHub Actions:

1. **Live Application**: [https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/](https://scapecustomers-hvhpchb9hwc6e5cb.westeurope-01.azurewebsites.net/)

2. **Deployment Status**: [![Deploy to Azure](https://github.com/carry2web/PHPDemoOIDCApp/actions/workflows/deploy.yml/badge.svg)](https://github.com/carry2web/PHPDemoOIDCApp/actions)

3. **Configuration Requirements**:
   - Configure Azure Application Settings with environment variables
   - Set up continuous deployment from GitHub
   - Configure custom domains and SSL certificates

### Environment Configuration

#### Required Environment Variables

```bash
# Internal Tenant (B2B) - S-Cape employees and partner agents  
INTERNAL_CLIENT_ID=your-b2b-client-id
INTERNAL_CLIENT_SECRET=your-b2b-client-secret
INTERNAL_TENANT_ID=your-b2b-tenant-id

# External Tenant (B2C) - Customer registration
EXTERNAL_CLIENT_ID=your-b2c-client-id  
EXTERNAL_CLIENT_SECRET=your-b2c-client-secret
EXTERNAL_TENANT_ID=your-b2c-tenant-id
B2C_TENANT_NAME=your-b2c-tenant-name
B2C_POLICY_SIGNUP_SIGNIN=B2C_1_signupsignin

# Microsoft Graph API (for user management and emails)
GRAPH_CLIENT_ID=your-graph-client-id
GRAPH_CLIENT_SECRET=your-graph-client-secret  
GRAPH_TENANT_ID=your-graph-tenant-id

# AWS S3 Integration
AWS_REGION=eu-west-1
AWS_S3_BUCKET=your-s3-bucket-name
AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET_ACCESS_KEY=your-aws-secret-key
AWS_ROLE_CUSTOMER=arn:aws:iam::account:role/CustomerRole
AWS_ROLE_AGENT=arn:aws:iam::account:role/AgentRole

# Application Settings
REDIRECT_URI=https://your-domain.com/callback.php
ADMIN_EMAIL=admin@your-domain.com
DEBUG=true
```

## � Monitoring & Debugging

### Debug Tools

1. **Azure Debug Console**: `/debug_azure.php`
   - Environment validation
   - Network connectivity tests  
   - Configuration verification
   - Performance metrics

2. **Cross-Tenant Diagnostics**: `/admin/cross_tenant_check.php`
   - Multi-tenant configuration validation
   - Authentication flow testing
   - Permission verification

3. **Configuration Checker**: `/admin/config_check.php`
   - Environment variable validation
   - Service connectivity tests
   - AWS integration verification

### Logging

The application includes comprehensive structured logging:
- **Azure-compatible logging** to `/home/LogFiles/`
- **Request tracking** with unique request IDs
- **Performance monitoring** with execution timing
- **Error capturing** with full stack traces

## 🔐 Security Features

### Authentication Security
- **Multi-factor authentication** via Entra ID
- **Cross-tenant isolation** with secure token handling
- **Session management** with Azure-optimized storage
- **Role-based access control** with granular permissions

### Data Security  
- **AWS S3 encryption** at rest and in transit
- **IAM federated access** with temporary credentials
- **Secure environment variables** via Azure Application Settings
- **HTTPS enforcement** for all communications

### Compliance
- **GDPR compliance** with user data protection
- **Audit logging** for all user actions
- **Secure credential storage** with Azure Key Vault integration
- **Regular security updates** via automated dependency management

## 🎯 User Workflows

### Customer Journey (B2C)
1. **Registration**: Self-service customer registration via B2C tenant
2. **Authentication**: Sign in with social providers or email/password
3. **Document Access**: Upload and download travel documents
4. **Profile Management**: Update personal information and preferences

### Agent Journey (B2B)  
1. **Application**: Submit agent application with business justification
2. **Approval Process**: Admin review and approval workflow
3. **Partner Access**: B2B guest user invitation and authentication
4. **Document Management**: Full access to customer documents and admin functions

### Admin Journey (Internal)
1. **Multi-tenant Management**: Switch between B2B and B2C contexts
2. **Agent Approval**: Review and approve/reject agent applications
3. **System Monitoring**: Access debug tools and system diagnostics
4. **Cross-tenant Operations**: Manage users across both tenants

## 📋 Setup Guides

### 🔧 Complete Setup Documentation
- **[AWS Setup Guide](AWS_SETUP_GUIDE.md)**: S3 bucket, IAM roles, and federated access
- **[Deployment Checklist](DEPLOYMENT_CHECKLIST.md)**: Production deployment steps
- **[Cross-Tenant Configuration](CROSS_TENANT_CHECKLIST.md)**: Multi-tenant setup guide
- **[AWS Credentials Helper](aws_credentials_helper.md)**: AWS access key configuration

### 🚀 Quick Setup Commands
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Validate configuration  
php admin/config_check.php

# Test connectivity
php debug_azure.php

# Run deployment
git push origin main  # Triggers automatic Azure deployment
```

## 🤝 Contributing

### Development Workflow
1. **Feature branches**: Create feature branches from `main`
2. **Testing**: Test locally with ngrok HTTPS tunneling
3. **Code review**: Pull request review process
4. **Automated deployment**: GitHub Actions handles Azure deployment

### Code Standards
- **PSR-4 autoloading**: Modern PHP namespace organization
- **Structured logging**: Consistent logging with request correlation
- **Error handling**: Comprehensive exception handling and user feedback
- **Security first**: Input validation, output escaping, and secure defaults

## 📞 Support & Contact

### Technical Support
- **GitHub Issues**: [Report bugs and feature requests](https://github.com/carry2web/PHPDemoOIDCApp/issues)
- **Documentation**: Comprehensive guides in the `/docs` directory
- **Debug Tools**: Built-in diagnostic tools for troubleshooting

### Business Contact
- **Email**: admin@scape-travel.com
- **Company**: S-Cape Travel Partnership Program
- **Platform**: Microsoft B2B/B2C Cross-tenant Authentication

---

## 📜 License

**Proprietary Software** - S-Cape Travel Development Team

This software is proprietary and confidential. Unauthorized copying, distribution, or modification is strictly prohibited.

---

## 🙏 Acknowledgments

### Technologies & Libraries
- **[jumbojett/openid-connect-php](https://github.com/jumbojett/OpenID-Connect-PHP)**: Core OIDC implementation
- **[AWS SDK for PHP](https://github.com/aws/aws-sdk-php)**: S3 and IAM integration  
- **[Microsoft Graph SDK](https://docs.microsoft.com/en-us/graph/)**: Email and user management
- **[Azure Web Apps](https://azure.microsoft.com/en-us/services/app-service/web/)**: Cloud hosting platform

### Inspiration
- **[Microsoft Woodgrove Groceries](https://github.com/microsoft/woodgrove-groceries)**: Advanced B2C implementation reference
- **[Azure AD B2B/B2C Samples](https://docs.microsoft.com/en-us/azure/active-directory-b2c/)**: Microsoft official samples

---

*Built with ❤️ by the S-Cape Travel Development Team*