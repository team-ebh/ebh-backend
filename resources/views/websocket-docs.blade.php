<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket API Documentation - For Mobile Developers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
</head>
<body class="bg-gray-50 min-h-screen py-8">
<div class="max-w-6xl mx-auto px-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 text-white shadow-2xl">
        <h1 class="text-4xl font-bold mb-3">📱 WebSocket API Documentation</h1>
        <p class="text-xl text-blue-100">Complete guide for React Native mobile developers</p>
        <div class="mt-4 flex gap-3 text-sm flex-wrap">
            <span class="bg-white/20 px-3 py-1 rounded-full">⚛️ React Native</span>
            <span class="bg-white/20 px-3 py-1 rounded-full">Laravel Reverb</span>
            <span class="bg-white/20 px-3 py-1 rounded-full">Pusher Protocol</span>
            <span class="bg-white/20 px-3 py-1 rounded-full">Real-time</span>
        </div>
    </div>

    <!-- Quick Access Navigation -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">🚀 Quick Access</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="#api-keys" class="block p-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors border-2 border-yellow-300">
                <div class="font-semibold text-yellow-900">🔑 API Keys</div>
                <div class="text-sm text-yellow-700">URLs, Keys, Headers</div>
            </a>
            <a href="#connection-info" class="block p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                <div class="font-semibold text-blue-900">Connection Info</div>
                <div class="text-sm text-blue-700">Host, Port, Channel</div>
            </a>
            <a href="#rest-api" class="block p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                <div class="font-semibold text-green-900">REST API</div>
                <div class="text-sm text-green-700">HTTP Endpoints</div>
            </a>
            <a href="#react-native" class="block p-3 bg-cyan-50 hover:bg-cyan-100 rounded-lg transition-colors">
                <div class="font-semibold text-cyan-900">⚛️ React Native</div>
                <div class="text-sm text-cyan-700">Implementation</div>
            </a>
            <a href="#testing" class="block p-3 bg-pink-50 hover:bg-pink-100 rounded-lg transition-colors">
                <div class="font-semibold text-pink-900">Testing Tools</div>
                <div class="text-sm text-pink-700">Web Test Pages</div>
            </a>
        </div>
    </div>

    <!-- API Keys & Authentication -->
    <div id="api-keys" class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">🔑 API Keys & Authentication</h2>

        <div class="space-y-6">
            <!-- Base URLs -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Base URLs</h3>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-700">REST API Base URL:</span>
                        <code class="bg-gray-200 px-3 py-1 rounded text-sm">{{ config('broadcasting.connections.reverb.options.scheme') }}://{{ config('app.domains.api') }}</code>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-700">WebSocket URL:</span>
                        <code class="bg-gray-200 px-3 py-1 rounded text-sm">ws://{{ config('broadcasting.connections.reverb.options.host') }}:{{ config('broadcasting.connections.reverb.options.port') }}</code>
                    </div>
                </div>
            </div>

            <!-- Authentication Headers -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Required Headers for REST API</h3>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <pre class="text-sm"><code>Content-Type: application/json
