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
        case 'COMPLETED':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-green bg-status-green-bg uppercase'
        case 'ON_HOLD':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-yellow bg-status-yellow-bg uppercase'
        case 'ACTIVE':
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-blue bg-status-blue-bg uppercase'
        default:
          return 'px-2.5 py-1 rounded-full text-xs font-medium text-center text-status-gray bg-status-gray-bg uppercase'
      }
    })
    return { badgeColorClasses }
  },
}
</script>
