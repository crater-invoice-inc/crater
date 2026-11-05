<template>
  <BaseDropdown>
    <template #activator>
      <BaseIcon name="DotsHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- edit template  -->
    <BaseDropdownItem @click="editTemplate(row)">
      <BaseIcon
        name="PencilIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.edit') }}
    </BaseDropdownItem>

    <!-- delete template  -->
    <BaseDropdownItem @click="removeTemplate(row.id)">
      <BaseIcon
        name="TrashIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.delete') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { useDialogStore } from '@/scripts/stores/dialog'
import { useI18n } from 'vue-i18n'
import { useCalculatorStore } from '@/scripts/admin/stores/calculator'
import { useModalStore } from '@/scripts/stores/modal'

const props = defineProps({
  row: {
    type: Object,
    default: null,
  },
  loadData: {
    type: Function,
    default: null,
  },
})

const dialogStore = useDialogStore()
const { t } = useI18n()
const calculatorStore = useCalculatorStore()
const modalStore = useModalStore()

function editTemplate(template) {
  calculatorStore.isTemplateEdit = true
  calculatorStore.currentTemplate = {
    id: template.id,
    name: template.name,
    category: template.category,
    unit_price: template.unit_price / 100,
  }

  modalStore.openModal({
    title: t('calculator.templates.edit'),
    componentName: 'CalculatorTemplateModal',
    size: 'sm',
  })
}

function removeTemplate(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('calculator.templates.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        calculatorStore.deleteTemplate(id)
      }
    })
}
</script>
