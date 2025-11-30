#!/bin/bash

echo "========================================="
echo "Broadcasting Setup Debug Script"
echo "========================================="
echo ""

# 1. Check environment
echo "1. Environment Check:"
echo "   APP_ENV: $(php -r "echo config('app.env');")"
echo "   Is Risky: $(php -r "echo \App\Enums\ApplicationEnvironmentEnum::isRiskyEnvironment() ? 'YES' : 'NO';")"
echo ""

# 2. Check route exists
echo "2. Route Check:"
php artisan route:list | grep "broadcasting/auth" && echo "   ✓ Route exists" || echo "   ✗ Route NOT found"
echo ""

# 3. Check Reverb config
echo "3. Reverb Configuration:"
echo "   Key: $(php -r "echo config('broadcasting.connections.reverb.key');")"
echo "   Host: $(php -r "echo config('broadcasting.connections.reverb.options.host');")"
echo "   Port: $(php -r "echo config('broadcasting.connections.reverb.options.port');")"
echo "   Scheme: $(php -r "echo config('broadcasting.connections.reverb.options.scheme');")"
echo ""

# 4. Check if Reverb is running
echo "4. Reverb Server Status:"
if ps aux | grep -v grep | grep "reverb:start" > /dev/null; then
    echo "   ✓ Reverb server is RUNNING"
    ps aux | grep -v grep | grep "reverb:start"
else
    echo "   ✗ Reverb server is NOT running"
    echo "   Start with: php artisan reverb:start"
fi
echo ""

# 5. Check port listener
echo "5. Port Listener Check:"
REVERB_PORT=$(php -r "echo config('broadcasting.connections.reverb.options.port') ?: '8080';")
if netstat -tlnp 2>/dev/null | grep ":$REVERB_PORT" > /dev/null || ss -tlnp 2>/dev/null | grep ":$REVERB_PORT" > /dev/null; then
    echo "   ✓ Port $REVERB_PORT is listening"
else
    echo "   ✗ Port $REVERB_PORT is NOT listening"
fi
echo ""

# 6. Test customer exists
echo "6. Test Customer Check:"
php -r "
\$customer = \App\Models\Customer::where('phone_number', '65656565')->first();
if (\$customer) {
    echo '   ✓ Customer #' . \$customer->id . ' exists' . PHP_EOL;
} else {
    echo '   ✗ Customer not found (phone: 65656565)' . PHP_EOL;
}
"
echo ""

# 7. Test token generation
echo "7. Token Generation Test:"
php -r "
\$customer = \App\Models\Customer::where('phone_number', '65656565')->first();
if (\$customer) {
    \$token = \$customer->createToken('debug-test')->plainTextToken;
    echo '   Token: ' . substr(\$token, 0, 30) . '...' . PHP_EOL;
    echo '   You can use this to test the endpoint manually' . PHP_EOL;
} else {
    echo '   Cannot generate token - customer not found' . PHP_EOL;
}
"
echo ""

echo "========================================="
echo "If all checks pass, the issue might be:"
echo "1. Need to clear caches: php artisan config:clear && php artisan route:clear"
echo "2. Need to restart Reverb: pkill -f reverb:start && php artisan reverb:start"
echo "3. Firewall blocking port $REVERB_PORT"
echo "4. SSL/TLS certificate issues (if using HTTPS)"
echo "========================================="
