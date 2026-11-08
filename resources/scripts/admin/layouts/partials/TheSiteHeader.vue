<template>
  <header
    class="
      site-header
      fixed
      top-0
      left-0
      right-0
      z-20
      flex
      items-center
      justify-between
      w-full
      px-4
      md:h-16 md:px-8
      pointer-events-none
    "
  >
    <!-- fond chrome sur mobile, barre glass au-dessus du contenu sur desktop -->
    <div class="absolute inset-0 bg-chrome md:hidden" aria-hidden="true"></div>
    <div
      class="
        glass-layer
        hidden
        md:block
        absolute
        inset-y-0
        right-0
        bg-surface-tertiary
        border-b
        border-line-light
        border-solid
      "
      :class="
        globalStore.isSidebarCollapsed ? 'left-16' : 'left-56 xl:left-64'
      "
      aria-hidden="true"
    ></div>

    <div
      class="relative flex items-center min-w-0 flex-1 pointer-events-auto"
      :class="globalStore.isSidebarCollapsed ? 'md:pl-16' : 'md:pl-56 xl:pl-64'"
    >
      <!-- toggle button (mobile) -->
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
          md:hidden md:ml-0
        "
        @click.prevent="onToggle"
      >
        <BaseIcon name="MenuIcon" class="!w-6 !h-6 text-chrome-fg" />
      </div>

      <router-link to="/admin/dashboard" class="block md:hidden">
        <img v-if="adminLogo" :src="adminLogo" class="h-6 ml-3" alt="Logo" />
        <MainLogo
          v-else
          class="h-6 ml-3"
          light-color="white"
          dark-color="white"
        />
      </router-link>
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

<style scoped>
.site-header {
  transition: padding 0.3s ease;
}

.glass-layer {
  transition: left 0.3s ease;
}
</style>
