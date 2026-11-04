<template>
  <BasePage>
    <SendEstimateModal />

    <BasePageHeader :title="$t('estimates.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />

        <BaseBreadcrumbItem
          :title="$tc('estimates.estimate', 2)"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="estimateStore.totalEstimateCount"
          variant="primary-outline"
          @click="toggleFilter"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              :class="slotProps.class"
              name="FilterIcon"
            />
            <BaseIcon v-else name="XIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <router-link
          v-if="userStore.hasAbilities(abilities.CREATE_ESTIMATE)"
          to="estimates/create"
        >
          <BaseButton variant="primary" class="ml-4">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('estimates.new_estimate') }}
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

      <BaseInputGroup :label="$t('estimates.status')">
        <BaseMultiselect
          v-model="filters.status"
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

      <BaseInputGroup :label="$t('general.to')">
        <BaseDatePicker
          v-model="filters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('estimates.estimate_number')">
        <BaseInput v-model="filters.estimate_number">
          <template #left="slotProps">
            <BaseIcon name="HashtagIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('estimates.no_estimates')"
      :description="$t('estimates.list_of_estimates')"
    >
      <ObservatoryIcon class="mt-5 mb-4" />

      <template #actions>
        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_ESTIMATE)"
          variant="primary-outline"
          @click="$router.push('/admin/estimates/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('estimates.add_new_estimate') }}
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
        </BaseTabGroup>

        <BaseDropdown
          v-if="
            estimateStore.selectedEstimates.length &&
            userStore.hasAbilities(abilities.EDIT_ESTIMATE)
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
            {{ $t('estimates.mark_as') }}
          </div>
          <BaseDropdownItem
            v-for="bulkStatus in visibleBulkStatuses"
            :key="bulkStatus.value"
            @click="changeMultipleEstimatesStatus(bulkStatus)"
          >
            <component
              :is="bulkStatus.icon"
              v-if="typeof bulkStatus.icon !== 'string'"
              class="mr-3 text-gray-600"
            />
            <BaseIcon
              v-else
              :name="bulkStatus.icon"
              class="mr-3 text-gray-600"
            />
            {{ $t(bulkStatus.label, 2) }}
          </BaseDropdownItem>

          <div
            v-if="
              visibleBulkStatuses.length &&
              userStore.hasAbilities(abilities.DELETE_ESTIMATE)
            "
            class="mx-4 my-1 border-t border-gray-200"
          />

          <BaseDropdownItem
            v-if="userStore.hasAbilities(abilities.DELETE_ESTIMATE)"
            @click="removeMultipleEstimates"
          >
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="tableComponent"
        :data="fetchData"
        :columns="estimateColumns"
        :placeholder-count="estimateStore.totalEstimateCount >= 20 ? 10 : 5"
        class="mt-10"
      >
        <template #header>
          <div class="absolute items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="estimateStore.selectAllField"
              variant="primary"
              @change="estimateStore.selectAllEstimates"
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

        <!-- Estimate date  -->
        <template #cell-estimate_date="{ row }">
          {{ row.data.formatted_estimate_date }}
        </template>

        <template #cell-estimate_number="{ row }">
          <router-link
            :to="{ path: `estimates/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.estimate_number }}
          </router-link>
        </template>

        <template #cell-name="{ row }">
          <BaseText :text="row.data.customer.name" :length="30" />
        </template>

        <template #cell-status="{ row }">
          <BaseEstimateStatusBadge
            :status="row.data.status"
            class="px-3 py-1"
          >
            {{ $t(`estimates.${row.data.status.toLowerCase()}`) }}
          </BaseEstimateStatusBadge>
        </template>

        <template #cell-total="{ row }">
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="row.data.customer.currency"
          />
        </template>

        <!-- Actions -->
        <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
          <EstimateDropDown :row="row.data" :table="tableComponent" />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, onUnmounted, reactive, ref, watch, inject } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useUserStore } from '@/scripts/admin/stores/user'
import { debouncedWatch } from '@vueuse/core'
import abilities from '@/scripts/admin/stub/abilities'

import ObservatoryIcon from '@/scripts/components/icons/empty/ObservatoryIcon.vue'
import PencilSheetIcon from '@/scripts/components/icons/PencilSheetIcon.vue'
import EstimateDropDown from '@/scripts/admin/components/dropdowns/EstimateIndexDropdown.vue'
import SendEstimateModal from '@/scripts/admin/components/modal-components/SendEstimateModal.vue'

