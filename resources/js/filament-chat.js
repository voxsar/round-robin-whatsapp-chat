import { createApp } from 'vue'
import App from './components/App.vue'

const mountElement = document.getElementById('filament-chat')

if (mountElement) {
  createApp(App).mount(mountElement)
}
