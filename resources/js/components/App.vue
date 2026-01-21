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
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Welcome to the Application</h2>
            <p class="text-gray-600 mb-4">
              This is a Laravel application with Vue.js frontend and Filament admin panel.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
              <div class="bg-blue-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">🎨 Vue.js Frontend</h3>
                <p class="text-blue-700">Interactive and reactive user interface built with Vue 3</p>
              </div>
              <div class="bg-green-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-green-900 mb-2">⚡ Laravel Backend</h3>
                <p class="text-green-700">Powerful PHP framework for robust API development</p>
              </div>
              <div class="bg-purple-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-purple-900 mb-2">🛠️ Filament Admin</h3>
                <p class="text-purple-700">Beautiful admin panel for managing your application</p>
              </div>
              <div class="bg-yellow-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-yellow-900 mb-2">💬 WhatsApp Integration</h3>
                <p class="text-yellow-700">Round-robin chat distribution system</p>
              </div>
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
      message: 'Vue is working!'
    }
  },
  mounted() {
    console.log('Vue app mounted successfully!');
  }
}
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
