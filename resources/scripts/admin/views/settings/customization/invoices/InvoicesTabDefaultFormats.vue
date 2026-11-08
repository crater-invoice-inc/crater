<template>
  <form @submit.prevent="submitForm">
    <h6 class="text-gray-900 text-lg font-medium">
      {{ $t('settings.customization.invoices.default_content') }}
    </h6>
    <p class="mt-1 text-sm text-gray-500 mb-2">
      {{ $t('settings.customization.invoices.default_content_description') }}
    </p>

    <BaseInputGroup
      :label="$t('settings.customization.invoices.default_invoice_email_body')"
      class="mt-6 mb-4"
    >
      <BaseCustomInput
        v-model="formatSettings.invoice_mail_body"
        :fields="invoiceMailFields"
      />
    </BaseInputGroup>

    <BaseInputGroup
      :label="
        $t('settings.customization.invoices.default_invoice_payment_information')
      "
      class="mt-6 mb-4"
    >
      <BaseCustomInput
        v-model="formatSettings.invoice_default_payment_information"
        :fields="invoiceDefaultFields"
        :show-fields-button="false"
      />
    </BaseInputGroup>

    <BaseInputGroup
      :label="
        $t('settings.customization.invoices.default_invoice_terms_and_conditions')
      "
      class="mt-6 mb-4"
    >
      <BaseCustomInput
        v-model="formatSettings.invoice_default_terms_and_conditions"
        :fields="invoiceDefaultFields"
        :show-fields-button="false"
      />
    </BaseInputGroup>

    <BaseButton
      :loading="isSaving"
      :disabled="isSaving"
      variant="primary"
      type="submit"
      class="mt-4"
    >
      <template #left="slotProps">
        <BaseIcon v-if="!isSaving" :class="slotProps.class" name="SaveIcon" />
      </template>
      {{ $t('settings.customization.save') }}
    </BaseButton>
  </form>
</template>

<script setup>
import { ref, reactive, inject } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const companyStore = useCompanyStore()
const utils = inject('utils')

const invoiceMailFields = ref([
  'customer',
  'customerCustom',
  'invoice',
  'invoiceCustom',
  'company',
])

const invoiceDefaultFields = ref([
  'customer',
  'company',
  'customerCustom',
  'invoice',
  'invoiceCustom',
])

let isSaving = ref(false)

const formatSettings = reactive({
  invoice_mail_body: null,
  invoice_default_payment_information: null,
  invoice_default_terms_and_conditions: null,
})

utils.mergeSettings(formatSettings, {
  ...companyStore.selectedCompanySettings,
})

async function submitForm() {
  isSaving.value = true

  let data = {
    settings: {
      ...formatSettings,
    },
  }

  await companyStore.updateCompanySettings({
    data,
    message: 'settings.customization.invoices.invoice_settings_updated',
  })

  isSaving.value = false

  return true
}
</script>
