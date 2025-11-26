# Broadcast Test Suite - Private Channels

Complete testing interface for Laravel Broadcasting with Private Channel authentication.

## 🎯 Purpose

These pages allow you to test private WebSocket channels with full authentication flow:

- Rider private channel (`rider.{id}`)
- Customer private channel (`customer.{id}`)

## 📁 Files

```
resources/views/broadcast-test/
├── index.blade.php      # Landing page with links to both tests
├── rider.blade.php      # Rider private channel test page
├── customer.blade.php   # Customer private channel test page
└── README.md           # This file
```

## 🚀 Access URLs

Make sure your server is running on port 9000:

```bash
php artisan serve --port=9000
```

Then visit:

- **Index**: http://admin.localhost:9000/broadcast-test
- **Rider Test**: http://admin.localhost:9000/broadcast-test/rider
- **Customer Test**: http://admin.localhost:9000/broadcast-test/customer

## 🔐 Authentication Flow

### Rider Authentication

1. **Sign In**: POST `/v1/riders/auth/signin` with `phone_number`
2. **Verify OTP**: POST `/v1/riders/auth/verify-otp` with `phone_number` and `otp_code`
3. **Get Token**: Receive Bearer token for API calls
4. **Connect**: Use token to authenticate WebSocket private channel

### Customer Authentication

1. **Sign In**: POST `/v1/customers/auth/signin` with `phone_number`
2. **Verify OTP**: POST `/v1/customers/auth/verify-otp` with `phone_number` and `otp_code`
3. **Get Token**: Receive Bearer token for API calls
4. **Connect**: Use token to authenticate WebSocket private channel

## 📡 Private Channels

### Rider Channel: `rider.{id}`

**Events:**

- `trip.cancelled_by_customer` - Customer cancelled their trip
- `trip.new_request` - New trip request sent to rider
- `trip.request_locked` - Trip request locked (another rider accepted)

**Authorization:** Requires rider authentication token

### Customer Channel: `customer.{id}`

**Events:**

- `trip.accepted` - Rider accepted the trip request
- `trip.cancelled_by_rider` - Rider cancelled the trip

**Authorization:** Requires customer authentication token

## 🧪 Testing Scenarios

### Scenario 1: Test Single Channel

1. Open either Rider or Customer test page
2. Login with credentials
3. Connect to private channel
4. Test API calls
5. Monitor incoming events

### Scenario 2: Test Bidirectional Communication

1. Open both pages in separate tabs/windows
2. Login as Rider in one tab
3. Login as Customer in another tab
4. Connect both to their private channels
5. Trigger events from one side
6. Observe events received on the other side

### Example: Cancel Trip Flow

1. **Rider Tab**: Accept a trip (or have existing active trip)
2. **Customer Tab**: Cancel the trip
3. **Rider Tab**: Should receive `trip.cancelled_by_customer` event
4. Verify event data in logs

## 🔧 Available API Tests

### Rider APIs

- **Get Trip Requests**: Fetch available trip requests
- **Get Active Trip**: Get current active trip
- **Update Location**: Update rider GPS location
- **Get Profile**: Fetch rider profile data

### Customer APIs

- **Get Trip Status**: Fetch status of specific trip
- **Get Form Data**: Get trip creation form data
- **Get Profile**: Fetch customer profile data
- **Create Trip**: Create a new trip request

## 🛠️ Technical Details

### WebSocket Configuration

```javascript
{
    broadcaster: 'reverb',
        key
:
    config('broadcasting.connections.reverb.key'),
        wsHost
:
    config('broadcasting.connections.reverb.host'),
        wsPort
:
    config('broadcasting.connections.reverb.port'),
        authEndpoint
:
    '/broadcasting/auth',
        auth
:
    {
        headers: {
            'Authorization'
        :
            'Bearer {token}'
        }
    }
}
```

### Channel Authorization

Channels are authorized in `routes/channels.php`:

```php
Broadcast::channel('rider.{riderId}', function ($user, $riderId) {
    return $user && ((int) $user->id === (int) $riderId);
});

Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    return $user && ((int) $user->id === (int) $customerId);
});
```

## 📊 Event Log Features

- **Color-coded events**: Different colors for success, error, info, and events
- **Timestamps**: All events show exact time received
- **JSON formatting**: Event data displayed in readable JSON format
- **Real-time updates**: Events appear instantly when received
- **Clear log**: Button to clear all events
- **Scroll history**: Maintains history of all received events

## 🐛 Troubleshooting

### Connection Issues

**Problem**: Cannot connect to WebSocket

- Check if Reverb server is running: `php artisan reverb:start`
- Verify configuration in `.env`
- Check browser console for errors

**Problem**: Authentication failed

- Verify phone number exists in database
- Check OTP is correct (default: 1234 for testing)
- Ensure token is being sent in Authorization header

**Problem**: Events not received

- Verify channel name matches user ID
- Check event listener is registered
- Confirm event is being dispatched from backend

### Common Errors

**"Channel subscription error"**

- Token might be invalid or expired
- User ID doesn't match channel ID
- Channel authorization failed

**"API Error: Network error"**

- API server not running
- Wrong API base URL
- CORS issues (check if subdomain is correct)

## 🔒 Security Notes

⚠️ **Important**: These pages are only available in non-production environments.

They are protected by:

```php
if (!ApplicationEnvironmentEnum::isRiskyEnvironment()) {
    // Broadcast test routes
}
```

## 📝 Development Notes

### Default Credentials

For testing, you can use:

- **Phone**: 65656565
- **OTP**: 0421

### Adding New Events

To add a new event to monitor:

1. Define event class extending `BaseSocketEvent`
2. Dispatch event: `broadcast(new YourEvent(...))`
3. Add listener in test page:

```javascript
channel.listen('.your.event.name', (data) => {
    log('event', 'Event Description', data);
});
```

## 📚 Related Files

- **Routes**: `routes/web.php` (broadcast-test routes)
- **Channels**: `routes/channels.php` (authorization rules)
- **Events**: `app/Events/Socket/` (event classes)
- **Base Event**: `app/Events/Socket/BaseSocketEvent.php`

## ✨ Features

✅ Full authentication flow
✅ Private channel testing
✅ Real-time event monitoring
✅ API testing interface
✅ Clean, modern UI
✅ Color-coded event types
✅ JSON data formatting
✅ Connection status indicator
✅ Event log with history
✅ Mobile responsive

---

Made with ❤️ for testing Laravel Broadcasting & Private Channels
