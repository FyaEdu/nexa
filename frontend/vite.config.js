import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

// Semua request /api diteruskan ke backend CodeIgniter 3, jadi tidak perlu pengaturan CORS.
// Tanpa Docker: backend di localhost:8080 (php -S localhost:8080 server.php).
// Dengan Docker: docker-compose.yml mengisi VITE_API_TARGET=http://backend:8080.
const apiTarget = process.env.VITE_API_TARGET || 'http://localhost:8080'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) }
  },
  server: {
    port: 5173,
    watch: { usePolling: process.env.VITE_USE_POLLING === 'true' },
    proxy: {
      '/api': { target: apiTarget, changeOrigin: true }
    }
  }
})
