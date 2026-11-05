<template>
  <div class="flex flex-col h-full">
    <div class="relative">
      <svg viewBox="0 0 220 128" class="w-full">
        <path
          v-for="(segment, i) in segments"
          :key="'seg-' + i"
          :d="segment.path"
          fill="none"
          :stroke="segment.color"
          stroke-width="18"
          stroke-linecap="round"
        />
      </svg>

      <div class="absolute inset-x-0 bottom-0 text-center">
        <span class="block text-2xl font-bold tracking-tight text-gray-900">
          {{ amountDue }}
        </span>
        <span class="block mt-0.5 text-sm text-gray-400">
          {{ $t('dashboard.gauge.outstanding') }}
        </span>
      </div>
    </div>

    <div class="mt-6">
      <div
        v-for="row in rows"
        :key="row.label"
        class="border-t border-gray-100 py-4 last:pb-0"
      >
        <div class="flex justify-between items-center mb-2.5">
          <span class="flex items-center text-sm text-gray-500">
            <span
              class="inline-block w-2.5 h-2.5 rounded-full mr-2"
              :style="{ background: row.color }"
            />
            {{ row.label }}
          </span>
          <span class="text-sm font-semibold text-gray-900">
            {{ row.amount }}
          </span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
          <div
            class="h-full rounded-full"
            :style="{ width: (row.ratio * 100) + '%', background: row.color }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const utils = inject('utils')
const { t } = useI18n()

const props = defineProps({
  sales: {
    type: Number,
    default: 0,
  },
  receipts: {
    type: Number,
    default: 0,
  },
})

const COLOR_PAID = '#C9C7F2'
const COLOR_DUE = '#5851D8'
const COLOR_EMPTY = '#EEF0F4'

const SEGMENTS = 14
const CX = 110
const CY = 112
const RADIUS = 86
const GAP_DEG = 7

const companyStore = useCompanyStore()

function polar(angleDeg, radius) {
  let rad = (angleDeg * Math.PI) / 180
  return {
    x: CX + radius * Math.cos(rad),
    y: CY + radius * Math.sin(rad),
  }
}

function arcPath(startDeg, endDeg) {
  let start = polar(startDeg, RADIUS)
  let end = polar(endDeg, RADIUS)
  return `M ${start.x.toFixed(2)} ${start.y.toFixed(2)} A ${RADIUS} ${RADIUS} 0 0 1 ${end.x.toFixed(2)} ${end.y.toFixed(2)}`
}

const paidRatio = computed(() => {
  if (!props.sales || props.sales <= 0) {
    return 0
  }
  return Math.min(Math.max(props.receipts / props.sales, 0), 1)
})

const dueRatio = computed(() => {
  if (!props.sales || props.sales <= 0) {
    return 0
  }
  return Math.min(Math.max((props.sales - props.receipts) / props.sales, 0), 1)
})

const segments = computed(() => {
  let paths = []
  let step = 180 / SEGMENTS

  for (let i = 0; i < SEGMENTS; i++) {
    let start = 180 + i * step + GAP_DEG / 2
    let end = 180 + (i + 1) * step - GAP_DEG / 2
    let boundary = (i + 1) / SEGMENTS
    let color = COLOR_EMPTY

    if (paidRatio.value >= boundary) {
      color = COLOR_PAID
    } else if (paidRatio.value + dueRatio.value >= boundary) {
      color = COLOR_DUE
    }

    paths.push({ path: arcPath(start, end), color })
  }

  return paths
})

const rows = computed(() => [
  {
    label: t('dashboard.gauge.collected'),
    amount: utils.formatMoney(props.receipts, companyStore.selectedCompanyCurrency),
    ratio: paidRatio.value,
    color: COLOR_PAID,
  },
  {
    label: t('dashboard.gauge.outstanding'),
    amount: utils.formatMoney(Math.max(props.sales - props.receipts, 0), companyStore.selectedCompanyCurrency),
    ratio: dueRatio.value,
    color: COLOR_DUE,
  },
])

const amountDue = computed(() => {
  return utils.formatMoney(Math.max(props.sales - props.receipts, 0), companyStore.selectedCompanyCurrency)
})
</script>
