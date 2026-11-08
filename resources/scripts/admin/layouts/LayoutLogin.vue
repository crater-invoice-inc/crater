<template>
  <div class="relative grid h-screen grid-cols-12 overflow-y-hidden bg-surface-tertiary">
    <NotificationRoot />

    <div
      class="
        relative
        flex
        flex-col
        items-center
        justify-center
        w-full
        col-span-12
        px-4
        py-10
        mx-auto
        md:py-0
      "
    >
      <div class="w-full max-w-sm">
        <div class="flex justify-center mb-8">
          <img
            v-if="loginPageLogo"
            :src="loginPageLogo"
            class="block w-44 h-auto max-w-full"
            alt="Logo"
          />
          <MainLogo
            v-else
            class="block w-44 h-auto max-w-full"
          />
        </div>

        <div
          class="
            w-full
            bg-surface
            rounded-2xl
            border border-line-light
            shadow-card
            px-6
            py-8
            sm:px-8
          "
        >
          <router-view />
        </div>

        <div
          class="
            mt-6
            text-sm
            not-italic
            font-medium
            leading-relaxed
            text-center
            text-muted
          "
        >
          <p>
            {{ copyrightText }}
            {{ new Date().getFullYear() }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import NotificationRoot from '@/scripts/components/notifications/NotificationRoot.vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'
import { computed } from 'vue'

const copyrightText = computed(() => {
  if (window.copyright_text) {
    return window.copyright_text
  }
  return 'Copyright @ Crater Invoice, Inc.'
})

const loginPageLogo = computed(() => {
  if (window.login_page_logo) {
    return window.login_page_logo
  }

  return false
})
</script>

<style lang="scss" scoped>
</style>
