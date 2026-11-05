<template>
  <BaseModal
    :show="modalStore.active && modalStore.componentName === 'CalculatorTemplateModal'"
    @close="closeTemplateModal"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalStore.title }}
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
            :label="$t('calculator.templates.category')"
            required
          >
            <BaseMultiselect
              v-model="calculatorStore.currentTemplate.category"
              :options="categoryOptions"
              label="label"
              value-prop="value"
              :placeholder="$t('calculator.templates.select_category')"
              :can-deselect="false"
              track-by="value"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('calculator.templates.default_price')"
          >
            <BaseMoney v-model="calculatorStore.currentTemplate.unit_price" />
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

const categoryOptions = [
  { value: 'pre_production', label: t('calculator.categories.pre_production') },
  { value: 'production', label: t('calculator.categories.production') },
  { value: 'post_production', label: t('calculator.categories.post_production') },
  { value: 'travel', label: t('calculator.categories.travel') },
  { value: 'production_expenses', label: t('calculator.categories.production_expenses') },
]

const rules = computed(() => {
  return {
    currentTemplate: {
      name: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      category: {
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
