<template>
  <span :class="badgeColorClasses">
    <slot />
  </span>
</template>

<script>
import { computed } from 'vue'

export default {
  props: {
    status: {
      type: String,
      required: false,
      default: '',
    },
  },

  setup(props) {
    const badgeColorClasses = computed(() => {
      switch (props.status) {
        case 'DRAFT':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-yellow bg-status-yellow-bg uppercase'
        case 'SENT':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-yellow bg-status-yellow-bg uppercase'
        case 'VIEWED':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-blue bg-status-blue-bg uppercase'
        case 'COMPLETED':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-green bg-status-green-bg uppercase'
        case 'DUE':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-yellow bg-status-yellow-bg uppercase'
        case 'OVERDUE':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-red bg-status-red-bg uppercase'
        case 'UNPAID':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-yellow bg-status-yellow-bg uppercase'
        case 'PARTIALLY_PAID':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-blue bg-status-blue-bg uppercase'
        case 'PAID':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-green bg-status-green-bg uppercase'
        default:
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-gray bg-status-gray-bg uppercase'
      }
    })
    return { badgeColorClasses }
  },
}
</script>
