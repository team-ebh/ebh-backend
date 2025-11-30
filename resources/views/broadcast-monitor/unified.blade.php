<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Unified Broadcast Monitor</title>
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
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-800">Unified Broadcast Monitor</h1>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Connection Status:</span>
                    <div id="connection-status" class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                        <span class="text-sm font-medium">Disconnected</span>
                    </div>
                </div>
            </div>

            <!-- Channel Type Selection -->
            <div class="mb-6 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-lg p-4">
                <h3 class="font-bold text-purple-900 mb-3">انتخاب نوع کانال</h3>
                <div class="flex gap-4">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="channel-type" value="monitor" class="mr-2" checked>
                        <span class="font-semibold text-green-700">Monitor Channels</span>
                        <span class="text-xs text-gray-600 block ml-6">Development</span>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="channel-type" value="private" class="mr-2">
                        <span class="font-semibold text-blue-700">Private Channels</span>
                        <span class="text-xs text-gray-600 block ml-6">Production</span>
                    </label>
                </div>
            </div>

            <!-- Customer and Rider Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Customer Section -->
                <div class="border-2 border-blue-200 rounded-lg p-4 bg-blue-50">
                    <h3 class="font-bold text-blue-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Customer Monitor
                    </h3>
                    <div class="mb-3">
                        <label for="customer-id" class="block text-sm font-medium text-blue-700 mb-2">Customer ID</label>
                        <input
                            type="number"
                            id="customer-id"
                            placeholder="Enter Customer ID"
                            class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            value="2"
                        >
                    </div>
                    <button
                        id="connect-customer-btn"
                        class="w-full px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                    >
                        Connect to Customer Channel
                    </button>
                    <div class="mt-3 text-xs text-blue-700">
                        <strong>Channel:</strong> <code id="customer-channel-name" class="bg-blue-100 px-2 py-1 rounded">monitor.customer.{id}</code>
                    </div>
                </div>

                <!-- Rider Section -->
                <div class="border-2 border-green-200 rounded-lg p-4 bg-green-50">
                    <h3 class="font-bold text-green-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        Rider Monitor
                    </h3>
                    <div class="mb-3">
                        <label for="rider-id" class="block text-sm font-medium text-green-700 mb-2">Rider ID</label>
                        <input
                            type="number"
                            id="rider-id"
                            placeholder="Enter Rider ID"
                            class="w-full px-4 py-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            value="1"
                        >
                    </div>
                    <button
                        id="connect-rider-btn"
                        class="w-full px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                    >
                        Connect to Rider Channel
                    </button>
                    <div class="mt-3 text-xs text-green-700">
                        <strong>Channel:</strong> <code id="rider-channel-name" class="bg-green-100 px-2 py-1 rounded">monitor.rider.{id}</code>
                    </div>
                </div>
            </div>

            <!-- Event Types Info -->
            <div class="bg-gradient-to-r from-blue-50 to-green-50 border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Listening for Events</h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong class="text-blue-700">Customer Events:</strong>
                        <ul class="list-disc list-inside text-blue-600 mt-1 ml-2">
                            <li><code class="bg-blue-100 px-1 rounded">trip.searching_for_rider</code></li>
                        </ul>
                    </div>
                    <div>
                        <strong class="text-green-700">Rider Events:</strong>
                        <ul class="list-disc list-inside text-green-600 mt-1 ml-2">
                            <li><code class="bg-green-100 px-1 rounded">trip.new_request</code></li>
                            <li><code class="bg-green-100 px-1 rounded">trip.request_cancelled</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Log -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Events Log</h2>
                <div class="flex gap-2">
                    <button id="clear-btn" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Clear Log
                    </button>
                    <button id="test-customer-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Test Customer Event
                    </button>
                    <button id="test-rider-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Test Rider Event
                    </button>
                </div>
            </div>
            <div id="events-container" class="space-y-4 max-h-[600px] overflow-y-auto">
                <div class="text-center text-gray-500 py-8">
                    No events received yet. Connect to channels to start monitoring.
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

    <script>
        console.log('Unified Monitor Script loaded');

        let pusher = null;
        let customerChannel = null;
        let riderChannel = null;
        let customerId = null;
        let riderId = null;

        const connectCustomerBtn = document.getElementById('connect-customer-btn');
        const connectRiderBtn = document.getElementById('connect-rider-btn');
        const customerIdInput = document.getElementById('customer-id');
        const riderIdInput = document.getElementById('rider-id');
        const eventsContainer = document.getElementById('events-container');
        const clearBtn = document.getElementById('clear-btn');
        const testCustomerBtn = document.getElementById('test-customer-btn');
        const testRiderBtn = document.getElementById('test-rider-btn');
        const connectionStatus = document.getElementById('connection-status');
        const customerChannelName = document.getElementById('customer-channel-name');
        const riderChannelName = document.getElementById('rider-channel-name');

        function updateConnectionStatus() {
            const isConnected = (customerChannel || riderChannel) !== null;
            if (isConnected) {
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

        function addEvent(eventName, data, channel, timestamp = new Date()) {
            const eventDiv = document.createElement('div');

            let colorClass = 'from-blue-50 to-blue-100 border-blue-200';
            let dotColor = 'bg-blue-500';
            let textColor = 'text-blue-900';
            let timeColor = 'text-blue-600';
            let badge = 'Customer';
            let badgeColor = 'bg-blue-500';

            if (channel && channel.includes('rider')) {
                colorClass = 'from-green-50 to-green-100 border-green-200';
                dotColor = 'bg-green-500';
                textColor = 'text-green-900';
                timeColor = 'text-green-600';
                badge = 'Rider';
                badgeColor = 'bg-green-500';
            }

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
                badge = 'System';
                badgeColor = 'bg-gray-500';
            } else if (eventName === 'Error') {
                colorClass = 'from-red-50 to-red-100 border-red-200';
                dotColor = 'bg-red-500';
                textColor = 'text-red-900';
                timeColor = 'text-red-600';
                badge = 'Error';
                badgeColor = 'bg-red-500';
            }

            eventDiv.className = `event-card bg-gradient-to-r ${colorClass} border rounded-lg p-4`;

            eventDiv.innerHTML = `
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full ${dotColor}"></div>
                        <span class="px-2 py-0.5 text-xs font-bold text-white ${badgeColor} rounded">${badge}</span>
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

        function initializePusher() {
            if (pusher) return;

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
                    addEvent('System', { message: 'WebSocket connected successfully' }, 'system');
                });

                pusher.connection.bind('error', function(err) {
                    console.error('Pusher connection error:', err);
                    addEvent('Error', { message: 'WebSocket connection error', error: err.error?.data?.message || err.toString() }, 'system');
                    updateConnectionStatus();
                });

                console.log('Pusher initialized successfully');
            } catch (e) {
                console.error('Error initializing Pusher:', e);
                addEvent('Error', { message: 'Failed to initialize WebSocket', error: e.message }, 'system');
            }
        }

        function getChannelType() {
            return document.querySelector('input[name="channel-type"]:checked').value;
        }

        function connectToCustomerChannel() {
            console.log('connectToCustomerChannel called');

            const id = customerIdInput.value;

            if (!id) {
                alert('Please enter a Customer ID');
                return;
            }

            initializePusher();

            if (customerChannel) {
                try {
                    customerChannel.unbind_all();
                    customerChannel.unsubscribe();
                } catch (e) {
                    console.error('Error leaving customer channel:', e);
                }
            }

            customerId = id;
            const channelType = getChannelType();
            const channelNameText = channelType === 'monitor'
                ? `monitor.customer.${customerId}`
                : `customer.${customerId}`;
            customerChannelName.textContent = channelNameText;

            try {
                console.log(`Subscribing to channel: ${channelNameText}`);
                addEvent('System', { message: `Connecting to ${channelNameText}...` }, 'customer');

                customerChannel = pusher.subscribe(channelNameText);

                customerChannel.bind('pusher:subscription_succeeded', function() {
                    console.log('Successfully subscribed to customer channel');
                    updateConnectionStatus();
                    addEvent('System', {
                        message: `Connected to ${channelNameText} channel`,
                        customerId: customerId,
                        timestamp: new Date().toISOString()
                    }, 'customer', new Date());
                });

                customerChannel.bind('pusher:subscription_error', function(status) {
                    console.error('Customer subscription error:', status);
                    updateConnectionStatus();
                    addEvent('Error', {
                        message: 'Failed to subscribe to customer channel',
                        status: status,
                        customerId: customerId
                    }, 'customer');
                });

                // Listen for trip searching for rider event
                customerChannel.bind('trip.searching_for_rider', function(data) {
                    console.log('Received trip.searching_for_rider event:', data);
                    addEvent('trip.searching_for_rider', data, 'customer');
                });

                connectCustomerBtn.textContent = 'Reconnect Customer';
            } catch (e) {
                console.error('Error connecting to customer channel:', e);
                updateConnectionStatus();
                addEvent('Error', {
                    message: 'Failed to connect to customer channel',
                    error: e.message,
                    customerId: customerId
                }, 'customer');
            }
        }

        function connectToRiderChannel() {
            console.log('connectToRiderChannel called');

            const id = riderIdInput.value;

            if (!id) {
                alert('Please enter a Rider ID');
                return;
            }

            initializePusher();

            if (riderChannel) {
                try {
                    riderChannel.unbind_all();
                    riderChannel.unsubscribe();
                } catch (e) {
                    console.error('Error leaving rider channel:', e);
                }
            }

            riderId = id;
            const channelType = getChannelType();
            const channelNameText = channelType === 'monitor'
                ? `monitor.rider.${riderId}`
                : `rider.${riderId}`;
            riderChannelName.textContent = channelNameText;

            try {
                console.log(`Subscribing to channel: ${channelNameText}`);
                addEvent('System', { message: `Connecting to ${channelNameText}...` }, 'rider');

                riderChannel = pusher.subscribe(channelNameText);

                riderChannel.bind('pusher:subscription_succeeded', function() {
                    console.log('Successfully subscribed to rider channel');
                    updateConnectionStatus();
                    addEvent('System', {
                        message: `Connected to ${channelNameText} channel`,
                        riderId: riderId,
                        timestamp: new Date().toISOString()
                    }, 'rider', new Date());
                });

                riderChannel.bind('pusher:subscription_error', function(status) {
                    console.error('Rider subscription error:', status);
                    updateConnectionStatus();
                    addEvent('Error', {
                        message: 'Failed to subscribe to rider channel',
                        status: status,
                        riderId: riderId
                    }, 'rider');
                });

                // Listen for new trip request event
                riderChannel.bind('trip.new_request', function(data) {
                    console.log('Received trip.new_request event:', data);
                    addEvent('trip.new_request', data, 'rider');
                });

                // Listen for trip request cancelled event
                riderChannel.bind('trip.request_cancelled', function(data) {
                    console.log('Received trip.request_cancelled event:', data);
                    addEvent('trip.request_cancelled', data, 'rider');
                });

                connectRiderBtn.textContent = 'Reconnect Rider';
            } catch (e) {
                console.error('Error connecting to rider channel:', e);
                updateConnectionStatus();
                addEvent('Error', {
                    message: 'Failed to connect to rider channel',
                    error: e.message,
                    riderId: riderId
                }, 'rider');
            }
        }

        connectCustomerBtn.addEventListener('click', connectToCustomerChannel);
        connectRiderBtn.addEventListener('click', connectToRiderChannel);

        customerIdInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') connectToCustomerChannel();
        });

        riderIdInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') connectToRiderChannel();
        });

        clearBtn.addEventListener('click', () => {
            eventsContainer.innerHTML = '<div class="text-center text-gray-500 py-8">Events log cleared.</div>';
        });

        testCustomerBtn.addEventListener('click', () => {
            addEvent('trip.searching_for_rider', {
                tripId: 123,
                riderCount: 5,
                radiusMeters: 2000,
                searchAttempt: 1,
                message: 'This is a test customer event'
            }, 'customer');
        });

        testRiderBtn.addEventListener('click', () => {
            addEvent('trip.new_request', {
                trip_id: 456,
                customer_name: 'Test Customer',
                pickup_location: 'Location A',
                dropoff_location: 'Location B',
                message: 'This is a test rider event'
            }, 'rider');
        });

        console.log('Event listeners attached');
    </script>
</body>
</html>
