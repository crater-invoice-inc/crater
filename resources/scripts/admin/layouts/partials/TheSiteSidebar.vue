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
      h-screen
      bg-white
      border-r border-gray-200 border-solid
      z-10
      md:fixed md:flex md:flex-col md:inset-y-0
      pt-16
    "
    :class="isCollapsed ? 'w-16' : 'w-56 xl:w-64'"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- collapse / expand toggle, shown when hovering the sidebar -->
    <button
      type="button"
      :title="
        isCollapsed ? $t('navigation.expand_sidebar') : $t('navigation.collapse_sidebar')
      "
      class="
        absolute
        top-36
        z-40
        flex
        items-center
        justify-center
        w-7
        h-7
        -right-3.5
        bg-white
        border border-gray-200 border-solid
        rounded-lg
        shadow-sm
        text-gray-400
        hover:text-gray-600
        transition-opacity
        duration-200
        focus:outline-none focus:opacity-100
      "
      :class="
        isHovered
          ? 'opacity-100'
          : 'opacity-0 pointer-events-none'
      "
      @click="globalStore.toggleSidebarCollapsed()"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
        class="w-3.5 h-3.5"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
      </svg>
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
        class="w-3.5 h-3.5 -ml-1"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
    </button>

    <div
      class="flex flex-col flex-1 pt-4 pb-4 min-h-0"
      :class="isCollapsed ? 'px-2' : 'px-4'"
    >
      <CompanySwitcher :collapsed="isCollapsed" />

      <div class="flex-1 min-h-0 overflow-y-auto mt-6">
        <nav class="space-y-1">
          <SidebarMenuItem
            v-for="item in mainMenuItems"
            :key="item.name"
            :item="item"
            :gap="item.gap"
            :collapsed="isCollapsed"
          />
        </nav>

        <nav class="mt-6 space-y-1">
          <SidebarMenuItem
            v-for="item in bottomMenuItems"
            :key="item.name"
            :item="item"
            :collapsed="isCollapsed"
          />
        </nav>
      </div>

      <div class="pt-4 mt-4 border-t border-gray-100">
        <SidebarUserCard :collapsed="isCollapsed" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
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

const isCollapsed = computed(() => globalStore.isSidebarCollapsed)
const isHovered = ref(false)

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
