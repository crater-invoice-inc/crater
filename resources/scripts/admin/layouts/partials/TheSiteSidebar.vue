<template>
  <!-- MOBILE MENU -->
  <TransitionRoot as="template" :show="globalStore.isSidebarOpen">
    <Dialog
      as="div"
      class="fixed inset-0 z-40 flex md:hidden"
      @close="globalStore.setSidebarVisibility(false)"
    >
      <TransitionChild
        as="template"
        enter="transition-opacity ease-linear duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="transition-opacity ease-linear duration-300"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <DialogOverlay class="fixed inset-0 bg-gray-600 bg-opacity-75" />
      </TransitionChild>

      <TransitionChild
        as="template"
        enter="transition ease-in-out duration-300"
        enter-from="-translate-x-full"
        enter-to="translate-x-0"
        leave="transition ease-in-out duration-300"
        leave-from="translate-x-0"
        leave-to="-translate-x-full"
      >
        <div class="relative flex flex-col flex-1 w-full max-w-xs bg-white">
          <TransitionChild
            as="template"
            enter="ease-in-out duration-300"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="ease-in-out duration-300"
            leave-from="opacity-100"
            leave-to="opacity-0"
          >
            <div class="absolute top-0 right-0 pt-2 -mr-12">
              <button
                class="
                  flex
                  items-center
                  justify-center
                  w-10
                  h-10
                  ml-1
                  rounded-full
                  focus:outline-none
                  focus:ring-2
                  focus:ring-inset
                  focus:ring-white
                "
                @click="globalStore.setSidebarVisibility(false)"
              >
                <span class="sr-only">Close sidebar</span>
                <BaseIcon
                  name="XIcon"
                  class="w-6 h-6 text-white"
                  aria-hidden="true"
                />
              </button>
            </div>
          </TransitionChild>

          <div class="flex flex-col flex-1 h-0 pt-5 pb-4 overflow-y-auto">
            <div class="flex items-center shrink-0 px-4 mb-6">
              <MainLogo
                class="block h-auto max-w-full w-36 text-primary-400"
                alt="Crater Logo"
              />
            </div>

            <div class="px-4 mb-6">
              <CompanySwitcher />
            </div>

            <nav class="flex-1 px-2 space-y-1">
              <SidebarMenuItem
                v-for="item in mainMenuItems"
                :key="item.name"
                :item="item"
                :gap="item.gap"
                @click="globalStore.setSidebarVisibility(false)"
              />
            </nav>

            <nav class="px-2 mt-4 space-y-1">
              <SidebarMenuItem
                v-for="item in bottomMenuItems"
                :key="item.name"
                :item="item"
                @click="globalStore.setSidebarVisibility(false)"
              />
            </nav>

            <div class="px-4 mt-4">
              <SidebarUserCard />
            </div>
          </div>
        </div>
      </TransitionChild>
      <div class="shrink-0 w-14">
        <!-- Force sidebar to shrink to fit close icon -->
      </div>
    </Dialog>
  </TransitionRoot>

  <!-- DESKTOP MENU -->
  <div
    class="
      hidden
      w-56
      h-screen
      overflow-y-auto
      bg-white
      border-r border-gray-200 border-solid
      xl:w-64
      md:fixed md:flex md:flex-col md:inset-y-0
      pt-16
    "
  >
    <div class="flex flex-col flex-1 px-4 pt-4 pb-4 overflow-y-auto">
      <CompanySwitcher />

      <nav class="flex-1 mt-6 space-y-1">
        <SidebarMenuItem
          v-for="item in mainMenuItems"
          :key="item.name"
          :item="item"
          :gap="item.gap"
        />
      </nav>

      <nav class="mt-6 space-y-1">
        <SidebarMenuItem
          v-for="item in bottomMenuItems"
          :key="item.name"
          :item="item"
        />
      </nav>

      <div class="pt-4 mt-4 border-t border-gray-100">
        <SidebarUserCard />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'
import CompanySwitcher from '@/scripts/components/CompanySwitcher.vue'
import SidebarMenuItem from '@/scripts/admin/layouts/partials/SidebarMenuItem.vue'
import SidebarUserCard from '@/scripts/admin/layouts/partials/SidebarUserCard.vue'

import {
  Dialog,
  DialogOverlay,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

import { useRoute } from 'vue-router'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useNotificationStore } from '@/scripts/stores/notification'

const route = useRoute()
const globalStore = useGlobalStore()
const dashboardStore = useDashboardStore()
const notificationStore = useNotificationStore()

const BOTTOM_MENU_NAMES = ['Users', 'Reports', 'Settings']

const mainMenuItems = computed(() => {
  let previousGroup = null

  return globalStore.mainMenu
    .filter((item) => !BOTTOM_MENU_NAMES.includes(item.name))
    .map((item) => {
      const gap = previousGroup !== null && item.group > previousGroup
      previousGroup = item.group

      return { ...item, gap }
    })
})

const bottomMenuItems = computed(() => {
  return globalStore.mainMenu.filter((item) =>
    BOTTOM_MENU_NAMES.includes(item.name)
  )
})

function hasActiveUrl(url) {
  return route.path.indexOf(url) > -1
}

// Sidebar badge counts: loaded once (the dashboard page loads the full
// data itself) and refreshed live when items are created or removed —
// any such action raises a success notification.
let isRefreshingCounts = false

const refreshCounts = useDebounceFn(async () => {
  if (isRefreshingCounts || !globalStore.isAppLoaded) return
  isRefreshingCounts = true
  try {
    await dashboardStore.loadCounts()
  } finally {
    isRefreshingCounts = false
  }
}, 500)

watch(
  () => notificationStore.notifications.length,
  () => refreshCounts()
)

watch(
  () => route.path,
  () => refreshCounts()
)

onMounted(() => {
  if (!dashboardStore.isDashboardDataLoaded) {
    refreshCounts()
  }
})
</script>