const estimateStore = useEstimateStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const tableComponent = ref(null)
const { t } = useI18n()
const showFilters = ref(false)
const status = ref([
  'DRAFT',
  'SENT',
  'VIEWED',
  'EXPIRED',
  'ACCEPTED',
  'REJECTED',
])

const isRequestOngoing = ref(true)
const activeTab = ref('general.draft')
const router = useRouter()

const bulkStatuses = [
  { value: 'DRAFT', label: 'estimates.draft', icon: PencilSheetIcon },
  { value: 'SENT', label: 'estimates.sent', icon: 'PaperAirplaneIcon' },
  { value: 'ACCEPTED', label: 'estimates.accepted', icon: 'CheckCircleIcon' },
  { value: 'REJECTED', label: 'estimates.rejected', icon: 'XCircleIcon' },
]

const selectedEstimatesList = computed(() =>
  estimateStore.estimates.filter((estimate) =>
    estimateStore.selectedEstimates.includes(estimate.id)
  )
)

const visibleBulkStatuses = computed(() =>
  bulkStatuses.filter((bulkStatus) =>
    selectedEstimatesList.value.some(
      (estimate) => estimate.status !== bulkStatus.value
    )
  )
)

let filters = reactive({
  customer_id: '',
  status: '',
  from_date: '',
  to_date: '',
  estimate_number: '',
})

const showEmptyScreen = computed(
  () => !estimateStore.totalEstimateCount && !isRequestOngoing.value
)

const selectField = computed({
  get: () => estimateStore.selectedEstimates,
  set: (val) => {
    estimateStore.selectEstimate(val)
  },
})

const estimateColumns = computed(() => {
  return [
    {
      key: 'checkbox',
      thClass: 'extra w-10 pr-0',
      sortable: false,
      tdClass: 'font-medium text-gray-900 pr-0',
    },
    {
      key: 'estimate_date',
      label: t('estimates.date'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-500',
    },
    { key: 'estimate_number', label: t('estimates.number', 2) },
    { key: 'name', label: t('estimates.customer') },
    { key: 'status', label: t('estimates.status') },
    {
      key: 'total',
      label: t('estimates.total'),
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'actions',
      tdClass: 'text-right text-sm font-medium pl-0',
      thClass: 'text-right pl-0',
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
  if (estimateStore.selectAllField) {
    estimateStore.selectAllEstimates()
  }
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.CREATE_ESTIMATE,
    abilities.EDIT_ESTIMATE,
    abilities.VIEW_ESTIMATE,
    abilities.SEND_ESTIMATE,
  ])
}

async function clearStatusSearch(removedOption, id) {
  filters.status = ''
  refreshTable()
}

function refreshTable() {
  tableComponent.value && tableComponent.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  let data = {
    customer_id: filters.customer_id,
    status: filters.status,
    from_date: filters.from_date,
    to_date: filters.to_date,
    estimate_number: filters.estimate_number,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isRequestOngoing.value = true

  let response = await estimateStore.fetchEstimates(data)

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
    default:
      filters.status = ''
      break
  }
}

function setFilters() {
  estimateStore.$patch((state) => {
    state.selectedEstimates = []
    state.selectAllField = false
  })

  refreshTable()
}

function clearFilter() {
  filters.customer_id = ''
  filters.status = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.estimate_number = ''

  activeTab.value = t('general.all')
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

async function changeMultipleEstimatesStatus(status) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_change_status'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        estimateStore.updateEstimatesStatus(status.value).then((res) => {
          refreshTable()
        })
      }
    })
}

async function removeMultipleEstimates() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        estimateStore.deleteMultipleEstimates().then((res) => {
          refreshTable()
          if (res.data) {
            estimateStore.$patch((state) => {
              state.selectedEstimates = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

function setActiveTab(val) {
  switch (val) {
    case 'DRAFT':
      activeTab.value = t('general.draft')
      break
    case 'SENT':
      activeTab.value = t('general.sent')
      break

    case 'VIEWED':
      activeTab.value = t('estimates.viewed')
      break

    case 'EXPIRED':
      activeTab.value = t('estimates.expired')
      break

    case 'ACCEPTED':
      activeTab.value = t('estimates.accepted')
      break

    case 'REJECTED':
      activeTab.value = t('estimates.rejected')
      break

    default:
      activeTab.value = t('general.all')
      break
  }
}
</script>
