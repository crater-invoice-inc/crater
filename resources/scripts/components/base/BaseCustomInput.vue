<template>
  <BaseContentPlaceholders v-if="contentLoading">
    <BaseContentPlaceholdersBox
      :rounded="true"
      class="w-full"
      style="height: 200px"
    />
  </BaseContentPlaceholders>

  <div v-else class="relative">
    <div v-if="showFieldsButton" class="absolute bottom-0 right-0 z-10">
      <BaseDropdown
        :close-on-select="true"
        max-height="220"
        position="top-end"
        width-class="w-92"
        class="mb-2"
      >
        <template #activator>
          <BaseButton type="button" variant="primary-outline" class="mr-4">
            {{ $t('settings.customization.insert_fields') }}
            <template #left="slotProps">
              <BaseIcon name="PlusSmIcon" :class="slotProps.class" />
            </template>
          </BaseButton>
        </template>

        <div class="flex p-2">
          <ul v-for="(type, index) in fieldList" :key="index" class="list-none">
            <li class="mb-1 ml-2 text-xs font-semibold text-gray-500 uppercase">
              {{ type.label }}
            </li>

            <li
              v-for="(field, fieldIndex) in type.fields"
              :key="fieldIndex"
              class="
                w-48
                text-sm
                font-normal
                cursor-pointer
                hover:bg-gray-100
                rounded
                ml-1
                py-0.5
              "
              @click="value += `{${field.value}}`"
            >
              <div class="flex pl-1">
                <BaseIcon
                  name="ChevronDoubleRightIcon"
                  class="h-3 mt-1 mr-2 text-gray-400"
                />

                {{ field.label }}
              </div>
            </li>
          </ul>
        </div>
      </BaseDropdown>
    </div>
    <BaseEditor v-model="value" />
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'

const props = defineProps({
  contentLoading: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: String,
    default: '',
  },
  fields: {
    type: Array,
    default: null,
  },
  showFieldsButton: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['update:modelValue'])

const customFieldsStore = useCustomFieldStore()

let fieldList = ref([])
let invoiceFields = ref([])
let estimateFields = ref([])
let paymentFields = ref([])
let customerFields = ref([])
const position = null

watch(
  () => props.fields,
  (val) => {
    if (props.fields && props.fields.length > 0) {
      getFields()
    }
  }
)

watch(
  () => customFieldsStore.customFields,
  (newValue) => {
    invoiceFields.value = newValue
      ? newValue.filter((field) => field.model_type === 'Invoice')
      : []
    customerFields.value = newValue
      ? newValue.filter((field) => field.model_type === 'Customer')
      : []
    paymentFields.value = newValue
      ? newValue.filter((field) => field.model_type === 'Payment')
      : []
    estimateFields.value = newValue.filter(
      (field) => field.model_type === 'Estimate'
    )
    getFields()
  }
)

onMounted(() => {
  fetchFields()
})

const value = computed({
  get: () => props.modelValue,
  set: (value) => {
    emit('update:modelValue', value)
  },
})

async function fetchFields() {
  await customFieldsStore.fetchCustomFields()
}

