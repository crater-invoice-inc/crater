<template>
  <BaseModal
    :show="modalStore.active && modalStore.componentName === 'CalculatorTemplateModal'"
    @close="closeTemplateModal"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalTitle }}
        <BaseIcon
          name="XIcon"
          class="h-6 w-6 text-gray-500 cursor-pointer"
          @click="closeTemplateModal"
        />
      </div>
    </template>

    <form action="" @submit.prevent="submitTemplateData">
      <div class="p-4 sm:p-6">
        <BaseInputGrid layout="one-column">
          <BaseInputGroup
            :label="$t('calculator.templates.name')"
            required
            :error="
              v$.currentTemplate.name.$error &&
              v$.currentTemplate.name.$errors[0].$message
            "
          >
            <BaseInput
              v-model="calculatorStore.currentTemplate.name"
              :invalid="v$.currentTemplate.name.$error"
              type="text"
              @input="v$.currentTemplate.name.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('calculator.templates.default_price')"
          >
            <BaseMoney v-model="calculatorStore.currentTemplate.unit_price" />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('calculator.templates.is_default')"
            variant="horizontal"
            class="flex flex-row-reverse"
          >
            <BaseSwitch
              v-model="calculatorStore.currentTemplate.is_default"
              class="flex items-center"
            />
          </BaseInputGroup>
        </BaseInputGrid>
      </div>

      <div
        class="z-0 flex justify-end p-4 border-t border-solid border-modal-bg"
      >
        <BaseButton
          class="mr-3 text-sm"
          variant="primary-outline"
          type="button"
          @click="closeTemplateModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>

        <BaseButton
          :loading="isSaving"
          :disabled="isSaving"
          variant="primary"
          type="submit"
        >
          <template #left="slotProps">
            <BaseIcon v-if="!isSaving" name="SaveIcon" :class="slotProps.class" />
          </template>
          {{ calculatorStore.isTemplateEdit ? $t('general.update') : $t('general.save') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { required, helpers } from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'
import { useCalculatorStore } from '@/scripts/admin/stores/calculator'
import { useModalStore } from '@/scripts/stores/modal'

const { t } = useI18n()
const calculatorStore = useCalculatorStore()
const modalStore = useModalStore()

let isSaving = ref(false)

// the category is set by the opener (per-list add button or row edit) and
// never changed inside the modal; the title reflects what is being added
const TEMPLATE_TITLES = {
  pre_production: {
    add: 'calculator.templates.add_role',
    edit: 'calculator.templates.edit_role',
  },
  production: {
    add: 'calculator.templates.add_role',
    edit: 'calculator.templates.edit_role',
  },
  post_production: {
    add: 'calculator.templates.add_role',
    edit: 'calculator.templates.edit_role',
  },
  production_expenses: {
    add: 'calculator.templates.add_expense',
    edit: 'calculator.templates.edit_expense',
  },
  equipment: {
    add: 'calculator.templates.add_equipment',
    edit: 'calculator.templates.edit_equipment',
  },
  travel: {
    add: 'calculator.templates.add_fee',
    edit: 'calculator.templates.edit_fee',
  },
}

const modalTitle = computed(() => {
  const titles = TEMPLATE_TITLES[calculatorStore.currentTemplate.category] || {
    add: 'calculator.templates.add_new',
    edit: 'calculator.templates.edit',
  }

  return t(
    calculatorStore.isTemplateEdit ? titles.edit : titles.add
  )
})

const rules = computed(() => {
  return {
    currentTemplate: {
      name: {
        required: helpers.withMessage(t('validation.required'), required),
      },
    },
  }
})

const v$ = useVuelidate(
  rules,
  computed(() => calculatorStore)
)

async function submitTemplateData() {
  v$.value.currentTemplate.$touch()

  if (v$.value.currentTemplate.$invalid) {
    return true
  }

  isSaving.value = true

  try {
    const data = {
      id: calculatorStore.currentTemplate.id,
      name: calculatorStore.currentTemplate.name,
      category: calculatorStore.currentTemplate.category,
      unit_price: Math.round((calculatorStore.currentTemplate.unit_price || 0) * 100),
      is_default: calculatorStore.currentTemplate.is_default ? 1 : 0,
    }

    const action = calculatorStore.isTemplateEdit
      ? calculatorStore.updateTemplate
      : calculatorStore.addTemplate

    await action(data)
    isSaving.value = false
    closeTemplateModal()
  } catch (err) {
    isSaving.value = false
    return true
  }
}

function closeTemplateModal() {
  modalStore.closeModal()
  setTimeout(() => {
    calculatorStore.resetCurrentTemplate()
    modalStore.$reset()
    v$.value.$reset()
  }, 300)
}
</script>
