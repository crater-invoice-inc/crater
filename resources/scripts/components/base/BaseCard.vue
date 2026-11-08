<template>
  <div class="bg-surface rounded-2xl shadow-card">
    <div
      v-if="hasHeaderSlot"
      class="px-5 py-4 text-heading border-b border-line-light border-solid"
    >
      <slot name="header" />
    </div>
    <div :class="containerClass">
      <slot />
    </div>
    <div
      v-if="hasFooterSlot"
      class="px-5 py-4 border-t border-line-light border-solid sm:px-6"
    >
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'

const props = defineProps({
  containerClass: {
    type: String,
    default: 'px-4 py-5 sm:px-8 sm:py-8',
  },
})

const slots = useSlots()

const hasHeaderSlot = computed(() => {
  return !!slots.header
})
const hasFooterSlot = computed(() => {
  return !!slots.footer
})
</script>
