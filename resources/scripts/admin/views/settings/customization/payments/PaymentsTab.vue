<template>
  <PaymentsTabPaymentNumber />

  <BaseDivider class="my-8" />

  <PaymentsTabDefaultFormats />

  <BaseDivider class="my-8" />

  <PaymentsTabNotes />

  <BaseDivider class="mt-6 mb-2" />

  <ul class="divide-y divide-gray-200">
    <BaseSwitchSection
      v-model="sendAsAttachmentField"
      :title="$t('settings.customization.payments.payment_email_attachment')"
      :description="
        $t(
          'settings.customization.payments.payment_email_attachment_setting_description'
        )
      "
    />
  </ul>
</template>

<script setup>
import { computed, reactive, inject } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import PaymentsTabPaymentNumber from './PaymentsTabPaymentNumber.vue'
import PaymentsTabDefaultFormats from './PaymentsTabDefaultFormats.vue'
import PaymentsTabNotes from './PaymentsTabNotes.vue'

const utils = inject('utils')
const companyStore = useCompanyStore()

const paymentSettings = reactive({
  payment_email_attachment: null,
})

utils.mergeSettings(paymentSettings, {
  ...companyStore.selectedCompanySettings,
})

const sendAsAttachmentField = computed({
  get: () => {
    return paymentSettings.payment_email_attachment === 'YES'
  },
  set: async (newValue) => {
    const value = newValue ? 'YES' : 'NO'

    let data = {
      settings: {
        payment_email_attachment: value,
      },
    }

    paymentSettings.payment_email_attachment = value

    await companyStore.updateCompanySettings({
      data,
      message: 'general.setting_updated',
    })
  },
})
</script>
