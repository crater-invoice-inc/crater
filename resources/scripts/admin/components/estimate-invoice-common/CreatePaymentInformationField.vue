<template>
  <div class="mb-6">
    <label class="text-gray-800 font-medium mb-4 text-sm">
      {{ $t(label) }}
    </label>
    <BaseCustomInput
      v-model="store[storeProp].payment_information"
      :content-loading="store.isFetchingInitialSettings"
      :fields="fields"
      :show-fields-button="showFieldsButton"
      class="mt-1"
    />
  </div>
</template>

<script setup>
defineProps({
  store: {
    type: Object,
    default: null,
  },
  storeProp: {
    type: String,
    default: '',
  },
  fields: {
    type: Object,
    default: null,
  },
  label: {
    type: String,
    default: 'invoices.payment_information',
  },
  showFieldsButton: {
    type: Boolean,
    default: true,
  },
})
</script>
