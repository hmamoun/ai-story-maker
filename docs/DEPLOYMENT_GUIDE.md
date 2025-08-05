# AI Story Maker - Master-Slave Architecture Deployment Guide

## Overview

This guide covers the deployment of the AI Story Maker plugin with a master-slave architecture where:
- **Master Server**: Handles subscription validation, credit management, and story generation
- **Client Sites**: Local WordPress installations that call the master server for story generation

## Prerequisites

### Master Server Requirements
- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- SSL certificate (required for production)
- OpenAI API key
- Stripe account (for payments)

### Client Site Requirements
- WordPress 6.0+
- PHP 8.0+
- Ability to make outbound HTTP requests
- `AISTMA_MASTER_URL` constant defined in wp-config.php

## Step 1: Master Server Setup

### 1.1 Install WordPress
```bash
# Download and install WordPress
wget https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
mv wordpress /var/www/master-server
cd /var/www/master-server

# Set permissions
chown -R www-data:www-data /var/www/master-server
chmod -R 755 /var/www/master-server
```

### 1.2 Configure Database
```sql
-- Create database
CREATE DATABASE aistma_master;
CREATE USER 'aistma_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON aistma_master.* TO 'aistma_user'@'localhost';
FLUSH PRIVILEGES;
```

### 1.3 Install Required Plugins
1. **Exedotcom API Gateway** - Upload the plugin files
2. **WooCommerce** (if using Stripe payments)
3. **Stripe for WooCommerce** (if using Stripe)

### 1.4 Configure Master Server Settings

#### A. OpenAI API Key
1. Go to **Exedotcom Gateway > AI Story Maker**
2. Enter your OpenAI API key in the "OpenAI API Key" field
3. Save settings

#### B. Database Tables
The plugin will automatically create required tables:
- `wp_exaig_orders` - Subscription and credit management
- `wp_exaig_aistma_calls_log` - API call logging

#### C. SSL Configuration
```apache
# Apache virtual host configuration
<VirtualHost *:443>
    ServerName master.yourdomain.com
    DocumentRoot /var/www/master-server
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/master-server>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Step 2: Client Site Setup

### 2.1 Install AI Story Maker Plugin
1. Upload the `ai-story-maker` plugin to `/wp-content/plugins/`
2. Activate the plugin
3. Go to **AI Story Maker > Settings**

### 2.2 Configure Master Server URL
Add this to your `wp-config.php`:
```php
// AI Story Maker Master Server Configuration
define('AISTMA_MASTER_URL', 'https://master.yourdomain.com');
```

### 2.3 Test Connection
1. Go to **AI Story Maker > Settings**
2. Click "Test Connection" to verify communication with master server
3. Check subscription status

## Step 3: Production Security

### 3.1 API Security
```php
// Add to wp-config.php on master server
define('AISTMA_API_SECRET', 'your-secure-api-secret-key');

// Add to wp-config.php on client sites
define('AISTMA_CLIENT_SECRET', 'your-secure-client-secret-key');
```

### 3.2 Rate Limiting
```apache
# Apache rate limiting
<Location "/wp-json/exaig/v1/">
    SetEnvIf Remote_Addr "^192\.168\.1\.100$" RATE_LIMIT_EXEMPT
    SetEnvIf Remote_Addr "^10\.0\.0\.50$" RATE_LIMIT_EXEMPT
    
    # Rate limit: 100 requests per minute per IP
    RewriteEngine On
    RewriteCond %{ENV:RATE_LIMIT_EXEMPT} !^1$
    RewriteCond %{HTTP:X-Forwarded-For} !^$
    RewriteCond %{HTTP:X-Forwarded-For} !^192\.168\.1\.100$
    RewriteCond %{HTTP:X-Forwarded-For} !^10\.0\.0\.50$
    RewriteRule .* - [E=RATE_LIMIT:1]
</Location>
```

### 3.3 Database Security
```sql
-- Create read-only user for client sites (if needed)
CREATE USER 'aistma_readonly'@'%' IDENTIFIED BY 'readonly_password';
GRANT SELECT ON aistma_master.* TO 'aistma_readonly'@'%';
```

## Step 4: Monitoring and Logging

### 4.1 Enable WordPress Debug Logging
```php
// Add to wp-config.php on master server
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 4.2 Custom Logging
The plugin automatically logs:
- API calls to master server
- Story generation requests
- Credit usage
- Error responses

### 4.3 Monitoring Script
Create a monitoring script to check system health:

