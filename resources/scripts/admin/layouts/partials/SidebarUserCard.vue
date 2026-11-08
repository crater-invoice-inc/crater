<template>
  <div ref="userCard" class="relative">
    <div
      :title="collapsed ? userStore.currentUser.name : null"
      :class="[
        'flex items-center w-full px-2.5 py-2 text-sm bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100',
        collapsed ? 'justify-center' : '',
      ]"
      @click="isShow = !isShow"
    >
      <img
        :src="previewAvatar"
        class="w-9 h-9 rounded-md object-cover shrink-0"
      />
      <div v-if="!collapsed" class="flex flex-col flex-1 min-w-0 ml-3">
        <span class="font-medium text-gray-900 truncate">
          {{ userStore.currentUser.name }}
        </span>
        <span class="text-xs text-gray-400 truncate">
          {{ userStore.currentUser.email }}
        </span>
      </div>
      <BaseIcon
        v-if="!collapsed"
        name="ChevronDownIcon"
        class="h-5 ml-2 shrink-0 text-gray-400"
      />
    </div>

    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    >
      <div
        v-if="isShow"
        class="
          absolute
          left-0
          bottom-full
          z-30
          mb-2
          w-[220px]
          bg-white
          rounded-lg
          shadow-lg
          py-1.5
        "
      >
        <router-link
          to="/admin/settings/account-settings"
          class="flex items-center px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50"
          @click="isShow = false"
        >
          <BaseIcon name="UserIcon" class="w-5 h-5 mr-3 text-gray-400" />
          {{ $t('navigation.profile') }}
        </router-link>

        <button
          class="
            flex
            items-center
            w-full
            px-3
            py-2.5
            text-sm text-gray-700
            hover:bg-gray-50
          "
          @click="logout"
        >
          <BaseIcon name="LogoutIcon" class="w-5 h-5 mr-3 text-gray-400" />
          {{ $t('navigation.logout') }}
        </button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { onClickOutside } from '@vueuse/core'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useAuthStore } from '@/scripts/admin/stores/auth'

defineProps({
  collapsed: { type: Boolean, default: false },
})

const userStore = useUserStore()
const authStore = useAuthStore()
const router = useRouter()

const isShow = ref(false)
const userCard = ref(null)

const previewAvatar = computed(() => {
  return userStore.currentUser && userStore.currentUser.avatar !== 0
    ? userStore.currentUser.avatar
    : getDefaultAvatar()
})

onClickOutside(userCard, () => {
  isShow.value = false
})

function getDefaultAvatar() {
  return import.meta.env.DEV
    ? '/img/default-avatar.jpg'
    : '/build/img/default-avatar.jpg'
}

async function logout() {
  isShow.value = false
  await authStore.logout()
  router.push('/login')
}
</script>
