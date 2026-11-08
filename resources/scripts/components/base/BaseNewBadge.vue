<template>
  <span
    :class="[
      sucess ? 'bg-status-green-bg text-status-green' : 'bg-status-red-bg text-status-red',
      'px-2.5 py-1 text-xs font-medium text-center uppercase rounded-full',
    ]"
  >
    <slot />
  </span>
</template>

<script setup>
const props = defineProps({
  sucess: {
    type: Boolean,
    default: false,
  },
})
</script>
