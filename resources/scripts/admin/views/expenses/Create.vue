<template>
  <CategoryModal />

  <BasePage class="relative">
    <form action="" @submit.prevent="submitForm">
      <!-- Page Header -->
      <BasePageHeader :title="pageTitle" class="mb-5">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem
            :title="$t('general.home')"
            to="/admin/dashboard"
          />

          <BaseBreadcrumbItem
            :title="$tc('expenses.expense', 2)"
            to="/admin/expenses"
          />

          <BaseBreadcrumbItem :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <BaseButton
            v-if="isEdit && expenseStore.currentExpense.attachment_receipt_url"
            :href="receiptDownloadUrl"
            tag="a"
            variant="primary-outline"
            type="button"
            class="mr-2"
          >
            <template #left="slotProps">
              <BaseIcon name="DownloadIcon" :class="slotProps.class" />
            </template>
            {{ $t('expenses.download_receipt') }}
          </BaseButton>

          <div class="hidden md:block">
            <BaseButton
              :loading="isSaving"
              :content-loading="isFetchingInitialData"
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
              {{
                isEdit
                  ? $t('expenses.update_expense')
                  : $t('expenses.save_expense')
              }}
            </BaseButton>
          </div>
        </template>
      </BasePageHeader>

      <BaseCard>
        <BaseInputGrid>
          <BaseInputGroup
            :label="$t('expenses.category')"
            :error="
              v$.currentExpense.expense_category_id.$error &&
              v$.currentExpense.expense_category_id.$errors[0].$message
            "
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseMultiselect
              v-model="expenseStore.currentExpense.expense_category_id"
              :content-loading="isFetchingInitialData"
              value-prop="id"
              label="name"
              track-by="id"
              :options="searchCategory"
              v-if="!isFetchingInitialData"
              :filter-results="false"
              resolve-on-load
              :delay="500"
              searchable
              :invalid="v$.currentExpense.expense_category_id.$error"
              :placeholder="$t('expenses.categories.select_a_category')"
              @input="v$.currentExpense.expense_category_id.$touch()"
            >
              <template #action>
                <BaseSelectAction @click="openCategoryModal">
                  <BaseIcon
                    name="PlusIcon"
                    class="h-4 mr-2 -ml-2 text-center text-primary-400"
                  />
                  {{ $t('settings.expense_category.add_new_category') }}
                </BaseSelectAction>
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('expenses.expense_date')"
            :error="
              v$.currentExpense.expense_date.$error &&
              v$.currentExpense.expense_date.$errors[0].$message
            "
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseDatePicker
              v-model="expenseStore.currentExpense.expense_date"
              :content-loading="isFetchingInitialData"
              :calendar-button="true"
              :invalid="v$.currentExpense.expense_date.$error"
              @input="v$.currentExpense.expense_date.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('expenses.due_date')"
            :error="
              v$.currentExpense.due_date.$error &&
              v$.currentExpense.due_date.$errors[0].$message
            "
            :content-loading="isFetchingInitialData"
          >
            <BaseDatePicker
              v-model="expenseStore.currentExpense.due_date"
              :content-loading="isFetchingInitialData"
              :calendar-button="true"
              :invalid="v$.currentExpense.due_date.$error"
              @input="v$.currentExpense.due_date.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('expenses.paid_at')"
            :content-loading="isFetchingInitialData"
          >
            <BaseDatePicker
              v-model="expenseStore.currentExpense.paid_at"
              :content-loading="isFetchingInitialData"
              :calendar-button="true"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('expenses.amount')"
            :error="
              v$.currentExpense.amount.$error &&
              v$.currentExpense.amount.$errors[0].$message
            "
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseMoney
              :key="expenseStore.currentExpense.selectedCurrency"
              v-model="amountData"
              class="focus:border focus:border-solid focus:border-primary-500"
              :invalid="v$.currentExpense.amount.$error"
              :currency="expenseStore.currentExpense.selectedCurrency"
              @input="v$.currentExpense.amount.$touch()"
            />
          </BaseInputGroup>
          <BaseInputGroup
            :label="$t('expenses.currency')"
            :content-loading="isFetchingInitialData"
            :error="
              v$.currentExpense.currency_id.$error &&
              v$.currentExpense.currency_id.$errors[0].$message
            "
            required
          >
            <BaseMultiselect
              v-model="expenseStore.currentExpense.currency_id"
              value-prop="id"
              label="name"
              track-by="name"
              :content-loading="isFetchingInitialData"
              :options="globalStore.currencies"
              searchable
              :can-deselect="false"
              :placeholder="$t('customers.select_currency')"
              :invalid="v$.currentExpense.currency_id.$error"
              class="w-full"
              @update:modelValue="onCurrencyChange"
            >
            </BaseMultiselect>
          </BaseInputGroup>

          <!-- Exchange rate converter -->
          <ExchangeRateConverter
            :store="expenseStore"
            store-prop="currentExpense"
            :v="v$.currentExpense"
            :is-loading="isFetchingInitialData"
            :is-edit="isEdit"
            :customer-currency="expenseStore.currentExpense.currency_id"
          />

          <BaseInputGroup
            :content-loading="isFetchingInitialData"
            :label="$t('expenses.customer')"
          >
            <BaseMultiselect
              v-model="expenseStore.currentExpense.customer_id"
              :content-loading="isFetchingInitialData"
              value-prop="id"
              label="name"
              track-by="id"
              :options="searchCustomer"
              v-if="!isFetchingInitialData"
              :filter-results="false"
              resolve-on-load
              :delay="500"
              searchable
              :placeholder="$t('customers.select_a_customer')"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :content-loading="isFetchingInitialData"
            :label="$t('payments.payment_mode')"
          >
            <BaseMultiselect
              v-model="expenseStore.currentExpense.payment_method_id"
              :content-loading="isFetchingInitialData"
              label="name"
              value-prop="id"
              track-by="name"
              :options="expenseStore.paymentModes"
              :placeholder="$t('payments.select_payment_mode')"
              searchable
            >
              <!-- <template #action>
                <BaseSelectAction @click="addPaymentMode">
                  <BaseIcon
                    name="PlusIcon"
                    class="h-4 mr-2 -ml-2 text-center text-primary-400"
                  />
                  {{ $t('settings.payment_modes.add_payment_mode') }}
                </BaseSelectAction>
              </template> -->
            </BaseMultiselect>
          </BaseInputGroup>

          <BaseInputGroup
            :content-loading="isFetchingInitialData"
            :label="$t('expenses.note')"
            :error="
              v$.currentExpense.notes.$error &&
              v$.currentExpense.notes.$errors[0].$message
            "
          >
            <BaseTextarea
              v-model="expenseStore.currentExpense.notes"
              :content-loading="isFetchingInitialData"
              :row="4"
              rows="4"
              @input="v$.currentExpense.notes.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('expenses.receipt')">
            <BaseFileUploader
              v-model="expenseStore.currentExpense.receiptFiles"
              accept="image/*,.doc,.docx,.pdf,.csv,.xlsx,.xls"
              @change="onFileInputChange"
              @remove="onFileInputRemove"
            />
          </BaseInputGroup>

          <!-- Expense Custom Fields -->
          <ExpenseCustomFields
            :is-edit="isEdit"
            class="col-span-2"
            :is-loading="isFetchingInitialData"
            type="Expense"
            :store="expenseStore"
            store-prop="currentExpense"
            :custom-field-scope="expenseValidationScope"
          />

          <div class="block md:hidden">
            <BaseButton
              :loading="isSaving"
              :tabindex="6"
              variant="primary"
              type="submit"
              class="flex justify-center w-full"
            >
              <template #left="slotProps">
                <BaseIcon
                  v-if="!isSaving"
                  name="SaveIcon"
                  :class="slotProps.class"
                />
              </template>
              {{
                isEdit
                  ? $t('expenses.update_expense')
                  : $t('expenses.save_expense')
              }}
            </BaseButton>
          </div>
        </BaseInputGrid>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  required,
  minValue,
  maxLength,
  helpers,
  requiredIf,
  decimal,
} from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useExpenseStore } from '@/scripts/admin/stores/expense'
import { useCategoryStore } from '@/scripts/admin/stores/category'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'
import { useModalStore } from '@/scripts/stores/modal'
import ExpenseCustomFields from '@/scripts/admin/components/custom-fields/CreateCustomFields.vue'
import CategoryModal from '@/scripts/admin/components/modal-components/CategoryModal.vue'
import ExchangeRateConverter from '@/scripts/admin/components/estimate-invoice-common/ExchangeRateConverter.vue'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const customerStore = useCustomerStore()
const companyStore = useCompanyStore()
const expenseStore = useExpenseStore()
const categoryStore = useCategoryStore()
const customFieldStore = useCustomFieldStore()
const modalStore = useModalStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const globalStore = useGlobalStore()

