<template>
  <div id="app-container" class="min-h-screen bg-gray-50">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900">
          Round Robin WhatsApp Chat
        </h1>
      </div>
    </header>
    <main>
      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <div class="border-4 border-dashed border-gray-200 rounded-lg p-8">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Start a chat session</h2>
            <p class="text-gray-600 mb-6">
              Tell us how to reach you and we will connect you to the next available agent.
            </p>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <section class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Pre-chat form</h3>
                <form class="space-y-4" @submit.prevent="startSession">
                  <div>
                    <label class="block text-sm font-medium text-gray-700" for="name">Name</label>
                    <input
                      id="name"
                      v-model="form.name"
                      type="text"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Your name"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700" for="email">Email (optional)</label>
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="you@example.com"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700" for="mobile">Mobile (optional)</label>
                    <input
                      id="mobile"
                      v-model="form.mobile"
                      type="tel"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="+1 555 123 4567"
                    />
                  </div>
                  <p class="text-sm text-gray-500">
                    Please provide at least one contact method (email or mobile).
                  </p>
                  <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-700">
                    {{ error }}
                  </div>
                  <div v-if="sessionId" class="rounded-md bg-green-50 p-3 text-sm text-green-700">
                    Session started! You can now send messages.
                  </div>
                  <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isSubmitting || sessionId"
                  >
                    {{ isSubmitting ? 'Starting…' : sessionId ? 'Session active' : 'Start session' }}
                  </button>
                </form>
              </section>

              <section class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Chat</h3>
                <div class="flex flex-col gap-4">
                  <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm text-gray-600">
                    {{ sessionId ? 'You are connected. Send your first message below.' : 'Start a session to unlock messaging.' }}
                  </div>
                  <div class="flex items-center gap-2">
                    <input
                      v-model="message"
                      type="text"
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Type your message..."
                      :disabled="!sessionId"
                    />
                    <button
                      type="button"
                      class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="!sessionId"
                    >
                      Send
                    </button>
                  </div>
                </div>
              </section>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      form: {
        name: '',
        email: '',
        mobile: ''
      },
      message: '',
      sessionId: null,
      error: null,
      isSubmitting: false
    }
  },
  methods: {
    async startSession() {
      this.error = null

      if (!this.form.email && !this.form.mobile) {
        this.error = 'Please provide either an email address or mobile number.'
        return
      }

      this.isSubmitting = true

      try {
        const response = await window.axios.post('/chat/session', {
          name: this.form.name,
          email: this.form.email || null,
          mobile: this.form.mobile || null
        })

        this.sessionId = response.data.id
      } catch (error) {
        if (error.response?.data?.message) {
          this.error = error.response.data.message
        } else {
          this.error = 'Unable to start a session. Please try again.'
        }
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
