<template>
  <!-- middle panel: budgets list -->
  <div
    class="
      fixed
      top-0
      left-0
      hidden
      h-full
      pt-16
      pb-[6.4rem]
      bg-white
      xl:block
      overflow-y-auto
    "
    :class="globalStore.isSidebarCollapsed ? 'xl:ml-16' : 'xl:ml-64'"
    style="width: 18rem"
  >
    <div class="px-4 pt-8 pb-4">
      <div class="flex items-center justify-between mb-4">
        <BaseButton
          size="sm"
          variant="primary-outline"
          @click="onNewCalculator"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('calculator.new_calculator') }}
        </BaseButton>
      </div>
    </div>

    <div v-if="isLoadingList" class="flex justify-center pt-6">
      <LoadingIcon class="h-6 w-6 text-primary-400" />
    </div>

    <ul v-else class="px-2">
      <li v-for="calculator in filteredCalculators" :key="calculator.id">
        <router-link
          :to="`/admin/calculators/${calculator.id}/view`"
          class="block px-4 py-3 mb-1 rounded-lg hover:bg-gray-50"
          :class="hasActiveUrl(calculator.id) ? 'bg-gray-100' : ''"
        >
          <div class="flex items-center justify-between">
            <div class="min-w-0">
              <p
                class="text-sm font-medium text-gray-900 truncate"
                :class="hasActiveUrl(calculator.id) ? 'text-primary-600' : ''"
              >
                {{ calculator.name }}
              </p>
              <p
                v-if="calculator.customer"
                class="text-xs text-gray-700 mt-0.5 truncate"
              >
                {{ calculator.customer.name }}
              </p>
              <p class="text-xs text-gray-500 mt-0.5">
                {{ calculator.formatted_created_at }}
              </p>
            </div>
            <BaseFormatMoney
              :amount="calculator.total"
              :currency="companyStore.selectedCompanyCurrency"
              class="text-sm font-medium text-gray-900 shrink-0 ml-2"
            />
          </div>
        </router-link>
      </li>

      <li v-if="!filteredCalculators.length" class="px-4 py-3">
        <p class="text-sm text-gray-500">
          {{ $t('calculator.no_calculators') }}
        </p>
      </li>
    </ul>
  </div>

  <!-- right content -->
  <BasePage v-if="isReady" class="xl:pl-[19rem] xl:ml-8">
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('calculator.title')" to="/admin/calculators" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center space-x-3">
          <BaseButton
            variant="primary-outline"
            :loading="isSaving"
            @click="onSaveCalculator"
          >
            <template #left="slotProps">
              <BaseIcon v-if="!isSaving" name="SaveIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.save') }}
          </BaseButton>

          <BaseDropdown v-if="currentId">
            <template #activator>
              <BaseButton size="md" variant="gray">
                <BaseIcon name="DotsHorizontalIcon" />
              </BaseButton>
            </template>
            <BaseDropdownItem @click="onDuplicateCalculator">
              <BaseIcon name="DuplicateIcon" class="mr-3 text-gray-600" />
              {{ $t('calculator.duplicate') }}
            </BaseDropdownItem>

            <BaseDropdownItem @click="onDeleteCalculator">
              <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
              {{ $t('general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </div>
      </template>
    </BasePageHeader>

    <!-- empty state (no budget yet) -->
    <BaseCard v-if="!currentId" class="mt-6">
      <div class="flex flex-col items-center justify-center py-16">
        <p class="text-lg font-medium text-gray-900">
          {{ $t('calculator.no_calculators') }}
        </p>
        <p class="mt-1 text-sm text-gray-500">
          {{ $t('calculator.list_of_calculators') }}
        </p>
        <BaseButton variant="primary-outline" class="mt-6" @click="onNewCalculator">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('calculator.new_calculator') }}
        </BaseButton>
      </div>
    </BaseCard>

    <template v-else>
      <!-- budget name & customer (outside the overview card) -->
      <div class="flex flex-col gap-6 mt-6 lg:flex-row">
        <div class="flex-1 min-w-0">
          <BaseInputGroup :label="$t('calculator.name')">
            <BaseInput v-model="nameField" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('calculator.customer')" class="mt-4">
            <BaseCustomerSelectInput
              v-model="calculatorStore.currentCalculator.customer_id"
              :placeholder="$t('customers.select_a_customer')"
            />
          </BaseInputGroup>
        </div>

        <div class="lg:w-72 shrink-0 lg:pt-1" style="text-align: center">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ $t('calculator.budget_total') }}
          </p>
          <p class="mt-1 text-4xl font-bold text-gray-900">
            <BaseFormatMoney
              :amount="totalCost * 100"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </p>
        </div>
      </div>

      <!-- budget overview card -->
      <BaseCard class="mt-6">
        <div class="px-5 py-5">
          <h5 class="text-lg font-semibold text-gray-900">
            {{ $t('calculator.overview') }}
          </h5>
        </div>

        <div
          class="px-5 pb-6 flex flex-col lg:flex-row gap-8"
          style="align-items: center"
        >
          <!-- categories breakdown -->
          <div class="flex-1">
            <div
              class="
                grid grid-cols-[auto_1fr_auto]
                gap-x-4 gap-y-3
                items-center
                text-sm
              "
              style="width: 85%"
            >
              <template v-for="category in categories" :key="category.key">
                <span
                  class="w-3 h-3 rounded-sm"
                  :style="{ backgroundColor: category.color }"
                />
                <span class="text-gray-700">
                  {{ $t(`calculator.categories.${category.key}`) }}
                </span>
                <span class="font-medium text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="categoryTotal(category.key) * 100"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </span>
              </template>
            </div>
          </div>

          <!-- donut chart -->
          <div class="relative h-48 w-48 shrink-0 mx-auto lg:mx-0">
            <canvas ref="donutCanvas" />
            <div
              class="
                absolute
                inset-0
                flex
                flex-col
                items-center
                justify-center
                pointer-events-none
              "
            >
              <BaseFormatMoney
                :amount="totalCost * 100"
                :currency="companyStore.selectedCompanyCurrency"
                class="text-lg font-bold text-gray-900"
              />
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- one table per category -->
      <BaseCard
        v-for="category in categories"
        :key="category.key"
        class="mt-6"
      >
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 border-solid">
          <h5 class="text-lg font-semibold text-gray-900">
            {{ $t(`calculator.categories.${category.key}`) }}
          </h5>

          <span class="text-sm font-medium text-gray-900">
            <BaseFormatMoney
              :amount="categoryTotal(category.key) * 100"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>

        <div class="px-5 py-4 calc-table">
          <table class="min-w-full table-fixed">
            <thead>
              <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <th class="pb-2 pr-3" style="width: 15rem">
                  {{ category.type === 'role' ? $t('calculator.role') : $t('calculator.expense') }}
                </th>
                <th class="pb-2 pr-3" style="width: 7rem">
                  {{ category.type === 'role' ? $t('calculator.rate_per_hour') : $t('calculator.cost') }}
                </th>
                <th class="pb-2 pr-3 w-20">
                  {{ category.type === 'role' ? $t('calculator.hours') : $t('calculator.quantity') }}
                </th>
                <th class="pb-2 pr-3 text-right" style="width: 15%">{{ $t('calculator.amount') }}</th>
                <th class="pb-2 w-8" />
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in itemsIn(category.key)"
                :key="item.__key"
                class="border-t border-gray-100 border-solid"
              >
                <td class="py-2 pr-3">
                  <BaseMultiselect
                    v-model="item.template_id"
                    :options="templatesFor(category.key)"
                    label="name"
                    value-prop="id"
                    track-by="name"
                    searchable
                    :placeholder="
                      category.type === 'role'
                        ? $t('calculator.select_role')
                        : $t('calculator.select_expense')
                    "
                    class="w-full"
                    @update:model-value="(value) => onTemplateSelected(item, value)"
                  />
                </td>
                <td class="py-2 pr-3">
                  <BaseMoney
                    v-model="item.unit_price"
                    :currency="{
                      symbol: '',
                      decimal: '.',
                      thousands: ',',
                      prefix: '',
                      precision: 2,
                      masked: false,
                    }"
                  />
                </td>
                <td class="py-2 pr-3">
                  <BaseInput v-model.number="item.quantity" type="number" step="0.5" min="0" />
                </td>
                <td class="py-2 pr-3 text-right font-medium text-gray-900">
                  <BaseFormatMoney
                    :amount="itemTotal(item) * 100"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
                <td class="py-2 text-right">
                  <BaseIcon
                    v-if="!isEmptyItem(item)"
                    name="TrashIcon"
                    class="h-5 text-gray-400 cursor-pointer hover:text-red-500"
                    @click="removeItem(item)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </template>
  </BasePage>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Chart from 'chart.js'
