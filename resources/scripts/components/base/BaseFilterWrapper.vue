<template>
  <transition
    enter-active-class="transition duration-500 ease-in-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in-out"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-show="show" class="relative z-10 p-4 md:p-8 bg-surface-secondary rounded-xl border border-line-light">
      <slot name="filter-header" />

      <label
        class="
          absolute
          text-sm
          leading-snug
          text-muted
          cursor-pointer
          hover:text-heading
          top-2.5
          right-3.5
        "
        @click="$emit('clear')"
      >
        {{ $t('general.clear_all') }}
      </label>

      <div
        class="flex flex-col space-y-3"
        :class="
          rowOnXl
            ? 'xl:flex-row xl:space-x-4 xl:space-y-0 xl:items-center'
            : 'lg:flex-row lg:space-x-4 lg:space-y-0 lg:items-center'
        "
      >
        <slot />
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  rowOnXl: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['clear'])
</script>
