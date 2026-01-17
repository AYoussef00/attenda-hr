# CDN Configuration Guide

This document explains how to configure and use a Content Delivery Network (CDN) with this Laravel application to improve website loading times and performance.

## What is a CDN?

A Content Delivery Network (CDN) is a globally distributed network of web servers that delivers content to users based on their geographic location. CDN benefits include:

- **Improved website loading times**: Assets are served from locations closer to users
- **Reduced bandwidth costs**: Offload static asset serving from your main server
- **Increased content availability**: Redundant servers provide better uptime
- **Improved website security**: DDoS protection and security features

## Popular CDN Providers

1. **Cloudflare** (Recommended for beginners)
   - Free tier available
   - Easy setup with DNS changes
   - Automatic HTTPS and DDoS protection
   - URL: https://www.cloudflare.com

2. **AWS CloudFront**
   - Integrates well with AWS S3
   - Pay-as-you-go pricing
   - Global edge locations
   - URL: https://aws.amazon.com/cloudfront

3. **MaxCDN / StackPath**
   - Simple pricing model
   - Good for WordPress/Laravel sites
   - URL: https://www.stackpath.com

4. **KeyCDN**
   - Affordable pricing
   - HTTP/2 support
   - Real-time analytics
   - URL: https://www.keycdn.com

## Configuration Steps

### Step 1: Set Up CDN Service

1. Sign up for a CDN provider (Cloudflare recommended)
2. Add your domain to the CDN service
3. Configure the CDN to serve static assets from your domain
4. Note your CDN URL (e.g., `https://cdn.yourdomain.com` or `https://d1234567890.cloudfront.net`)

### Step 2: Configure Laravel Application

Add the CDN URL to your `.env` file:

```env
APP_URL=https://yourdomain.com
CDN_URL=https://cdn.yourdomain.com
```

Or if using Cloudflare with a CNAME:
```env
CDN_URL=https://cdn-1234567890.cloudfront.net
```

### Step 3: Sync Assets to CDN

Upload your static assets (from `public/` directory) to your CDN:

**For Cloudflare:**
- Assets are automatically cached when accessed through the CDN domain

**For AWS CloudFront with S3:**
```bash
# Install AWS CLI and sync assets
aws s3 sync public/ s3://your-bucket-name/public/ --delete
```

**For Manual Upload:**
- Upload files from `public/` directory to your CDN's storage
- Maintain the same directory structure

### Step 4: Update DNS (if using custom CDN domain)

If you're using a custom CDN domain like `cdn.yourdomain.com`:

1. Add a CNAME record: `cdn.yourdomain.com` → Your CDN endpoint
2. Wait for DNS propagation (can take up to 48 hours)

### Step 5: Clear Cache

After configuration, clear Laravel cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Using CDN Functions

The application includes helper functions for CDN URLs:

### `cdn_asset($path)`
Generate an asset URL using CDN if configured:

```php
// Before (without CDN)
$url = asset('images/logo.png');
// Returns: https://yourdomain.com/images/logo.png

// After (with CDN)
$url = cdn_asset('images/logo.png');
// Returns: https://cdn.yourdomain.com/images/logo.png
```

### `cdn_storage($path)`
Generate a storage URL using CDN:

```php
// Before (without CDN)
$url = asset('storage/logos/company.png');
// Returns: https://yourdomain.com/storage/logos/company.png

// After (with CDN)
$url = cdn_storage('logos/company.png');
// Returns: https://cdn.yourdomain.com/storage/logos/company.png
```

## Testing CDN

1. **Verify CDN URLs**: Check that asset URLs use your CDN domain
2. **Test Performance**: Use tools like:
   - [GTmetrix](https://gtmetrix.com/)
   - [PageSpeed Insights](https://pagespeed.web.dev/)
   - [WebPageTest](https://www.webpagetest.org/)

3. **Check Headers**: Verify CDN headers:
   ```bash
   curl -I https://cdn.yourdomain.com/images/logo.png
   ```

## Troubleshooting

### Assets Not Loading
- Verify CDN_URL is correctly set in `.env`
- Check that files are uploaded to CDN
- Clear Laravel config cache: `php artisan config:clear`

### CDN Not Working
- Verify DNS is properly configured (if using custom domain)
- Check CDN service status
- Review CDN provider logs/dashboard

### Mixed Content Warnings
- Ensure CDN URL uses HTTPS
- Check that your main site also uses HTTPS

## Disabling CDN

To disable CDN and use regular asset URLs:

1. Remove or comment out `CDN_URL` in `.env`:
   ```env
   # CDN_URL=https://cdn.yourdomain.com
   ```

2. Clear cache:
   ```bash
   php artisan config:clear
   ```

The application will automatically fall back to using `APP_URL` for assets.

## Additional Resources

- [Laravel Asset Management](https://laravel.com/docs/asset-management)
- [Cloudflare Documentation](https://developers.cloudflare.com/)
- [AWS CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)
