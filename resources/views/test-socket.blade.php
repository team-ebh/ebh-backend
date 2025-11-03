<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Test - Laravel Reverb</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-purple-900 to-violet-900 min-h-screen flex items-center justify-center p-8">
<div class="max-w-4xl w-full">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-5xl font-bold text-white mb-4">🚀 WebSocket Testing</h1>
        <p class="text-xl text-purple-200">Laravel Reverb Real-Time Communication</p>
    </div>

    <!-- Status Card -->
    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-8 border border-white/20">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div id="status-indicator" class="w-12 h-12 rounded-full bg-gray-500/20 flex items-center justify-center">
                    <div class="w-6 h-6 rounded-full bg-gray-400 animate-pulse"></div>
                </div>
                <div>
                    <div class="text-white font-semibold text-lg">Connection Status</div>
                    <div id="connection-status" class="text-purple-200 text-sm">Connecting...</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-white text-sm">Channel: <span class="font-mono font-bold text-purple-300">test-channel</span></div>
                <div class="text-white text-sm">Port: <span class="font-mono font-bold text-purple-300">{{ config('broadcasting.connections.reverb.options.port') }}</span></div>
            </div>
        </div>
    </div>

    <!-- Main Options Grid -->
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <!-- Sender Card -->
        <a href="/websocket-sender" target="_blank"
           class="group bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-8 shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300">
            <div class="text-6xl mb-4">📤</div>
            <h2 class="text-2xl font-bold text-white mb-2">Message Sender</h2>
            <p class="text-blue-100 mb-4">Send messages to WebSocket</p>
            <div class="bg-white/20 rounded-lg p-3 mb-4">
                <div class="text-white text-sm space-y-1">
                    <div>✓ Message sending form</div>
                    <div>✓ Sent messages statistics</div>
                    <div>✓ Send status display</div>
                </div>
            </div>
            <div class="flex items-center justify-between text-white">
                <span class="text-sm">Open in new tab</span>
                <span class="group-hover:translate-x-2 transition-transform">→</span>
            </div>
        </a>

        <!-- Receiver Card -->
        <a href="/websocket-receiver" target="_blank"
           class="group bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-8 shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300">
            <div class="text-6xl mb-4">📥</div>
            <h2 class="text-2xl font-bold text-white mb-2">Message Receiver</h2>
            <p class="text-purple-100 mb-4">Receive messages from WebSocket</p>
            <div class="bg-white/20 rounded-lg p-3 mb-4">
                <div class="text-white text-sm space-y-1">
                    <div>✓ Real-Time connection</div>
                    <div>✓ Received messages display</div>
                    <div>✓ Statistics and connection status</div>
                </div>
            </div>
            <div class="flex items-center justify-between text-white">
                <span class="text-sm">Open in new tab</span>
                <span class="group-hover:translate-x-2 transition-transform">→</span>
            </div>
        </a>
    </div>

    <!-- Instructions Card -->
    <div class="bg-white rounded-2xl p-8 shadow-2xl">
        <h3 class="text-2xl font-bold text-gray-900 mb-4">📋 Usage Guide</h3>

        <div class="space-y-4">
            <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold flex-shrink-0">1</div>
                <div>
                    <div class="font-semibold text-gray-900">Start Reverb Server</div>
                    <code class="text-sm bg-gray-100 px-3 py-1 rounded mt-1 inline-block">php artisan reverb:start</code>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center font-bold flex-shrink-0">2</div>
                <div>
                    <div class="font-semibold text-gray-900">Open Receiver Page</div>
                    <p class="text-sm text-gray-600">In a separate tab to receive messages</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-pink-500 text-white flex items-center justify-center font-bold flex-shrink-0">3</div>
                <div>
                    <div class="font-semibold text-gray-900">Open Sender Page</div>
                    <p class="text-sm text-gray-600">In another tab to send messages</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center font-bold flex-shrink-0">4</div>
                <div>
                    <div class="font-semibold text-gray-900">Send a message and see the results!</div>
                    <p class="text-sm text-gray-600">Your message should appear instantly in Receiver</p>
                </div>
            </div>
        </div>

        <!-- Quick Test -->
        <div class="mt-6 p-4 bg-yellow-50 rounded-lg border-2 border-yellow-200">
            <div class="flex items-start gap-3">
                <div class="text-2xl">💡</div>
                <div class="flex-1">
                    <div class="font-semibold text-yellow-900 mb-1">Quick Test with cURL:</div>
                    <code class="text-xs bg-yellow-100 px-2 py-1 rounded block overflow-x-auto">