Accept: application/json
Language: en  <span class="text-green-600">// Required! (en or ar)</span></code></pre>
                </div>
                <p class="text-sm text-gray-600 mt-2">⚠️ The <code class="bg-gray-200 px-2 py-1 rounded">Language</code> header is mandatory for all API requests.</p>
            </div>

            <!-- WebSocket Keys -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">WebSocket Connection Keys</h3>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-700">App Key:</span>
                        <code class="bg-gray-200 px-3 py-1 rounded text-sm font-mono">{{ config('broadcasting.connections.reverb.key') }}</code>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-700">Cluster:</span>
                        <code class="bg-gray-200 px-3 py-1 rounded text-sm">mt1</code>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2">💡 These keys are used to initialize the Pusher client in your mobile app.</p>
            </div>

            <!-- Network Configuration -->
            <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                <h4 class="font-semibold text-blue-900 mb-2">📱 Important: Network Configuration for Mobile Devices</h4>
                <div class="text-sm text-blue-800 space-y-2">
                    <p><strong>iOS Simulator:</strong> Use <code class="bg-blue-100 px-2 py-1 rounded">localhost</code> or <code class="bg-blue-100 px-2 py-1 rounded">127.0.0.1</code></p>
                    <p><strong>Android Emulator:</strong> Use <code class="bg-blue-100 px-2 py-1 rounded">10.0.2.2</code> (this maps to host machine's localhost)</p>
                    <p><strong>Physical Devices:</strong> Use your computer's local IP address (e.g., <code class="bg-blue-100 px-2 py-1 rounded">192.168.1.100</code>)</p>
                    <p class="mt-2"><strong>Find your IP:</strong></p>
                    <ul class="ml-4 space-y-1">
                        <li>• Windows: <code class="bg-blue-100 px-2 py-1 rounded">ipconfig</code> in Command Prompt</li>
                        <li>• Mac: <code class="bg-blue-100 px-2 py-1 rounded">ifconfig | grep "inet "</code> in Terminal</li>
                        <li>• Linux: <code class="bg-blue-100 px-2 py-1 rounded">ip addr show</code> in Terminal</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Information -->
    <div id="connection-info" class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">🔌 WebSocket Connection Details</h2>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">WebSocket Server</h3>
                <div class="space-y-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Host:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">{{ config('broadcasting.connections.reverb.options.host') }}</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Port:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">{{ config('broadcasting.connections.reverb.options.port') }}</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">App Key:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">{{ config('broadcasting.connections.reverb.key') }}</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Scheme:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">{{ config('broadcasting.connections.reverb.options.scheme') }}</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Force TLS:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">false</code>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Channel & Event</h3>
                <div class="space-y-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Channel:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">test-channel</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Event:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">message.sent</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Channel Type:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">Public</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Protocol:</span>
                        <code class="bg-gray-200 px-2 py-1 rounded text-sm">Pusher</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
            <div class="flex items-start gap-3">
                <span class="text-2xl">💡</span>
                <div>
                    <div class="font-semibold text-yellow-900 mb-1">Important Notes:</div>
                    <ul class="text-sm text-yellow-800 space-y-1">
                        <li>• This server uses Pusher protocol - use Pusher client libraries</li>
                        <li>• The channel is <strong>public</strong> - no authentication required</li>
                        <li>• All messages are broadcasted to all connected clients</li>
                        <li>• Connection stays alive until manually disconnected</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- REST API Endpoints -->
    <div id="rest-api" class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">🌐 REST API Endpoints</h2>

        <!-- Send Message Endpoint -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <span class="bg-green-100 text-green-800 font-bold px-3 py-1 rounded text-sm">POST</span>
                <code class="text-lg font-mono">/v1/test/send</code>
            </div>

            <p class="text-gray-700 mb-4">Send a message through WebSocket to all connected clients.</p>

            <div class="space-y-4">
                <div>
                    <h4 class="font-semibold text-gray-900 mb-2">Request Headers:</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-http">Content-Type: application/json
Accept: application/json
Language: en</code></pre>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 mb-2">Request Body:</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-json">{
  "message": "Hello from mobile!",
  "sender": "Mobile App"
}</code></pre>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 mb-2">Response (200 OK):</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-json">{
  "success": true,
  "message": "Message broadcasted successfully",
  "data": {
    "message": "Hello from mobile!",
    "sender": "Mobile App",
    "channel": "test-channel",
    "event": "message.sent"
  }
}</code></pre>
                </div>
            </div>
        </div>

        <!-- Get Connection Info Endpoint -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <span class="bg-blue-100 text-blue-800 font-bold px-3 py-1 rounded text-sm">GET</span>
                <code class="text-lg font-mono">/v1/test/connection-info</code>
            </div>

            <p class="text-gray-700 mb-4">Get WebSocket connection information programmatically.</p>

            <div class="space-y-4">
                <div>
                    <h4 class="font-semibold text-gray-900 mb-2">Response (200 OK):</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-json">{
  "success": true,
  "connection": {
    "host": "{{ config('broadcasting.connections.reverb.options.host') }}",
    "port": {{ config('broadcasting.connections.reverb.options.port') }},
    "app_key": "{{ config('broadcasting.connections.reverb.key') }}",
    "scheme": "{{ config('broadcasting.connections.reverb.options.scheme') }}",
    "channel": "test-channel",
    "event": "message.sent"
  }
}</code></pre>
                </div>
            </div>
        </div>

        <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
            <div class="font-semibold text-blue-900 mb-2">Base URL:</div>
            <code class="text-blue-800">{{ config('broadcasting.connections.reverb.options.scheme') }}://{{ config('app.domains.api') }}</code>
        </div>
    </div>

    <!-- React Native Implementation -->
    <div id="react-native" class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">⚛️ React Native Implementation</h2>

        <div class="space-y-6">
            <!-- Installation -->
            <div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">1. Installation</h3>
                <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-bash">npm install pusher-js
