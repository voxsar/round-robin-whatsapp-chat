<template>
  <div class="min-h-screen bg-slate-50">
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
      <button
        class="flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        @click="toggleOpen"
      >
        <span class="text-base">💬</span>
        <span v-if="!isOpen">Chat with us</span>
        <span v-else>Close</span>
      </button>

      <div
        v-if="isOpen"
        class="w-[22rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
      >
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-900 px-4 py-3 text-white">
          <div>
            <p class="text-sm font-semibold">Round Robin WhatsApp</p>
            <p class="text-xs text-slate-200">{{ connectionLabel }}</p>
          </div>
          <span
            class="inline-flex items-center rounded-full px-2 py-1 text-[0.65rem] font-semibold"
            :class="connectionBadgeClass"
          >
            {{ connectionStatus }}
          </span>
        </div>

        <div v-if="!sessionId" class="p-4">
          <h2 class="text-base font-semibold text-slate-900">Start a chat</h2>
          <p class="mt-1 text-sm text-slate-500">Share a few details and we will connect you.</p>
          <form class="mt-4 space-y-3" @submit.prevent="startChat">
            <div>
              <label class="text-xs font-semibold uppercase text-slate-500">Name</label>
              <input
                v-model="preChatForm.name"
                type="text"
                required
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="Jane Doe"
              />
            </div>
            <div>
              <label class="text-xs font-semibold uppercase text-slate-500">WhatsApp Number</label>
              <input
                v-model="preChatForm.phone"
                type="tel"
                required
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="+1 555 000 0000"
              />
            </div>
            <div>
              <label class="text-xs font-semibold uppercase text-slate-500">How can we help?</label>
              <textarea
                v-model="preChatForm.message"
                rows="3"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="Tell us a bit about what you need."
              ></textarea>
            </div>
            <button
              type="submit"
              class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
            >
              Start chat
            </button>
          </form>
        </div>

        <div v-else class="flex h-[28rem] flex-col">
          <div class="flex-1 space-y-4 overflow-y-auto px-4 py-4">
            <div
              v-for="message in messages"
              :key="message.id"
              class="flex"
              :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[75%] rounded-2xl px-4 py-2 text-sm shadow-sm"
                :class="message.role === 'user'
                  ? 'bg-emerald-600 text-white'
                  : 'bg-slate-100 text-slate-700'"
              >
                <p>{{ message.text }}</p>
                <p class="mt-1 text-[0.65rem] opacity-70">{{ message.timestamp }}</p>
              </div>
            </div>

            <div v-if="messages.length === 0" class="rounded-xl border border-dashed border-slate-200 p-4 text-center">
              <p class="text-sm text-slate-500">You are connected to our WhatsApp group. Say hello!</p>
            </div>
          </div>

          <form class="border-t border-slate-100 p-3" @submit.prevent="sendMessage">
            <div class="flex items-center gap-2">
              <input
                v-model="newMessage"
                type="text"
                class="flex-1 rounded-full border border-slate-200 px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="Type your message..."
                required
              />
              <button
                type="submit"
                class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
              >
                Send
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Pusher from 'pusher-js';

export default {
  name: 'App',
  data() {
    return {
      isOpen: false,
      sessionId: null,
      messages: [],
      connectionStatus: 'disconnected',
      preChatForm: {
        name: '',
        phone: '',
        message: ''
      },
      newMessage: ''
    }
  },
  computed: {
    connectionLabel() {
      if (this.connectionStatus === 'connecting') {
        return 'Connecting to WhatsApp...'
      }
      if (this.connectionStatus === 'connected') {
        return 'Connected to WhatsApp group'
      }
      return 'Offline'
    },
    connectionBadgeClass() {
      if (this.connectionStatus === 'connected') {
        return 'bg-emerald-500/20 text-emerald-100'
      }
      if (this.connectionStatus === 'connecting') {
        return 'bg-amber-500/20 text-amber-100'
      }
      return 'bg-slate-700 text-slate-200'
    }
  },
  methods: {
    toggleOpen() {
      this.isOpen = !this.isOpen
    },
    startChat() {
      this.sessionId = `session-${Date.now()}`
      this.connectionStatus = 'connected'

      if (this.preChatForm.message) {
        this.messages.push({
          id: `msg-${Date.now()}`,
          role: 'user',
          text: this.preChatForm.message,
          timestamp: new Date().toLocaleTimeString()
        })
      }
    },
    async sendMessage() {
      if (!this.newMessage.trim()) {
        return
      }

      const messagePayload = {
        sessionId: this.sessionId,
        message: this.newMessage.trim()
      }

      this.messages.push({
        id: `msg-${Date.now()}`,
        role: 'user',
        text: messagePayload.message,
        timestamp: new Date().toLocaleTimeString()
      })

      this.newMessage = ''
      this.connectionStatus = 'connecting'

      try {
        await fetch('/chat/message', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(messagePayload)
        })
        this.connectionStatus = 'connected'
      } catch (error) {
        this.connectionStatus = 'disconnected'
      }
    }
  }
};
</script>

<style scoped>
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-thumb {
  background-color: rgba(148, 163, 184, 0.6);
  border-radius: 999px;
}
</style>