import { useCalculatorStore } from '@/scripts/admin/stores/calculator'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const calculatorStore = useCalculatorStore()
const companyStore = useCompanyStore()
const globalStore = useGlobalStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

// display order of the budget tables, the overview legend and the donut chart
const BUDGET_CATEGORIES = [
  { key: 'pre_production', type: 'role' },
  { key: 'production', type: 'role' },
  { key: 'post_production', type: 'role' },
  { key: 'production_expenses', type: 'cost' },
  { key: 'equipment', type: 'cost' },
  { key: 'travel', type: 'cost' },
]

const CATEGORY_COLORS = {
  pre_production: '#8B5CF6',
  production: '#5851D8',
  post_production: '#3FAE8C',
  production_expenses: '#F5B14C',
  equipment: '#3B82F6',
  travel: '#E0785C',
}

const categories = BUDGET_CATEGORIES.map((category) => ({
  ...category,
  color: CATEGORY_COLORS[category.key],
}))

const isLoadingList = ref(true)
const isSaving = ref(false)
const isReady = ref(false)
const donutCanvas = ref(null)
let donutChart = null
let itemKeySeed = 0

const currentId = computed(() =>
  route.name === 'calculators.view' ? route.params.id : null
)

const pageTitle = computed(() =>
  calculatorStore.currentCalculator.name || t('calculator.new_calculator')
)

