<template>
  <BaseSettingCard
    :title="$t('settings.calculator.title')"
    :description="$t('settings.calculator.description')"
  >
    <CalculatorTemplateModal />

    <template #action>
      <BaseButton variant="primary-outline" @click="openTemplateModal">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ $t('calculator.templates.add_new') }}
      </BaseButton>
    </template>

    <div
      v-for="group in groupedTemplates"
      :key="group.category"
      class="mt-8"
    >
      <h6 class="mb-2 text-sm font-semibold text-gray-900">
        {{ $t(`calculator.categories.${group.category}`) }}
      </h6>

      <BaseTable
        v-if="group.items.length"
        :data="group.items"
        :columns="templateColumns"
        :show-pagination="false"
      >
        <template #cell-unit_price="{ row }">
          <BaseFormatMoney
            :amount="row.data.unit_price"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </template>

        <template #cell-is_default="{ row }">
          <BaseBadge
            :bg-color="row.data.is_default ? '#D9F2E7' : '#F3F4F6'"
            :color="row.data.is_default ? '#2C7A5B' : '#6B7280'"
          >
            {{ row.data.is_default ? $t('general.yes') : $t('general.no') }}
          </BaseBadge>
        </template>

        <template #cell-actions="{ row }">
          <CalculatorTemplateDropdown
            :row="row.data"
            :load-data="loadData"
          />
        </template>
      </BaseTable>

      <p v-else class="text-sm text-gray-500">
        {{ $t('calculator.templates.none_in_category') }}
      </p>
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCalculatorStore } from '@/scripts/admin/stores/calculator'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useModalStore } from '@/scripts/stores/modal'
import CalculatorTemplateDropdown from '@/scripts/admin/components/dropdowns/CalculatorTemplateDropdown.vue'
import CalculatorTemplateModal from '@/scripts/admin/components/modal-components/CalculatorTemplateModal.vue'

const { t } = useI18n()
const calculatorStore = useCalculatorStore()
const companyStore = useCompanyStore()
const modalStore = useModalStore()

const ROLE_CATEGORIES = ['pre_production', 'production', 'post_production']
const COST_CATEGORIES = ['travel', 'production_expenses']

const groupedTemplates = computed(() => {
  const groups = [...ROLE_CATEGORIES, ...COST_CATEGORIES].map(
    (category) => ({
      category,
      items: calculatorStore.templates.filter(
        (template) => template.category === category
      ),
    })
  )

  return groups
})

const templateColumns = computed(() => {
  return [
    {
      key: 'name',
      label: t('calculator.templates.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'unit_price',
      label: t('calculator.templates.default_price'),
    },
    {
      key: 'is_default',
      label: t('calculator.templates.is_default'),
    },
    {
      key: 'actions',
      label: '',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})

onMounted(() => {
  loadData()
})

async function loadData() {
  await calculatorStore.fetchTemplates({ limit: 'all' })
}

function openTemplateModal() {
  calculatorStore.resetCurrentTemplate()

  modalStore.openModal({
    title: t('calculator.templates.add_new'),
    componentName: 'CalculatorTemplateModal',
    size: 'sm',
  })
}
</script>
