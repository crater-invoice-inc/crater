<template>
  <span :class="badgeColorClasses">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: false,
    default: '',
  },
})

const badgeColorClasses = computed(() => {
  switch (props.status) {
    case 'DRAFT':
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-yellow bg-status-yellow-bg uppercase'
    case 'SENT':
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-yellow bg-status-yellow-bg uppercase'
    case 'VIEWED':
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-blue bg-status-blue-bg uppercase'
    case 'EXPIRED':
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-red bg-status-red-bg uppercase'
    case 'ACCEPTED':
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-green bg-status-green-bg uppercase'
    case 'REJECTED':
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-purple bg-status-purple-bg uppercase'
    default:
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-gray bg-status-gray-bg uppercase'
  }
})
</script>
