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
              <label class="text-xs font-semibold uppercase text-slate-500">Email</label>
              <input
                v-model="preChatForm.email"
                type="email"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="jane@example.com"
              />
            </div>
            <div>
              <label class="text-xs font-semibold uppercase text-slate-500">WhatsApp Number</label>
              <input
                v-model="preChatForm.mobile"
                type="tel"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="+1 555 000 0000"
              />
            </div>
            <button
              type="submit"
              :disabled="isCreatingSession"
              class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
            >
              {{ isCreatingSession ? 'Creating...' : 'Start chat' }}
            </button>
          </form>
        </div>

        <div v-else class="flex h-[28rem] flex-col">
          <div class="flex-1 space-y-4 overflow-y-auto px-4 py-4">
            <div
              v-for="message in messages"
              :key="message.id"
              class="flex"
              :class="message.sender === 'visitor' ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[75%] rounded-2xl px-4 py-2 text-sm shadow-sm"
                :class="message.sender === 'visitor'
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
      sessionData: null,
      messages: [],
      connectionStatus: 'disconnected',
      isCreatingSession: false,
      preChatForm: {
        name: '',
        email: '',
        mobile: ''
      },
      newMessage: '',
      pusher: null,
      channel: null
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
  mounted() {
    this.initPusher()
  },
  methods: {
    toggleOpen() {
      this.isOpen = !this.isOpen
    },
    initPusher() {
      const appElement = document.getElementById('app')
      const pusherKey = appElement?.getAttribute('data-pusher-key')
      const pusherCluster = appElement?.getAttribute('data-pusher-cluster')

      if (pusherKey) {
        this.pusher = new Pusher(pusherKey, {
          cluster: pusherCluster || 'mt1'
        })
      }
    },
    async startChat() {
      if (!this.preChatForm.name || (!this.preChatForm.email && !this.preChatForm.mobile)) {
        alert('Please provide your name and either email or mobile number')
        return
      }

      this.isCreatingSession = true
      this.connectionStatus = 'connecting'

      try {
        const response = await fetch('/chat/session', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({
            name: this.preChatForm.name,
            email: this.preChatForm.email || null,
            mobile: this.preChatForm.mobile || null
          })
        })

        if (!response.ok) {
          throw new Error('Failed to create chat session')
        }

        const data = await response.json()
        this.sessionData = data.session
        this.sessionId = data.session.id
        this.connectionStatus = 'connected'

        // Subscribe to Pusher channel
        if (this.pusher && data.channel) {
          this.channel = this.pusher.subscribe(data.channel)
          this.channel.bind('message', (messageData) => {
            if (messageData.message && messageData.message.sender !== 'visitor') {
              this.messages.push({
                id: messageData.message.id || `msg-${Date.now()}`,
                sender: messageData.message.sender || 'agent',
                text: messageData.message.text,
                timestamp: new Date(messageData.message.timestamp || Date.now()).toLocaleTimeString()
              })
            }
          })
        }
      } catch (error) {
        console.error('Error creating chat session:', error)
        this.connectionStatus = 'disconnected'
        alert('Failed to start chat session. Please check your configuration.')
      } finally {
        this.isCreatingSession = false
      }
    },
    async sendMessage() {
      if (!this.newMessage.trim() || !this.sessionId) {
        return
      }

      const messageText = this.newMessage.trim()
      const messageId = `msg-${Date.now()}`

      this.messages.push({
        id: messageId,
        sender: 'visitor',
        text: messageText,
        timestamp: new Date().toLocaleTimeString()
      })

      this.newMessage = ''

      try {
        const response = await fetch('/chat/message', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({
            session_id: this.sessionId,
            message: messageText
          })
        })

        if (!response.ok) {
          throw new Error('Failed to send message')
        }
      } catch (error) {
        console.error('Error sending message:', error)
        alert('Failed to send message')
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
