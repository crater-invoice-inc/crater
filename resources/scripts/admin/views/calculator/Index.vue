<template>
  <BasePage>
    <BasePageHeader :title="$t('calculator.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('calculator.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_CALCULATOR)"
          @click="$router.push('/admin/calculators/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('calculator.new_calculator') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('calculator.no_calculators')"
      :description="$t('calculator.list_of_calculators')"
    >
      <template #actions>
        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_CALCULATOR)"
          variant="primary-outline"
          @click="$router.push('/admin/calculators/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('calculator.new_calculator') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div
        class="
          relative
          flex
          items-center
          justify-end
          h-5
          border-gray-200 border-solid
        "
      >
        <BaseDropdown v-if="calculatorStore.selectedCalculators.length">
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
          <BaseDropdownItem @click="removeMultipleCalculators">
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="table"
        :data="fetchData"
        :columns="calculatorColumns"
        :placeholder-count="calculatorStore.totalCalculators >= 20 ? 10 : 5"
        class="mt-3"
      >
        <template #header>
          <div class="absolute items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="calculatorStore.selectAllField"
              variant="primary"
              @change="calculatorStore.selectAllCalculators"
            />
          </div>
        </template>

        <template #cell-status="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.id"
              v-model="selectField"
              :value="row.data.id"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <router-link
            :to="{ path: `calculators/${row.data.id}/edit` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.name }}
          </router-link>
        </template>

        <template #cell-items_count="{ row }">
          <span>{{ row.data.items_count }}</span>
        </template>

        <template #cell-total="{ row }">
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </template>

        <template #cell-created_at="{ row }">
          <span>{{ row.data.formatted_created_at }}</span>
        </template>

        <template v-if="hasAbilities()" #cell-actions="{ row }">
          <CalculatorDropdown
            :row="row.data"
            :table="table"
            :load-data="refreshTable"
          />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, reactive, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCalculatorStore } from '@/scripts/admin/stores/calculator'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'
import CalculatorDropdown from '@/scripts/admin/components/dropdowns/CalculatorIndexDropdown.vue'
import abilities from '@/scripts/admin/stub/abilities'

const calculatorStore = useCalculatorStore()
const companyStore = useCompanyStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
let isFetchingInitialData = ref(true)

const table = ref(null)

const showEmptyScreen = computed(
  () => !calculatorStore.totalCalculators && !isFetchingInitialData.value
)

const selectField = computed({
  get: () => calculatorStore.selectedCalculators,
  set: (value) => {
    return calculatorStore.selectCalculator(value)
  },
})

const calculatorColumns = computed(() => {
  return [
    {
      key: 'status',
      thClass: 'extra w-10',
      tdClass: 'font-medium text-gray-900',
      placeholderClass: 'w-10',
      sortable: false,
    },
    {
      key: 'name',
      label: t('calculator.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    { key: 'items_count', label: t('calculator.items_count') },
    { key: 'total', label: t('calculator.total') },
    { key: 'created_at', label: t('calculator.added_on') },

    {
      key: 'actions',
      thClass: 'text-right',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})

onUnmounted(() => {
  if (calculatorStore.selectAllField) {
    calculatorStore.selectAllCalculators()
  }
})

function hasAbilities() {
  return userStore.hasAbilities([
    abilities.DELETE_CALCULATOR,
    abilities.EDIT_CALCULATOR,
  ])
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  let data = {
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isFetchingInitialData.value = true

  let response = await calculatorStore.fetchCalculators(data)

  isFetchingInitialData.value = false

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

function removeMultipleCalculators() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('calculator.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        calculatorStore.deleteMultipleCalculators().then((response) => {
          if (response.data.success) {
            table.value && table.value.refresh()
          }
        })
      }
    })
}
</script>
