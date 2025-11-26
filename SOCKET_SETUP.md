# WebSocket Private Channel Setup Guide

## Overview
This guide explains how to set up private channel broadcasting for customer and rider events.

## Routes Available (Local & Dev Only)

**Test Pages:**
- `/socket` - Main index page
- `/socket/customer` - Customer test page
- `/socket/rider` - Rider test page

**Broadcasting Auth:**
- `POST /broadcasting/auth` - Channel authorization endpoint

## Server Requirements

### 1. Reverb Server Must Be Running

```bash
# Start Reverb server
php artisan reverb:start

# Or in background
php artisan reverb:start &

# Or with debug mode
php artisan reverb:start --debug
```

### 2. Environment Variables

Make sure these are set in your `.env` file:

```env
# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb Configuration
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"  # or your server host
REVERB_PORT=8080
REVERB_SCHEME=http       # or https for production
```

### 3. Test Authentication

Test the broadcasting auth endpoint:

```bash
# Get a token first (replace with actual customer phone)
php artisan tinker --execute="
\$customer = \App\Models\Customer::where('phone_number', '65656565')->first();
\$token = \$customer->createToken('test')->plainTextToken;
echo 'Token: ' . \$token . PHP_EOL;
"

# Test the endpoint
curl -X POST https://admin.dev.ebhapp.com/broadcasting/auth \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "socket_id=123.456&channel_name=private-customer.6"
```

**Expected Response (200 OK):**
```json
{
  "auth": "your-app-key:signature"
}
```

**Error Response (403):**
```json
{
  "message": "No bearer token"
}
```

## Troubleshooting

### Error: 403 on /broadcasting/auth

**Possible causes:**

1. **Route not registered**
   - Check: Is `APP_ENV=dev` in `.env`?
   - The route only works on non-risky environments (local, dev, test)
   - Run: `php artisan route:list | grep broadcasting`

2. **CSRF token issue**
   - The route is already excluded from CSRF in `bootstrap/app.php`
   - Check: Is the exclusion still there?

3. **Bearer token not sent**
   - Check browser Network tab for the `/broadcasting/auth` request
   - Verify `Authorization` header is present

4. **Invalid token**
   - Token must be a valid Sanctum personal access token
   - Check database: `personal_access_tokens` table

5. **Reverb server not running**
   - Check: `ps aux | grep reverb`
   - Start if needed: `php artisan reverb:start`

### Error: WebSocket Connection Failed

1. **Check Reverb server is running**
   ```bash
   ps aux | grep reverb
   netstat -tlnp | grep 8080  # or your REVERB_PORT
   ```

2. **Check firewall/ports**
   - Reverb port (default 8080) must be open
   - WebSocket traffic must be allowed

3. **Check config values**
   ```bash
   php artisan config:clear
   php artisan config:cache
   php -r "echo config('broadcasting.connections.reverb.options.host');"
   ```

### Debugging Steps

1. **Enable debug logging in route** (already enabled)
   - Check `storage/logs/laravel.log` for:
     - "Broadcasting auth request received"
     - "Token check"
     - "Token lookup"
     - "User found"
     - "Broadcasting auth successful"

2. **Check Reverb logs**
   ```bash
   # If running with --debug
   tail -f /tmp/reverb-debug.log

   # Or Laravel log
   tail -f storage/logs/laravel.log
   ```

3. **Test manually via browser console**
   ```javascript
   // Check if Echo is loaded
   console.log(window.Echo);

   // Check token
   console.log('Token:', token);
   console.log('Customer ID:', customerId);

   // Check WebSocket connection
   // Should see "Connection Established" in Reverb logs
   ```

## Production/Stage Setup

**Important:** These test routes and the custom `/broadcasting/auth` endpoint are **disabled** on production and stage servers for security.

For production broadcasting:
1. Use Laravel's built-in broadcasting authentication
2. Configure proper Reverb credentials
3. Use SSL/TLS (wss://) for WebSocket connections
4. Implement proper rate limiting

## Events

### Customer Events (sent to customer channel)
- `trip.accepted` - When rider accepts trip
- `trip.cancelled_by_rider` - When rider cancels trip
- `trip.status_updated` - When trip status changes

### Rider Events (sent to rider channel)
- `trip.new_request` - New trip request
- `trip.cancelled_by_customer` - When customer cancels trip
- `trip.request_locked` - When trip request is locked

## Channel Authorization

Channels are defined in `routes/channels.php`:

```php
// Customer private channel
Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    $authenticatedUser = $user ?? auth('customer')->user() ?? auth('sanctum')->user();
    return $authenticatedUser && ((int) $authenticatedUser->id === (int) $customerId);
});

// Rider private channel
Broadcast::channel('rider.{riderId}', function ($user, $riderId) {
    $authenticatedUser = $user ?? auth('rider')->user() ?? auth('sanctum')->user();
    return $authenticatedUser && ((int) $authenticatedUser->id === (int) $riderId);
});
```

## Default Test Credentials

**Phone:** 65656565
**OTP:** 0421

Works for both customer and rider.
