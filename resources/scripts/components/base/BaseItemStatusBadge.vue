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
    case 'ACTIVE':
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-green bg-status-green-bg uppercase'
    case 'INACTIVE':
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-gray bg-status-gray-bg uppercase'
    default:
      return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-gray bg-status-gray-bg uppercase'
  }
})
</script>
