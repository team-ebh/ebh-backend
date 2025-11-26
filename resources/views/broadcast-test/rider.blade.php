<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rider Private Channel Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .event-card {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-lg shadow-lg p-6 mb-6 text-white">
            <h1 class="text-3xl font-bold mb-2">🚗 Rider Private Channel Test</h1>
            <p class="text-green-100">Test private channel authentication and real-time events</p>
        </div>

        <!-- Login Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">1️⃣ Login as Rider</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input
                        type="text"
                        id="phone-number"
                        placeholder="65656565"
                        value="65656565"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">OTP Code</label>
                    <input
                        type="text"
                        id="otp-code"
                        placeholder="0421"
                        value="0421"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                </div>
                <div class="flex items-end gap-2">
                    <button
                        id="sign-in-btn"
                        class="flex-1 px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition"
                    >
                        Sign In
                    </button>
                    <button
                        id="verify-btn"
                        class="flex-1 px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                        disabled
                    >
                        Verify OTP
                    </button>
                </div>
            </div>
            <div id="auth-status" class="mt-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
                Not authenticated
            </div>
        </div>

        <!-- WebSocket Connection Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">2️⃣ Connect to Private Channel</h2>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-2">
                        <strong>Channel:</strong> <code id="channel-name" class="bg-gray-100 px-2 py-1 rounded">rider.{id}</code>
                    </p>
                    <p class="text-xs text-gray-500">This is a private channel. Requires authentication.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        id="connect-btn"
                        class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition disabled:bg-gray-400"
                        disabled
                    >
                        Connect
                    </button>
                    <div id="connection-status" class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                        <span class="text-sm font-medium">Disconnected</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800"><strong>Listening for:</strong></p>
                <ul class="list-disc list-inside text-xs text-blue-700 mt-2 space-y-1">
                    <li><code class="bg-blue-100 px-2 py-1 rounded">trip.cancelled_by_customer</code></li>
                    <li><code class="bg-blue-100 px-2 py-1 rounded">trip.new_request</code></li>
                    <li><code class="bg-blue-100 px-2 py-1 rounded">trip.request_locked</code></li>
                </ul>
            </div>
        </div>

        <!-- Events Log -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">📋 Events Log</h2>
                <button id="clear-btn" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition text-sm">
                    Clear Log
                </button>
            </div>
            <div id="events-container" class="space-y-3 max-h-[500px] overflow-y-auto">
                <div class="text-center text-gray-500 py-8">
                    Please authenticate and connect to start monitoring events
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script>
        const API_BASE = 'http://api.localhost:9000/v1/riders';
        let token = null;
        let riderId = null;
        let echo = null;
        let channel = null;

        // Elements
        const signInBtn = document.getElementById('sign-in-btn');
        const verifyBtn = document.getElementById('verify-btn');
        const connectBtn = document.getElementById('connect-btn');
        const phoneInput = document.getElementById('phone-number');
        const otpInput = document.getElementById('otp-code');
        const authStatus = document.getElementById('auth-status');
        const channelName = document.getElementById('channel-name');
        const connectionStatus = document.getElementById('connection-status');
        const eventsContainer = document.getElementById('events-container');
        const clearBtn = document.getElementById('clear-btn');

        function log(type, message, data = null) {
            const colors = {
                success: { bg: 'from-green-50 to-green-100', border: 'border-green-200', text: 'text-green-900', dot: 'bg-green-500' },
                error: { bg: 'from-red-50 to-red-100', border: 'border-red-200', text: 'text-red-900', dot: 'bg-red-500' },
                info: { bg: 'from-blue-50 to-blue-100', border: 'border-blue-200', text: 'text-blue-900', dot: 'bg-blue-500' },
                event: { bg: 'from-purple-50 to-purple-100', border: 'border-purple-200', text: 'text-purple-900', dot: 'bg-purple-500' },
            };

            const color = colors[type] || colors.info;
            const timestamp = new Date().toLocaleTimeString();

            const eventDiv = document.createElement('div');
            eventDiv.className = `event-card bg-gradient-to-r ${color.bg} border ${color.border} rounded-lg p-4`;
            eventDiv.innerHTML = `
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full ${color.dot}"></div>
                        <h3 class="font-bold ${color.text}">${message}</h3>
                    </div>
                    <span class="text-xs ${color.text} opacity-75">${timestamp}</span>
                </div>
                ${data ? `<pre class="bg-white rounded p-2 text-xs overflow-x-auto border border-gray-200">${JSON.stringify(data, null, 2)}</pre>` : ''}
            `;

            if (eventsContainer.firstChild?.classList?.contains('text-center')) {
                eventsContainer.innerHTML = '';
            }
            eventsContainer.insertBefore(eventDiv, eventsContainer.firstChild);
        }

        function updateConnectionStatus(connected) {
            connectionStatus.innerHTML = connected
                ? `<div class="w-3 h-3 rounded-full bg-green-500 pulse-dot"></div><span class="text-sm font-medium text-green-700">Connected</span>`
                : `<div class="w-3 h-3 rounded-full bg-gray-400"></div><span class="text-sm font-medium text-gray-600">Disconnected</span>`;
        }

        // Sign In
        signInBtn.addEventListener('click', async () => {
            const phone = phoneInput.value;
            log('info', 'Sending sign-in request...', { phone_number: phone });

            try {
                const response = await fetch(`${API_BASE}/auth/sign-in`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ phone_number: phone })
                });

                const data = await response.json();
                if (response.ok) {
                    log('success', 'Sign-in successful! OTP sent', data);
                    verifyBtn.disabled = false;
                    authStatus.innerHTML = `<span class="text-blue-600">✓ OTP sent to ${phone}. Please verify.</span>`;
                } else {
                    log('error', 'Sign-in failed', data);
                    authStatus.innerHTML = `<span class="text-red-600">✗ Sign-in failed: ${data.message || 'Unknown error'}</span>`;
                }
            } catch (error) {
                log('error', 'Sign-in error', { error: error.message });
                authStatus.innerHTML = `<span class="text-red-600">✗ Network error</span>`;
            }
        });

        // Verify OTP
        verifyBtn.addEventListener('click', async () => {
            const phone = phoneInput.value;
            const otp = otpInput.value;
            log('info', 'Verifying OTP...', { phone_number: phone, otp: otp });

            try {
                const response = await fetch(`${API_BASE}/auth/sign-in/verify-otp`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ phone_number: phone, otp: otp })
                });

                const data = await response.json();

                // Debug: log full response
                console.log('Full response:', data);

                if (response.ok && data.data?.token) {
                    token = data.data.token;
                    riderId = data.data.rider.id;

                    console.log('Token:', token);
                    console.log('Rider ID:', riderId);

                    log('success', 'Authentication successful!', { rider_id: riderId, token: token.substring(0, 20) + '...' });
                    authStatus.innerHTML = `<span class="text-green-600">✓ Authenticated as Rider #${riderId}</span>`;
                    channelName.textContent = `rider.${riderId}`;
                    connectBtn.disabled = false;
                } else {
                    log('error', 'OTP verification failed', data);
                    authStatus.innerHTML = `<span class="text-red-600">✗ Invalid OTP</span>`;
                }
            } catch (error) {
                log('error', 'Verification error', { error: error.message });
                authStatus.innerHTML = `<span class="text-red-600">✗ Network error</span>`;
            }
        });

        // Connect to WebSocket
        connectBtn.addEventListener('click', () => {
            console.log('Connect clicked. Token:', token, 'Rider ID:', riderId);

            if (!token || !riderId) {
                log('error', 'Please authenticate first', { token: !!token, riderId: !!riderId });
                return;
            }

            log('info', 'Connecting to private channel...', { channel: `rider.${riderId}` });

            // Debug: Check if Echo is available
            console.log('window.Echo:', window.Echo);
            console.log('typeof Echo:', typeof Echo);

            if (!window.Echo) {
                log('error', 'Laravel Echo not loaded!', {
                    windowEcho: typeof window.Echo,
                    Echo: typeof Echo
                });
                return;
            }

            echo = new window.Echo({
                broadcaster: 'pusher',
                key: '{{ config("broadcasting.connections.reverb.key") }}',
                wsHost: '{{ config("broadcasting.connections.reverb.options.host") }}',
                wsPort: {{ config("broadcasting.connections.reverb.options.port") ?? 8080 }},
                wssPort: {{ config("broadcasting.connections.reverb.options.port") ?? 8080 }},
                forceTLS: false,
                encrypted: false,
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
                cluster: 'mt1',
                authEndpoint: 'http://admin.localhost:9000/broadcasting/auth',
                auth: {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    }
                }
            });

            channel = echo.private(`rider.${riderId}`);

            channel.subscribed(() => {
                log('success', 'Connected to private channel!', { channel: `rider.${riderId}` });
                updateConnectionStatus(true);
            });

            channel.error((error) => {
                log('error', 'Channel subscription error', error);
                updateConnectionStatus(false);
            });

            channel.listen('.trip.cancelled_by_customer', (data) => {
                log('event', '🚫 Trip Cancelled by Customer', data);
            });

            channel.listen('.trip.new_request', (data) => {
                log('event', '🆕 New Trip Request', data);
            });

            channel.listen('.trip.request_locked', (data) => {
                log('event', '🔒 Trip Request Locked', data);
            });

            connectBtn.textContent = 'Connected';
            connectBtn.disabled = true;
        });

        clearBtn.addEventListener('click', () => {
            eventsContainer.innerHTML = '<div class="text-center text-gray-500 py-8">Events cleared</div>';
        });
    </script>
</body>
</html>
