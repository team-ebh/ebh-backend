# Laravel Reverb WebSocket Testing Guide

This guide explains how to test WebSocket functionality using Laravel Reverb.

## Prerequisites

- Laravel Reverb installed and configured
- Redis running (for session storage)
- PHP 8.2+ with required extensions

## Configuration

### Environment Variables

The following environment variables are already configured in `.env`:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=891894
REVERB_APP_KEY=rjh7g66iuhl8zikoxhth
REVERB_APP_SECRET=0aicozxgivnh53kekrd5
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Testing Methods

### Method 1: Using the Web Test Page (Recommended)

This is the easiest way to test WebSocket functionality.

#### Steps:

1. **Start the Reverb server:**
   ```bash
   php artisan reverb:start
   ```

   You should see output like:
   ```
   INFO Server running on http://0.0.0.0:8080
   ```

2. **Start the Laravel development server** (in a separate terminal):
   ```bash
   php artisan serve --port=9000
   ```

3. **Open the test page in your browser:**
   ```
   http://api.localhost:9000/test-socket
   ```

4. **Test the connection:**
   - The page will automatically connect to the WebSocket server
   - You should see the status change from "Disconnected" to "Connected"
   - Type a message and sender name
   - Click "Send Message via API"
   - The message should appear in the "Received Messages" section

#### What's Happening:

- The web page connects to Reverb WebSocket server on port 8080
- When you click "Send Message", it makes a POST request to `/api/v1/test-socket/send`
- The API endpoint triggers the `TestMessageSent` event
- The event is broadcasted via Reverb to all connected clients
- Your browser receives the message and displays it

---

### Method 2: Using cURL and Browser Console

#### Step 1: Start Reverb Server
```bash
php artisan reverb:start
```

#### Step 2: Connect via Browser Console

Open your browser console (F12) and paste this code:

```javascript
// Initialize Pusher client
const pusher = new Pusher('rjh7g66iuhl8zikoxhth', {
    wsHost: 'localhost',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    cluster: 'mt1'
});

// Connection handlers
pusher.connection.bind('connected', () => {
    console.log('✅ Connected to Reverb');
});

pusher.connection.bind('error', (err) => {
    console.error('❌ Connection error:', err);
});

// Subscribe to channel
const channel = pusher.subscribe('test-channel');

// Listen for messages
channel.bind('message.sent', (data) => {
    console.log('📨 Message received:', data);
});
```

#### Step 3: Send a Message via cURL

In a terminal, run:

```bash
curl -X POST http://api.localhost:9000/v1/test/send \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Hello from cURL!",
    "sender": "Terminal User"
  }'
```

You should see the message appear in your browser console.

---

### Method 3: Using Postman

#### Step 1: Get Connection Info

**GET** `http://api.localhost:9000/v1/test/connection-info`

Response:
```json
{
    "success": true,
    "connection": {
        "host": "localhost",
        "port": 8080,
        "app_key": "rjh7g66iuhl8zikoxhth",
        "scheme": "http",
        "channel": "test-channel",
        "event": "message.sent"
    },
    "instructions": [...]
}
```

#### Step 2: Send a Message

**POST** `http://api.localhost:9000/v1/test/send`

Headers:
- `Content-Type: application/json`
- `Accept: application/json`

Body (JSON):
```json
{
    "message": "Test message from Postman",
    "sender": "Postman User"
}
```

Response:
```json
{
    "success": true,
    "message": "Message broadcasted successfully",
    "data": {
        "message": "Test message from Postman",
        "sender": "Postman User",
        "channel": "test-channel",
        "event": "message.sent"
    }
}
```

---

## API Endpoints

### GET `/v1/test/connection-info`

Returns WebSocket connection information and instructions.

**Response:**
```json
{
    "success": true,
    "connection": {
        "host": "localhost",
        "port": 8080,
        "app_key": "rjh7g66iuhl8zikoxhth",
        "scheme": "http",
        "channel": "test-channel",
        "event": "message.sent"
    },
    "instructions": [...]
}
```

### POST `/v1/test/send`

Broadcasts a message to all connected WebSocket clients.

**Request Body:**
```json
{
    "message": "Your message here",
    "sender": "Sender name (optional)"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Message broadcasted successfully",
    "data": {
        "message": "Your message here",
        "sender": "Sender name",
        "channel": "test-channel",
        "event": "message.sent"
    }
}
```

---

## Technical Details

### Event: `TestMessageSent`

Location: `app/Events/TestMessageSent.php`

```php
class TestMessageSent implements ShouldBroadcast
{
    public function __construct(
        public string $message,
        public string $sender = 'System'
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('test-channel')];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'sender' => $this->sender,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### Channel: `test-channel`

This is a **public channel**, meaning anyone can subscribe without authentication.

### Event Name: `message.sent`

Full event path: `test-channel.message.sent`

---

## Troubleshooting

### Issue: "Disconnected" status on test page

**Solution:**
1. Make sure Reverb server is running: `php artisan reverb:start`
2. Check if port 8080 is not being used by another application
3. Verify `.env` configuration

### Issue: Messages not appearing

**Solution:**
1. Check browser console for errors (F12)
2. Verify Reverb server is running and showing connection logs
3. Make sure you're subscribed to the correct channel

### Issue: CORS errors

**Solution:**
Add CORS configuration in `config/cors.php` if needed:

```php
'paths' => ['api/*', 'broadcasting/auth', 'reverb/*'],
'allowed_origins' => ['*'],
```

### Issue: Port 8080 already in use

**Solution:**
Change the Reverb port in `.env`:

```env
REVERB_PORT=8081
VITE_REVERB_PORT=8081
```

Then restart Reverb server.

---

## Monitoring Reverb

### View Reverb Logs

When running `php artisan reverb:start`, you'll see real-time logs:

```
INFO Server running on http://0.0.0.0:8080
INFO Connection established from 127.0.0.1
INFO Subscribed to channel: test-channel
INFO Broadcasting event: test-channel.message.sent
```

### Debug Mode

Start Reverb with verbose output:

```bash
php artisan reverb:start --debug
```

---

## Production Considerations

For production deployment:

1. **Use HTTPS/WSS**
   ```env
   REVERB_SCHEME=https
   ```

2. **Use a process manager** (Supervisor, PM2, etc.)
   ```bash
   php artisan reverb:start --no-interaction
   ```

3. **Configure firewall** to allow port 8080 (or your custom port)

4. **Use authentication** for private channels

5. **Monitor Reverb** using Laravel Horizon or custom monitoring

---

## Next Steps

### Creating Private Channels

For authenticated users:

1. Define the channel authorization in `routes/channels.php`:
   ```php
   Broadcast::channel('user.{userId}', function ($user, $userId) {
       return (int) $user->id === (int) $userId;
   });
   ```

2. Change event to use PrivateChannel:
   ```php
   public function broadcastOn(): array
   {
       return [new PrivateChannel("user.{$this->userId}")];
   }
   ```

3. Client must authenticate before subscribing

### Creating Presence Channels

For showing online users:

```php
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return ['id' => $user->id, 'name' => $user->name];
});
```

---

## Resources

- [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Pusher Protocol Documentation](https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol/)

---

## Summary

**Quick Test:**
1. `php artisan reverb:start`
2. `php artisan serve --port=9000`
3. Open `http://api.localhost:9000/test-socket`
4. Send a message and see it appear in real-time!

**Your WebSocket is working if:**
- ✅ Status shows "Connected"
- ✅ Messages appear in "Received Messages"
- ✅ Reverb logs show connections and broadcasts
