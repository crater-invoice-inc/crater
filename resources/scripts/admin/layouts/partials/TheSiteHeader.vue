<template>
  <!-- Barre mobile : logo + menu burger (pas de barre sur desktop) -->
  <header class="fixed top-0 left-0 right-0 z-20 w-full px-4 py-3 bg-chrome md:hidden">
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <!-- toggle button -->
        <div
          :class="{ 'is-active': globalStore.isSidebarOpen }"
          class="
            flex
            p-1
            overflow-visible
            text-sm
            ease-linear
            bg-white
            bg-opacity-10
            border-0
            rounded-lg
            cursor-pointer
          "
          @click.prevent="onToggle"
        >
          <BaseIcon name="MenuIcon" class="!w-6 !h-6 text-chrome-fg" />
        </div>

        <router-link to="/admin/dashboard" class="block">
          <img v-if="adminLogo" :src="adminLogo" class="h-6 ml-3" alt="Logo" />
          <MainLogo
            v-else
            class="h-6 ml-3"
            light-color="white"
            dark-color="white"
          />
        </router-link>
      </div>
    </div>
  </header>
</template>

<script setup>
import { useGlobalStore } from '@/scripts/admin/stores/global'

import MainLogo from '@/scripts/components/icons/MainLogo.vue'
import { computed } from 'vue'

const globalStore = useGlobalStore()

const adminLogo = computed(() => {
  if (globalStore.globalSettings.admin_portal_logo) {
    return '/storage/' + globalStore.globalSettings.admin_portal_logo
  }

  return false
})

function onToggle() {
  globalStore.setSidebarVisibility(true)
}
</script>
