# KYC System - Project Structure

## Directory Organization

After reorganization, the project now follows a clean, scalable structure:

```
KYC/
├── app/                          # Application logic
│   ├── auth/                      # Authentication modules
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── logout.php
│   │   └── switch_account.php
│   ├── config/                    # Configuration files
│   │   ├── db.php                 # Database connection
│   │   └── session.php            # Session management
│   ├── handlers/                  # API handlers & controllers
│   │   ├── client.php
│   │   ├── kyc.php
│   │   ├── logins.php
│   │   ├── register.php
│   │   └── get_clients.php
│   ├── includes/                  # Reusable components
│   │   └── sidebar.php
│   └── pages/                     # Application pages
│       ├── dashboard.php
│       ├── clients.php
│       ├── kyc-verification.php
│       ├── kyc-individual.php
│       ├── kyc-corporate.php
│       ├── kyc-individual-review.php
│       ├── kyc-corporate-review.php
│       └── policy.php
│
├── public/                        # Publicly accessible folder
│   ├── index.php                  # Entry point
│   ├── diagnostics.html
│   ├── css/                       # Stylesheets
│   │   ├── index.css
│   │   ├── global.css
│   │   ├── auth.css
│   │   ├── dashboard.css
│   │   ├── clients.css
│   │   └── images/                # CSS-related images
│   └── images/                    # Static images
│       ├── SterlingLogo.png
│       ├── SterlingLogo2.png
│       └── (other images)
│
├── database/                      # Database related files
│   ├── migrations/                # Database schema files
│   │   ├── kyc_system.sql
│   │   ├── database.sql
│   │   ├── alter_clients_table.sql
│   │   └── alter_clients_missing_columns.sql
│   └── seeds/                     # Sample data
│       └── insert-sample-clients.php
│
├── tests/                         # Test files
│   ├── test-clients.php
│   └── test-login.php
│
├── docs/                          # Documentation
│   ├── DATABASE_SETUP.md
│   ├── FILE_REFERENCE.md
│   ├── FILE_STRUCTURE.md
│   ├── HANDLERS_GUIDE.md
│   ├── IMPLEMENTATION_SUMMARY.md
│   ├── PROJECT_STRUCTURE.md
│   ├── SETUP_AND_TESTING.md
│   └── SIDEBAR_REFACTOR.md
│
├── .htaccess                      # Apache rewrite rules
├── .git/                          # Version control
└── README.md                      # This file

```

## Key Changes

1. **Separation of Concerns**: Code is organized by function (auth, pages, handlers, config)
2. **Public Folder**: All publicly accessible files are in `/public`, improving security
3. **Asset Management**: CSS and images organized in `/public`
4. **Database Scripts**: Database migrations and seeds isolated in `/database`
5. **Documentation**: All markdown files organized in `/docs`
6. **Testing**: Test files isolated in `/tests`

## File Path Updates

After migration, the application maintains proper relative path references:

- Config files use relative paths: `require_once '../config/session.php'`
- Page stylesheets reference: `href="../../public/css/style.css"`
- Static assets reference: `src="../../public/images/logo.png"`
- Session redirects use dynamic URL construction to work from any file location

## Entry Point

- **Old**: `localhost/KYC/index.php`
- **New**: `localhost/KYC/` (automatically routes to public/index.php via .htaccess)

## Security Benefits

1. Non-public files (config, handlers) are outside web root
2. Only essential files are publicly accessible
3. Easier to avoid accidental exposure of sensitive configuration

## Development Notes

- All PHP requires/includes have been updated to reflect new structure
- CSS and asset paths have been corrected for new folder layout
- Database redirects use dynamic URL construction for flexibility
- The .htaccess file ensures proper routing to the public folder entry point
