<template>
  <router-link
    v-if="!loading"
    class="
      relative
      flex
      justify-between
      p-4
      rounded-2xl
      xl:p-6
      lg:col-span-2
    "
    :class="large
      ? 'lg:!col-span-3 bg-gradient-to-br from-hero-from to-hero-to shadow-card hover:from-primary-700 hover:to-primary-500'
      : 'bg-surface border border-line-light shadow-card hover:bg-surface-secondary'"
    :to="route"
  >
    <div>
      <span
        class="text-xl font-semibold leading-tight xl:text-3xl"
        :class="large ? 'text-white' : 'text-heading'"
      >
        <slot />
      </span>
      <span
        class="block mt-1 text-sm leading-tight xl:text-lg"
        :class="large ? 'text-white text-opacity-75' : 'text-muted'"
      >
        {{ label }}
      </span>
    </div>
    <div class="flex items-center">
      <component
        :is="iconComponent"
        class="w-10 h-10 xl:w-12 xl:h-12"
        :class="large ? 'text-white text-opacity-90' : 'text-primary-500'"
      />
    </div>
  </router-link>

  <StatsCardPlaceholder v-else-if="large" />

  <StatsCardSmPlaceholder v-else />
</template>


<script setup>
import StatsCardPlaceholder from '@/scripts/customer/views/dashboard/DashboardStatsPlaceholder.vue'
import StatsCardSmPlaceholder from '@/scripts/customer/views/dashboard/DashboardStatsSmPlaceholder.vue'

defineProps({
  iconComponent: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  route: {
    type: Object,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  large: {
    type: Boolean,
    default: false,
  },
})
</script>
