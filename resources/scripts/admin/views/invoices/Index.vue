<template>
  <BasePage>
    <SendInvoiceModal />
    <BasePageHeader :title="$t('invoices.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$tc('invoices.invoice', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="invoiceStore.invoiceTotalCount"
          variant="primary-outline"
          @click="toggleFilter"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FilterIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <router-link
          v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
          to="invoices/create"
        >
          <BaseButton variant="primary" class="ml-4">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('invoices.new_invoice') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilter"
    >
      <BaseInputGroup :label="$tc('customers.customer', 1)">
        <BaseCustomerSelectInput
          v-model="filters.customer_id"
          :placeholder="$t('customers.type_or_click')"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('invoices.status')">
        <BaseMultiselect
          v-model="filters.status"
          :groups="true"
          :options="status"
          searchable
          :placeholder="$t('general.select_a_status')"
          @update:modelValue="setActiveTab"
          @remove="clearStatusSearch()"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.from')">
        <BaseDatePicker
          v-model="filters.from_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <div
        class="hidden w-8 h-0 mx-4 border border-gray-400 border-solid xl:block"
        style="margin-top: 1.5rem"
      />

      <BaseInputGroup :label="$t('general.to')" class="mt-2">
        <BaseDatePicker
          v-model="filters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('invoices.invoice_number')">
        <BaseInput v-model="filters.invoice_number">
          <template #left="slotProps">
            <BaseIcon name="HashtagIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('invoices.no_invoices')"
      :description="$t('invoices.list_of_invoices')"
    >
      <MoonwalkerIcon class="mt-5 mb-4" />
      <template
        v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
        #actions
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/invoices/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('invoices.add_new_invoice') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div
        class="
          relative
          flex
          items-center
          justify-between
          h-10
          mt-5
          list-none
          border-b-2 border-gray-200 border-solid
        "
      >
        <!-- Tabs -->
        <BaseTabGroup class="-mb-5" @change="setStatusFilter">
          <BaseTab :title="$t('general.all')" filter="" />
          <BaseTab :title="$t('general.draft')" filter="DRAFT" />
          <BaseTab :title="$t('general.sent')" filter="SENT" />
          <BaseTab :title="$t('general.due')" filter="DUE" />
        </BaseTabGroup>

        <BaseDropdown
          v-if="
            invoiceStore.selectedInvoices.length &&
            userStore.hasAbilities([
              abilities.SEND_INVOICE,
              abilities.DELETE_INVOICE,
            ])
          "
          class="absolute float-right"
        >
          <template #activator>
            <span
              class="
                flex
                text-sm
                font-medium
                cursor-pointer
                select-none
                text-primary-400
              "
            >
              {{ $t('general.actions') }}
              <BaseIcon name="ChevronDownIcon" />
            </span>
          </template>

          <div
            v-if="visibleBulkStatuses.length"
            class="px-4 pt-2 pb-1 text-xs font-medium text-gray-400 uppercase select-none"
          >
            {{ $t('invoices.mark_as') }}
          </div>
          <BaseDropdownItem
            v-for="bulkStatus in visibleBulkStatuses"
            :key="bulkStatus.value"
            :disabled="hasPaidInvoiceSelected"
            @click="changeMultipleInvoicesStatus(bulkStatus)"
          >
            <span
              class="flex items-center"
              :class="hasPaidInvoiceSelected ? 'text-gray-300 cursor-not-allowed' : ''"
              :title="hasPaidInvoiceSelected ? $t('invoices.paid_invoice_selected') : ''"
            >
              <component
                :is="bulkStatus.icon"
                v-if="typeof bulkStatus.icon !== 'string'"
                class="mr-3"
                :class="hasPaidInvoiceSelected ? 'text-gray-300' : 'text-gray-600'"
              />
              <BaseIcon
                v-else
                :name="bulkStatus.icon"
                class="mr-3"
                :class="hasPaidInvoiceSelected ? 'text-gray-300' : 'text-gray-600'"
              />
              {{ $t(bulkStatus.label, 2) }}
            </span>
          </BaseDropdownItem>

          <div
            v-if="
              visibleBulkStatuses.length &&
              userStore.hasAbilities(abilities.CREATE_PAYMENT)
            "
            class="mx-4 my-1 border-t border-gray-200"
          />

          <BaseDropdownItem
            v-if="userStore.hasAbilities(abilities.CREATE_PAYMENT)"
            :disabled="!hasPayableInvoiceSelected"
            @click="addBulkPayments"
          >
            <span
              class="flex items-center"
              :class="!hasPayableInvoiceSelected ? 'text-gray-300 cursor-not-allowed' : ''"
              :title="!hasPayableInvoiceSelected ? $t('invoices.paid_invoice_selected') : ''"
            >
              <BaseIcon
                name="CreditCardIcon"
                class="mr-3"
                :class="!hasPayableInvoiceSelected ? 'text-gray-300' : 'text-gray-600'"
              />
              {{
                invoiceStore.selectedInvoices.length > 1
                  ? $t('payments.record_payments')
                  : $t('payments.record_payment')
              }}
            </span>
          </BaseDropdownItem>
          <div
            v-if="
              visibleBulkStatuses.length &&
              userStore.hasAbilities(abilities.DELETE_INVOICE)
            "
            class="mx-4 my-1 border-t border-gray-200"
          />
          <BaseDropdownItem
            v-if="userStore.hasAbilities(abilities.DELETE_INVOICE)"
            @click="removeMultipleInvoices"
          >
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="table"
        :data="fetchData"
        :columns="invoiceColumns"
        :placeholder-count="invoiceStore.invoiceTotalCount >= 20 ? 10 : 5"
        class="mt-10"
      >
        <!-- Select All Checkbox -->
        <template #header>
          <div class="absolute items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="invoiceStore.selectAllField"
              variant="primary"
              @change="invoiceStore.selectAllInvoices"
            />
          </div>
        </template>

        <template #cell-checkbox="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.id"
              v-model="selectField"
              :value="row.data.id"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <BaseText :text="row.data.customer.name" :length="30" />
        </template>

        <!-- Invoice Number  -->
        <template #cell-invoice_number="{ row }">
          <router-link
            :to="{ path: `invoices/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.invoice_number }}
          </router-link>
        </template>

        <!-- Invoice date  -->
        <template #cell-invoice_date="{ row }">
          {{ row.data.formatted_invoice_date }}
        </template>

        <!-- Invoice Total  -->
        <template #cell-total="{ row }">
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="row.data.customer.currency"
          />
        </template>

        <!-- Invoice status  -->
        <template #cell-status="{ row }">
          <BaseInvoiceStatusBadge :status="row.data.status" class="px-3 py-1">
            {{ $t(`invoices.${row.data.status.toLowerCase()}`) }}
          </BaseInvoiceStatusBadge>
        </template>

        <!-- Due Amount + Paid Status  -->
        <template #cell-due_amount="{ row }">
          <div class="flex justify-between">
            <BaseFormatMoney
              :amount="row.data.due_amount"
              :currency="row.data.currency"
            />

            <BasePaidStatusBadge
              v-if="row.data.overdue"
              status="OVERDUE"
              class="px-1 py-0.5 ml-2"
            >
              {{ $t('invoices.overdue') }}
            </BasePaidStatusBadge>

            <BasePaidStatusBadge
              v-if="row.data.paid_status !== 'PAID'"
              :status="row.data.paid_status"
              class="px-1 py-0.5 ml-2"
            >
              {{ $t(`invoices.${row.data.paid_status.toLowerCase()}`) }}
            </BasePaidStatusBadge>
          </div>
        </template>

        <!-- Actions -->
        <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
          <InvoiceDropdown :row="row.data" :table="table" />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, onUnmounted, reactive, ref, watch, inject } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import { debouncedWatch } from '@vueuse/core'

