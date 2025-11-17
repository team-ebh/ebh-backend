<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rider Broadcast Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .event-card {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-800">Rider Broadcast Monitor</h1>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Connection Status:</span>
                    <div id="connection-status" class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                        <span class="text-sm font-medium">Disconnected</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="rider-id" class="block text-sm font-medium text-gray-700 mb-2">Rider ID</label>
                    <input
                        type="number"
                        id="rider-id"
                        placeholder="Enter Rider ID"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        value="1"
                    >
                </div>
                <div class="flex items-end">
                    <button
                        id="connect-btn"
                        class="w-full px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                    >
                        Connect to Channel
                    </button>
                </div>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="font-semibold text-green-900 mb-2">Channel Info</h3>
                <p class="text-sm text-green-800">
                    <strong>Channel:</strong> <code id="channel-name" class="bg-green-100 px-2 py-1 rounded">monitor.rider.{id}</code>
                </p>
                <p class="text-sm text-green-800 mt-2">
                    <strong>Listening for events:</strong>
                </p>
                <ul class="list-disc list-inside text-sm text-green-700 mt-1">
                    <li><code class="bg-green-100 px-2 py-1 rounded">.trip.new_request</code> - New trip request notification</li>
                    <li><code class="bg-green-100 px-2 py-1 rounded">.trip.request_cancelled</code> - Trip request cancelled</li>
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Events Log</h2>
                <div class="flex gap-2">
                    <button id="clear-btn" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Clear Log
                    </button>
                    <button id="test-new-request-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Test New Request
                    </button>
                    <button id="test-cancelled-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Test Cancelled
                    </button>
                </div>
            </div>
            <div id="events-container" class="space-y-4 max-h-[600px] overflow-y-auto">
                <div class="text-center text-gray-500 py-8">
                    No events received yet. Connect to a rider channel to start monitoring.
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

    <script>
        console.log('Script loaded');

        let pusher = null;
        let currentChannel = null;
        let riderId = null;

        const connectBtn = document.getElementById('connect-btn');
        const riderIdInput = document.getElementById('rider-id');
        const eventsContainer = document.getElementById('events-container');
        const clearBtn = document.getElementById('clear-btn');
        const testNewRequestBtn = document.getElementById('test-new-request-btn');
        const testCancelledBtn = document.getElementById('test-cancelled-btn');
        const connectionStatus = document.getElementById('connection-status');
        const channelName = document.getElementById('channel-name');

        console.log('Elements found:', { connectBtn, riderIdInput, eventsContainer });

        function updateConnectionStatus(connected) {
            if (connected) {
                connectionStatus.innerHTML = `
                    <div class="w-3 h-3 rounded-full bg-green-500 pulse-dot"></div>
                    <span class="text-sm font-medium text-green-700">Connected</span>
                `;
            } else {
                connectionStatus.innerHTML = `
                    <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                    <span class="text-sm font-medium text-gray-600">Disconnected</span>
                `;
            }
        }

        function addEvent(eventName, data, timestamp = new Date()) {
            const eventDiv = document.createElement('div');

            let colorClass = 'from-green-50 to-green-100 border-green-200';
            let dotColor = 'bg-green-500';
            let textColor = 'text-green-900';
            let timeColor = 'text-green-600';

            if (eventName.includes('cancelled')) {
                colorClass = 'from-red-50 to-red-100 border-red-200';
                dotColor = 'bg-red-500';
                textColor = 'text-red-900';
                timeColor = 'text-red-600';
            } else if (eventName === 'System') {
                colorClass = 'from-gray-50 to-gray-100 border-gray-200';
                dotColor = 'bg-gray-500';
                textColor = 'text-gray-900';
                timeColor = 'text-gray-600';
            } else if (eventName === 'Error') {
                colorClass = 'from-red-50 to-red-100 border-red-200';
                dotColor = 'bg-red-500';
                textColor = 'text-red-900';
                timeColor = 'text-red-600';
            }

            eventDiv.className = `event-card bg-gradient-to-r ${colorClass} border rounded-lg p-4`;

            eventDiv.innerHTML = `
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full ${dotColor}"></div>
                        <h3 class="font-bold ${textColor}">${eventName}</h3>
                    </div>
                    <span class="text-xs ${timeColor}">${timestamp.toLocaleTimeString()}</span>
                </div>
                <pre class="bg-white rounded p-3 text-sm overflow-x-auto border border-gray-200">${JSON.stringify(data, null, 2)}</pre>
            `;

            if (eventsContainer.firstChild.classList?.contains('text-center')) {
                eventsContainer.innerHTML = '';
            }

            eventsContainer.insertBefore(eventDiv, eventsContainer.firstChild);
        }

        function connectToChannel() {
            console.log('connectToChannel called');

            const id = riderIdInput.value;

            if (!id) {
                alert('Please enter a Rider ID');
                return;
            }

            if (currentChannel) {
                try {
                    currentChannel.unbind_all();
                    currentChannel.unsubscribe();
                } catch (e) {
                    console.error('Error leaving channel:', e);
                }
            }

            riderId = id;
            const channelNameText = `monitor.rider.${riderId}`;
            channelName.textContent = channelNameText;

            // Initialize Pusher if not already
            if (!pusher) {
                try {
                    const reverbKey = '{{ config("broadcasting.connections.reverb.key") }}';
                    const reverbHost = '{{ config("broadcasting.connections.reverb.host") }}';
                    const reverbPort = {{ config("broadcasting.connections.reverb.port") ?? 8080 }};

                    console.log('Initializing Pusher with:', { reverbKey, reverbHost, reverbPort });

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

                    pusher.connection.bind('connected', function() {
                        console.log('Pusher connected');
                        addEvent('System', { message: 'WebSocket connected successfully' });
                    });

                    pusher.connection.bind('error', function(err) {
                        console.error('Pusher connection error:', err);
                        addEvent('Error', { message: 'WebSocket connection error', error: err.error?.data?.message || err.toString() });
                        updateConnectionStatus(false);
                    });

                    console.log('Pusher initialized successfully');
                } catch (e) {
                    console.error('Error initializing Pusher:', e);
                    addEvent('Error', { message: 'Failed to initialize WebSocket', error: e.message });
                    return;
                }
            }

            try {
                // Subscribe to public monitoring channel (no auth required)
                console.log(`Subscribing to channel: ${channelNameText}`);
                addEvent('System', { message: `Connecting to ${channelNameText}...` });

                currentChannel = pusher.subscribe(channelNameText);

                currentChannel.bind('pusher:subscription_succeeded', function() {
                    console.log('Successfully subscribed to channel');
                    updateConnectionStatus(true);
                    addEvent('System', {
                        message: `Connected to ${channelNameText} channel`,
                        riderId: riderId,
                        timestamp: new Date().toISOString()
                    }, new Date());
                });

                currentChannel.bind('pusher:subscription_error', function(status) {
                    console.error('Subscription error:', status);
                    updateConnectionStatus(false);
                    addEvent('Error', {
                        message: 'Failed to subscribe to channel',
                        status: status,
                        riderId: riderId
                    });
                });

                // Listen for new trip request event
                currentChannel.bind('trip.new_request', function(data) {
                    console.log('Received trip.new_request event:', data);
                    addEvent('trip.new_request', data);

                    // Play sound or show notification
                    if (Notification.permission === 'granted') {
                        new Notification('New Trip Request!', {
                            body: `Trip ID: ${data.trip_id || data.id}`,
                            icon: '/favicon.ico'
                        });
                    }
                });

                // Listen for trip request cancelled event
                currentChannel.bind('trip.request_cancelled', function(data) {
                    console.log('Received trip.request_cancelled event:', data);
                    addEvent('trip.request_cancelled', data);
                });

                connectBtn.textContent = 'Reconnect';
            } catch (e) {
                console.error('Error connecting to channel:', e);
                updateConnectionStatus(false);
                addEvent('Error', {
                    message: 'Failed to connect to channel',
                    error: e.message,
                    riderId: riderId
                });
            }
        }

        connectBtn.addEventListener('click', function() {
            console.log('Button clicked');
            connectToChannel();
        });

        riderIdInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                connectToChannel();
            }
        });

        clearBtn.addEventListener('click', () => {
            eventsContainer.innerHTML = '<div class="text-center text-gray-500 py-8">Events log cleared.</div>';
        });

        testNewRequestBtn.addEventListener('click', () => {
            addEvent('trip.new_request', {
                trip_id: 456,
                customer_name: 'John Doe',
                pickup_location: 'Kuwait City',
                dropoff_location: 'Salmiya',
                distance: '5.2 km',
                estimated_fare: '3.500 KD',
                passenger_count: 2,
                accessibility_requirements: [],
                message: 'This is a test new trip request event'
            });
        });

        testCancelledBtn.addEventListener('click', () => {
            addEvent('trip.request_cancelled', {
                trip_id: 456,
                reason: 'trip_assigned',
                message: 'This is a test trip cancelled event'
            });
        });

        // Request notification permission
        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }

        console.log('Event listeners attached');
    </script>
</body>
</html>
