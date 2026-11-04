<template>
  <header
    class="
      fixed
      top-0
      left-0
      z-20
      flex
      items-center
      justify-between
      w-full
      px-4
      py-3
      md:h-16 md:px-8
      bg-gradient-to-r
      from-primary-500
      to-primary-400
    "
  >
    <router-link
      to="/admin/dashboard"
      class="
        float-none
        text-lg
        not-italic
        font-black
        tracking-wider
        text-white
        brand-main
        md:float-left
        font-base
        hidden
        md:block
      "
    >
      <img v-if="adminLogo" :src="adminLogo" class="h-6" />
      <MainLogo v-else class="h-6" light-color="white" dark-color="white" />
    </router-link>

    <!-- toggle button-->
    <div
      :class="{ 'is-active': globalStore.isSidebarOpen }"
      class="
        flex
        float-left
        p-1
        overflow-visible
        text-sm
        ease-linear
        bg-white
        border-0
        rounded
        cursor-pointer
        md:hidden md:ml-0
        hover:bg-gray-100
      "
      @click.prevent="onToggle"
    >
      <BaseIcon name="MenuIcon" class="!w-6 !h-6 text-gray-500" />
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
