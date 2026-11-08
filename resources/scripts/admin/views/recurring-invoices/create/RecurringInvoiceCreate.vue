<template>
  <SelectTemplateModal />
  <ItemModal />
  <TaxTypeModal />
  <SalesTax
    v-if="salesTaxEnabled && !isLoadingContent"
    :store="recurringInvoiceStore"
    store-prop="newRecurringInvoice"
    :is-edit="isEdit"
    :customer="recurringInvoiceStore.newRecurringInvoice.customer"
  />
  <BasePage class="relative invoice-create-page">
    <form @submit.prevent="submitForm">
      <BasePageHeader :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem
            :title="$t('general.home')"
            to="/admin/dashboard"
          />
          <BaseBreadcrumbItem
            :title="$t('recurring_invoices.title', 2)"
            to="/admin/recurring-invoices"
          />
          <BaseBreadcrumbItem
            v-if="$route.name === 'invoices.edit'"
            :title="$t('recurring_invoices.edit_invoice')"
            to="#"
            active
          />
          <BaseBreadcrumbItem v-else :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <router-link
            :to="`/invoices/pdf/${recurringInvoiceStore.newRecurringInvoice.unique_hash}`"
          >
            <BaseButton
              v-if="$route.name === 'invoices.edit'"
              target="_blank"
              class="mr-3"
              variant="primary-outline"
              type="button"
            >
              <span class="flex">
                {{ $t('general.view_pdf') }}
              </span>
            </BaseButton>
          </router-link>

          <BaseButton
            :loading="isSaving"
            :disabled="isSaving"
            variant="primary"
            type="submit"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isSaving"
                name="SaveIcon"
                :class="slotProps.class"
              />
            </template>
            {{ $t('recurring_invoices.save_invoice') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <!-- Select Customer & Basic Fields  -->
      <div class="grid-cols-12 gap-8 mt-6 mb-8 lg:grid">
        <InvoiceBasicFields
          :v="v$"
          :is-loading="isLoadingContent"
          :is-edit="isEdit"
        />
      </div>

      <BaseScrollPane>
        <!-- Invoice Items -->
        <CreateItems
          :currency="recurringInvoiceStore.newRecurringInvoice.currency"
          :is-loading="isLoadingContent"
          :item-validation-scope="recurringInvoiceValidationScope"
          :store="recurringInvoiceStore"
          store-prop="newRecurringInvoice"
        />

        <!-- Invoice Templates -->
        <div
          class="
            block
            mt-10
            invoice-foot
            lg:flex lg:justify-between lg:items-start
          "
        >
          <div class="w-full relative lg:w-1/2">
            <!-- Invoice Custom Notes -->
            <NoteFields
              :store="recurringInvoiceStore"
              store-prop="newRecurringInvoice"
              :fields="recurringInvoiceFields"
              type="Invoice"
              :show-note-popup="false"
            />

            <!-- Invoice Custom Fields -->
            <InvoiceCustomFields
              type="Invoice"
              :is-edit="isEdit"
              :is-loading="isLoadingContent"
              :store="recurringInvoiceStore"
              store-prop="newRecurringInvoice"
              :custom-field-scope="recurringInvoiceValidationScope"
              class="mb-6"
            />

            <!-- Invoice Template Button-->
            <SelectTemplateButton
              :store="recurringInvoiceStore"
              store-prop="newRecurringInvoice"
            />
          </div>

          <!-- Invoice Total Card -->
          <CreateTotal
            :currency="recurringInvoiceStore.newRecurringInvoice.currency"
            :is-loading="isLoadingContent"
            :store="recurringInvoiceStore"
            store-prop="newRecurringInvoice"
            tax-popup-type="invoice"
          />
        </div>
      </BaseScrollPane>
    </form>
  </BasePage>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useModalStore } from '@/scripts/stores/modal'
import CreateItems from '@/scripts/admin/components/estimate-invoice-common/CreateItems.vue'
import CreateTotal from '@/scripts/admin/components/estimate-invoice-common/CreateTotal.vue'
import SelectTemplateButton from '@/scripts/admin/components/estimate-invoice-common/SelectTemplateButton.vue'
import InvoiceBasicFields from './RecurringInvoiceCreateBasicFields.vue'
import InvoiceCustomFields from '@/scripts/admin/components/custom-fields/CreateCustomFields.vue'
import NoteFields from '@/scripts/admin/components/estimate-invoice-common/CreateNotesField.vue'
import SalesTax from '@/scripts/admin/components/estimate-invoice-common/SalesTax.vue'

import {
  required,
  maxLength,
  numeric,
  helpers,
  requiredIf,
  decimal,
} from '@vuelidate/validators'

import useVuelidate from '@vuelidate/core'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'
import { useRecurringInvoiceStore } from '@/scripts/admin/stores/recurring-invoice'
import { useModuleStore } from '@/scripts/admin/stores/module'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import SelectTemplateModal from '@/scripts/admin/components/modal-components/SelectTemplateModal.vue'
import TaxTypeModal from '@/scripts/admin/components/modal-components/TaxTypeModal.vue'
import ItemModal from '@/scripts/admin/components/modal-components/ItemModal.vue'

const recurringInvoiceStore = useRecurringInvoiceStore()
const companyStore = useCompanyStore()
const customFieldStore = useCustomFieldStore()
const moduleStore = useModuleStore()
const modalStore = useModalStore()
const customerStore = useCustomerStore()
const recurringInvoiceValidationScope = 'newRecurringInvoice'
const notificationStore = useNotificationStore()
const { t } = useI18n()
let isSaving = ref(false)

const recurringInvoiceFields = ref([
  'customer',
  'company',
  'customerCustom',
  'invoice',
  'invoiceCustom',
])

let route = useRoute()
let router = useRouter()

let isLoadingContent = computed(
  () =>
    recurringInvoiceStore.isFetchingInvoice ||
    recurringInvoiceStore.isFetchingInitialSettings
)

let pageTitle = computed(() =>
  isEdit.value
    ? t('recurring_invoices.edit_invoice')
    : t('recurring_invoices.new_invoice')
)

let isEdit = computed(() => route.name === 'recurring-invoices.edit')

const salesTaxEnabled = computed(() => {
  return (
    companyStore.selectedCompanySettings.sales_tax_us_enabled === 'YES' &&
    moduleStore.salesTaxUSEnabled
  )
})

const rules = {
  starts_at: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  status: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  frequency: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  limit_by: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  limit_date: {
    required: helpers.withMessage(
      t('validation.required'),
      requiredIf(function () {
        return recurringInvoiceStore.newRecurringInvoice.limit_by === 'DATE'
      })
    ),
  },
  limit_count: {
    required: helpers.withMessage(
      t('validation.required'),
      requiredIf(function () {
        return recurringInvoiceStore.newRecurringInvoice.limit_by === 'COUNT'
      })
    ),
  },
  selectedFrequency: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  customer_id: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  exchange_rate: {
    required: requiredIf(function () {
      helpers.withMessage(t('validation.required'), required)
      return recurringInvoiceStore.showExchangeRate
    }),
    decimal: helpers.withMessage(t('validation.valid_exchange_rate'), decimal),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => recurringInvoiceStore.newRecurringInvoice),
  { $scope: recurringInvoiceValidationScope }
)

recurringInvoiceStore.resetCurrentRecurringInvoice()
recurringInvoiceStore.fetchRecurringInvoiceInitialSettings(isEdit.value)
customFieldStore.resetCustomFields()
v$.value.$reset

watch(
  () => recurringInvoiceStore.newRecurringInvoice.customer,
  (newVal) => {
    if (newVal && newVal.currency) {
      recurringInvoiceStore.newRecurringInvoice.currency = newVal.currency
    } else {
      recurringInvoiceStore.newRecurringInvoice.currency =
        companyStore.selectedCompanyCurrency
    }
  }
)

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return false
  }

  isSaving.value = true

  let data = {
    ...recurringInvoiceStore.newRecurringInvoice,
    sub_total: recurringInvoiceStore.getSubTotal,
    total: recurringInvoiceStore.getTotal,
    tax: recurringInvoiceStore.getTotalTax,
  }

  if (data.customer && !data.customer.email && data.send_automatically) {
    notificationStore.showNotification({
      type: 'error',
      message: t('recurring_invoices.add_customer_email'),
    })
    editCustomer()
    isSaving.value = false
    return
  }

  if (route.params.id) {
    recurringInvoiceStore
      .updateRecurringInvoice(data)
      .then((res) => {
        if (res.data.data) {
          router.push(`/admin/recurring-invoices/${res.data.data.id}/view`)
        }
        isSaving.value = false
      })
      .catch((err) => {
        isSaving.value = false
      })
  } else {
    submitCreate(data)
  }
}

async function editCustomer() {
  let selectedCustomer = recurringInvoiceStore.newRecurringInvoice.customer.id

  await customerStore.fetchCustomer(selectedCustomer)
  modalStore.openModal({
    title: t('customers.edit_customer'),
    componentName: 'CustomerModal',
  })
}

function submitCreate(data) {
  recurringInvoiceStore
    .addRecurringInvoice(data)
    .then((res) => {
      if (res.data.data) {
        router.push(`/admin/recurring-invoices/${res.data.data.id}/view`)
      }
      isSaving.value = false
    })
    .catch((err) => {
      isSaving.value = false
    })
}

function checkValid() {
  return false
}
</script>
