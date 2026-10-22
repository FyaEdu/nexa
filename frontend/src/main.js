import { createApp } from 'vue'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import './assets/nexa.css'
import App from './App.vue'
import router from './router'

const app = createApp(App).use(router)
// Tunggu navigasi pertama selesai supaya layout tidak berkedip sebelum halaman login
router.isReady().then(() => app.mount('#app'))
