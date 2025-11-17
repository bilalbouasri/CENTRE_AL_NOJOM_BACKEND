# Laravel Cloud Deployment Guide

## Issue: PHP Version Compatibility

The error you're encountering is because Laravel Cloud is using PHP 8.4.2, but the dependencies in your `composer.lock` file are locked to older PHP versions.

## Solution Steps

### 1. Force Fresh Deployment
If Laravel Cloud is caching the old `composer.lock` file:

1. **Clear Deployment Cache** (in Laravel Cloud dashboard):
   - Go to your application settings
   - Look for "Clear Cache" or "Rebuild" options
   - Force a fresh deployment

2. **Alternative: Delete and Recreate Application**:
   - If clearing cache doesn't work, delete the application in Laravel Cloud
   - Create a new application from the same repository
   - This ensures a completely fresh install

### 2. Verify Current Repository State
The repository now contains:
- ✅ Updated `composer.json` with PHP `^8.2 || ^8.3 || ^8.4` requirement
- ✅ Updated `composer.lock` with compatible package versions:
  - `lcobucci/clock`: 3.5.0 (supports PHP 8.4)
  - `lcobucci/jwt`: 4.3.0 (supports PHP 8.4)
- ✅ Laravel Cloud configuration file (`.laravel-cloud.yaml`)

### 3. Manual Deployment Steps (if needed)

If automatic deployment continues to fail, you can try:

1. **SSH into Laravel Cloud** (if available):
   ```bash
   composer install --no-dev --optimize-autoloader --ignore-platform-reqs
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan migrate --force
   ```

2. **Check PHP Version**:
   ```bash
   php --version
   ```

### 4. Environment Configuration

Make sure your Laravel Cloud environment has:

**Required Environment Variables:**
```
APP_NAME=Centre Al Nojom
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.laravel.cloud

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

JWT_SECRET=...
```

### 5. Troubleshooting Common Issues

**Issue: Still getting old package versions**
- Laravel Cloud might be using a cached deployment
- Solution: Clear deployment cache or recreate application

**Issue: Database connection errors**
- Verify database credentials in environment variables
- Ensure database is accessible from Laravel Cloud

**Issue: JWT authentication not working**
- Make sure `JWT_SECRET` is set in environment variables
- Run `php artisan jwt:secret` if needed

## Verification

After successful deployment, verify:

1. **API Endpoints**: Test `/api/auth/login` endpoint
2. **Database**: Check if migrations ran successfully
3. **Environment**: Verify all environment variables are set correctly

## Support

If issues persist:
1. Check Laravel Cloud deployment logs
2. Contact Laravel Cloud support with the error details
3. Provide them with the updated repository URL and commit hash

**Repository**: https://github.com/bilalbouasri/CENTRE_AL_NOJOM_BACKEND.git
**Latest Commit**: `fc32075` (includes PHP 8.4 compatibility fixes)