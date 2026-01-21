<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="bg-white shadow-sm">
      <div class="mx-auto max-w-6xl px-6 py-8">
        <h1 class="text-3xl font-semibold">Round Robin WhatsApp Chat</h1>
        <p class="mt-2 text-slate-600">
          Proof-of-concept demo that spins up a WhatsApp group on first contact and streams updates via Pusher.
        </p>
      </div>
    </header>
    <main>
      <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <section class="bg-white shadow-sm rounded-lg p-6">
          <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="text-2xl font-semibold text-gray-800">Live group chat</h2>
              <p class="text-sm text-gray-500">Channel: {{ channelName }}</p>
            </div>
            <span
              class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
              :class="statusClasses"
            >
              <span class="h-2 w-2 rounded-full" :class="statusDotClasses"></span>
              {{ statusLabel }}
            </span>
          </header>

          <div class="mt-6">
            <div v-if="pusherError" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
              {{ pusherError }}
            </div>
            <div
              v-else
              class="h-64 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-4"
            >
              <p v-if="messages.length === 0" class="text-sm text-gray-500">
                No messages yet. Broadcast a message to see it appear here.
              </p>
              <ul v-else class="space-y-3">
                <li
                  v-for="(message, index) in messages"
                  :key="`${message.id ?? index}-${message.timestamp}`"
                  class="rounded-lg bg-white p-3 shadow-sm"
                >
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-700">{{ message.sender }}</p>
                    <p class="text-xs text-gray-400">{{ message.timestamp }}</p>
                  </div>
                  <p class="mt-1 text-sm text-gray-600">{{ message.body }}</p>
                </li>
              </ul>
            </div>
          </div>
        </section>
      </div>
    </section>
  </div>
</template>

<script>
import Pusher from 'pusher-js';

export default {
  name: 'App',
  data() {
    return {
      groupId: 'default',
      messages: [],
      pusherClient: null,
      pusherError: '',
      connected: false
    };
  },
  computed: {
    channelName() {
      return `chat-session.${this.groupId}`;
    },
    statusLabel() {
      return this.connected ? 'Connected' : 'Waiting for connection';
    },
    statusClasses() {
      return this.connected
        ? 'bg-green-50 text-green-700'
        : 'bg-yellow-50 text-yellow-700';
    },
    statusDotClasses() {
      return this.connected ? 'bg-green-500' : 'bg-yellow-500';
    }
  },
  mounted() {
    const key = import.meta.env.VITE_PUSHER_APP_KEY;
    const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;

    if (!key || !cluster) {
      this.pusherError = 'Pusher is not configured. Set VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER.';
      return;
    }

    const client = new Pusher(key, {
      cluster,
      forceTLS: true
    });

    client.connection.bind('connected', () => {
      this.connected = true;
    });
    client.connection.bind('disconnected', () => {
      this.connected = false;
    });
    client.connection.bind('error', (error) => {
      this.pusherError = error?.error?.message || 'Unable to connect to Pusher.';
    });

    const channel = client.subscribe(this.channelName);
    channel.bind('group-message-received', (payload) => {
      const incoming = payload?.message || payload || {};
      this.messages.push({
        id: incoming.id ?? null,
        sender: incoming.sender ?? 'Unknown sender',
        body: incoming.body ?? JSON.stringify(incoming),
        timestamp: incoming.timestamp ?? new Date().toLocaleTimeString()
      });
    });

    this.pusherClient = client;
  },
  beforeUnmount() {
    if (this.pusherClient) {
      this.pusherClient.disconnect();
    }
  }
};
</script>

<style scoped>
</style>
