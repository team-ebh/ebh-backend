<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Advanced Broadcast Monitor</title>
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
        .channel-badge {
            transition: all 0.2s;
        }
        .channel-badge.connected {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-800">Advanced Broadcast Monitor</h1>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">WebSocket:</span>
                    <div id="connection-status" class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                        <span class="text-sm font-medium">Disconnected</span>
                    </div>
                </div>
            </div>

            <!-- Channel Type Selection -->
            <div class="mb-6">
                <h3 class="font-bold text-gray-800 mb-3">انتخاب نوع کانال</h3>
                <div class="flex gap-4">
                    <button onclick="selectChannelType('customer')"
                            id="btn-type-customer"
                            class="flex-1 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        کانال مشتری (Customer)
                    </button>
                    <button onclick="selectChannelType('rider')"
                            id="btn-type-rider"
                            class="flex-1 px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        کانال راننده (Rider)
                    </button>
                </div>
            </div>

            <!-- Channel Pattern Selection -->
            <div id="channel-pattern-section" class="mb-6 hidden">
                <h3 class="font-bold text-gray-800 mb-3">انتخاب الگوی کانال</h3>
                <div class="space-y-3">
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 transition">
                        <input type="radio" name="channel-pattern" value="private" class="w-5 h-5 text-blue-600" checked>
                        <div class="mr-3">
                            <div class="font-semibold text-gray-800">کانال خصوصی (Private Channel)</div>
                            <div class="text-sm text-gray-600" id="private-channel-pattern"></div>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-green-400 transition">
                        <input type="radio" name="channel-pattern" value="monitor" class="w-5 h-5 text-green-600">
                        <div class="mr-3">
                            <div class="font-semibold text-gray-800">کانال مانیتورینگ (Monitor Channel)</div>
                            <div class="text-sm text-gray-600" id="monitor-channel-pattern"></div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Add Channel Form -->
            <div id="add-channel-section" class="mb-6 hidden">
                <h3 class="font-bold text-gray-800 mb-3">اضافه کردن کانال</h3>
                <div class="flex gap-3">
                    <input
                        type="number"
                        id="channel-id-input"
                        placeholder="شناسه (ID) را وارد کنید"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        min="1"
                    >
                    <button
                        id="add-channel-btn"
                        onclick="addChannel()"
                        class="px-6 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition"
                    >
                        اضافه کردن کانال
                    </button>
                </div>
            </div>

            <!-- Active Channels -->
            <div id="active-channels-section" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-800">کانال‌های فعال</h3>
                    <button onclick="disconnectAll()" class="text-sm px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                        قطع همه کانال‌ها
                    </button>
                </div>
                <div id="active-channels-list" class="flex flex-wrap gap-2">
                    <!-- Channels will be added here dynamically -->
                </div>
            </div>
        </div>

        <!-- Events Log -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-800">لاگ رویدادها</h2>
                <div class="flex gap-2">
                    <button id="clear-btn" onclick="clearEvents()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        پاک کردن لاگ
                    </button>
                    <button id="test-btn" onclick="testEvent()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        تست رویداد
                    </button>
                </div>
            </div>
            <div id="events-container" class="space-y-4 max-h-[600px] overflow-y-auto">
                <div class="text-center text-gray-500 py-8">
                    هیچ رویدادی دریافت نشده است. یک کانال اضافه کنید تا مانیتورینگ شروع شود.
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

    <script>
        let pusher = null;
        let activeChannels = {}; // {channelName: channelObject}
        let currentChannelType = null; // 'customer' or 'rider'

        const connectionStatus = document.getElementById('connection-status');
        const eventsContainer = document.getElementById('events-container');
        const channelPatternSection = document.getElementById('channel-pattern-section');
        const addChannelSection = document.getElementById('add-channel-section');
        const activeChannelsSection = document.getElementById('active-channels-section');
        const activeChannelsList = document.getElementById('active-channels-list');
        const channelIdInput = document.getElementById('channel-id-input');

        function selectChannelType(type) {
            currentChannelType = type;

            // Update button styles
            document.getElementById('btn-type-customer').className =
                type === 'customer'
                ? 'flex-1 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg border-4 border-blue-800 transition'
                : 'flex-1 px-6 py-3 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition';

            document.getElementById('btn-type-rider').className =
                type === 'rider'
                ? 'flex-1 px-6 py-3 bg-green-600 text-white font-semibold rounded-lg border-4 border-green-800 transition'
                : 'flex-1 px-6 py-3 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition';

            // Update channel patterns
            const privatePattern = type === 'customer' ? 'customer.{customerId}' : 'rider.{riderId}';
            const monitorPattern = type === 'customer' ? 'monitor.customer.{customerId}' : 'monitor.rider.{riderId}';

            document.getElementById('private-channel-pattern').textContent = privatePattern;
            document.getElementById('monitor-channel-pattern').textContent = monitorPattern;

            // Show sections
            channelPatternSection.classList.remove('hidden');
            addChannelSection.classList.remove('hidden');

            // Initialize Pusher if not already
            initializePusher();
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
                    updateConnectionStatus(true);
                    addEvent('System', { message: 'WebSocket متصل شد' }, 'system');
                });

                pusher.connection.bind('error', function(err) {
                    console.error('Pusher connection error:', err);
                    updateConnectionStatus(false);
                    addEvent('Error', { message: 'خطا در اتصال WebSocket', error: err.error?.data?.message || err.toString() }, 'system');
                });

                console.log('Pusher initialized');
            } catch (e) {
                console.error('Error initializing Pusher:', e);
                addEvent('Error', { message: 'خطا در راه‌اندازی WebSocket', error: e.message }, 'system');
            }
        }

        function addChannel() {
            const id = channelIdInput.value;

            if (!id || id < 1) {
                alert('لطفاً شناسه معتبر وارد کنید');
                return;
            }

            if (!currentChannelType) {
                alert('لطفاً ابتدا نوع کانال را انتخاب کنید');
                return;
            }

            // Get selected pattern
            const pattern = document.querySelector('input[name="channel-pattern"]:checked').value;

            // Build channel name
            let channelName;
            if (pattern === 'private') {
                channelName = currentChannelType === 'customer' ? `customer.${id}` : `rider.${id}`;
            } else {
                channelName = currentChannelType === 'customer' ? `monitor.customer.${id}` : `monitor.rider.${id}`;
            }

            // Check if already connected
            if (activeChannels[channelName]) {
                alert('این کانال قبلاً اضافه شده است');
                return;
            }

            connectToChannel(channelName, id);
            channelIdInput.value = '';
        }

        function connectToChannel(channelName, id) {
            try {
                console.log(`Subscribing to channel: ${channelName}`);
                addEvent('System', { message: `در حال اتصال به ${channelName}...` }, 'system');

                const channel = pusher.subscribe(channelName);

                channel.bind('pusher:subscription_succeeded', function() {
                    console.log(`Successfully subscribed to ${channelName}`);
                    addEvent('System', {
                        message: `به کانال ${channelName} متصل شدید`,
                        timestamp: new Date().toISOString()
                    }, 'system');

                    addChannelBadge(channelName, id);
                });

                channel.bind('pusher:subscription_error', function(status) {
                    console.error('Subscription error:', status);
                    addEvent('Error', {
                        message: `خطا در اتصال به ${channelName}`,
                        status: status
                    }, 'system');
                });

                // Listen for events based on channel type
                if (channelName.includes('customer')) {
                    channel.bind('trip.searching_for_rider', function(data) {
                        console.log('Received trip.searching_for_rider:', data);
                        addEvent('trip.searching_for_rider', data, channelName);
                    });
                } else if (channelName.includes('rider')) {
                    channel.bind('trip.new_request', function(data) {
                        console.log('Received trip.new_request:', data);
                        addEvent('trip.new_request', data, channelName);
                    });

                    channel.bind('trip.request_cancelled', function(data) {
                        console.log('Received trip.request_cancelled:', data);
                        addEvent('trip.request_cancelled', data, channelName);
                    });
                }

                activeChannels[channelName] = channel;

            } catch (e) {
                console.error('Error connecting to channel:', e);
                addEvent('Error', {
                    message: `خطا در اتصال به کانال ${channelName}`,
                    error: e.message
                }, 'system');
            }
        }

        function addChannelBadge(channelName, id) {
            const badge = document.createElement('div');
            badge.id = `badge-${channelName}`;
            badge.className = 'channel-badge connected px-4 py-2 rounded-lg flex items-center gap-2 text-white font-semibold';

            if (channelName.includes('customer')) {
                badge.className += ' bg-blue-600';
            } else {
                badge.className += ' bg-green-600';
            }

            badge.innerHTML = `
                <div class="w-2 h-2 rounded-full bg-white pulse-dot"></div>
                <span>${channelName}</span>
                <button onclick="disconnectChannel('${channelName}')" class="ml-2 hover:bg-white hover:bg-opacity-20 rounded px-2 py-1">
                    ✕
                </button>
            `;

            activeChannelsList.appendChild(badge);
            activeChannelsSection.classList.remove('hidden');
        }

        function disconnectChannel(channelName) {
            if (activeChannels[channelName]) {
                activeChannels[channelName].unbind_all();
                activeChannels[channelName].unsubscribe();
                delete activeChannels[channelName];

                const badge = document.getElementById(`badge-${channelName}`);
                if (badge) badge.remove();

                addEvent('System', { message: `از کانال ${channelName} قطع شدید` }, 'system');

                if (Object.keys(activeChannels).length === 0) {
                    activeChannelsSection.classList.add('hidden');
                }
            }
        }

        function disconnectAll() {
            Object.keys(activeChannels).forEach(channelName => {
                disconnectChannel(channelName);
            });
        }

        function updateConnectionStatus(connected) {
            if (connected) {
                connectionStatus.innerHTML = `
                    <div class="w-3 h-3 rounded-full bg-green-500 pulse-dot"></div>
                    <span class="text-sm font-medium text-green-700">متصل</span>
                `;
            } else {
                connectionStatus.innerHTML = `
                    <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                    <span class="text-sm font-medium text-gray-600">قطع شده</span>
                `;
            }
        }

        function addEvent(eventName, data, channel, timestamp = new Date()) {
            const eventDiv = document.createElement('div');

            let colorClass = 'from-gray-50 to-gray-100 border-gray-200';
            let dotColor = 'bg-gray-500';
            let textColor = 'text-gray-900';
            let timeColor = 'text-gray-600';
            let badge = 'System';
            let badgeColor = 'bg-gray-500';

            if (channel && channel.includes('customer')) {
                colorClass = 'from-blue-50 to-blue-100 border-blue-200';
                dotColor = 'bg-blue-500';
                textColor = 'text-blue-900';
                timeColor = 'text-blue-600';
                badge = 'Customer';
                badgeColor = 'bg-blue-500';
            } else if (channel && channel.includes('rider')) {
                colorClass = 'from-green-50 to-green-100 border-green-200';
                dotColor = 'bg-green-500';
                textColor = 'text-green-900';
                timeColor = 'text-green-600';
                badge = 'Rider';
                badgeColor = 'bg-green-500';
            }

            if (eventName === 'Error') {
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
                        ${channel !== 'system' ? `<code class="text-xs bg-white px-2 py-1 rounded">${channel}</code>` : ''}
                        <h3 class="font-bold ${textColor}">${eventName}</h3>
                    </div>
                    <span class="text-xs ${timeColor}">${timestamp.toLocaleTimeString('fa-IR')}</span>
                </div>
                <pre class="bg-white rounded p-3 text-sm overflow-x-auto border border-gray-200">${JSON.stringify(data, null, 2)}</pre>
            `;

            if (eventsContainer.firstChild.classList?.contains('text-center')) {
                eventsContainer.innerHTML = '';
            }

            eventsContainer.insertBefore(eventDiv, eventsContainer.firstChild);
        }

        function clearEvents() {
            eventsContainer.innerHTML = '<div class="text-center text-gray-500 py-8">لاگ پاک شد.</div>';
        }

        function testEvent() {
            if (currentChannelType === 'customer') {
                addEvent('trip.searching_for_rider', {
                    tripId: 999,
                    riderCount: 3,
                    radiusMeters: 1500,
                    searchAttempt: 1,
                    message: 'این یک رویداد تست است'
                }, 'monitor.customer.test');
            } else if (currentChannelType === 'rider') {
                addEvent('trip.new_request', {
                    trip_id: 999,
                    customer_name: 'تست',
                    pickup_location: 'مبدا تست',
                    dropoff_location: 'مقصد تست',
                    message: 'این یک رویداد تست است'
                }, 'monitor.rider.test');
            }
        }

        // Handle Enter key in ID input
        channelIdInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') addChannel();
        });

        console.log('Advanced Monitor loaded');
    </script>
</body>
</html>
