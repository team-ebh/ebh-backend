<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Broadcast Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold mb-4">Simple Broadcast Test</h1>

            <div class="mb-4">
                <h2 class="font-bold mb-2">Connection Status:</h2>
                <div id="status" class="text-gray-600">Not connected</div>
            </div>

            <div class="mb-4">
                <h2 class="font-bold mb-2">انتخاب نوع کانال:</h2>
                <div class="flex gap-4 mb-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="channel-type" value="monitor" checked class="mr-2">
                        <span class="font-semibold">Monitor Channels</span>
                        <span class="text-xs text-gray-600">(Development)</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="channel-type" value="private" class="mr-2">
                        <span class="font-semibold">Private Channels</span>
                        <span class="text-xs text-gray-600">(Production)</span>
                    </label>
                </div>
            </div>

            <div class="mb-4 flex gap-2">
                <button onclick="connectToChannel()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Connect to Customer 2
                </button>
                <button onclick="testBroadcast()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Send Test Broadcast
                </button>
                <button onclick="disconnect()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Disconnect
                </button>
            </div>

            <div class="mb-4 bg-yellow-50 p-3 rounded">
                <div id="channel-info" class="text-sm">
                    <strong>Channel:</strong> <code id="current-channel" class="bg-yellow-100 px-2 py-1 rounded">Not connected</code>
                </div>
            </div>

            <div class="bg-gray-100 p-4 rounded">
                <h2 class="font-bold mb-2">Events Log:</h2>
                <div id="events" class="space-y-2 max-h-96 overflow-y-auto"></div>
            </div>

            <div class="mt-4 bg-blue-50 p-4 rounded">
                <h3 class="font-bold text-blue-900 mb-2">Debug Info:</h3>
                <div id="debug" class="text-sm text-blue-800 space-y-1"></div>
            </div>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        const reverbKey = '{{ config("broadcasting.connections.reverb.key") }}';
        const reverbHost = '{{ config("broadcasting.connections.reverb.host") }}';
        const reverbPort = {{ config("broadcasting.connections.reverb.port") ?? 8080 }};

        let pusher = null;
        let channel = null;

        function log(message, type = 'info') {
            const eventsDiv = document.getElementById('events');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'text-red-600' : type === 'success' ? 'text-green-600' : 'text-gray-700';
            eventsDiv.innerHTML = `<div class="${color}">[${timestamp}] ${message}</div>` + eventsDiv.innerHTML;
            console.log(`[${timestamp}] ${message}`);
        }

        function updateStatus(message, connected = false) {
            const statusDiv = document.getElementById('status');
            statusDiv.textContent = message;
            statusDiv.className = connected ? 'text-green-600 font-bold' : 'text-gray-600';
        }

        function addDebug(label, value) {
            const debugDiv = document.getElementById('debug');
            debugDiv.innerHTML += `<div><strong>${label}:</strong> ${value}</div>`;
        }

        // Initialize debug info
        addDebug('Reverb Key', reverbKey);
        addDebug('Reverb Host', reverbHost);
        addDebug('Reverb Port', reverbPort);
        addDebug('Browser', navigator.userAgent.split(')')[0] + ')');

        function getChannelType() {
            return document.querySelector('input[name="channel-type"]:checked').value;
        }

        function initPusher() {
            if (pusher) return;

            log('🚀 Initializing Pusher...');

            try {
                pusher = new Pusher(reverbKey, {
                    wsHost: reverbHost,
                    wsPort: reverbPort,
                    wssPort: reverbPort,
                    forceTLS: false,
                    encrypted: false,
                    disableStats: true,
                    enabledTransports: ['ws', 'wss'],
                    cluster: 'mt1'
                });

                log('✅ Pusher instance created');

                // Connection events
                pusher.connection.bind('connecting', function() {
                    log('🔄 Connecting to WebSocket...');
                    updateStatus('Connecting...');
                });

                pusher.connection.bind('connected', function() {
                    log('✅ WebSocket connected!', 'success');
                    updateStatus('Connected!', true);
                });

                pusher.connection.bind('unavailable', function() {
                    log('❌ WebSocket unavailable', 'error');
                    updateStatus('Connection unavailable');
                });

                pusher.connection.bind('failed', function() {
                    log('❌ WebSocket connection failed', 'error');
                    updateStatus('Connection failed');
                });

                pusher.connection.bind('disconnected', function() {
                    log('⚠️ Disconnected from WebSocket');
                    updateStatus('Disconnected');
                });

                pusher.connection.bind('error', function(err) {
                    log('❌ WebSocket error: ' + JSON.stringify(err), 'error');
                    updateStatus('Error');
                });
            } catch (e) {
                log('❌ Error initializing Pusher: ' + e.message, 'error');
                console.error(e);
            }
        }

        function connectToChannel() {
            initPusher();

            // Disconnect existing channel
            if (channel) {
                try {
                    channel.unbind_all();
                    channel.unsubscribe();
                    log('🔌 Disconnected from previous channel');
                } catch (e) {
                    console.error('Error disconnecting:', e);
                }
            }

            const channelType = getChannelType();
            const channelName = channelType === 'monitor'
                ? 'monitor.customer.2'
                : 'customer.2';

            // Update UI
            document.getElementById('current-channel').textContent = channelName;

            // Subscribe to channel
            log(`📡 Subscribing to ${channelName}...`);
            channel = pusher.subscribe(channelName);

            channel.bind('pusher:subscription_succeeded', function() {
                log(`✅ Successfully subscribed to ${channelName}!`, 'success');
            });

            channel.bind('pusher:subscription_error', function(status) {
                log('❌ Subscription error: ' + JSON.stringify(status), 'error');
            });

            // Listen for our event
            channel.bind('trip.searching_for_rider', function(data) {
                log('🎉 RECEIVED EVENT: trip.searching_for_rider', 'success');
                log('📦 Data: ' + JSON.stringify(data, null, 2), 'success');
            });

            // Listen for all events (debug)
            channel.bind_global(function(event, data) {
                if (!event.startsWith('pusher:')) {
                    log('📨 Event received: ' + event + ' => ' + JSON.stringify(data));
                }
            });
        }

        function disconnect() {
            if (channel) {
                try {
                    channel.unbind_all();
                    channel.unsubscribe();
                    channel = null;
                    log('🔌 Disconnected', 'success');
                    updateStatus('Disconnected');
                    document.getElementById('current-channel').textContent = 'Not connected';
                } catch (e) {
                    log('❌ Error disconnecting: ' + e.message, 'error');
                }
            }

            if (pusher) {
                pusher.disconnect();
                pusher = null;
                log('🔌 Pusher disconnected', 'success');
            }
        }

        function testBroadcast() {
            log('🧪 Sending test broadcast via PHP...');

            fetch('/test-broadcast-trigger', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                log('✅ Test broadcast sent: ' + JSON.stringify(data), 'success');
            })
            .catch(err => {
                log('❌ Failed to send test broadcast: ' + err.message, 'error');
            });
        }

        // Auto-connect on load
        setTimeout(() => {
            log('🏁 Page loaded, ready to connect!');
        }, 100);
    </script>
</body>
</html>