async function getFields() {
  fieldList.value = []
  if (props.fields && props.fields.length > 0) {
    if (props.fields.find((field) => field == 'shipping')) {
      fieldList.value.push({
        label: 'Shipping Address',
        fields: [
          { label: 'Address name', value: 'SHIPPING_ADDRESS_NAME' },
          { label: 'Country', value: 'SHIPPING_COUNTRY' },
          { label: 'State', value: 'SHIPPING_STATE' },
          { label: 'City', value: 'SHIPPING_CITY' },
          { label: 'Address Street 1', value: 'SHIPPING_ADDRESS_STREET_1' },
          { label: 'Address Street 2', value: 'SHIPPING_ADDRESS_STREET_2' },
          { label: 'Phone', value: 'SHIPPING_PHONE' },
          { label: 'Zip Code', value: 'SHIPPING_ZIP_CODE' },
        ],
      })
    }

    if (props.fields.find((field) => field == 'billing')) {
      fieldList.value.push({
        label: 'Billing Address',
        fields: [
          { label: 'Address name', value: 'BILLING_ADDRESS_NAME' },
          { label: 'Country', value: 'BILLING_COUNTRY' },
          { label: 'State', value: 'BILLING_STATE' },
          { label: 'City', value: 'BILLING_CITY' },
          { label: 'Address Street 1', value: 'BILLING_ADDRESS_STREET_1' },
          { label: 'Address Street 2', value: 'BILLING_ADDRESS_STREET_2' },
          { label: 'Phone', value: 'BILLING_PHONE' },
          { label: 'Zip Code', value: 'BILLING_ZIP_CODE' },
        ],
      })
    }

    if (props.fields.find((field) => field == 'customer')) {
      fieldList.value.push({
        label: 'Customer',
        fields: [
          { label: 'Display Name', value: 'CONTACT_DISPLAY_NAME' },
          { label: 'Contact Name', value: 'PRIMARY_CONTACT_NAME' },
          { label: 'Email', value: 'CONTACT_EMAIL' },
          { label: 'Phone', value: 'CONTACT_PHONE' },
          { label: 'Website', value: 'CONTACT_WEBSITE' },
          ...customerFields.value.map((i) => ({
            label: i.label,
            value: i.slug,
          })),
        ],
      })
    }

    if (props.fields.find((field) => field == 'invoice')) {
      fieldList.value.push({
        label: 'Invoice',
        fields: [
          { label: 'Date', value: 'INVOICE_DATE' },
          { label: 'Due Date', value: 'INVOICE_DUE_DATE' },
          { label: 'Number', value: 'INVOICE_NUMBER' },
          { label: 'Ref Number', value: 'INVOICE_REF_NUMBER' },
          ...invoiceFields.value.map((i) => ({
            label: i.label,
            value: i.slug,
          })),
        ],
      })
    }

    if (props.fields.find((field) => field == 'estimate')) {
      fieldList.value.push({
        label: 'Estimate',
        fields: [
          { label: 'Date', value: 'ESTIMATE_DATE' },
          { label: 'Expiry Date', value: 'ESTIMATE_EXPIRY_DATE' },
          { label: 'Number', value: 'ESTIMATE_NUMBER' },
          { label: 'Ref Number', value: 'ESTIMATE_REF_NUMBER' },
          ...estimateFields.value.map((i) => ({
            label: i.label,
            value: i.slug,
          })),
        ],
      })
    }

    if (props.fields.find((field) => field == 'payment')) {
      fieldList.value.push({
        label: 'Payment',
        fields: [
          { label: 'Date', value: 'PAYMENT_DATE' },
          { label: 'Number', value: 'PAYMENT_NUMBER' },
          { label: 'Mode', value: 'PAYMENT_MODE' },
          { label: 'Amount', value: 'PAYMENT_AMOUNT' },
          ...paymentFields.value.map((i) => ({
            label: i.label,
            value: i.slug,
          })),
        ],
      })
    }

    if (props.fields.find((field) => field == 'company')) {
      fieldList.value.push({
        label: 'Company',
        fields: [
          { label: 'Company Name', value: 'COMPANY_NAME' },
          { label: 'Country', value: 'COMPANY_COUNTRY' },
          { label: 'State', value: 'COMPANY_STATE' },
          { label: 'City', value: 'COMPANY_CITY' },
          { label: 'Address Street 1', value: 'COMPANY_ADDRESS_STREET_1' },
          { label: 'Address Street 2', value: 'COMPANY_ADDRESS_STREET_2' },
          { label: 'Phone', value: 'COMPANY_PHONE' },
          { label: 'Zip Code', value: 'COMPANY_ZIP_CODE' },
        ],
      })
    }
  }
}

getFields()
</script>
