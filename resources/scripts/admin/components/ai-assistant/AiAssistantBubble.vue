<template>
  <button
    v-if="isVisible"
    type="button"
    class="
      fixed z-30 flex items-center justify-center w-12 h-12 text-white
      transition-colors rounded-full shadow-lg bg-primary-500 hover:bg-primary-600
      focus:outline-none focus:ring-2 focus:ring-primary-300 bottom-6 right-6
    "
    :title="$t('ai_assistant.title')"
    :aria-label="$t('ai_assistant.title')"
    @click="aiStore.openPanel()"
  >
    <BaseIcon name="SparklesIcon" />
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAiAssistantStore } from '@/scripts/admin/stores/ai-assistant'
import { useModalStore } from '@/scripts/stores/modal'
import { useUserStore } from '@/scripts/admin/stores/user'

const route = useRoute()
const { t } = useI18n()

const aiStore = useAiAssistantStore()
const modalStore = useModalStore()
const userStore = useUserStore()

const EDIT_ROUTES = [
  'estimates.create',
  'estimates.edit',
  'invoices.create',
  'invoices.edit',
]

// v1: owner (company owner_id) and Crater super admin only, matching the
// backend authorization rule.
const isVisible = computed(() => {
  return (
    EDIT_ROUTES.includes(route.name) &&
    !modalStore.active &&
    !!userStore.currentUser &&
    (userStore.currentUser.is_owner ||
      userStore.currentUser.role === 'super admin')
  )
})
</script>
