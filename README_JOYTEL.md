# JoyTel API Integration - Laravel 12

Sistem backend Laravel 12 untuk integrasi dengan JoyTel API yang mendukung Warehouse API (System 1) dan RSP API (System 2).

## Features

- ✅ **Warehouse API Integration** (eSIM Order, OTA Recharge)
- ✅ **RSP API Integration** (Coupon Redeem, eSIM Management) 
- ✅ **Production-ready Architecture** (Service classes, Controllers, Jobs)
- ✅ **Queue System** untuk asynchronous processing
- ✅ **Idempotent Webhook Handling** 
- ✅ **Signature Validation** (SHA1, MD5)
- ✅ **Comprehensive Logging**
- ✅ **Database Migrations**
- ✅ **Error Handling & Retry Logic**

## Requirements

- PHP 8.4 atau lebih tinggi
- Laravel 12
- MariaDB/MySQL
- Redis (optional, untuk caching)
- Supervisor (untuk production queue workers)

## Installation

### 1. Clone & Setup Project

```bash
git clone <repository-url> joytel-api
cd joytel-api
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Database Configuration

Edit `.env` file:

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=joytel_api
DB_USERNAME=your_username  
DB_PASSWORD=your_password
```

### 3. JoyTel API Configuration

Tambahkan konfigurasi JoyTel di `.env`:

```env
# JoyTel Warehouse API
JOYTEL_CUSTOMER_CODE=your_customer_code
JOYTEL_CUSTOMER_AUTH=your_customer_auth
JOYTEL_WAREHOUSE_BASE_URL=https://api.joytelshop.com

# JoyTel RSP API  
JOYTEL_APP_ID=your_app_id
JOYTEL_APP_SECRET=your_app_secret
JOYTEL_RSP_BASE_URL=https://esim.joytelecom.com/openapi

# Optional Settings
JOYTEL_API_TIMEOUT=30
JOYTEL_SIGNATURE_TOLERANCE=600
```

### 4. Queue Configuration

```env
QUEUE_CONNECTION=database
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Start Queue Workers

Development:
```bash
php artisan queue:work --queue=joytel,default
```

Production (with Supervisor):
```bash
# Copy supervisor config
sudo cp examples/supervisor-joytel.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start joytel-workers:*
```

## API Documentation

### Base URL
```
http://your-domain.com/api
```

### Submit eSIM Order

**POST** `/orders`

```json
{
  "product_code": "ESIM_GLOBAL_1GB",
  "quantity": 1,
  "receive_name": "John Doe", 
  "phone": "+1234567890",
  "email": "john@example.com",
  "cid": "CUSTOMER_123",
  "system_type": "warehouse"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order submitted successfully",
  "data": {
    "order_tid": "JT20240223123456ABCDEF",
    "order_code": "JT_ORDER_123",
    "status": "processing",
    "system_type": "warehouse"
  }
}
```

### 2. Query Order Status

**GET** `/orders/query?order_tid=JT20240223123456ABCDEF`

**Response:**
```json
{
  "success": true,
  "data": {
    "order_tid": "JT20240223123456ABCDEF",
    "order_code": "JT_ORDER_123",
    "product_code": "ESIM_GLOBAL_1GB",
    "quantity": 1,
    "status": "completed",
    "sn_pin": "1234567890123456",
    "qrcode": "LPA:1$esim.example.com$12345",
    "created_at": "2024-02-23T12:34:56Z",
    "completed_at": "2024-02-23T12:45:00Z"
  }
}
```

### 3. Submit OTA Recharge

**POST** `/orders/ota-recharge`

```json
{
  "product_code": "RECHARGE_5GB",
  "sn_pin": "1234567890123456",
  "quantity": 1
}
```

### 4. List Orders

**GET** `/orders?per_page=10&status=completed&system_type=warehouse`

## Webhook Endpoints

### 1. SN/PIN Callback (Warehouse)
**POST** `/webhook/sn-pin`

### 2. QR Code Callback  
**POST** `/webhook/qr-code`

### 3. Coupon Redeem Notification (RSP)
**POST** `/webhook/notify/coupon/redeem`

### 4. eSIM Progress Notification (RSP)
**POST** `/webhook/notify/esim/esim-progress`

## Architecture

```
app/
├── Models/
│   ├── Order.php          # Order model dengan status management
│   └── JoytelLog.php      # API logging model
├── Services/
│   ├── JoytelService.php  # Base service class
│   ├── WarehouseService.php # Warehouse API integration
│   └── RSPService.php     # RSP API integration  
├── Http/Controllers/
│   ├── OrderController.php    # Order management
│   └── WebhookController.php  # Webhook handlers
└── Jobs/
    └── RedeemCouponJob.php   # Async coupon redemption
