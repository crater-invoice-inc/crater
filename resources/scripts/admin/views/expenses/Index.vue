<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('expenses.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$tc('expenses.expense', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="expenseStore.totalExpenses"
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

        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_EXPENSE)"
          class="ml-4"
          variant="primary"
          @click="$router.push('expenses/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('expenses.add_expense') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('expenses.customer')">
        <BaseCustomerSelectInput
          v-model="filters.customer_id"
          :placeholder="$t('customers.type_or_click')"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('expenses.category')">
        <BaseMultiselect
          v-model="filters.expense_category_id"
          value-prop="id"
          label="name"
          track-by="name"
          :filter-results="false"
          resolve-on-load
          :delay="500"
          :options="searchCategory"
          searchable
          :placeholder="$t('expenses.categories.select_a_category')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('expenses.paid_status')">
        <BaseMultiselect
          v-model="filters.paid_status"
          value-prop="value"
          label="label"
          track-by="label"
          :options="paidStatuses"
          :can-deselect="true"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('expenses.from_date')">
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

      <BaseInputGroup :label="$t('expenses.to_date')">
        <BaseDatePicker
          v-model="filters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('expenses.from_due_date')">
        <BaseDatePicker
          v-model="filters.from_due_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>
      <div
        class="hidden w-8 h-0 mx-4 border border-gray-400 border-solid xl:block"
        style="margin-top: 1.5rem"
      />

      <BaseInputGroup :label="$t('expenses.to_due_date')">
        <BaseDatePicker
          v-model="filters.to_due_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Empty Table Placeholder -->
    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('expenses.no_expenses')"
      :description="$t('expenses.list_of_expenses')"
    >
      <UFOIcon class="mt-5 mb-4" />

      <template
        v-if="userStore.hasAbilities(abilities.CREATE_EXPENSE)"
        #actions
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/expenses/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('expenses.add_new_expense') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>
    <div v-show="!showEmptyScreen" class="relative table-container">
      <div class="relative flex items-center justify-end h-5">
        <BaseDropdown
          v-if="
            expenseStore.selectedExpenses.length &&
            userStore.hasAbilities(abilities.DELETE_EXPENSE)
          "
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

          <BaseDropdownItem
            v-if="userStore.hasAbilities(abilities.DELETE_EXPENSE)"
            @click="removeMultipleExpenses"
          >
            <BaseIcon name="TrashIcon" class="h-5 mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="tableComponent"
        :data="fetchData"
        :columns="expenseColumns"
        class="mt-3"
      >
        <!-- Select All Checkbox -->
        <template #header>
          <div class="absolute items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="selectAllFieldStatus"
              variant="primary"
              @change="expenseStore.selectAllExpenses"
            />
          </div>
        </template>
        <template #cell-status="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.id"
              v-model="selectField"
              :value="row.data.id"
              variant="primary"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <router-link
            :to="{ path: `expenses/${row.data.id}/edit` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.expense_category.name }}
          </router-link>
        </template>

        <template #cell-amount="{ row }">
          <BaseFormatMoney
            :amount="row.data.amount"
            :currency="row.data.currency"
          />
        </template>

        <template #cell-expense_date="{ row }">
          {{ row.data.formatted_expense_date }}
        </template>

        <template #cell-due_date="{ row }">
          {{ row.data.formatted_due_date ? row.data.formatted_due_date : '-' }}
        </template>

        <template #cell-paid_status="{ row }">
          <BasePaidStatusBadge :status="row.data.paid_at ? 'PAID' : 'UNPAID'">
            {{ row.data.paid_at ? $t('expenses.paid') : $t('expenses.unpaid') }}
          </BasePaidStatusBadge>
        </template>

        <template #cell-user_name="{ row }">
          <BaseText
            :text="row.data.customer ? row.data.customer.name : '-'"
            :length="30"
          />
        </template>

        <template #cell-notes="{ row }">
          <div class="notes">
            <div class="truncate note w-60">
              {{ row.data.notes ? row.data.notes : '-' }}
            </div>
          </div>
        </template>

        <template v-if="hasAbilities()" #cell-actions="{ row }">
          <ExpenseDropdown
            :row="row.data"
            :table="tableComponent"
            :load-data="refreshTable"
          />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, computed, reactive, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useExpenseStore } from '@/scripts/admin/stores/expense'
