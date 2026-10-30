<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { login } from '@/stores/auth'
import { STATUSES } from '@/utils/constants'

const route = useRoute()
const router = useRouter()
const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

// Akun dari data dummy (backend/application/database/dummy.json)
const demo = [
  { role: 'Administrator', u: 'admin', p: 'admin123' },
  { role: 'Pemilik Usaha', u: 'pemilik', p: 'pemilik123' },
  { role: 'Staff Operasional', u: 'operasional', p: 'ops123' },
  { role: 'Staff Penjualan', u: 'penjualan', p: 'sales123' }
]

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await login(username.value, password.value)
    router.push(route.query.redirect || '/dashboard')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function fill(a) {
  username.value = a.u
  password.value = a.p
}
</script>

<template>
  <div class="nx-login">
    <section class="nx-login-brand">
      <div class="nx-wordmark">NEXA</div>
      <p class="nx-tag">Next-generation Asset Management</p>
      <p class="nx-lede">Catat setiap motor dari dibeli, diperiksa, diperbaiki, sampai terjual, lengkap dengan semua biayanya.</p>
      <ol class="nx-mini-road d-none d-md-grid">
        <li v-for="s in STATUSES" :key="s.key">{{ s.label }}</li>
      </ol>
    </section>

    <section class="nx-login-form">
      <form class="card card-body p-4 d-flex flex-column gap-3" @submit.prevent="submit">
        <h1 class="h3 mb-0">Masuk ke NEXA</h1>
        <div v-if="route.query.expired" class="alert alert-warning py-2 mb-0">Sesi berakhir. Silakan masuk lagi.</div>
        <div>
          <label class="form-label" for="lg-user">Username atau email</label>
          <input id="lg-user" v-model="username" class="form-control" required autocomplete="username" />
        </div>
        <div>
          <label class="form-label" for="lg-pass">Password</label>
          <input id="lg-pass" v-model="password" type="password" class="form-control" required autocomplete="current-password" />
        </div>
        <div v-if="error" class="alert alert-danger py-2 mb-0">{{ error }}</div>
        <button class="btn btn-primary w-100" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>Masuk
        </button>
      </form>

      <div>
        <p class="small text-body-secondary mb-2">Akun data dummy, pilih untuk mengisi otomatis:</p>
        <div class="row g-2">
          <div v-for="a in demo" :key="a.u" class="col-6">
            <button type="button" class="nx-demo" @click="fill(a)">
              <b>{{ a.role }}</b><span>{{ a.u }} / {{ a.p }}</span>
            </button>
          </div>
        </div>
      </div>
      <p class="small text-body-secondary mb-0">Prototype NEXA, Kelompok B, Teknik Informatika, Fakultas Teknik Universitas Muhammadiyah Jakarta, 2026.</p>
    </section>
  </div>
</template>