```

## Security Features

### Signature Validation

**Warehouse (SHA1):** 
```php
$signature = sha1($customerCode . $customerAuth . $warehouse . $type . $orderTid . $receiveName . $phone . $timestamp . $itemList);
```

**RSP (MD5):**
```php  
$ciphertext = md5($appId . $transId . $timestamp . $appSecret);
```

### Idempotent Webhooks
- Cache-based duplicate detection
- Transaction safety
- Retry logic dengan backoff

### Logging
- Comprehensive API request/response logging
- Error tracking dengan context
- Performance monitoring

## Database Schema

### Orders Table
```sql
- id (bigint)
- order_tid (string, unique)
- order_code (string, nullable)  
- product_code (string)
- quantity (integer)
- status (enum: pending, processing, completed, failed)
- sn_pin (text, nullable)
- qrcode (text, nullable)
- cid (string, nullable)
- request_data (json)
- response_data (json)
- system_type (enum: warehouse, rsp)
- timestamps
```

### JoyTel Logs Table
```sql  
- id (bigint)
- transaction_id (string)
- system_type (string)
- endpoint (string)
- request/response data (json)
- response_time (decimal)
- order_tid (string, nullable)
- timestamps
```

## Configuration

### Error Codes
```php
'000' => 'Success',
'401' => 'Invalid customer ID', 
'403' => 'Encryption failed',
'407' => 'IP not whitelist',
'500' => 'Parameter exception',
'600' => 'Business processing failed',
'999' => 'System exception'
```

### Queue Configuration
```php
// config/queue.php
'joytel' => [
    'driver' => 'database',
    'table' => 'jobs',
    'queue' => 'joytel',
    'retry_after' => 90,
]
```

## Production Deployment

### 1. Server Requirements
- PHP 8.2+ dengan extensions: mbstring, zip, xml, curl, json
- MariaDB/MySQL 8.0+
- Nginx/Apache
- Supervisor untuk queue workers
- SSL certificate untuk HTTPS

### 2. Optimization
```bash
# Optimize untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --no-dev --optimize-autoloader
```

### 3. Queue Workers
```bash
# Start dengan Supervisor
sudo supervisorctl start joytel-workers:*

# Monitor queues
php artisan queue:monitor joytel,default --max-time=3600
```

### 4. Monitoring
- Log files: `storage/logs/`
- Queue status: `php artisan queue:failed`
- Health check: `GET /api/utils/health`

## Testing

### Unit Tests
```bash
php artisan test
```

### API Testing
```bash
# Submit order
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "product_code": "ESIM_GLOBAL_1GB",
    "quantity": 1,
    "receive_name": "John Doe",
    "phone": "+1234567890",
    "email": "john@example.com"
  }'
```

### Webhook Testing
```bash  
# Test SN/PIN webhook
curl -X POST http://localhost:8000/api/webhook/sn-pin \
  -H "Content-Type: application/json" \
  -d '{
    "order_tid": "JT20240223123456ABCDEF",
    "sn_pin": "1234567890123456",
    "order_code": "JT_ORDER_123"
  }'
```

## Examples

Lihat file `examples/joytel_examples.php` untuk contoh lengkap penggunaan API.

## Troubleshooting

### Common Issues

1. **Queue not processing**: Check supervisor status
2. **Signature validation failed**: Verify credentials dan timestamp
3. **Database connection error**: Check `.env` database config
4. **Webhook not received**: Verify URL dan firewall settings

### Debug Commands
```bash
# Queue status
php artisan queue:work --verbose

# Check configuration
php artisan route:list
php artisan config:show joytel

# View logs
tail -f storage/logs/laravel.log
```

## Support

Untuk pertanyaan atau issues:
1. Check logs di `storage/logs/`
2. Review JoyTel API documentation
3. Submit issue dengan log details

## License

This project is licensed under the MIT License.