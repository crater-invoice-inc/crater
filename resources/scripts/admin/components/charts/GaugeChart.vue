<template>
  <div class="flex flex-col h-full">
    <div class="relative">
      <svg viewBox="0 0 220 128" class="w-full">
        <!-- Remaining track -->
        <path
          :d="arcPath(180, 360)"
          fill="none"
          :stroke="COLOR_EMPTY"
          stroke-width="22"
          stroke-linecap="round"
        />
        <!-- Collected -->
        <path
          v-if="paidArc.end > paidArc.start"
          :d="arcPath(paidArc.start, paidArc.end)"
          fill="none"
          :stroke="COLOR_PAID"
          stroke-width="22"
          stroke-linecap="round"
        />
        <!-- Outstanding -->
        <path
          v-if="dueArc.end > dueArc.start"
          :d="arcPath(dueArc.start, dueArc.end)"
          fill="none"
          :stroke="COLOR_DUE"
          stroke-width="22"
          stroke-linecap="round"
        />
      </svg>

      <div class="absolute inset-x-0 text-center" style="top: 52%">
        <span class="block text-2xl font-bold tracking-tight text-gray-900">
          {{ amountDue }}
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

const CX = 110
const CY = 112
const RADIUS = 86

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

function ratio(value, total) {
  if (!total || total <= 0) {
    return 0
  }
  return Math.min(Math.max(value / total, 0), 1)
}

const paidRatio = computed(() => ratio(props.receipts, props.sales))

const dueRatio = computed(() => ratio(props.sales - props.receipts, props.sales))

const paidArc = computed(() => {
  let start = 180
  let end = 180 + 180 * paidRatio.value
  if (end - start < 3) {
    end = start
  }
  return { start, end }
})

const dueArc = computed(() => {
  let start = paidArc.value.end
  let end = Math.min(start + 180 * dueRatio.value, 360)
  if (end - start < 3) {
    end = start
  }
  return { start, end }
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
