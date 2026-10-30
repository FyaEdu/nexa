<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { auth, logout } from '@/stores/auth'
import { MENU, ROLES, DONE_STAGE } from '@/utils/constants'

const route = useRoute()
const router = useRouter()
const navOpen = ref(false)

const menu = computed(() => MENU.filter((m) => m.roles.includes(auth.user?.role)))
const initials = computed(() => (auth.user?.name || '').split(' ').map((x) => x[0]).slice(0, 2).join(''))

watch(() => route.fullPath, () => { navOpen.value = false })

async function doLogout() {
  await logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="nx-shell" :class="{ 'nav-open': navOpen }">
    <aside class="nx-side" aria-label="Navigasi utama">
      <div class="nx-brand"><b>NEXA</b><span>Manajemen aset<br />jual beli motor</span></div>
      <nav class="nav flex-column gap-1">
        <router-link v-for="m in menu" :key="m.key" :to="m.path" class="nx-nav-link" :class="{ active: route.meta.menu === m.key }" active-class="active">
          <i class="bi" :class="m.icon" aria-hidden="true"></i>
          <span class="flex-grow-1">{{ m.label }}</span>
          <small v-if="m.stage > DONE_STAGE" class="nx-stage" :title="`Dikerjakan di Tahap ${m.stage}`">T{{ m.stage }}</small>
        </router-link>
        <button type="button" class="nx-nav-link btn-reset" @click="doLogout">
          <i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>Keluar</span>
        </button>
      </nav>
      <div class="nx-side-foot">NEXA, Kelompok B<br />Teknik Informatika UMJ 2026</div>
    </aside>
    <div class="nx-scrim" @click="navOpen = false"></div>

    <div class="nx-main">
      <header class="nx-topbar">
        <button type="button" class="btn btn-light d-lg-none" aria-label="Buka menu" @click="navOpen = !navOpen">
          <i class="bi bi-list"></i>
        </button>
        <h1 class="nx-title">{{ route.meta.title }}</h1>
        <div class="d-flex align-items-center gap-2">
          <div class="nx-avatar" aria-hidden="true">{{ initials }}</div>
          <div class="d-none d-md-block lh-sm">
            <div class="fw-semibold small">{{ auth.user?.name }}</div>
            <div class="text-body-secondary" style="font-size: 12px">{{ ROLES[auth.user?.role] }}</div>
          </div>
        </div>
      </header>
      <main class="nx-content">
        <slot />
      </main>
    </div>
  </div>
</template>