import { useCategoryStore } from '@/scripts/admin/stores/category'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { debouncedWatch } from '@vueuse/core'
import { useUserStore } from '@/scripts/admin/stores/user'

import abilities from '@/scripts/admin/stub/abilities'

import UFOIcon from '@/scripts/components/icons/empty/UFOIcon.vue'
import ExpenseDropdown from '@/scripts/admin/components/dropdowns/ExpenseIndexDropdown.vue'

const companyStore = useCompanyStore()
const expenseStore = useExpenseStore()
const dialogStore = useDialogStore()
const categoryStore = useCategoryStore()
const userStore = useUserStore()

let isFetchingInitialData = ref(true)
let showFilters = ref(null)

const filters = reactive({
  expense_category_id: '',
  from_date: '',
  to_date: '',
  from_due_date: '',
  to_due_date: '',
  paid_status: '',
  customer_id: '',
})

const paidStatuses = computed(() => [
  { value: 'PAID', label: t('expenses.paid') },
  { value: 'UNPAID', label: t('expenses.unpaid') },
])

const { t } = useI18n()
let tableComponent = ref(null)

const showEmptyScreen = computed(() => {
  return !expenseStore.totalExpenses && !isFetchingInitialData.value
})

const selectField = computed({
  get: () => expenseStore.selectedExpenses,
  set: (value) => {
    return expenseStore.selectExpense(value)
  },
})

const selectAllFieldStatus = computed({
  get: () => expenseStore.selectAllField,
  set: (value) => {
    return expenseStore.setSelectAllState(value)
  },
})

const expenseColumns = computed(() => {
  return [
    {
      key: 'status',
      thClass: 'extra w-10',
      tdClass: 'font-medium text-gray-900',
      placeholderClass: 'w-10',
      sortable: false,
    },
    {
      key: 'expense_date',
      label: 'Date',
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'due_date',
      label: 'Due Date',
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'paid_status',
      label: 'Status',
      sortable: false,
    },
    {
      key: 'name',
      label: 'Category',
      thClass: 'extra',
      tdClass: 'cursor-pointer font-medium text-primary-500',
    },
    { key: 'user_name', label: 'Customer' },
    { key: 'notes', label: 'Note' },
    { key: 'amount', label: 'Amount' },
    {
      key: 'actions',
      sortable: false,
      tdClass: 'text-right text-sm font-medium',
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
  if (expenseStore.selectAllField) {
    expenseStore.selectAllExpenses()
  }
})

onMounted(() => {
  categoryStore.fetchCategories({ limit: 'all' })
})

async function searchCategory(search) {
  let res = await categoryStore.fetchCategories({ search })
  return res.data.data
}

async function fetchData({ page, filter, sort }) {
  let data = {
    ...filters,

    orderByField: sort.fieldName || 'created_at',

    orderBy: sort.order || 'desc',

    page,
  }

  isFetchingInitialData.value = true

  let response = await expenseStore.fetchExpenses(data)
  isFetchingInitialData.value = false

  return {
    data: response.data.data,

    pagination: {
      data: response.data.data,
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 10,
    },
  }
}

function refreshTable() {
  tableComponent.value && tableComponent.value.refresh()
}

function setFilters() {
  refreshTable()
}

function clearFilter() {
  filters.expense_category_id = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.from_due_date = ''
  filters.to_due_date = ''
  filters.paid_status = ''
  filters.customer_id = ''
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function hasAbilities() {
  return userStore.hasAbilities([
    abilities.DELETE_EXPENSE,
    abilities.EDIT_EXPENSE,
  ])
}

function removeMultipleExpenses() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('expenses.confirm_delete', 2),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      size: 'lg',
      hideNoButton: false,
    })
    .then((res) => {
      if (res) {
        expenseStore.deleteMultipleExpenses().then((response) => {
          if (response.data) {
            refreshTable()
          }
        })
      }
    })
}
</script>
