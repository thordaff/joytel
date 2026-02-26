# JoyTel API - Production Deployment Checklist

## Pre-Deployment Checklist

### ✅ Environment Configuration

- [ ] **Server Requirements Met**
  - [ ] PHP 8.2+ installed with required extensions
  - [ ] MariaDB/MySQL 8.0+ running
  - [ ] Nginx/Apache configured
  - [ ] SSL certificate installed (HTTPS required)
  - [ ] Supervisor installed for queue management

- [ ] **Environment Variables (.env)**
  ```bash
  # APP Settings
  APP_ENV=production
  APP_DEBUG=false
  APP_KEY=base64:generated_key_here
  APP_URL=https://your-domain.com
  
  # Database
  DB_CONNECTION=mariadb
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=joytel_production
  DB_USERNAME=joytel_user
  DB_PASSWORD=secure_password
  
  # JoyTel Credentials (CRITICAL)
  JOYTEL_CUSTOMER_CODE=your_actual_customer_code
  JOYTEL_CUSTOMER_AUTH=your_actual_customer_auth
  JOYTEL_APP_ID=your_actual_app_id
  JOYTEL_APP_SECRET=your_actual_app_secret
  
  # URLs
  JOYTEL_WAREHOUSE_BASE_URL=https://api.joytelshop.com
  JOYTEL_RSP_BASE_URL=https://esim.joytelecom.com/openapi
  
  # Queue
  QUEUE_CONNECTION=database
  
  # Logging
  LOG_LEVEL=info
  LOG_CHANNEL=stack
  ```

- [ ] **File Permissions**
  ```bash
  chown -R www-data:www-data /path/to/project
  chmod -R 755 /path/to/project
  chmod -R 775 /path/to/project/storage
  chmod -R 775 /path/to/project/bootstrap/cache
  ```

### ✅ Security Configuration

- [ ] **Firewall Rules**
  - [ ] Port 80 (HTTP) redirect to 443
  - [ ] Port 443 (HTTPS) open
  - [ ] Database port (3306) restricted to localhost
  - [ ] SSH port secured (non-standard port + key-based auth)

- [ ] **SSL/TLS Configuration**
  - [ ] Valid SSL certificate installed
  - [ ] HTTPS enforced for all routes
  - [ ] Security headers configured in web server

- [ ] **Application Security**
  - [ ] `APP_DEBUG=false` in production
  - [ ] Strong database passwords
  - [ ] API rate limiting configured
  - [ ] Input validation on all endpoints

### ✅ Database Preparation

- [ ] **Database Setup**
  ```bash
  # Create production database
  mysql -u root -p
  CREATE DATABASE joytel_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'joytel_user'@'localhost' IDENTIFIED BY 'secure_password';
  GRANT ALL PRIVILEGES ON joytel_production.* TO 'joytel_user'@'localhost';
  FLUSH PRIVILEGES;
  ```

- [ ] **Run Migrations**
  ```bash
  php artisan migrate --force
  ```

- [ ] **Database Backup Strategy**
  - [ ] Automated daily backups configured
  - [ ] Backup retention policy set (30 days recommended)
  - [ ] Backup restoration tested

## Deployment Steps

### 1. Code Deployment

```bash
# Clone repository
git clone <repository-url> /var/www/joytel-api
cd /var/www/joytel-api

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set up environment
cp .env.example .env
# Edit .env with production values
php artisan key:generate

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force
```

### 2. Web Server Configuration

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/joytel-api/public;
    index index.php;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

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

### 3. Queue Worker Setup

```bash
# Copy supervisor configuration
sudo cp examples/supervisor-joytel.conf /etc/supervisor/conf.d/

# Edit paths in the config file
sudo nano /etc/supervisor/conf.d/supervisor-joytel.conf

# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start joytel-workers:*
```

### 4. Monitoring Setup

#### Log Rotation
```bash
# Create logrotate config
sudo nano /etc/logrotate.d/joytel-api

# Content:
/var/www/joytel-api/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 0644 www-data www-data
}

/var/log/joytel-*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
}
```

#### Health Check Script
```bash
#!/bin/bash
# /usr/local/bin/joytel-health-check.sh

HEALTH_URL="https://your-domain.com/api/utils/health"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" $HEALTH_URL)

if [ $RESPONSE != "200" ]; then
    echo "JoyTel API health check failed: HTTP $RESPONSE"
    # Send alert (email, Slack, etc.)
    exit 1
fi

echo "JoyTel API health check passed"
exit 0
```

### 5. Cron Jobs