let isSaving = ref(false)
let isFetchingInitialData = ref(false)
const expenseValidationScope = 'newExpense'
const isAttachmentReceiptRemoved = ref(false)

const rules = computed(() => {
  return {
    currentExpense: {
      expense_category_id: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      expense_date: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      due_date: {
        afterExpenseDate: helpers.withMessage(
          t('validation.due_date_after_expense_date'),
          (value) =>
            !value ||
            !expenseStore.currentExpense.expense_date ||
            new Date(value) >= new Date(expenseStore.currentExpense.expense_date)
        ),
      },

      amount: {
        required: helpers.withMessage(t('validation.required'), required),
        minValue: helpers.withMessage(
          t('validation.price_minvalue'),
          minValue(0.1)
        ),
        maxLength: helpers.withMessage(
          t('validation.price_maxlength'),
          maxLength(20)
        ),
      },

      notes: {
        maxLength: helpers.withMessage(
          t('validation.description_maxlength'),
          maxLength(65000)
        ),
      },
      currency_id: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      exchange_rate: {
        required: requiredIf(function () {
          helpers.withMessage(t('validation.required'), required)
          return expenseStore.showExchangeRate
        }),
        decimal: helpers.withMessage(
          t('validation.valid_exchange_rate'),
          decimal
        ),
      },
    },
  }
})