const nameField = computed({
  get: () => calculatorStore.currentCalculator.name,
  set: (value) => {
    calculatorStore.currentCalculator.name = value
  },
})

const filteredCalculators = computed(() => calculatorStore.calculators)

const totalCost = computed(() =>
  categories.reduce((sum, category) => sum + categoryTotal(category.key), 0)
)

function itemTotal(item) {
  return (item.unit_price || 0) * (item.quantity || 0)
}

function categoryTotal(categoryKey) {
  return calculatorStore.currentCalculator.calculator_items
    .filter((item) => item.category === categoryKey)
    .reduce((sum, item) => sum + itemTotal(item), 0)
}

function itemsIn(categoryKey) {
  return calculatorStore.currentCalculator.calculator_items.filter(
    (item) => item.category === categoryKey
  )
}

function templatesFor(categoryKey) {
  return calculatorStore.templates.filter(
    (template) => template.category === categoryKey
  )
}

function hasActiveUrl(id) {
  return route.params.id == id
}

function makeItem(categoryKey) {
  const category = BUDGET_CATEGORIES.find(
    (candidate) => candidate.key === categoryKey
  )

  return {
    __key: ++itemKeySeed,
    id: null,
    category: categoryKey,
    type: category ? category.type : 'cost',
    template_id: null,
    item_name: '',
    description: '',
    unit_price: '',
    quantity: '',
  }
}

function isEmptyItem(item) {
  return !item.item_name && !(item.unit_price > 0)
}

function ensureTrailingEmptyRows() {
  const items = calculatorStore.currentCalculator.calculator_items

  BUDGET_CATEGORIES.forEach(({ key }) => {
    const ofCategory = items.filter((item) => item.category === key)
    const emptyIndexes = []

    ofCategory.forEach((item) => {
      if (isEmptyItem(item)) {
        emptyIndexes.push(items.indexOf(item))
      }
    })

    // keep only the last empty row of this category, remove the others
    emptyIndexes
      .slice(0, -1)
      .sort((a, b) => b - a)
      .forEach((index) => items.splice(index, 1))

    // a new empty row appears in last position once the previous one is filled
    const last = ofCategory[ofCategory.length - 1]

    if (!last || !isEmptyItem(last)) {
      items.push(makeItem(key))
    }
  })
}

function removeItem(item) {
  const items = calculatorStore.currentCalculator.calculator_items
  const index = items.indexOf(item)

  if (index !== -1) {
    items.splice(index, 1)
  }
}

function onTemplateSelected(item, templateId) {
  const template = calculatorStore.templates.find(
    (candidate) => candidate.id === templateId
  )

  if (template) {
    item.item_name = template.name
    item.unit_price = template.unit_price / 100
  }
}

function updateDonutChart() {
  if (!donutCanvas.value) {
    return
  }

  let data = categories.map((category) => categoryTotal(category.key))
  let colors = categories.map((category) => category.color)

  if (data.reduce((sum, value) => sum + value, 0) === 0) {
    data = [1]
    colors = ['#E5E7EB']
  }

  if (donutChart) {
    donutChart.data.datasets[0].data = data
    donutChart.data.datasets[0].backgroundColor = colors
    donutChart.update()
    return
  }

  donutChart = new Chart(donutCanvas.value.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: categories.map((category) => category.key),
      datasets: [
        {
          data,
          backgroundColor: colors,
          borderWidth: 2,
          hoverBorderColor: '#fff',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutoutPercentage: 72,
      legend: {
        display: false,
      },
      tooltips: {
        callbacks: {
          label: (tooltipItem) => {
            const value = donutChart.data.datasets[0].data[tooltipItem.index]

            return `${t(`calculator.categories.${categories[tooltipItem.index].key}`)} : ${value.toFixed(2)} $`
          },
        },
      },
    },
  })
}

