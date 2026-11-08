<template>
  <span
    class="
      px-2.5
      py-1
      text-xs
      font-medium
      text-center text-status-green
      uppercase
      rounded-full
      bg-status-green-bg
    "
    :style="{ backgroundColor: bgColor, color }"
  >
    <slot />
  </span>
</template>

<script setup>
const props = defineProps({
  bgColor: {
    type: String,
    default: null,
  },
  color: {
    type: String,
    default: null,
  },
})
</script>
