# webMUSHRA with Laravel Authentication

This repository contains webMUSHRA wrapped in a Laravel application with authentication support, including Microsoft OAuth via Laravel Socialite.

## Features

- **User Authentication**: Laravel Breeze authentication system with traditional email/password login
- **Microsoft OAuth**: Sign in with Microsoft account (Azure AD/Office 365)
- **Protected Access**: webMUSHRA tests only accessible to authenticated users
- **Result Tracking**: Test results are associated with authenticated users
- **Secure Storage**: Results stored in Laravel storage with user information

## Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL database
- Node.js & NPM (for frontend assets)
- Web server (Apache/Nginx) or PHP built-in server for development

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd webMUSHRA
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install NPM dependencies and build assets
npm install
npm run build
```

### 3. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Update the following values in `.env`:

```env
APP_NAME="webMUSHRA"
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webmushra
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

# Microsoft OAuth (optional, but required for Microsoft sign-in)
MICROSOFT_CLIENT_ID=your_microsoft_client_id
MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret
MICROSOFT_REDIRECT_URI="${APP_URL}/auth/microsoft/callback"
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Create Database

Create a MySQL database named `webmushra` (or the name you configured in `.env`):

```sql
CREATE DATABASE webmushra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Create Storage Link

```bash
php artisan storage:link
```

## Microsoft OAuth Setup (Optional)

To enable Microsoft sign-in, you need to register an application in Azure AD:

### 1. Register Application in Azure Portal

1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to **Azure Active Directory** > **App registrations**
3. Click **New registration**
4. Enter application details:
   - Name: `webMUSHRA`
   - Supported account types: Choose appropriate option
   - Redirect URI: `http://localhost:8000/auth/microsoft/callback` (or your production URL)
5. Click **Register**

### 2. Configure Application

1. Copy the **Application (client) ID** and add it to `.env` as `MICROSOFT_CLIENT_ID`
2. Go to **Certificates & secrets**
3. Create a new client secret
4. Copy the secret value and add it to `.env` as `MICROSOFT_CLIENT_SECRET`
5. Go to **API permissions** and ensure the following permissions are granted:
   - Microsoft Graph > User.Read (Delegated)

### 3. Update Redirect URI

If deploying to production, update the redirect URI in both:
- Azure AD app registration
- `.env` file (`MICROSOFT_REDIRECT_URI`)

## Running the Application

### Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Production Deployment

For production, configure your web server (Apache/Nginx) to point to the `public` directory.

Example Nginx configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/webMUSHRA/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Usage

### User Registration

Users can register in two ways:

1. **Email/Password**: Use the standard registration form at `/register`
2. **Microsoft OAuth**: Click "Sign in with Microsoft" on the login page

### Accessing Tests

1. Navigate to the application URL
2. Log in with your credentials or Microsoft account
3. You'll be redirected to `/mushra` where all webMUSHRA tests are available
4. Select a test configuration from the URL parameter, e.g., `/mushra/index.html?config=mushra.yaml`

### Test Results

Test results are automatically saved when users complete tests. Results are stored in:
- **Location**: `storage/app/mushra-results/{testId}/`
- **Format**: CSV files (one per test type: mushra.csv, paired_comparison.csv, etc.)
- **User Tracking**: Each result includes user ID and email

## Directory Structure

```
webMUSHRA/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Auth/
│   │       │   └── MicrosoftAuthController.php  # Microsoft OAuth handler
│   │       └── MushraResultsController.php      # Results storage
│   └── Models/
├── config/
│   └── services.php                             # Microsoft OAuth config
├── public/
│   └── mushra/                                  # Original webMUSHRA files
│       ├── index.html
│       ├── configs/                             # Test configurations
│       ├── lib/                                 # JavaScript libraries
│       └── ...
├── resources/
│   └── views/
│       └── auth/
│           └── login.blade.php                  # Modified with Microsoft button
├── routes/
│   └── web.php                                  # Route definitions
└── storage/
    └── app/
        └── mushra-results/                      # Test results stored here
```

## Customization

### Adding New Tests

1. Create a new YAML configuration file in `public/mushra/configs/`
2. Ensure `remoteService` is set to `/api/mushra/results`
3. Access the test via `/mushra/index.html?config=your-config.yaml`

### Modifying Authentication

The authentication system uses Laravel Breeze. You can customize:
- Login/registration views in `resources/views/auth/`
- Authentication logic in `app/Http/Controllers/Auth/`
- Middleware and guards in `config/auth.php`

### Changing Result Storage

Results are currently stored as CSV files in Laravel storage. To modify:
- Edit `app/Http/Controllers/MushraResultsController.php`
- Implement database storage, cloud storage, or other backends

## Troubleshooting

### 403 Forbidden accessing /mushra

- Ensure you're logged in
- Check that routes are properly configured in `routes/web.php`
- Verify middleware is applied correctly

### Microsoft OAuth not working

- Verify client ID and secret in `.env`
- Check redirect URI matches in Azure AD and `.env`
- Ensure Microsoft Graph permissions are granted
- Clear config cache: `php artisan config:clear`

### Results not saving

- Check storage permissions: `chmod -R 775 storage`
- Verify user is authenticated when submitting results
- Check logs in `storage/logs/laravel.log`

### Database connection errors

- Verify MySQL is running
- Check database credentials in `.env`
- Ensure database exists
- Test connection: `php artisan db:show`

## Security Considerations

- **HTTPS**: Always use HTTPS in production
- **Environment Variables**: Never commit `.env` to version control
- **File Permissions**: Set appropriate permissions on storage and cache directories
- **Session Security**: Configure session security settings in `config/session.php`
- **CORS**: Configure CORS if accessing from different domains

## Original webMUSHRA Documentation

For information about webMUSHRA test configuration and features, see:
- Original README: `public/mushra/README.md`
- Configuration examples: `public/mushra/configs/`
- Official documentation: https://github.com/audiolabs/webMUSHRA

## License

This Laravel wrapper is provided as-is. webMUSHRA is licensed under its original terms (see `public/mushra/LICENSE.txt`).

## Support

For issues related to:
- **Laravel integration**: Open an issue in this repository
- **webMUSHRA functionality**: Refer to the [official webMUSHRA repository](https://github.com/audiolabs/webMUSHRA)
- **Microsoft OAuth**: Check [Laravel Socialite documentation](https://laravel.com/docs/socialite)
