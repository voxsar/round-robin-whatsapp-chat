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

    <main class="mx-auto max-w-6xl px-6 py-10">
      <div class="rounded-2xl bg-white p-8 shadow">
        <h2 class="text-2xl font-semibold">Demo Overview</h2>
        <ul class="mt-4 space-y-3 text-slate-600">
          <li>• Start a chat from the widget in the bottom-right corner.</li>
          <li>• The backend creates a WhatsApp group using configured participants + bot number.</li>
          <li>• Messages posted to the group are pushed to the UI via Pusher.</li>
        </ul>
      </div>
    </main>

    <!-- Floating chat widget -->
    <section class="fixed bottom-6 right-6 z-50">
      <button
        v-if="!isOpen"
        class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500 text-2xl text-white shadow-lg hover:bg-emerald-600"
        @click="isOpen = true"
        aria-label="Open chat"
      >
        💬
      </button>

      <div
        v-else
        class="flex w-80 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
      >
        <div class="flex items-center justify-between bg-emerald-500 px-4 py-3 text-white">
          <div>
            <p class="text-sm font-medium">WhatsApp POC Chat</p>
            <p class="text-xs opacity-80">
              {{ session?.group_subject ?? 'Ready to connect' }}
            </p>
          </div>
          <button class="text-lg" @click="isOpen = false" aria-label="Close chat">×</button>
        </div>

        <div class="flex-1 space-y-4 overflow-y-auto bg-slate-50 p-4">
          <!-- Pre-chat form -->
          <div v-if="!hasSession" class="rounded-lg border border-dashed border-slate-300 bg-white p-4">
            <h3 class="text-sm font-semibold text-slate-700">Start a chat</h3>
            <p class="mt-1 text-xs text-slate-500">Enter your details to spin up a WhatsApp group.</p>

            <div class="mt-3 space-y-3">
              <input
                v-model="form.name"
                type="text"
                placeholder="Name"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              />
              <input
                v-model="form.email"
                type="email"
                placeholder="Email (optional)"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              />
              <input
                v-model="form.mobile"
                type="tel"
                placeholder="Mobile (optional)"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              />
              <input
                v-model="form.instance"
                type="text"
                placeholder="Instance (optional)"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              />

              <button
                class="w-full rounded-lg bg-emerald-500 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-600 disabled:opacity-60 disabled:cursor-not-allowed"
                :disabled="isLoading"
                @click="startSession"
              >
                {{ isLoading ? 'Starting...' : 'Start Chat' }}
              </button>

              <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
            </div>
          </div>

          <!-- Message list -->
          <div v-else class="space-y-3">
            <div
              v-for="message in messages"
              :key="message.id"
              class="flex"
              :class="message.sender === 'visitor' ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[75%] rounded-2xl px-3 py-2 text-sm"
                :class="message.sender === 'visitor'
                  ? 'bg-emerald-500 text-white'
                  : 'bg-white text-slate-700 border border-slate-200'"
              >
                <p class="whitespace-pre-line">{{ message.text }}</p>
                <p class="mt-1 text-[10px] opacity-70">{{ formatTime(message.timestamp) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Composer -->
        <div class="border-t border-slate-200 bg-white p-3">
          <div class="flex items-center gap-2">
            <input
              v-model="draft"
              type="text"
              placeholder="Type your message..."
              class="flex-1 rounded-full border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
              :disabled="!hasSession || isSending"
              @keydown.enter.prevent="sendMessage"
            />
            <button
              class="rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600 disabled:opacity-60 disabled:cursor-not-allowed"
              :disabled="!hasSession || isSending"
              @click="sendMessage"
            >
              Send
            </button>
          </div>
          <p v-if="error && hasSession" class="mt-2 text-xs text-red-500">{{ error }}</p>
        </div>
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
      isOpen: false,
      isLoading: false,
      isSending: false,
      hasSession: false,
      error: '',
      session: null,
      channelName: '',
      messages: [],
      draft: '',
      form: {
        name: '',
        email: '',
        mobile: '',
        instance: ''
      },
      pusher: null,
      pusherChannel: null
    };
  },
  mounted() {
    const appEl = document.getElementById('app');
    if (appEl?.dataset?.whatsappInstance) {
      this.form.instance = appEl.dataset.whatsappInstance;
    }
  },
  beforeUnmount() {
    // Clean up Pusher subscriptions
    try {
      if (this.pusherChannel && this.pusher) this.pusher.unsubscribe(this.channelName);
      if (this.pusher) this.pusher.disconnect();
    } catch (_) {}
  },
  methods: {
    async startSession() {
      this.error = '';

      if (!this.form.name) {
        this.error = 'Name is required.';
        return;
      }
      if (!this.form.email && !this.form.mobile) {
        this.error = 'Provide at least an email or mobile.';
        return;
      }

      this.isLoading = true;

      try {
        const response = await fetch('/api/chat/session', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            name: this.form.name,
            email: this.form.email || null,
            mobile: this.form.mobile || null,
            instance: this.form.instance || null
          })
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          this.error = data.message || 'Unable to start the session.';
          return;
        }

        this.session = data.session;
        this.channelName = data.channel;
        this.hasSession = true;
        this.messages = [];

        this.setupPusher();
      } catch (e) {
        this.error = 'Network error starting session.';
      } finally {
        this.isLoading = false;
      }
    },

    setupPusher() {
      const appEl = document.getElementById('app');
      const key = appEl?.dataset?.pusherKey;
      const cluster = appEl?.dataset?.pusherCluster || 'mt1';

      if (!key) {
        this.error = 'Missing Pusher key (data-pusher-key on #app).';
        return;
      }
      if (!this.channelName) {
        this.error = 'Missing channel name from session response.';
        return;
      }

      // Ensure no duplicate subscriptions if startSession is called again
      try {
        if (this.pusher) this.pusher.disconnect();
      } catch (_) {}

      this.pusher = new Pusher(key, { cluster });

      this.pusherChannel = this.pusher.subscribe(this.channelName);
      this.pusherChannel.bind('message', (payload) => {
        if (payload?.message) {
          this.messages.push(payload.message);
        }
      });
    },

    async sendMessage() {
      this.error = '';

      const text = this.draft.trim();
      if (!text || !this.hasSession || this.isSending) return;

      this.isSending = true;
      this.draft = '';

      // optimistic UI
      this.messages.push({
        id: (crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`),
        sender: 'visitor',
        text,
        timestamp: new Date().toISOString()
      });

      try {
        const res = await fetch('/api/chat/message', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            session_id: this.session.id,
            text
          })
        });

        if (!res.ok) {
          const data = await res.json().catch(() => ({}));
          this.error = data.message || 'Message failed to send.';
        }
      } catch (_) {
        this.error = 'Message failed to send (network error).';
      } finally {
        this.isSending = false;
      }
    },

    formatTime(timestamp) {
      if (!timestamp) return '';
      const date = new Date(timestamp);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>
