<template>
  <span :class="[badgeColorClasses, defaultClass]">
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
    defaultClass: {
      type: String,
      default: 'px-2.5 py-0.5 text-xs font-medium uppercase rounded-full',
    },
  },

  setup(props) {
    const badgeColorClasses = computed(() => {
      switch (props.status) {
        case 'PAID':
          return 'text-status-green bg-status-green-bg uppercase font-medium text-center'
        case 'UNPAID':
          return 'text-status-yellow bg-status-yellow-bg uppercase font-medium text-center'
        case 'PARTIALLY_PAID':
          return 'text-status-blue bg-status-blue-bg uppercase font-medium text-center'
        case 'OVERDUE':
          return 'text-status-red bg-status-red-bg uppercase font-medium text-center'
        default:
          return 'text-status-gray bg-status-gray-bg uppercase font-medium text-center'
      }
    })
    return { badgeColorClasses }
  },
}
</script>
