import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useGlobalStore } from '@/scripts/admin/stores/global'

//admin routes
import AdminRoutes from '@/scripts/admin/admin-router'
//  Customers routes
import CustomerRoutes from '@/scripts/customer/customer-router'
//Payment Routes

let routes = []
routes = routes.concat(AdminRoutes, CustomerRoutes)

const router = createRouter({
  history: createWebHistory(),
  linkActiveClass: 'active',
  routes,
})

router.beforeEach((to, from, next) => {
  const userStore = useUserStore()
  const globalStore = useGlobalStore()
  let ability = to.meta.ability
  const { isAppLoaded } = globalStore

  if (ability && isAppLoaded && to.meta.requiresAuth) {
    if (userStore.hasAbilities(ability)) {
      next()
    } else next({ name: 'account.settings' })
  } else if (to.meta.isOwner && isAppLoaded) {
    if (userStore.currentUser.is_owner) {
      next()
    } else next({ name: 'dashboard' })
  } else if (to.meta.isOwnerOrSuperAdmin && isAppLoaded) {
    // Matches the AI assistant backend rule: company owner or Crater
    // super admin role.
    if (
      userStore.currentUser.is_owner ||
      userStore.currentUser.role === 'super admin'
    ) {
      next()
    } else next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
