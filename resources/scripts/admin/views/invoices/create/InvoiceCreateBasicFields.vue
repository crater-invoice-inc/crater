<template>
  <div
    class="
      grid grid-cols-1 gap-y-5 gap-x-4
      mt-6 mb-8
      lg:grid-cols-12 lg:gap-x-6 lg:gap-y-0
    "
  >
    <BaseCustomerSelectPopup
      v-model="invoiceStore.newInvoice.customer"
      :valid="v.customer_id"
      :content-loading="isLoading"
      type="invoice"
      :show-shipping="false"
      class="lg:col-span-4"
    />

    <div
      class="
        grid grid-cols-1 gap-y-5 gap-x-4
        sm:grid-cols-2
        lg:col-span-8
        lg:grid-cols-3 lg:self-start
      "
    >
      <BaseInputGroup
        :label="$t('invoices.invoice_date')"
        :content-loading="isLoading"
        required
        :error="v.invoice_date.$error && v.invoice_date.$errors[0].$message"
      >
        <BaseDatePicker
          v-model="invoiceStore.newInvoice.invoice_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('invoices.due_date')"
        :content-loading="isLoading"
      >
        <BaseDatePicker
          v-model="invoiceStore.newInvoice.due_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('invoices.invoice_number')"
        :content-loading="isLoading"
        :error="v.invoice_number.$error && v.invoice_number.$errors[0].$message"
        required
      >
        <BaseInput
          v-model="invoiceStore.newInvoice.invoice_number"
          :content-loading="isLoading"
          @input="v.invoice_number.$touch()"
        />
      </BaseInputGroup>

      <ExchangeRateConverter
        :store="invoiceStore"
        store-prop="newInvoice"
        :v="v"
        :is-loading="isLoading"
        :is-edit="isEdit"
        :customer-currency="invoiceStore.newInvoice.currency_id"
      />
    </div>
  </div>
</template>

<script setup>
import ExchangeRateConverter from '@/scripts/admin/components/estimate-invoice-common/ExchangeRateConverter.vue'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'

const props = defineProps({
  v: {
    type: Object,
    default: null,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
})

const invoiceStore = useInvoiceStore()
</script>
