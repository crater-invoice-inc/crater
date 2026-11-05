<template>
  <div>
    <div class="grid grid-cols-1 gap-6 mt-10 xl:grid-cols-2">
      <!-- Due Invoices -->
      <div
        v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"
        class="due-invoices"
      >
        <div class="relative z-10 flex items-center justify-between mb-3">
          <h6 class="mb-0 text-base font-semibold leading-normal">
            {{ $t('dashboard.recent_invoices_card.title') }}
          </h6>

          <button
            type="button"
            class="flex items-center justify-center text-primary-500 hover:text-primary-600 transition-colors"
            :title="$t('dashboard.recent_invoices_card.view_all')"
            @click="$router.push('/admin/invoices')"
          >
            <BaseIcon name="EyeIcon" class="w-6 h-6" />
          </button>
        </div>

        <BaseTable
          :data="dashboardStore.recentDueInvoices"
          :columns="dueInvoiceColumns"
          :loading="!dashboardStore.isDashboardDataLoaded"
        >
          <template #cell-user="{ row }">
            <router-link
              :to="{ path: `invoices/${row.data.id}/view` }"
              class="font-medium text-primary-500"
            >
              {{ row.data.customer.name }}
            </router-link>
          </template>

          <template #cell-due_amount="{ row }">
            <BaseFormatMoney
              :amount="row.data.due_amount"
              :currency="row.data.customer.currency"
            />
          </template>

          <!-- Actions -->
          <template
            v-if="hasAtleastOneInvoiceAbility()"
            #cell-actions="{ row }"
          >
            <InvoiceDropdown :row="row.data" :table="invoiceTableComponent" />
          </template>
        </BaseTable>
      </div>

      <!-- Recent Estimates -->
      <div
        v-if="userStore.hasAbilities(abilities.VIEW_ESTIMATE)"
        class="recent-estimates"
      >
        <div class="relative z-10 flex items-center justify-between mb-3">
          <h6 class="mb-0 text-base font-semibold leading-normal">
            {{ $t('dashboard.recent_estimate_card.title') }}
          </h6>

          <button
            type="button"
            class="flex items-center justify-center text-primary-500 hover:text-primary-600 transition-colors"
            :title="$t('dashboard.recent_estimate_card.view_all')"
            @click="$router.push('/admin/estimates')"
          >
            <BaseIcon name="EyeIcon" class="w-6 h-6" />
          </button>
        </div>

        <BaseTable
          :data="dashboardStore.recentEstimates"
          :columns="recentEstimateColumns"
          :loading="!dashboardStore.isDashboardDataLoaded"
        >
          <template #cell-user="{ row }">
            <router-link
              :to="{ path: `estimates/${row.data.id}/view` }"
              class="font-medium text-primary-500"
            >
              {{ row.data.customer.name }}
            </router-link>
          </template>

          <template #cell-total="{ row }">
            <BaseFormatMoney
              :amount="row.data.total"
              :currency="row.data.customer.currency"
            />
          </template>

          <template
            v-if="hasAtleastOneEstimateAbility()"
            #cell-actions="{ row }"
          >
            <EstimateDropdown :row="row" :table="estimateTableComponent" />
          </template>
        </BaseTable>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import InvoiceDropdown from '@/scripts/admin/components/dropdowns/InvoiceIndexDropdown.vue'
import EstimateDropdown from '@/scripts/admin/components/dropdowns/EstimateIndexDropdown.vue'

const dashboardStore = useDashboardStore()

const { t } = useI18n()
const userStore = useUserStore()

const invoiceTableComponent = ref(null)
const estimateTableComponent = ref(null)

const dueInvoiceColumns = computed(() => {
  return [
    {
      key: 'formattedDueDate',
      label: t('dashboard.recent_invoices_card.due_on'),
    },
    {
      key: 'user',
      label: t('dashboard.recent_invoices_card.customer'),
    },
    {
      key: 'due_amount',
      label: t('dashboard.recent_invoices_card.amount_due'),
    },
    {
      key: 'actions',
      tdClass: 'text-right text-sm font-medium pl-0',
      thClass: 'text-right pl-0',
      sortable: false,
    },
  ]
})

const recentEstimateColumns = computed(() => {
  return [
    {
      key: 'formattedEstimateDate',
      label: t('dashboard.recent_estimate_card.date'),
    },
    {
      key: 'user',
      label: t('dashboard.recent_estimate_card.customer'),
    },
    {
      key: 'total',
      label: t('dashboard.recent_estimate_card.amount_due'),
    },
    {
      key: 'actions',
      tdClass: 'text-right text-sm font-medium pl-0',
      thClass: 'text-right pl-0',
      sortable: false,
    },
  ]
})

function hasAtleastOneInvoiceAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_INVOICE,
    abilities.EDIT_INVOICE,
    abilities.VIEW_INVOICE,
    abilities.SEND_INVOICE,
  ])
}

function hasAtleastOneEstimateAbility() {
  return userStore.hasAbilities([
    abilities.CREATE_ESTIMATE,
    abilities.EDIT_ESTIMATE,
    abilities.VIEW_ESTIMATE,
    abilities.SEND_ESTIMATE,
  ])
}
</script>
