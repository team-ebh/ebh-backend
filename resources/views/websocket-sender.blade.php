<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Sender - Laravel Reverb</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen py-8">
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-2xl p-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">📤 Message Sender</h1>
            <p class="text-gray-600">Send messages through WebSocket</p>
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
            </div>
        </div>

        <!-- Instructions -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
            <h3 class="font-semibold text-blue-900 mb-2">📋 Instructions:</h3>
            <ol class="text-sm text-blue-800 space-y-1">
                <li><strong>1.</strong> This page is for sending messages</li>
                <li><strong>2.</strong> Open receiver page in another tab: <a href="/websocket-receiver" target="_blank" class="underline font-medium">Receiver Page</a></li>
                <li><strong>3.</strong> Make sure Reverb is running: <code class="bg-blue-100 px-2 py-0.5 rounded">php artisan reverb:start</code></li>
            </ol>
        </div>

        <!-- Send Form -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Message:</label>
                <textarea id="message-input"
                          class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                          rows="4"
                          placeholder="Type your message here...">Hello! This is a test message.</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sender:</label>
                <input type="text" id="sender-input"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Your name"
                       value="Test User">
            </div>

            <!-- Send Button -->
            <button onclick="sendMessage()"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 px-6 rounded-lg transition-all transform hover:scale-105 shadow-lg">
                📨 Send Message
            </button>

            <!-- Status Messages -->
            <div id="status-container" class="mt-4"></div>
        </div>

        <!-- Stats -->
        <div class="mt-8 grid grid-cols-2 gap-4">
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-green-600" id="sent-count">0</div>
                <div class="text-sm text-green-800">Messages Sent</div>
            </div>
            <div class="bg-red-50 rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-red-600" id="error-count">0</div>
                <div class="text-sm text-red-800">Errors</div>
            </div>
        </div>

        <!-- Connection Info -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Connection Info:</h3>
            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                <div><strong>Channel:</strong> test-channel</div>
                <div><strong>Event:</strong> message.sent</div>
                <div><strong>Host:</strong> localhost</div>
                <div><strong>Port:</strong> 8080</div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 flex gap-3">
            <a href="/websocket-receiver" target="_blank"
               class="flex-1 text-center bg-purple-100 hover:bg-purple-200 text-purple-800 font-medium py-2 px-4 rounded-lg transition-colors">
                📥 Receiver Page
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
    let sentCount = 0;
    let errorCount = 0;

    const statusIndicator = document.getElementById('status-indicator');
    const connectionStatus = document.getElementById('connection-status');

    // Initialize Pusher for connection status
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
            connected: 'bg-green-500',
            connecting: 'bg-yellow-500 animate-pulse',
            disconnected: 'bg-red-500',
            error: 'bg-red-600 animate-pulse'
        };

        statusIndicator.className = `w-4 h-4 rounded-full ${colors[status] || 'bg-gray-400'}`;
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        pusher.disconnect();
    });

    async function sendMessage() {
        const message = document.getElementById('message-input').value.trim();
        const sender = document.getElementById('sender-input').value.trim();

        if (!message) {
            showStatus('Please enter a message', 'error');
            return;
        }

        if (!sender) {
            showStatus('Please enter sender name', 'error');
            return;
        }

        try {
            showStatus('Sending...', 'loading');

            const response = await fetch('/v1/test/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Language': 'en'
                },
                body: JSON.stringify({message, sender})
            });

            const data = await response.json();

            if (data.success) {
                sentCount++;
                document.getElementById('sent-count').textContent = sentCount;
                showStatus('✅ Message sent successfully!', 'success');

                // Show message details
                setTimeout(() => {
                    showStatus(`
                        <div class="text-left">
                            <strong>Message:</strong> ${escapeHtml(message)}<br>
                            <strong>Sender:</strong> ${escapeHtml(sender)}<br>
                            <strong>Time:</strong> ${new Date().toLocaleTimeString()}
                        </div>
                    `, 'info');
                }, 1500);
            } else {
                throw new Error('Send failed');
            }
        } catch (error) {
            errorCount++;
            document.getElementById('error-count').textContent = errorCount;
            showStatus('❌ Send error: ' + error.message, 'error');
            console.error('Error:', error);
        }
    }

    function showStatus(message, type) {
        const container = document.getElementById('status-container');
        const colors = {
            success: 'bg-green-50 border-green-200 text-green-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            loading: 'bg-blue-50 border-blue-200 text-blue-800',
            info: 'bg-purple-50 border-purple-200 text-purple-800'
        };

        container.innerHTML = `
            <div class="p-4 rounded-lg border-2 ${colors[type] || colors.info} animate-fadeIn">
                ${message}
            </div>
        `;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Handle Enter key
    document.getElementById('message-input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter' && e.ctrlKey) {
            sendMessage();
        }
    });

    document.getElementById('sender-input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
</script>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
</style>
</body>
</html>
