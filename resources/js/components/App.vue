<template>
  <div :class="containerClass">
    <div class="pointer-events-auto fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
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

        <div v-if="!sessionId && embedMode !== 'filament'" class="p-4">
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
            <div class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
              <p class="font-medium">Before we start</p>
              <div class="rounded-lg border border-emerald-200 bg-white p-3">
                <p class="text-xs font-semibold uppercase text-emerald-600">Save our number</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                  <span class="text-sm font-semibold text-slate-900">
                    {{ botNumber || 'Contact number not configured' }}
                  </span>
                  <button
                    v-if="botNumber"
                    type="button"
                    class="rounded-full border border-emerald-200 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                    @click="copyBotNumber"
                  >
                    Copy
                  </button>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                  Add this number to your contacts so you can receive WhatsApp messages.
                </p>
              </div>
              <label class="flex items-start gap-2">
                <input v-model="consents.savedContact" type="checkbox" class="mt-0.5" />
                <span>
                  I saved <strong>{{ botNumber || 'our WhatsApp number' }}</strong> in my contacts.
                </span>
              </label>
              <label class="flex items-start gap-2">
                <input v-model="consents.joinGroup" type="checkbox" class="mt-0.5" />
                <span>I want to be added to the WhatsApp group for this chat.</span>
              </label>
            </div>
            <button
              type="submit"
              :disabled="isCreatingSession || !canStartChat"
              class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
            >
              {{ isCreatingSession ? 'Creating...' : 'Start chat' }}
            </button>
          </form>
        </div>

        <div v-else-if="!sessionId && embedMode === 'filament'" class="p-4">
          <h2 class="text-base font-semibold text-slate-900">Load a chat</h2>
          <p class="mt-1 text-sm text-slate-500">Search by session ID, email, or mobile number.</p>
          <form class="mt-4 space-y-3" @submit.prevent="loadExistingChat">
            <div>
              <label class="text-xs font-semibold uppercase text-slate-500">Session ID</label>
              <input
                v-model="lookupForm.session_id"
                type="text"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="123"
              />
            </div>
            <div>
              <label class="text-xs font-semibold uppercase text-slate-500">Email</label>
              <input
                v-model="lookupForm.email"
                type="email"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="jane@example.com"
              />
            </div>
            <div>
              <label class="text-xs font-semibold uppercase text-slate-500">Mobile</label>
              <input
                v-model="lookupForm.mobile"
                type="tel"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                placeholder="+1 555 000 0000"
              />
            </div>
            <button
              type="submit"
              class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
            >
              Load chat
            </button>
          </form>
        </div>

        <div v-else class="flex h-[28rem] flex-col">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
            <div>
              <p class="text-xs font-semibold uppercase text-slate-400">Chat ID</p>
              <p class="text-sm text-slate-700">{{ sessionLabel }}</p>
            </div>
            <button
              class="text-xs font-semibold text-rose-600 hover:text-rose-700"
              type="button"
              @click="endChat"
            >
              End chat
            </button>
          </div>
          <div ref="messagesContainer" class="flex-1 space-y-4 overflow-y-auto px-4 py-4">
            <div
              v-for="message in messages"
              :key="message.id"
              class="flex"
              :class="message.sender === 'visitor' ? 'justify-end' : (message.sender === 'system' ? 'justify-center' : 'justify-start')"
            >
              <div
                class="max-w-[75%] rounded-2xl px-4 py-2 text-sm shadow-sm"
                :class="message.sender === 'visitor'
                  ? 'bg-emerald-600 text-white'
                  : message.sender === 'system'
                    ? 'bg-slate-200 text-slate-600'
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
      lookupForm: {
        session_id: '',
        email: '',
        mobile: ''
      },
      consents: {
        savedContact: false,
        joinGroup: false
      },
      newMessage: '',
      pusher: null,
      channel: null,
      botNumber: '',
      embedMode: 'web'
    }
  },
  computed: {
    canStartChat() {
      return !!this.preChatForm.name && (!!this.preChatForm.email || !!this.preChatForm.mobile)
    },
    containerClass() {
      return this.embedMode === 'filament' ? 'pointer-events-none' : 'min-h-screen bg-slate-50'
    },
    sessionLabel() {
      return (this.sessionData && this.sessionData.session_id) ? this.sessionData.session_id : this.sessionId
    },
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
    this.restoreSession()
  },
  methods: {
    toggleOpen() {
      this.isOpen = !this.isOpen
    },
    initPusher() {
      const appElement = this.$el?.closest('[data-pusher-key]') || document.getElementById('app') || document.getElementById('filament-chat')
      const pusherKey = appElement?.getAttribute('data-pusher-key')
      const pusherCluster = appElement?.getAttribute('data-pusher-cluster')
      this.botNumber = appElement?.getAttribute('data-whatsapp-bot-number') || ''
      this.embedMode = appElement?.getAttribute('data-embed') || 'web'

      if (pusherKey) {
        this.pusher = new Pusher(pusherKey, {
          cluster: pusherCluster || 'mt1'
        })
      }
    },
    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$refs.messagesContainer
        if (container) {
          container.scrollTop = container.scrollHeight
        }
      })
    },
    async copyBotNumber() {
      if (!this.botNumber) {
        return
      }
      try {
        await navigator.clipboard.writeText(this.botNumber)
      } catch (error) {
        console.error('Failed to copy bot number', error)
      }
    },
    normalizeMessage(rawMessage) {
      const timestampValue = new Date(rawMessage?.timestamp || rawMessage?.sent_at || Date.now()).getTime()
      return {
        id: rawMessage?.id || `msg-${Date.now()}-${Math.random()}`,
        sender: rawMessage?.sender || 'agent',
        senderName: rawMessage?.sender_name || null,
        text: rawMessage?.text || rawMessage?.message || '',
        timestamp: new Date(timestampValue).toLocaleTimeString(),
        timestampValue
      }
    },
    subscribeToChannel(channelName) {
      if (!this.pusher || !channelName) {
        return
      }
      if (this.channel) {
        this.channel.unbind_all()
        this.pusher.unsubscribe(this.channel.name)
      }

      this.channel = this.pusher.subscribe(channelName)
      this.channel.bind('message', (messageData) => {
        if (!messageData?.message) {
          return
        }
        const normalized = this.normalizeMessage(messageData.message)
        const exists = this.messages.some((message) => message.id === normalized.id)
        if (!exists && normalized.sender === 'visitor') {
          const recentMatch = this.messages.find((message) => {
            if (message.sender !== 'visitor') {
              return false
            }
            if (message.text !== normalized.text) {
              return false
            }
            return Math.abs((normalized.timestampValue || 0) - (message.timestampValue || 0)) < 5000
          })
          if (recentMatch) {
            return
          }
        }
        if (!exists) {
          this.messages.push(normalized)
          this.scrollToBottom()
        }
      })
    },
    async restoreSession() {
      if (this.embedMode === 'filament') {
        return
      }
      const stored = localStorage.getItem('rr_chat_session')
      if (!stored) {
        return
      }

      let payload = {}
      try {
        payload = JSON.parse(stored)
      } catch {
        localStorage.removeItem('rr_chat_session')
        return
      }

      if (!payload.session_id && !payload.mobile && !payload.email) {
        return
      }

      try {
        const response = await fetch('/chat/session/lookup', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          localStorage.removeItem('rr_chat_session')
          return
        }

        const data = await response.json()
        this.sessionData = data.session
        this.sessionId = data.session.id
        this.connectionStatus = 'connected'
        this.messages = (data.messages || []).map(this.normalizeMessage)
        this.subscribeToChannel(data.channel)
        this.scrollToBottom()
      } catch (error) {
        console.error('Error restoring chat session:', error)
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
            mobile: this.preChatForm.mobile || null,
            add_to_group: this.consents.joinGroup
          })
        })

        if (!response.ok) {
          throw new Error('Failed to create chat session')
        }

        const data = await response.json()
        this.sessionData = data.session
        this.sessionId = data.session.id
        this.connectionStatus = 'connected'
        this.messages = (data.messages || []).map(this.normalizeMessage)

        // Subscribe to Pusher channel
        this.subscribeToChannel(data.channel)
        this.scrollToBottom()

        localStorage.setItem('rr_chat_session', JSON.stringify({
          session_id: data.session.id,
          email: this.preChatForm.email || null,
          mobile: this.preChatForm.mobile || null
        }))
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
        timestamp: new Date().toLocaleTimeString(),
        timestampValue: Date.now()
      })
      this.scrollToBottom()

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
    },
    async endChat() {
      if (!this.sessionId) {
        return
      }
      const confirmed = window.confirm('End this chat?')
      if (!confirmed) {
        return
      }

      try {
        await fetch('/chat/session/end', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({ session_id: this.sessionId })
        })
      } catch (error) {
        console.error('Error ending chat:', error)
      }

      localStorage.removeItem('rr_chat_session')
      this.sessionId = null
      this.sessionData = null
      this.messages = []
      this.connectionStatus = 'disconnected'
      this.newMessage = ''
    },
    async loadExistingChat() {
      const payload = {
        session_id: this.lookupForm.session_id || null,
        email: this.lookupForm.email || null,
        mobile: this.lookupForm.mobile || null
      }

      if (!payload.session_id && !payload.email && !payload.mobile) {
        alert('Provide a session ID, email, or mobile number.')
        return
      }

      try {
        const response = await fetch('/chat/session/lookup', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          alert('Chat not found.')
          return
        }

        const data = await response.json()
        this.sessionData = data.session
        this.sessionId = data.session.id
        this.connectionStatus = 'connected'
        this.messages = (data.messages || []).map(this.normalizeMessage)
        this.subscribeToChannel(data.channel)
        this.scrollToBottom()
      } catch (error) {
        console.error('Error loading chat:', error)
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