# or
yarn add pusher-js
</code></pre>
            </div>

            <!-- WebSocket Hook -->
            <div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">2. WebSocket Hook</h3>
                <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-javascript">// hooks/useWebSocket.js
import { useEffect, useState, useCallback } from 'react';
import Pusher from 'pusher-js';

export const useWebSocket = () => {
  const [messages, setMessages] = useState([]);
  const [isConnected, setIsConnected] = useState(false);
  const [pusher, setPusher] = useState(null);

  useEffect(() => {
    // Initialize Pusher
    const pusherInstance = new Pusher('{{ config('broadcasting.connections.reverb.key') }}', {
      wsHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
      wsPort: {{ config('broadcasting.connections.reverb.options.port') }},
      wssPort: {{ config('broadcasting.connections.reverb.options.port') }},
      forceTLS: false,
      enabledTransports: ['ws', 'wss'],
      cluster: 'mt1',
    });

    // Connection state
    pusherInstance.connection.bind('connected', () => {
      console.log('✅ Connected to WebSocket');
      setIsConnected(true);
    });

    pusherInstance.connection.bind('disconnected', () => {
      console.log('❌ Disconnected from WebSocket');
      setIsConnected(false);
    });

    // Subscribe to channel
    const channel = pusherInstance.subscribe('test-channel');

    // Listen for messages
    channel.bind('message.sent', (data) => {
      console.log('📨 Message received:', data);
      setMessages((prev) => [data, ...prev]);
    });

    setPusher(pusherInstance);

    // Cleanup
    return () => {
      pusherInstance.disconnect();
    };
  }, []);

  const sendMessage = useCallback(async (message, sender) => {
    try {
      const response = await fetch('{{ config('broadcasting.connections.reverb.options.scheme') }}://{{ config('app.domains.api') }}/v1/test/send', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Language': 'en',
        },
        body: JSON.stringify({ message, sender }),
      });

      const data = await response.json();

      if (data.success) {
        console.log('✅ Message sent successfully');
        return true;
      }
      return false;
    } catch (error) {
      console.error('❌ Failed to send message:', error);
      return false;
    }
  }, []);

  return { messages, isConnected, sendMessage };
};
</code></pre>
            </div>

            <!-- Usage Example -->
            <div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">3. Usage in Component</h3>
                <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-javascript">// screens/ChatScreen.js
import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, FlatList, StyleSheet } from 'react-native';
import { useWebSocket } from '../hooks/useWebSocket';

