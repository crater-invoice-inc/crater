<template>
  <div
    class="
      grid grid-cols-1 gap-y-5 gap-x-4
      mt-6 mb-8
      lg:grid-cols-12 lg:gap-x-6 lg:gap-y-0
    "
  >
    <BaseCustomerSelectPopup
      v-model="estimateStore.newEstimate.customer"
      :valid="v.customer_id"
      :content-loading="isLoading"
      type="estimate"
      class="lg:col-span-6"
    />

    <div
      class="
        grid grid-cols-1 gap-y-5 gap-x-4
        p-5
        bg-surface
        border border-line-light border-solid
        rounded-2xl
        shadow-card
        sm:grid-cols-2
        lg:col-span-6
        lg:grid-cols-2 lg:self-start
      "
    >
      <BaseInputGroup
        :label="$t('reports.estimates.estimate_date')"
        :content-loading="isLoading"
        required
        :error="v.estimate_date.$error && v.estimate_date.$errors[0].$message"
      >
        <BaseDatePicker
          v-model="estimateStore.newEstimate.estimate_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('estimates.expiry_date')"
        :content-loading="isLoading"
      >
        <BaseDatePicker
          v-model="estimateStore.newEstimate.expiry_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('estimates.estimate_number')"
        :content-loading="isLoading"
        required
        :error="
          v.estimate_number.$error && v.estimate_number.$errors[0].$message
        "
      >
        <BaseInput
          v-model="estimateStore.newEstimate.estimate_number"
          :content-loading="isLoading"
        >
        </BaseInput>
      </BaseInputGroup>

      <!-- <BaseInputGroup
        :label="$t('estimates.ref_number')"
        :content-loading="isLoading"
        :error="
          v.reference_number.$error && v.reference_number.$errors[0].$message
        "
      >
        <BaseInput
          v-model="estimateStore.newEstimate.reference_number"
          :content-loading="isLoading"
          @input="v.reference_number.$touch()"
        >
          <template #left="slotProps">
            <BaseIcon name="HashtagIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup> -->
      <ExchangeRateConverter
        :store="estimateStore"
        store-prop="newEstimate"
        :v="v"
        :is-loading="isLoading"
        :is-edit="isEdit"
        :customer-currency="estimateStore.newEstimate.currency_id"
      />
    </div>
  </div>
</template>

<script setup>
import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import ExchangeRateConverter from '@/scripts/admin/components/estimate-invoice-common/ExchangeRateConverter.vue'

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

const estimateStore = useEstimateStore()
</script>
