# Production Deployment Checklist

## Before Deployment

### Environment Configuration
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY` (if not set)
- [ ] Use strong database password
- [ ] Configure production mail settings
- [ ] Set `SESSION_SECURE_COOKIE=true`
- [ ] Configure Paystack production keys

### Security
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Enable HTTPS
- [ ] Configure security headers in web server
- [ ] Set up firewall rules
- [ ] Disable directory listing
- [ ] Remove `.git` folder from public server

### Performance
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `npm run build`
- [ ] Set up Redis for sessions/cache (optional)
- [ ] Configure CDN for assets (optional)

### Database
- [ ] Run all migrations
- [ ] Set up automated backups
- [ ] Test database connection

### Monitoring
- [ ] Set up error logging
- [ ] Configure log rotation
- [ ] Set up uptime monitoring
- [ ] Configure email alerts for errors

### Testing
- [ ] Test booking flow end-to-end
- [ ] Test payment processing
- [ ] Test email delivery
- [ ] Test admin panel
- [ ] Test on mobile devices
