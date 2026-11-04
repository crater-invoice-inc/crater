<template>
  <BasePage class="xl:pl-96">
    <BasePageHeader :title="pageTitle.invoice_number">
      <template #actions>
        <BaseButton
          :disabled="isSendingEmail"
          variant="primary-outline"
          class="mr-2"
          tag="a"
          :href="`/invoices/pdf/${invoice.unique_hash}`"
          download
        >
          <template #left="slotProps">
            <BaseIcon name="DownloadIcon" :class="slotProps.class" />
            {{ $t('invoices.download') }}
          </template>
        </BaseButton>

        <BaseButton
          v-if="
            invoiceStore?.selectedViewInvoice?.paid_status !== 'PAID' &&
            globalStore.enabledModules.includes('Payments')
          "
          variant="primary"
          @click="payInvoice"
        >
          {{ $t('invoices.pay_invoice') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Sidebar -->
    <div
      class="fixed top-0 left-0 hidden h-full pt-16 pb-4 bg-white w-88 xl:block"
    >
      <div
        class="
          flex
          items-center
          justify-between
          px-4
          pt-8
          pb-6
          border border-gray-200 border-solid
        "
      >
        <BaseInput
          v-model="searchData.invoice_number"
          :placeholder="$t('general.search')"
          type="text"
          variant="gray"
          @input="onSearch"
        >
          <template #right>
            <BaseIcon name="SearchIcon" class="h-5 text-gray-400" />
          </template>
        </BaseInput>

        <div class="flex ml-3" role="group" aria-label="First group">
          <BaseDropdown
            position="bottom-start"
            width-class="w-50"
            position-class="left-0"
          >
            <template #activator>
              <BaseButton variant="gray">
                <BaseIcon name="FilterIcon" class="h-5" />
              </BaseButton>
            </template>

            <div
              class="
                px-4
                py-1
                pb-2
                mb-2
                text-sm
                border-b border-gray-200 border-solid
              "
            >
              {{ $t('general.sort_by') }}
            </div>

            <div class="px-2">
              <BaseDropdownItem class="pt-3 rounded-md hover:rounded-md">
                <BaseInputGroup class="-mt-3 font-normal">
                  <BaseRadio
                    id="filter_invoice_date"
                    v-model="searchData.orderByField"
                    :label="$t('invoices.invoice_date')"
                    name="filter"
                    size="sm"
                    value="invoice_date"
                    @update:modelValue="onSearch"
                  />
                </BaseInputGroup>
              </BaseDropdownItem>
            </div>

            <div class="px-2">
              <BaseDropdownItem class="pt-3 rounded-md hover:rounded-md">
                <BaseInputGroup class="-mt-3 font-normal">
                  <BaseRadio
                    id="filter_due_date"
                    v-model="searchData.orderByField"
                    :label="$t('invoices.due_date')"
                    name="filter"
                    size="sm"
                    value="due_date"
                    @update:modelValue="onSearch"
                  />
                </BaseInputGroup>
              </BaseDropdownItem>
            </div>

            <div class="px-2">
              <BaseDropdownItem class="pt-3 rounded-md hover:rounded-md">
                <BaseInputGroup class="-mt-3 font-normal">
                  <BaseRadio
                    id="filter_invoice_number"
                    v-model="searchData.orderByField"
                    :label="$t('invoices.invoice_number')"
                    size="sm"
                    name="filter"
                    value="invoice_number"
                    @update:modelValue="onSearch"
                  />
                </BaseInputGroup>
              </BaseDropdownItem>
            </div>
          </BaseDropdown>

          <BaseButton class="ml-1" variant="white" @click="sortData">
            <BaseIcon v-if="getOrderBy" name="SortAscendingIcon" class="h-5" />
            <BaseIcon v-else name="SortDescendingIcon" class="h-5" />
          </BaseButton>
        </div>
      </div>

      <div
        class="
          h-full
          pb-32
          overflow-y-scroll
          border-l border-gray-200 border-solid
          sw-scroll
        "
      >
        <router-link
          v-for="(invoice, index) in invoiceStore.invoices"
          :id="'invoice-' + invoice.id"
          :key="index"
          :to="`/${globalStore.companySlug}/customer/invoices/${invoice.id}/view`"
          :class="[
            'flex justify-between p-4 items-center cursor-pointer hover:bg-gray-100 border-l-4 border-transparent',
            {
              'bg-gray-100 border-l-4 border-primary-500 border-solid':
                hasActiveUrl(invoice.id),
            },
          ]"
          style="border-bottom: 1px solid rgba(185, 193, 209, 0.41)"
        >
          <div class="flex-2">
            <div
              class="
                mb-1
                not-italic
                font-medium
                leading-5
                text-gray-500
                capitalize
                text-md
              "
            >
              {{ invoice.invoice_number }}
            </div>
            <BaseInvoiceStatusBadge :status="invoice.status">
              {{ $t(`invoices.${invoice.status.toLowerCase()}`) }}
            </BaseInvoiceStatusBadge>
          </div>

          <div class="flex-1 whitespace-nowrap right">
            <BaseFormatMoney
              class="
                mb-2
                text-xl
                not-italic
                font-semibold
                leading-8
                text-right text-gray-900
                block
              "
              :amount="invoice.total"
              :currency="invoice.currency"
            />

            <div class="text-sm text-right text-gray-500 non-italic">
              {{ invoice.formatted_invoice_date }}
            </div>
          </div>
        </router-link>

        <p
          v-if="!invoiceStore.invoices.length"
          class="flex justify-center px-4 mt-5 text-sm text-gray-600"
        >
          {{ $t('invoices.no_matching_invoices') }}
        </p>
      </div>
    </div>

    <!-- pdf -->
    <div
      class="flex flex-col min-h-0 mt-8 overflow-hidden"
      style="height: 75vh"
    >
      <iframe
        v-if="shareableLink"
        ref="report"
        :src="shareableLink"
        class="flex-1 border border-gray-400 border-solid rounded-md"
        @click="ViewReportsPDF"
      />
    </div>
  </BasePage>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import BaseDropdown from '@/scripts/components/base/BaseDropdown.vue'
import BaseDropdownItem from '@/scripts/components/base/BaseDropdownItem.vue'
import { debounce } from 'lodash'
import { ref, reactive, computed, inject, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import moment from 'moment'
import { useInvoiceStore } from '@/scripts/customer/stores/invoice'
import { useGlobalStore } from '@/scripts/customer/stores/global'

// Router
const route = useRoute()
//store

const invoiceStore = useInvoiceStore()
const globalStore = useGlobalStore()
const { tm } = useI18n()

//local state
let invoice = reactive({})
let searchData = reactive({
  orderBy: '',
  orderByField: '',
  invoice_number: '',
  // searchText: '',
})

let url = ref(null)
let siteURL = ref(null)
let isSearching = ref(false)
let isSendingEmail = ref(false)
let isMarkingAsSent = ref(false)

//Utils
const utils = inject('utils')

//Store

const notificationStore = useNotificationStore()

// Computed Props

const pageTitle = computed(() => {
  return invoiceStore.selectedViewInvoice
})

const getOrderBy = computed(() => {
  if (searchData.orderBy === 'asc' || searchData.orderBy == null) {
    return true
  }
  return false
})

const getOrderName = computed(() =>
  getOrderBy.value ? tm('general.ascending') : tm('general.descending')
)

const shareableLink = computed(() => {
  return invoice.unique_hash ? `/invoices/pdf/${invoice.unique_hash}` : false
})

// Watcher

watch(route, () => {
  loadInvoice()
})

// Created

loadInvoices()
loadInvoice()

onSearch = debounce(onSearch, 500)

// Methods

function hasActiveUrl(id) {
  return route.params.id == id
}

async function loadInvoices() {
  await invoiceStore.fetchInvoices(
    {
      limit: 'all',
    },
    globalStore.companySlug
  )

  setTimeout(() => {
    scrollToInvoice()
  }, 500)
}

async function loadInvoice() {
  if (route && route.params.id) {
    let response = await invoiceStore.fetchViewInvoice(
      {
        id: route.params.id,
      },
      globalStore.companySlug
    )

    if (response.data) {
      Object.assign(invoice, response.data.data)
    }
  }
}

function scrollToInvoice() {
  const el = document.getElementById(`invoice-${route.params.id}`)

  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
    el.classList.add('shake')
  }
}

async function onSearch() {
  let data = {}

  if (
    searchData.invoice_number !== '' &&
    searchData.invoice_number !== null &&
    searchData.invoice_number !== undefined
  ) {
    data.invoice_number = searchData.invoice_number
  }

  if (searchData.orderBy !== null && searchData.orderBy !== undefined) {
    data.orderBy = searchData.orderBy
  }

  if (
    searchData.orderByField !== null &&
    searchData.orderByField !== undefined
  ) {
    data.orderByField = searchData.orderByField
  }

  isSearching.value = true
  try {
    let response = await invoiceStore.searchInvoice(
      data,
      globalStore.companySlug
    )
    isSearching.value = false

    if (response.data.data) {
      invoiceStore.invoices = response.data.data
    }
  } catch (error) {
    isSearching.value = false
  }
}

function sortData() {
  if (searchData.orderBy === 'asc') {
    searchData.orderBy = 'desc'
    onSearch()
    return true
  }
  searchData.orderBy = 'asc'
  onSearch()
  return true
}

function payInvoice() {
  router.push({
    name: 'invoice.portal.payment',
    params: {
      id: invoiceStore.selectedViewInvoice.id,
      company: invoiceStore.selectedViewInvoice.company.slug,
    },
  })
}
</script>
