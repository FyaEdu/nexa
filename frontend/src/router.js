import { createRouter, createWebHistory } from 'vue-router'
import { auth, hasRole, clearAuth } from '@/stores/auth'
import { setUnauthorizedHandler } from '@/api'
import { MENU, ALL_ROLES, DONE_STAGE } from '@/utils/constants'
import LoginView from '@/views/LoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import PlaceholderView from '@/views/PlaceholderView.vue'

const roles = (key) => MENU.find((m) => m.key === key).roles

// Halaman Tahap 2-4 (sudah selesai)
const builtRoutes = [
  { path: '/kendaraan', name: 'kendaraan', component: () => import('@/views/MotorListView.vue'), meta: { title: 'Kendaraan', roles: roles('kendaraan') } },
  { path: '/kendaraan/:id(\\d+)/:tab?', name: 'kendaraan-detail', component: () => import('@/views/MotorDetailView.vue'), meta: { title: 'Detail kendaraan', roles: roles('kendaraan'), menu: 'kendaraan' } },
  { path: '/inspeksi', name: 'inspeksi', component: () => import('@/views/InspectionListView.vue'), meta: { title: 'Inspeksi', roles: roles('inspeksi') } },
  { path: '/inspeksi/baru/:motorId(\\d+)?', name: 'inspeksi-baru', component: () => import('@/views/InspectionFormView.vue'), meta: { title: 'Inspeksi baru', roles: roles('inspeksi'), menu: 'inspeksi' } },
  { path: '/biaya', name: 'biaya', component: () => import('@/views/CostView.vue'), meta: { title: 'Biaya', roles: roles('biaya') } },
  { path: '/inventaris', name: 'inventaris', component: () => import('@/views/InventoryView.vue'), meta: { title: 'Inventaris & sparepart', roles: roles('inventaris') } }
]

// Menu yang tahapnya belum dikerjakan memakai PlaceholderView.
// Saat Tahap 5 dikerjakan, tambahkan route view-nya ke builtRoutes dan naikkan DONE_STAGE di constants.js.
const placeholderRoutes = MENU.filter((m) => m.stage > DONE_STAGE).map((m) => ({
  path: m.path,
  name: m.key,
  component: PlaceholderView,
  props: { menuKey: m.key },
  meta: { title: m.label, roles: m.roles }
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView, meta: { public: true, title: 'Masuk' } },
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', name: 'dashboard', component: DashboardView, meta: { title: 'Dashboard', roles: ALL_ROLES } },
    ...builtRoutes,
    ...placeholderRoutes,
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' }
  ]
})

router.beforeEach((to) => {
  if (to.meta.public) {
    if (to.name === 'login' && auth.token) return { name: 'dashboard' }
    return true
  }
  if (!auth.token) return { name: 'login', query: { redirect: to.fullPath } }
  if (!hasRole(to.meta.roles)) return { name: 'dashboard' }
  return true
})

router.afterEach((to) => {
  document.title = `${to.meta.title ?? 'NEXA'} – NEXA`
})

setUnauthorizedHandler(() => {
  clearAuth()
  router.push({ name: 'login', query: { expired: '1' } })
})

export default router