const v$ = useVuelidate(rules, expenseStore, {
  $scope: expenseValidationScope,
})

const amountData = computed({
  get: () => expenseStore.currentExpense.amount / 100,
  set: (value) => {
    expenseStore.currentExpense.amount = Math.round(value * 100)
  },
})

const isEdit = computed(() => route.name === 'expenses.edit')

const pageTitle = computed(() =>
  isEdit.value ? t('expenses.edit_expense') : t('expenses.new_expense')
)

const receiptDownloadUrl = computed(() =>
  isEdit.value ? `/reports/expenses/${route.params.id}/download-receipt` : ''
)

expenseStore.resetCurrentExpenseData()
customFieldStore.resetCustomFields()

loadData()

function onFileInputChange(fileName, file) {
  expenseStore.currentExpense.attachment_receipt = file
}

function onFileInputRemove() {
  expenseStore.currentExpense.attachment_receipt = null
  isAttachmentReceiptRemoved.value = true
}

function openCategoryModal() {
  modalStore.openModal({
    title: t('settings.expense_category.add_category'),
    componentName: 'CategoryModal',
    size: 'sm',
  })
}

function onCurrencyChange(v) {
  expenseStore.currentExpense.selectedCurrency = globalStore.currencies.find(
    (c) => c.id === v
  )
}

async function searchCategory(search) {
  let res = await categoryStore.fetchCategories({ search })
  if(res.data.data.length>0 && categoryStore.editCategory) {
    let categoryFound = res.data.data.find((c) => c.id==categoryStore.editCategory.id)
    if(!categoryFound) {
      let edit_category = Object.assign({}, categoryStore.editCategory)
      res.data.data.unshift(edit_category)
    }
  }
  return res.data.data
}

async function searchCustomer(search) {
  let res = await customerStore.fetchCustomers({ search })
  if(res.data.data.length>0 && customerStore.editCustomer) {
    let customerFound = res.data.data.find((c) => c.id==customerStore.editCustomer.id)
    if(!customerFound) {
      let edit_customer = Object.assign({}, customerStore.editCustomer)
      res.data.data.unshift(edit_customer)
    }
  }
  return res.data.data
}

async function loadData() {
  if (!isEdit.value) {
    expenseStore.currentExpense.currency_id =
      companyStore.selectedCompanyCurrency.id
    expenseStore.currentExpense.selectedCurrency =
      companyStore.selectedCompanyCurrency
  }

  isFetchingInitialData.value = true
  await expenseStore.fetchPaymentModes({ limit: 'all' })

  if (isEdit.value) {
    const expenseData = await expenseStore.fetchExpense(route.params.id)

    expenseStore.currentExpense.currency_id =
      expenseStore.currentExpense.selectedCurrency.id

    if(expenseData.data) {
      if(!categoryStore.editCategory && expenseData.data.data.expense_category) {
        categoryStore.editCategory = expenseData.data.data.expense_category
      }

      if(!customerStore.editCustomer && expenseData.data.data.customer) {
        customerStore.editCustomer = expenseData.data.data.customer
      }
    }

  } else if (route.query.customer) {
    expenseStore.currentExpense.customer_id = route.query.customer
  }

  isFetchingInitialData.value = false
}

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  isSaving.value = true

  let formData = expenseStore.currentExpense

  try {
    if (isEdit.value) {
      await expenseStore.updateExpense({
        id: route.params.id,
        data: formData,
        isAttachmentReceiptRemoved: isAttachmentReceiptRemoved.value
      })
    } else {
      await expenseStore.addExpense(formData)
    }
    isSaving.value = false
    expenseStore.currentExpense.attachment_receipt = null
    isAttachmentReceiptRemoved.value = false
    router.push('/admin/expenses')
  } catch (err) {
    console.error(err)
    isSaving.value = false
    return
  }
}

onBeforeUnmount(() => {
  expenseStore.resetCurrentExpenseData()
  customerStore.editCustomer = null
  categoryStore.editCategory = null
})
</script>