```bash
# Add to crontab (sudo crontab -e)

# Laravel scheduler
* * * * * cd /var/www/joytel-api && php artisan schedule:run >> /dev/null 2>&1

# Health check every 5 minutes
*/5 * * * * /usr/local/bin/joytel-health-check.sh >> /var/log/joytel-health.log 2>&1

# Queue monitoring (restart failed workers)
*/10 * * * * supervisorctl restart joytel-workers:* >> /var/log/supervisor-restart.log 2>&1
```

## Post-Deployment Verification

### ✅ Functional Tests

- [ ] **API Endpoints**
  ```bash
  # Health check
  curl -X GET https://your-domain.com/api/utils/health
  
  # Config check
  curl -X GET https://your-domain.com/api/utils/config
  
  # Submit test order
  curl -X POST https://your-domain.com/api/orders \
    -H "Content-Type: application/json" \
    -d '{"product_code":"TEST","quantity":1,"receive_name":"Test","phone":"+123456789"}'
  ```

- [ ] **Queue System**
  ```bash
  # Check supervisor status
  sudo supervisorctl status
  
  # Check queue processing
  php artisan queue:work --once --verbose
  
  # Check failed jobs
  php artisan queue:failed
  ```

- [ ] **Database Connectivity**
  ```bash
  # Test database connection
  php artisan tinker
  > \App\Models\Order::count()
  > exit
  ```

### ✅ Security Verification

- [ ] **SSL/HTTPS**
  - [ ] SSL Labs test: https://www.ssllabs.com/ssltest/
  - [ ] Force HTTP to HTTPS redirect working
  - [ ] Security headers present

- [ ] **API Security**
  - [ ] Rate limiting working (if configured)
  - [ ] No debug information exposed
  - [ ] Error messages don't reveal sensitive info

### ✅ Performance Tests

- [ ] **Load Testing**
  ```bash
  # Simple load test with curl
  for i in {1..100}; do
    curl -s -o /dev/null https://your-domain.com/api/utils/health &
  done
  wait
  ```

- [ ] **Response Times**
  - [ ] Health endpoint < 500ms
  - [ ] Order submission < 2s
  - [ ] Order query < 1s

## Monitoring & Maintenance

### Daily Checks

- [ ] Check application logs: `tail -f /var/www/joytel-api/storage/logs/laravel.log`
- [ ] Check queue worker logs: `tail -f /var/log/joytel-queue-worker.log`
- [ ] Check failed jobs: `php artisan queue:failed`
- [ ] Monitor disk space: `df -h`

### Weekly Checks

- [ ] Review API usage patterns
- [ ] Check database performance
- [ ] Update dependencies if needed: `composer update`
- [ ] Review and rotate logs

### Monthly Checks

- [ ] Security updates
- [ ] Performance optimization
- [ ] Backup restoration test
- [ ] SSL certificate expiry check

## Troubleshooting Commands

```bash
# Check Laravel logs
tail -f /var/www/joytel-api/storage/logs/laravel.log

# Check queue status
php artisan queue:work --verbose

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo supervisorctl restart joytel-workers:*

# Check service status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo supervisorctl status

# Database connection test
php artisan tinker
> DB::connection()->getPdo();
```

## Emergency Procedures

### API Down
1. Check web server status
2. Check PHP-FPM status  
3. Check database connectivity
4. Check application logs
5. Restart services if needed

### Queue Not Processing
1. Check supervisor status
2. Check worker logs
3. Clear failed jobs if needed
4. Restart queue workers

### Database Issues
1. Check database server status
2. Check connection credentials
3. Check disk space
4. Restore from backup if needed

## Rollback Plan

In case of deployment issues:

```bash
# 1. Stop queue workers
sudo supervisorctl stop joytel-workers:*

# 2. Restore previous version
cd /var/www
mv joytel-api joytel-api-failed
mv joytel-api-backup joytel-api

# 3. Restore database (if schema changed)
mysql -u joytel_user -p joytel_production < backup.sql

# 4. Restart services
sudo systemctl restart nginx php8.2-fpm
sudo supervisorctl start joytel-workers:*
```

---

## ✅ Final Checklist

- [ ] All environment variables configured
- [ ] SSL certificate installed and working
- [ ] Database migrations completed
- [ ] Queue workers running
- [ ] All API endpoints responding
- [ ] Webhook endpoints accessible
- [ ] Monitoring configured
- [ ] Backup strategy implemented
- [ ] Documentation updated
- [ ] Team notified of deployment

**Deployment Date**: ________________  
**Deployed by**: ________________  
**Version**: ________________