<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Test - Private Channels</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">🔐 Private Channel Test Suite</h1>
            <p class="text-gray-600">Test WebSocket private channels with authentication</p>
        </div>

        <!-- Cards Container -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Rider Card -->
            <a href="/broadcast-test/rider" class="block group">
                <div class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-2 border-transparent hover:border-green-500">
                    <div class="flex items-center justify-center mb-6">
                        <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center text-4xl shadow-lg group-hover:scale-110 transition-transform">
                            🚗
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 text-center mb-3">Rider Test</h2>
                    <p class="text-gray-600 text-center mb-4 text-sm">
                        Test private channel for riders with authentication
                    </p>
                    <div class="space-y-2 text-xs text-gray-500">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span>Channel: <code class="bg-gray-100 px-2 py-1 rounded">rider.{id}</code></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span>Events: trip.cancelled_by_customer, trip.new_request</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span>Auth: Rider API Token</span>
                        </div>
                    </div>
                    <div class="mt-6 text-center">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-700 rounded-lg font-semibold group-hover:bg-green-600 group-hover:text-white transition-colors">
                            Test Rider Channel
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </a>

            <!-- Customer Card -->
            <a href="/broadcast-test/customer" class="block group">
                <div class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border-2 border-transparent hover:border-blue-500">
                    <div class="flex items-center justify-center mb-6">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-4xl shadow-lg group-hover:scale-110 transition-transform">
                            👤
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 text-center mb-3">Customer Test</h2>
                    <p class="text-gray-600 text-center mb-4 text-sm">
                        Test private channel for customers with authentication
                    </p>
                    <div class="space-y-2 text-xs text-gray-500">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <span>Channel: <code class="bg-gray-100 px-2 py-1 rounded">customer.{id}</code></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <span>Events: trip.accepted, trip.cancelled_by_rider</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <span>Auth: Customer API Token</span>
                        </div>
                    </div>
                    <div class="mt-6 text-center">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-semibold group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            Test Customer Channel
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Info Section -->
        <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-3">📋 Testing Instructions</h3>
            <ol class="space-y-2 text-sm text-gray-600">
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 text-green-700 rounded-full flex items-center justify-center font-bold text-xs">1</span>
                    <span>Choose either <strong>Rider</strong> or <strong>Customer</strong> test page</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 text-green-700 rounded-full flex items-center justify-center font-bold text-xs">2</span>
                    <span>Login using phone number and OTP (default: 65656565 / 0421)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 text-green-700 rounded-full flex items-center justify-center font-bold text-xs">3</span>
                    <span>Connect to the private channel after authentication</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 text-green-700 rounded-full flex items-center justify-center font-bold text-xs">4</span>
                    <span>Test API calls and monitor real-time events</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-100 text-green-700 rounded-full flex items-center justify-center font-bold text-xs">5</span>
                    <span>Open both pages simultaneously to test bidirectional communication</span>
                </li>
            </ol>
        </div>

        <!-- Technical Info -->
        <div class="mt-6 bg-gradient-to-r from-gray-800 to-gray-900 rounded-xl shadow-lg p-6 text-white">
            <h3 class="text-lg font-bold mb-3">⚙️ Technical Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <p class="text-gray-400 mb-1">Broadcasting Driver</p>
                    <p class="font-mono bg-gray-700 px-2 py-1 rounded">{{ config('broadcasting.default') }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">WebSocket Server</p>
                    <p class="font-mono bg-gray-700 px-2 py-1 rounded">{{ config('broadcasting.connections.reverb.host') }}:{{ config('broadcasting.connections.reverb.port') }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">API Base URL</p>
                    <p class="font-mono bg-gray-700 px-2 py-1 rounded">http://api.localhost:9000</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">Auth Endpoint</p>
                    <p class="font-mono bg-gray-700 px-2 py-1 rounded">/broadcasting/auth</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Made with ❤️ for testing Laravel Broadcasting & Private Channels</p>
        </div>
    </div>
</body>
</html>