import MoonwalkerIcon from '@/scripts/components/icons/empty/MoonwalkerIcon.vue'
import PencilSheetIcon from '@/scripts/components/icons/PencilSheetIcon.vue'
import InvoiceDropdown from '@/scripts/admin/components/dropdowns/InvoiceIndexDropdown.vue'
import SendInvoiceModal from '@/scripts/admin/components/modal-components/SendInvoiceModal.vue'
// Stores
const invoiceStore = useInvoiceStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const { t } = useI18n()

// Local State
const utils = inject('$utils')
const table = ref(null)
const showFilters = ref(false)

const status = ref([
  {
    label: 'Status',
    options: ['DRAFT', 'DUE', 'SENT', 'VIEWED', 'COMPLETED'],
  },
  {
    label: 'Paid Status',
    options: ['UNPAID', 'PAID', 'PARTIALLY_PAID'],
  },
  ,
])
const isRequestOngoing = ref(true)
const activeTab = ref('general.draft')
const router = useRouter()
const userStore = useUserStore()

const bulkStatuses = [
  { value: 'DRAFT', label: 'invoices.draft', icon: PencilSheetIcon },
  { value: 'SENT', label: 'invoices.sent', icon: 'PaperAirplaneIcon' },
]

const selectedInvoicesList = computed(() =>
  invoiceStore.invoices.filter((invoice) =>
    invoiceStore.selectedInvoices.includes(invoice.id)
  )
)

const visibleBulkStatuses = computed(() =>
  bulkStatuses.filter((bulkStatus) =>
    selectedInvoicesList.value.some(
      (invoice) => invoice.status !== bulkStatus.value
    )
  )
)

