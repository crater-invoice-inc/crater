<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'calculators.view'" variant="primary">
        <BaseIcon name="DotsHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="DotsHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- edit calculator  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_CALCULATOR)"
      :to="`/admin/calculators/${row.id}/edit`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- delete calculator  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_CALCULATOR)"
      @click="removeCalculator(row.id)"
    >
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
import { useRoute } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'

const props = defineProps({
  row: {
    type: Object,
    default: null,
  },
  table: {
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
const route = useRoute()
const userStore = useUserStore()

function removeCalculator(id) {
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
        calculatorStore.deleteCalculator(id).then((response) => {
          if (response.data.success) {
            props.loadData && props.loadData()
          }
        })
      }
    })
}
</script>
