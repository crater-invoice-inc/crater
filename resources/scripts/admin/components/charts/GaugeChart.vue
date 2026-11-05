<template>
  <div class="flex flex-col h-full">
    <div class="flex items-center justify-center gap-6">
      <span class="flex items-center text-sm text-gray-500">
        <span
          class="inline-block w-2.5 h-2.5 rounded-full mr-2"
          :style="{ background: COLOR_PAID }"
        />
        {{ $t('dashboard.gauge.collected') }}
      </span>
      <span class="flex items-center text-sm text-gray-500">
        <span
          class="inline-block w-2.5 h-2.5 rounded-full mr-2"
          :style="{ background: COLOR_DUE }"
        />
        {{ $t('dashboard.gauge.outstanding') }}
      </span>
    </div>

    <div class="relative mt-2 flex-1">
      <svg viewBox="0 0 220 128" class="w-full">
        <!-- Tick marks -->
        <line
          v-for="(tick, i) in ticks"
          :key="'tick-' + i"
          :x1="tick.x1"
          :y1="tick.y1"
          :x2="tick.x2"
          :y2="tick.y2"
          stroke="#E5E7EB"
          stroke-width="1"
          stroke-linecap="round"
        />
        <!-- Gauge segments -->
        <path
          v-for="(segment, i) in segments"
          :key="'seg-' + i"
          :d="segment.path"
          fill="none"
          :stroke="segment.color"
          stroke-width="20"
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
  </div>
</template>

<script setup>
import { computed, inject } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const utils = inject('utils')

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
const GAP_DEG = 4
const TICK_START = 102
const TICK_END = 110

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

const ticks = computed(() => {
  let lines = []
  let step = 180 / 50

  for (let i = 0; i <= 50; i++) {
    let angle = 180 + i * step
    let p1 = polar(angle, TICK_START)
    let p2 = polar(angle, TICK_END)
    lines.push({
      x1: p1.x.toFixed(2),
      y1: p1.y.toFixed(2),
      x2: p2.x.toFixed(2),
      y2: p2.y.toFixed(2),
    })
  }

  return lines
})

const amountDue = computed(() => {
  return utils.formatMoney(Math.max(props.sales - props.receipts, 0), companyStore.selectedCompanyCurrency)
})
</script>