export const ChatScreen = () => {
  const [message, setMessage] = useState('');
  const { messages, isConnected, sendMessage } = useWebSocket();

  const handleSend = async () => {
    if (message.trim()) {
      const success = await sendMessage(message, 'React Native App');
      if (success) {
        setMessage('');
      }
    }
  };

  return (
    &lt;View style={styles.container}&gt;
      {/* Connection Status */}
      &lt;View style={[styles.status, isConnected ? styles.connected : styles.disconnected]}&gt;
        &lt;Text style={styles.statusText}&gt;
          {isConnected ? '✅ Connected' : '❌ Disconnected'}
        &lt;/Text&gt;
      &lt;/View&gt;

      {/* Messages List */}
      &lt;FlatList
        data={messages}
        keyExtractor={(item, index) => index.toString()}
        renderItem={({ item }) => (
          &lt;View style={styles.messageCard}&gt;
            &lt;Text style={styles.sender}&gt;{item.sender}&lt;/Text&gt;
            &lt;Text style={styles.message}&gt;{item.message}&lt;/Text&gt;
            &lt;Text style={styles.timestamp}&gt;
              {new Date(item.timestamp).toLocaleTimeString()}
            &lt;/Text&gt;
          &lt;/View&gt;
        )}
        inverted
      /&gt;

      {/* Send Message Input */}
      &lt;View style={styles.inputContainer}&gt;
        &lt;TextInput
          style={styles.input}
          value={message}
          onChangeText={setMessage}
          placeholder="Type a message..."
          placeholderTextColor="#999"
        /&gt;
        &lt;TouchableOpacity
          style={styles.sendButton}
          onPress={handleSend}
          disabled={!isConnected || !message.trim()}
        &gt;
          &lt;Text style={styles.sendButtonText}&gt;Send&lt;/Text&gt;
        &lt;/TouchableOpacity&gt;
      &lt;/View&gt;
    &lt;/View&gt;
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  status: {
    padding: 12,
    alignItems: 'center',
  },
  connected: {
    backgroundColor: '#d4edda',
  },
  disconnected: {
    backgroundColor: '#f8d7da',
  },
  statusText: {
    fontSize: 14,
    fontWeight: '600',
  },
  messageCard: {
    backgroundColor: 'white',
    margin: 8,
    padding: 12,
    borderRadius: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  sender: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 4,
  },
  message: {
    fontSize: 16,
    color: '#666',
    marginBottom: 4,
  },
  timestamp: {
    fontSize: 12,
    color: '#999',
  },
  inputContainer: {
    flexDirection: 'row',
    padding: 12,
    backgroundColor: 'white',
    borderTopWidth: 1,
    borderTopColor: '#ddd',
  },
  input: {
    flex: 1,
    backgroundColor: '#f5f5f5',
    borderRadius: 20,
    paddingHorizontal: 16,
    paddingVertical: 8,
    fontSize: 16,
    marginRight: 8,
  },
  sendButton: {
    backgroundColor: '#007AFF',
    borderRadius: 20,
    paddingHorizontal: 20,
    paddingVertical: 8,
    justifyContent: 'center',
  },
  sendButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
});
</code></pre>
            </div>
        </div>
    </div>

    <!-- Testing Section -->
    <div id="testing" class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">🧪 Testing</h2>

        <div class="space-y-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Test Your Implementation</h3>
                <p class="text-gray-700 mb-4">Use our web-based testing tools to verify your mobile implementation:</p>

                <div class="grid md:grid-cols-2 gap-4">
                    <a href="/websocket-sender" target="_blank" class="block p-4 bg-blue-50 hover:bg-blue-100 rounded-lg border-2 border-blue-200 transition-colors">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-2xl">📤</span>
                            <div class="font-semibold text-blue-900 text-lg">Sender Page</div>
                        </div>
                        <p class="text-sm text-blue-700">Send test messages to your mobile app</p>
                    </a>

                    <a href="/websocket-receiver" target="_blank" class="block p-4 bg-purple-50 hover:bg-purple-100 rounded-lg border-2 border-purple-200 transition-colors">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-2xl">📥</span>
                            <div class="font-semibold text-purple-900 text-lg">Receiver Page</div>
                        </div>
                        <p class="text-sm text-purple-700">View messages sent from your mobile app</p>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Testing Steps</h3>
                <ol class="space-y-3">
                    <li class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                        <div>
                            <div class="font-semibold text-gray-900">Implement WebSocket in your React Native app</div>
                            <p class="text-sm text-gray-600">Copy the code examples above into your project</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                        <div>
                            <div class="font-semibold text-gray-900">Open the Receiver page</div>
                            <p class="text-sm text-gray-600">Use the link above to monitor incoming messages</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
                        <div>
                            <div class="font-semibold text-gray-900">Send message from your mobile app</div>
                            <p class="text-sm text-gray-600">The message should appear in the Receiver page instantly</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">4</span>
                        <div>
                            <div class="font-semibold text-gray-900">Send message from Sender page</div>
                            <p class="text-sm text-gray-600">Your mobile app should receive it in real-time</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">🔧 Troubleshooting</h2>

        <div class="space-y-4">
            <div class="p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
                <h4 class="font-semibold text-yellow-900 mb-2">Connection Failed</h4>
                <ul class="text-sm text-yellow-800 space-y-1">
                    <li>• Verify the WebSocket host and port are correct</li>
                    <li>• Make sure Reverb server is running: <code class="bg-yellow-100 px-2 py-1 rounded">php artisan reverb:start</code></li>
                    <li>• Check if your device/emulator can reach the server</li>
                    <li>• For iOS Simulator: Use <code class="bg-yellow-100 px-2 py-1 rounded">localhost</code></li>
                    <li>• For Android Emulator: Use <code class="bg-yellow-100 px-2 py-1 rounded">10.0.2.2</code> instead of localhost</li>
                    <li>• For Physical Device: Use your computer's IP address</li>
                </ul>
            </div>

            <div class="p-4 bg-red-50 rounded-lg border-l-4 border-red-500">
                <h4 class="font-semibold text-red-900 mb-2">Messages Not Received</h4>
                <ul class="text-sm text-red-800 space-y-1">
                    <li>• Check if you're subscribed to the correct channel: <code class="bg-red-100 px-2 py-1 rounded">test-channel</code></li>
                    <li>• Verify the event name is correct: <code class="bg-red-100 px-2 py-1 rounded">message.sent</code></li>
                    <li>• Check console logs for connection/subscription errors</li>
                    <li>• Ensure the connection state is "connected" before sending</li>
                </ul>
            </div>

            <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                <h4 class="font-semibold text-blue-900 mb-2">API Requests Failing</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• Add the required header: <code class="bg-blue-100 px-2 py-1 rounded">Language: en</code></li>
                    <li>• Check the base URL points to: <code class="bg-blue-100 px-2 py-1 rounded">{{ config('broadcasting.connections.reverb.options.scheme') }}://{{ config('app.domains.api') }}</code></li>
                    <li>• For physical devices, replace the domain with your computer's IP address</li>
                    <li>• Verify Content-Type is set to <code class="bg-blue-100 px-2 py-1 rounded">application/json</code></li>
                </ul>
            </div>

            <div class="p-4 bg-green-50 rounded-lg border-l-4 border-green-500">
                <h4 class="font-semibold text-green-900 mb-2">Network Configuration for Physical Devices</h4>
                <ul class="text-sm text-green-800 space-y-1">
                    <li>• Find your computer's IP: <code class="bg-green-100 px-2 py-1 rounded">ipconfig</code> (Windows) or <code class="bg-green-100 px-2 py-1 rounded">ifconfig</code> (Mac/Linux)</li>
                    <li>• Replace <code class="bg-green-100 px-2 py-1 rounded">localhost</code> with your IP in all URLs</li>
                    <li>• Example: <code class="bg-green-100 px-2 py-1 rounded">http://192.168.1.100:9000/v1/test/send</code></li>
                    <li>• Both devices must be on the same network</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center py-8 text-gray-600">
        <p class="mb-2">Laravel Reverb WebSocket API Documentation</p>
        <p class="text-sm">Need help? Check our <a href="/test-socket" class="text-blue-600 hover:underline">Web Testing Tools</a></p>
    </div>
</div>
</body>
</html>
