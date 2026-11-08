<template>
  <div class="flex flex-wrap justify-between">
    <div>
      <h3 class="text-2xl font-bold text-left text-heading">
        {{ title }}
      </h3>
      <slot />
    </div>
    <div class="flex items-center">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: {
    type: [String],
    default: '',
    required: true,
  },
})
</script>