async function loadCalculatorData() {
  if (donutChart) {
    donutChart.destroy()
    donutChart = null
  }

  if (!currentId.value) {
    calculatorStore.resetCurrentCalculator()
    return
  }

  await calculatorStore.fetchCalculator(currentId.value)

  calculatorStore.currentCalculator.calculator_items =
    (
      calculatorStore.currentCalculator.calculator_items || []
    ).map((item) => {
      const template = calculatorStore.templates.find(
        (candidate) => candidate.name === item.item_name
      )

      return {
        ...item,
        __key: ++itemKeySeed,
        template_id: template ? template.id : null,
        unit_price: item.unit_price / 100,
      }
    })

  calculatorStore.currentCalculator.calculator_items =
    calculatorStore.currentCalculator.calculator_items.filter(
      (item) => item.item_name
    )

  ensureTrailingEmptyRows()

  await nextTick()
  updateDonutChart()
}

function refreshListAndMaybeRedirect() {
  return calculatorStore
    .fetchCalculators({ limit: 'all', orderByField: 'created_at', orderBy: 'desc' })
    .then(() => {
      if (route.name === 'calculators.index') {
        const latest = calculatorStore.calculators[0]

        if (latest) {
          router.replace(`/admin/calculators/${latest.id}/view`)
        }
      }
    })
}

async function onNewCalculator() {
  calculatorStore.resetCurrentCalculator()

  const response = await calculatorStore.addCalculator({
    name: t('calculator.default_name'),
    notes: '',
    customer_id: null,
    calculator_items: [],
  })

  await refreshListAndMaybeRedirect()

  router.push(`/admin/calculators/${response.data.data.id}/view`)
}

async function onSaveCalculator() {
  if (!currentId.value) {
    return
  }

  if (!calculatorStore.currentCalculator.name) {
    notificationStore.showNotification({
      type: 'error',
      message: t('validation.required'),
    })
    return
  }

  isSaving.value = true

  try {
    await calculatorStore.updateCalculator({
      id: currentId.value,
      name: calculatorStore.currentCalculator.name,
      notes: calculatorStore.currentCalculator.notes,
      customer_id: calculatorStore.currentCalculator.customer_id,
      calculator_items: calculatorStore.currentCalculator.calculator_items
        .filter((item) => item.item_name)
        .map((item) => ({
          category: item.category,
          type: item.type,
          item_name: item.item_name,
          description: item.description,
          unit_price: Math.round((item.unit_price || 0) * 100),
          quantity: item.quantity || 0,
        })),
    })

    await refreshListAndMaybeRedirect()
    isSaving.value = false
  } catch (err) {
    isSaving.value = false
  }
}

async function onDuplicateCalculator() {
  isSaving.value = true

  try {
    const response = await calculatorStore.duplicateCalculator(currentId.value)

    await refreshListAndMaybeRedirect()

    isSaving.value = false
    router.push(`/admin/calculators/${response.data.data.id}/view`)
  } catch (err) {
    isSaving.value = false
  }
}

function onDeleteCalculator() {
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
    .then(async (res) => {
      if (res) {
        await calculatorStore.deleteCalculator(currentId.value)
        await refreshListAndMaybeRedirect()

        if (route.name === 'calculators.view') {
          router.replace('/admin/calculators')
        }
      }
    })
}

watch(
  () => route.params.id,
  () => {
    loadCalculatorData()
  }
)

let isEnsuringRows = false

watch(
  () => calculatorStore.currentCalculator.calculator_items,
  () => {
    if (isEnsuringRows) {
      return
    }

    isEnsuringRows = true

    setTimeout(() => {
      try {
        ensureTrailingEmptyRows()
      } finally {
        isEnsuringRows = false
      }
    }, 300)
  },
  { deep: true }
)

watch(totalCost, () => {
  updateDonutChart()
})

onMounted(async () => {
  await Promise.all([
    refreshListAndMaybeRedirect().finally(() => {
      isLoadingList.value = false
    }),
    calculatorStore.fetchTemplates({ limit: 'all' }),
  ])

  await loadCalculatorData()

  isReady.value = true
  await nextTick()
  updateDonutChart()
})
</script>

<style scoped>
.calc-table :deep(.multiselect-single-label) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
}
</style>