const hasPaidInvoiceSelected = computed(() =>
  selectedInvoicesList.value.some((invoice) => invoice.paid_status === 'PAID')
)

const hasPayableInvoiceSelected = computed(() =>
  selectedInvoicesList.value.some((invoice) => invoice.due_amount > 0)
)

let filters = reactive({
  customer_id: '',
  status: '',
  from_date: '',
  to_date: '',
  invoice_number: '',
})

const showEmptyScreen = computed(
  () => !invoiceStore.invoiceTotalCount && !isRequestOngoing.value
)

const selectField = computed({
  get: () => invoiceStore.selectedInvoices,
  set: (value) => {
    return invoiceStore.selectInvoice(value)
  },
})

const invoiceColumns = computed(() => {
  return [
    {
      key: 'checkbox',
      thClass: 'extra w-10',
      tdClass: 'font-medium text-gray-900',
      placeholderClass: 'w-10',
      sortable: false,
    },
    {
      key: 'invoice_date',
      label: t('invoices.date'),
      thClass: 'extra',
      tdClass: 'font-medium',
    },
    { key: 'invoice_number', label: t('invoices.number') },
    { key: 'name', label: t('invoices.customer') },
    { key: 'status', label: t('invoices.status') },
    {
      key: 'due_amount',
      label: t('dashboard.recent_invoices_card.amount_due'),
    },
    {
      key: 'total',
      label: t('invoices.total'),
      tdClass: 'font-medium text-gray-900',
    },

    {
      key: 'actions',
      label: t('invoices.action'),
      tdClass: 'text-right text-sm font-medium',
      thClass: 'text-right',
      sortable: false,
    },
  ]
})

debouncedWatch(
  filters,
  () => {
    setFilters()
  },
  { debounce: 500 }
)

onUnmounted(() => {
  if (invoiceStore.selectAllField) {
    invoiceStore.selectAllInvoices()
  }
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_INVOICE,
    abilities.EDIT_INVOICE,
    abilities.VIEW_INVOICE,
    abilities.SEND_INVOICE,
  ])
}

async function clearStatusSearch(removedOption, id) {
  filters.status = ''
  refreshTable()
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  let data = {
    customer_id: filters.customer_id,
    status: filters.status,
    from_date: filters.from_date,
    to_date: filters.to_date,
    invoice_number: filters.invoice_number,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isRequestOngoing.value = true

  let response = await invoiceStore.fetchInvoices(data)

  isRequestOngoing.value = false

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 10,
    },
  }
}

function setStatusFilter(val) {
  if (activeTab.value == val.title) {
    return true
  }

  activeTab.value = val.title

  switch (val.title) {
    case t('general.draft'):
      filters.status = 'DRAFT'
      break
    case t('general.sent'):
      filters.status = 'SENT'
      break

    case t('general.due'):
      filters.status = 'DUE'
      break

    default:
      filters.status = ''
      break
  }
}

function setFilters() {
  invoiceStore.$patch((state) => {
    state.selectedInvoices = []
    state.selectAllField = false
  })

  refreshTable()
}

function clearFilter() {
  filters.customer_id = ''
  filters.status = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.invoice_number = ''

  activeTab.value = t('general.all')
}

async function addBulkPayments() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message:
        invoiceStore.selectedInvoices.length > 1
          ? t('payments.confirm_bulk_payment')
          : t('payments.confirm_single_bulk_payment'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        invoiceStore
          .addBulkPayments({
            ids: invoiceStore.selectedInvoices,
          })
          .then(() => {
            router.push('/admin/payments')
          })
      }
    })
}

async function changeMultipleInvoicesStatus(bulkStatus) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_change_status'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        invoiceStore.updateInvoicesStatus(bulkStatus.value).then((res) => {
          table.value && table.value.refresh()
        })
      }
    })
}

async function removeMultipleInvoices() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        await invoiceStore.deleteMultipleInvoices().then((res) => {
          if (res.data.success) {
            refreshTable()

            invoiceStore.$patch((state) => {
              state.selectedInvoices = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function setActiveTab(val) {
  switch (val) {
    case 'DRAFT':
      activeTab.value = t('general.draft')
      break
    case 'SENT':
      activeTab.value = t('general.sent')
      break

    case 'DUE':
      activeTab.value = t('general.due')
      break

    case 'COMPLETED':
      activeTab.value = t('invoices.completed')
      break

    case 'PAID':
      activeTab.value = t('invoices.paid')
      break

    case 'UNPAID':
      activeTab.value = t('invoices.unpaid')
      break

    case 'PARTIALLY_PAID':
      activeTab.value = t('invoices.partially_paid')
      break

    case 'VIEWED':
      activeTab.value = t('invoices.viewed')
      break

    default:
      activeTab.value = t('general.all')
      break
  }
}
</script>