curl -X POST {{ config('broadcasting.connections.reverb.options.scheme') }}://{{ config('app.domains.api') }}/v1/test/send \<br>
  -H "Content-Type: application/json" \<br>
  -H "Accept: application/json" \<br>
  -H "Language: en" \<br>
  -d '{"message":"Test","sender":"cURL"}'
                    </code>
                </div>
            </div>
        </div>

        <!-- API Docs Link -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3">
            <a href="/websocket-docs" class="text-center bg-gradient-to-r from-blue-100 to-purple-100 hover:from-blue-200 hover:to-purple-200 text-blue-900 font-medium py-3 px-4 rounded-lg transition-colors">
                📱 Mobile Developer Docs
            </a>
            <a href="/docs/v1/riders" class="text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-3 px-4 rounded-lg transition-colors">
                📚 API Documentation
            </a>
            <a href="https://laravel.com/docs/reverb" target="_blank" class="text-center bg-purple-100 hover:bg-purple-200 text-purple-800 font-medium py-3 px-4 rounded-lg transition-colors">
                📖 Laravel Reverb Docs
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-8 text-purple-200 text-sm">
        <p>Laravel Reverb WebSocket Testing Interface</p>
        <p class="mt-1">Powered by Laravel {{ app()->version() }}</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0-rc2/dist/web/pusher.min.js"></script>
<script>
    const statusIndicator = document.getElementById('status-indicator');
    const connectionStatus = document.getElementById('connection-status');

    // Initialize Pusher
    const pusher = new Pusher('{{ config('broadcasting.connections.reverb.key') }}', {
        wsHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
        wsPort: {{ config('broadcasting.connections.reverb.options.port') }},
        wssPort: {{ config('broadcasting.connections.reverb.options.port') }},
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        cluster: 'mt1'
    });

    // Connection handlers
    pusher.connection.bind('connected', function () {
        setConnectionStatus('Connected', 'connected');
        console.log('✅ Connected to WebSocket');
    });

    pusher.connection.bind('connecting', function () {
        setConnectionStatus('Connecting...', 'connecting');
        console.log('🔄 Connecting...');
    });

    pusher.connection.bind('disconnected', function () {
        setConnectionStatus('Disconnected', 'disconnected');
        console.log('❌ Disconnected');
    });

    pusher.connection.bind('unavailable', function () {
        setConnectionStatus('Unavailable', 'error');
        console.log('⚠️ Connection unavailable');
    });

    pusher.connection.bind('error', function (err) {
        setConnectionStatus('Connection Error', 'error');
        console.error('❌ Connection error:', err);
    });

    function setConnectionStatus(text, status) {
        connectionStatus.textContent = text;

        const colors = {
            connected: { bg: 'bg-green-500/20', dot: 'bg-green-500' },
            connecting: { bg: 'bg-yellow-500/20', dot: 'bg-yellow-500 animate-pulse' },
            disconnected: { bg: 'bg-red-500/20', dot: 'bg-red-500' },
            error: { bg: 'bg-red-600/20', dot: 'bg-red-600 animate-pulse' }
        };

        const color = colors[status] || { bg: 'bg-gray-500/20', dot: 'bg-gray-400' };

        statusIndicator.className = `w-12 h-12 rounded-full ${color.bg} flex items-center justify-center`;
        statusIndicator.innerHTML = `<div class="w-6 h-6 rounded-full ${color.dot}"></div>`;
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        pusher.disconnect();
    });
</script>
</body>
</html>
