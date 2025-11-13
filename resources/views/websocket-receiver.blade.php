<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Receiver - Laravel Reverb</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-50 to-pink-100 min-h-screen py-8">
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-2xl p-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">📥 Message Receiver</h1>
            <p class="text-gray-600">Listen to WebSocket messages in real-time</p>
        </div>

        <!-- Connection Status -->
        <div class="mb-6 p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg border-2 border-purple-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div id="status-indicator" class="w-4 h-4 rounded-full bg-gray-400 animate-pulse"></div>
                    <div>
                        <div class="font-semibold text-gray-900">Connection Status:</div>
                        <div id="connection-status" class="text-sm text-gray-600">Connecting...</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-purple-600" id="received-count">0</div>
                    <div class="text-xs text-purple-800">Messages Received</div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="mb-6 p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
            <h3 class="font-semibold text-yellow-900 mb-2">📋 Instructions:</h3>
            <ol class="text-sm text-yellow-800 space-y-1">
                <li><strong>1.</strong> This page only receives messages</li>
                <li><strong>2.</strong> To send messages use this link: <a href="/websocket-sender" target="_blank"
                                                                           class="underline font-medium">Sender Page</a>
                </li>
                <li><strong>3.</strong> When Status shows "Connected", you're ready to receive</li>
                <li><strong>4.</strong> New messages are added to the list automatically</li>
            </ol>
        </div>

        <!-- Messages Container -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-900">Received Messages:</h2>
                <button onclick="clearMessages()"
                        class="bg-red-100 hover:bg-red-200 text-red-800 text-sm font-medium py-1 px-3 rounded-lg transition-colors">
                    🗑️ Clear All
                </button>
            </div>

            <div id="messages"
                 class="space-y-3 max-h-[500px] overflow-y-auto p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                <div class="text-center text-gray-500 text-sm py-8" id="waiting-message">
                    <div class="text-4xl mb-2">📭</div>
                    <div>Waiting for messages...</div>
                    <div class="text-xs mt-2">Send a message from Sender page</div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600" id="messages-today">0</div>
                <div class="text-xs text-green-800">Today</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-600" id="last-message-time">--:--</div>
                <div class="text-xs text-blue-800">Last Message</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-600" id="uptime">00:00</div>
                <div class="text-xs text-purple-800">Uptime</div>
            </div>
        </div>

        <!-- Connection Details -->
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">WebSocket Connection Details:</h3>
            <div class="grid grid-cols-2 gap-2 text-xs font-mono text-gray-600">
                <div><strong>Host:</strong> {{ config('broadcasting.connections.reverb.options.host') }}</div>
                <div><strong>Port:</strong> {{ config('broadcasting.connections.reverb.options.port') }}</div>
                <div><strong>Channel:</strong> test-channel</div>
                <div><strong>Event:</strong> message.sent</div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 flex gap-3">
            <a href="/websocket-sender" target="_blank"
               class="flex-1 text-center bg-blue-100 hover:bg-blue-200 text-blue-800 font-medium py-2 px-4 rounded-lg transition-colors">
                📤 Sender Page
            </a>
            <a href="/test-socket"
               class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded-lg transition-colors">
                🔄 Main Page
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0-rc2/dist/web/pusher.min.js"></script>
<script>
    let receivedCount = 0;
    let startTime = Date.now();
    let uptimeInterval;

    const statusIndicator = document.getElementById('status-indicator');
    const connectionStatus = document.getElementById('connection-status');
    const messagesContainer = document.getElementById('messages');
    const waitingMessage = document.getElementById('waiting-message');

    // Initialize Pusher
    const pusher = new Pusher('{{ config('broadcasting.connections.reverb.key') }}', {
        wsHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
        wsPort: {{ config('broadcasting.connections.reverb.options.port') }},
        wssPort: {{ config('broadcasting.connections.reverb.options.port') }},
        forceTLS: {{ config('broadcasting.connections.reverb.options.scheme') === 'https' ? 'true' : 'false' }},
        enabledTransports: ['ws', 'wss'],
        cluster: 'mt1'
    });

    // Connection handlers
    pusher.connection.bind('connected', function () {
        setConnectionStatus('Connected', 'connected');
        console.log('✅ Connected to WebSocket');
        startUptime();
    });

    pusher.connection.bind('connecting', function () {
        setConnectionStatus('Connecting...', 'connecting');
        console.log('🔄 Connecting...');
    });

    pusher.connection.bind('disconnected', function () {
        setConnectionStatus('Disconnected', 'disconnected');
        console.log('❌ Disconnected');
        stopUptime();
    });

    pusher.connection.bind('unavailable', function () {
        setConnectionStatus('Unavailable', 'error');
        console.log('⚠️ Connection unavailable');
    });

    pusher.connection.bind('error', function (err) {
        setConnectionStatus('Connection Error', 'error');
        console.error('❌ Connection error:', err);
    });

    // Subscribe to channel
    const channel = pusher.subscribe('test-channel');

    channel.bind('pusher:subscription_succeeded', function () {
        console.log('✅ Subscribed to test-channel');
    });

    channel.bind('pusher:subscription_error', function (err) {
        console.error('❌ Subscription error:', err);
    });

    // Listen for messages
    channel.bind('message.sent', function (data) {
        console.log('📨 Message received:', data);
        addMessage(data);
    });

    function setConnectionStatus(text, status) {
        connectionStatus.textContent = text;

        const colors = {
            connected: 'bg-green-500',
            connecting: 'bg-yellow-500 animate-pulse',
            disconnected: 'bg-red-500',
            error: 'bg-red-600 animate-pulse'
        };

        statusIndicator.className = `w-4 h-4 rounded-full ${colors[status] || 'bg-gray-400'}`;
    }

    function addMessage(data) {
        receivedCount++;
        document.getElementById('received-count').textContent = receivedCount;
        document.getElementById('messages-today').textContent = receivedCount;

        const now = new Date();
        document.getElementById('last-message-time').textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });

        // Remove waiting message
        if (waitingMessage) {
            waitingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = 'bg-white rounded-lg border-2 border-purple-200 p-4 shadow-sm hover:shadow-md transition-shadow animate-slideIn';
        messageDiv.innerHTML = `
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold">
                        ${escapeHtml(data.sender.charAt(0).toUpperCase())}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">${escapeHtml(data.sender)}</div>
                        <div class="text-xs text-gray-500">${new Date(data.timestamp).toLocaleTimeString('en-US')}</div>
                    </div>
                </div>
                <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">New</span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <p class="text-gray-800 leading-relaxed whitespace-pre-wrap">${escapeHtml(data.message)}</p>
            </div>
            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                <span>📡 Channel: test-channel</span>
                <span>•</span>
                <span>⏰ ${new Date(data.timestamp).toLocaleDateString('en-US')}</span>
            </div>
        `;

        messagesContainer.insertBefore(messageDiv, messagesContainer.firstChild);

        // Remove 'New' badge after 3 seconds
        setTimeout(() => {
            const badge = messageDiv.querySelector('.bg-green-100');
            if (badge) {
                badge.style.opacity = '0';
                badge.style.transition = 'opacity 0.5s';
                setTimeout(() => badge.remove(), 500);
            }
        }, 3000);
    }

    function clearMessages() {
        if (confirm('Are you sure you want to clear all messages?')) {
            messagesContainer.innerHTML = `
                <div class="text-center text-gray-500 text-sm py-8" id="waiting-message">
                    <div class="text-4xl mb-2">📭</div>
                    <div>All messages cleared</div>
                    <div class="text-xs mt-2">Waiting for new messages...</div>
                </div>
            `;
            receivedCount = 0;
            document.getElementById('received-count').textContent = '0';
            document.getElementById('messages-today').textContent = '0';
        }
    }

    function startUptime() {
        uptimeInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            document.getElementById('uptime').textContent =
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }, 1000);
    }

    function stopUptime() {
        if (uptimeInterval) {
            clearInterval(uptimeInterval);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        pusher.disconnect();
        stopUptime();
    });
</script>

<style>
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .animate-slideIn {
        animation: slideIn 0.3s ease-out;
    }

    /* Custom scrollbar */
    #messages::-webkit-scrollbar {
        width: 8px;
    }

    #messages::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    #messages::-webkit-scrollbar-thumb {
        background: #a855f7;
        border-radius: 4px;
    }

    #messages::-webkit-scrollbar-thumb:hover {
        background: #9333ea;
    }
</style>
</body>
</html>