```php
<?php
// monitoring.php - Place in master server root
require_once('wp-config.php');
require_once('wp-load.php');

$checks = [
    'database' => check_database_connection(),
    'openai' => check_openai_api(),
    'subscriptions' => check_active_subscriptions(),
    'credits' => check_credit_usage()
];

header('Content-Type: application/json');
echo json_encode($checks, JSON_PRETTY_PRINT);

function check_database_connection() {
    global $wpdb;
    $result = $wpdb->get_var("SELECT 1");
    return ['status' => $result ? 'ok' : 'error', 'message' => $result ? 'Connected' : 'Failed'];
}

function check_openai_api() {
    $api_key = get_option('aistma_master_openai_api_key');
    if (empty($api_key)) {
        return ['status' => 'error', 'message' => 'API key not configured'];
    }
    
    // Test API call
    $response = wp_remote_post('https://api.openai.com/v1/models', [
        'headers' => ['Authorization' => 'Bearer ' . $api_key],
        'timeout' => 10
    ]);
    
    return [
        'status' => wp_remote_retrieve_response_code($response) === 200 ? 'ok' : 'error',
        'message' => wp_remote_retrieve_response_code($response) === 200 ? 'API working' : 'API failed'
    ];
}

function check_active_subscriptions() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}exaig_orders WHERE status = 'active'");
    return ['status' => 'ok', 'count' => $count];
}

function check_credit_usage() {
    global $wpdb;
    $usage = $wpdb->get_results("
        SELECT domain, credits_total, credits_used, 
               (credits_total - credits_used) as remaining
        FROM {$wpdb->prefix}exaig_orders 
        WHERE status = 'active' AND credits_used > 0
        ORDER BY credits_used DESC
        LIMIT 10
    ");
    return ['status' => 'ok', 'usage' => $usage];
}
?>
```

## Step 5: Backup Strategy

### 5.1 Database Backups
```bash
#!/bin/bash
# backup_master_db.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u aistma_user -p aistma_master > /backups/master_db_$DATE.sql
gzip /backups/master_db_$DATE.sql

# Keep only last 30 days
find /backups -name "master_db_*.sql.gz" -mtime +30 -delete
```

### 5.2 Plugin Files Backup
```bash
#!/bin/bash
# backup_plugins.sh
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf /backups/plugins_$DATE.tar.gz /var/www/master-server/wp-content/plugins/

# Keep only last 7 days
find /backups -name "plugins_*.tar.gz" -mtime +7 -delete
```

## Step 6: Troubleshooting

### Common Issues

#### 1. API Connection Failed
- Check `AISTMA_MASTER_URL` constant
- Verify SSL certificate
- Check firewall settings
- Test with curl: `curl -X POST https://master.yourdomain.com/wp-json/exaig/v1/generate-story`

#### 2. Credit Not Decrementing
- Check database permissions
- Verify subscription status
- Check error logs
- Test with monitoring script

#### 3. OpenAI API Errors
- Verify API key is correct
- Check API usage limits
- Monitor token usage
- Check network connectivity

### Debug Commands
```bash
# Check WordPress debug log
tail -f /var/www/master-server/wp-content/debug.log

# Check Apache error log
tail -f /var/log/apache2/error.log

# Check database connections
mysql -u aistma_user -p -e "SHOW PROCESSLIST;"

# Test API endpoint
curl -X POST https://master.yourdomain.com/wp-json/exaig/v1/generate-story \
  -H "Content-Type: application/json" \
  -d '{"domain":"test.com","prompt_text":"test"}'
```

## Step 7: Performance Optimization

### 7.1 Caching
```php
// Add to wp-config.php
define('WP_CACHE', true);

// Install Redis or Memcached
// Configure object caching
```

### 7.2 Database Optimization
```sql
-- Add indexes for better performance
ALTER TABLE wp_exaig_orders ADD INDEX idx_domain_status (domain, status);
ALTER TABLE wp_exaig_orders ADD INDEX idx_created_at (created_at);
ALTER TABLE wp_exaig_aistma_calls_log ADD INDEX idx_domain_date (domain, created_at);
```

### 7.3 CDN Configuration
```apache
# Apache CDN headers
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
```

## Step 8: Scaling Considerations

### 8.1 Load Balancing
- Use multiple master servers behind a load balancer
- Implement database replication
- Use Redis for session storage

### 8.2 Auto-scaling
- Monitor API usage patterns
- Scale based on concurrent requests
- Implement queue system for high load

### 8.3 Geographic Distribution
- Deploy master servers in different regions
- Use CDN for static assets
- Implement health checks

## Support and Maintenance

### Regular Maintenance Tasks
1. **Daily**: Check error logs and API usage
2. **Weekly**: Review credit usage and subscription status
3. **Monthly**: Update plugins and security patches
4. **Quarterly**: Performance review and optimization

### Emergency Procedures
1. **API Outage**: Switch to local OpenAI calls
2. **Database Issues**: Restore from backup
3. **Security Breach**: Rotate API keys and secrets

### Contact Information
- **Technical Support**: support@yourdomain.com
- **Emergency**: emergency@yourdomain.com
- **Documentation**: docs.yourdomain.com

---

**Last Updated**: December 2024
**Version**: 1.0.0 