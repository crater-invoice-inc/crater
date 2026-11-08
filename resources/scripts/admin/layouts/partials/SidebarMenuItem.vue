<template>
  <router-link
    :to="item.link"
    :title="collapsed ? $t(item.title) : null"
    :class="[
      hasActiveUrl(item.link)
        ? 'text-primary-600 bg-primary-50'
        : 'text-gray-700',
      collapsed
        ? 'cursor-pointer mx-2 px-0 py-2.5 rounded-lg hover:bg-gray-50 group flex items-center justify-center text-sm not-italic font-medium'
        : 'cursor-pointer px-3 py-2.5 rounded-lg hover:bg-gray-50 group flex items-center text-sm not-italic font-medium',
    ]"
    :style="gap && !collapsed ? { marginTop: '24px' } : {}"
  >
    <BaseIcon
      :name="item.icon"
      :class="[
        hasActiveUrl(item.link)
          ? 'text-primary-500 group-hover:text-primary-500 '
          : 'text-gray-400 group-hover:text-gray-600',
        collapsed ? 'shrink-0 h-5 w-5' : 'mr-3.5 shrink-0 h-5 w-5',
      ]"
    />

    <span v-if="!collapsed">{{ $t(item.title) }}</span>

    <span
      v-if="badgeCount && !collapsed"
      class="
        px-2
        py-0.5
        ml-auto
        text-xs
        font-medium
        text-gray-500
        bg-gray-100
        rounded-full
      "
    >
      {{ badgeCount }}
    </span>
  </router-link>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'

const props = defineProps({
  item: { type: Object, required: true },
  gap: { type: Boolean, default: false },
  collapsed: { type: Boolean, default: false },
})

const route = useRoute()
const dashboardStore = useDashboardStore()

const MENU_COUNT_FIELDS = {
  Customers: 'totalCustomerCount',
  Estimates: 'totalEstimateCount',
  Invoices: 'totalInvoiceCount',
}

const badgeCount = computed(() => {
  const field = MENU_COUNT_FIELDS[props.item.name]

  return field ? dashboardStore.stats[field] : 0
})

function hasActiveUrl(url) {
  return route.path.indexOf(url) > -1
}
</script>
