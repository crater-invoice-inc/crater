<template>
  <MenuItem v-slot="{ active }" v-bind="$attrs">
    <a
      href="#"
      :class="[
        active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
        'group flex items-center px-4 py-2 text-sm font-normal min-w-0',
      ]"
    >
      <slot :active="active" />
    </a>
  </MenuItem>
</template>

<script setup>
import { MenuItem } from '@headlessui/vue'
</script>
