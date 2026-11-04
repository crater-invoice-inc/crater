<template>
  <BasePage class="xl:pl-96">
    <BasePageHeader :title="pageTitle.estimate_number">
      <template #actions>
        <div class="mr-3 text-sm">
          <BaseButton
            v-if="estimateStore.selectedViewEstimate.status === 'DRAFT'"
            variant="primary"
            @click="AcceptEstimate"
          >
            {{ $t('estimates.accept_estimate') }}
          </BaseButton>
        </div>
        <div class="mr-3 text-sm">
          <BaseButton
            v-if="estimateStore.selectedViewEstimate.status === 'DRAFT'"
            variant="primary-outline"
            @click="RejectEstimate"
          >
            {{ $t('estimates.reject_estimate') }}
          </BaseButton>
        </div>
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
          v-model="searchData.estimate_number"
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
              <BaseDropdownItem class="rounded-md pt-3 hover:rounded-md">
                <BaseInputGroup class="-mt-3 font-normal">
                  <BaseRadio
                    id="filter_estimate_date"
                    v-model="searchData.orderByField"
                    :label="$t('reports.estimates.estimate_date')"
                    size="sm"
                    name="filter"
                    value="estimate_date"
                    @change="onSearch"
                  />
                </BaseInputGroup>
              </BaseDropdownItem>
            </div>

            <div class="px-2">
              <BaseDropdownItem class="rounded-md pt-3 hover:rounded-md">
                <BaseInputGroup class="-mt-3 font-normal">
                  <BaseRadio
                    id="filter_due_date"
                    v-model="searchData.orderByField"
                    :label="$t('estimates.due_date')"
                    value="expiry_date"
                    size="sm"
                    name="filter"
                    @update:modelValue="onSearch"
                  />
                </BaseInputGroup>
              </BaseDropdownItem>
            </div>

            <div class="px-2">
              <BaseDropdownItem class="rounded-md pt-3 hover:rounded-md">
                <BaseInputGroup class="-mt-3 font-normal">
                  <BaseRadio
                    id="filter_estimate_number"
                    v-model="searchData.orderByField"
                    :label="$t('estimates.estimate_number')"
                    value="estimate_number"
                    size="sm"
                    name="filter"
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
          v-for="(estimate, index) in estimateStore.estimates"
          :id="'estimate-' + estimate.id"
          :key="index"
          :to="`/${globalStore.companySlug}/customer/estimates/${estimate.id}/view`"
          :class="[
            'flex justify-between p-4 items-center cursor-pointer hover:bg-gray-100 border-l-4 border-transparent',
            {
              'bg-gray-100 border-l-4 border-primary-500 border-solid':
                hasActiveUrl(estimate.id),
            },
          ]"
          style="border-bottom: 1px solid rgba(185, 193, 209, 0.41)"
        >
          <div class="flex-2">
            <div
              class="
                mb-1
                text-md
                not-italic
                font-medium
                leading-5
                text-gray-500
                capitalize
              "
            >
              {{ estimate.estimate_number }}
            </div>

            <BaseEstimateStatusBadge :status="estimate.status">
              {{ $t(`estimates.${estimate.status.toLowerCase()}`) }}
            </BaseEstimateStatusBadge>
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
              :amount="estimate.total"
              :currency="estimate.currency"
            />
            <div class="text-sm text-right text-gray-500 non-italic">
              {{ estimate.formatted_estimate_date }}
            </div>
          </div>
        </router-link>

        <p
          v-if="!estimateStore.estimates.length"
          class="flex justify-center px-4 mt-5 text-sm text-gray-600"
        >
          {{ $t('estimates.no_matching_estimates') }}
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
        :src="shareableLink"
        class="flex-1 border border-gray-400 border-solid rounded-md"
      />
    </div>
  </BasePage>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import BaseDropdown from '@/scripts/components/base/BaseDropdown.vue'
import BaseDropdownItem from '@/scripts/components/base/BaseDropdownItem.vue'
import { debounce } from 'lodash'
import { ref, reactive, computed, inject, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import moment from 'moment'
import { useEstimateStore } from '@/scripts/customer/stores/estimate'
import { useGlobalStore } from '@/scripts/customer/stores/global'
import { useDialogStore } from '@/scripts/stores/dialog'

// Router
const route = useRoute()
const router = useRouter()

// store
const estimateStore = useEstimateStore()
const globalStore = useGlobalStore()
const dialogStore = useDialogStore()
const { tm, t } = useI18n()

// local state
let estimate = reactive({})
let searchData = reactive({
  orderBy: '',
  orderByField: '',
  estimate_number: '',
})

let isSearching = ref(false)

//Utils

const utils = inject('utils')
//Store

const notificationStore = useNotificationStore()

// Computed Props

const pageTitle = computed(() => {
  return estimateStore.selectedViewEstimate
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
  return estimate.unique_hash ? `/estimates/pdf/${estimate.unique_hash}` : false
})

// Watcher

watch(route, () => {
  loadEstimate()
})

// Created

loadEstimates()
loadEstimate()

onSearch = debounce(onSearch, 500)

// Methods

function hasActiveUrl(id) {
  return route.params.id == id
}

async function loadEstimates() {
  await estimateStore.fetchEstimate({ limit: 'all' }, globalStore.companySlug)

  setTimeout(() => {
    scrollToEstimate()
  }, 500)
}

async function loadEstimate() {
  if (route && route.params.id) {
    let response = await estimateStore.fetchViewEstimate(
      {
        id: route.params.id,
      },
      globalStore.companySlug
    )

    if (response.data) {
      Object.assign(estimate, response.data.data)
    }
  }
}

function scrollToEstimate() {
  const el = document.getElementById(`estimate-${route.params.id}`)

  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
    el.classList.add('shake')
  }
}

async function onSearch() {
  let data = {}

  if (
    searchData.estimate_number !== '' &&
    searchData.estimate_number !== null &&
    searchData.estimate_number !== undefined
  ) {
    data.estimate_number = searchData.estimate_number
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
    let response = await estimateStore.searchEstimate(
      data,
      globalStore.companySlug
    )
    isSearching.value = false

    if (response.data.data) {
      estimateStore.estimates = response.data.data
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

async function AcceptEstimate() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_mark_as_accepted', 1),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      size: 'lg',
      hideNoButton: false,
    })
    .then(async (res) => {
      let data = {
        slug: globalStore.companySlug,
        id: route.params.id,
        status: 'ACCEPTED',
      }
      if (res) {
        estimateStore.acceptEstimate(data)
        router.push({ name: 'estimates.dashboard' })
      }
    })
}

async function RejectEstimate() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_mark_as_rejected', 1),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      size: 'lg',
      hideNoButton: false,
    })
    .then(async (res) => {
      let data = {
        slug: globalStore.companySlug,
        id: route.params.id,
        status: 'REJECTED',
      }
      if (res) {
        estimateStore.rejectEstimate(data)
        router.push({ name: 'estimates.dashboard' })
      }
    })
}

function copyPdfUrl() {
  let pdfUrl = `${window.location.origin}/estimates/pdf/${estimate?.unique_hash}`
  utils.copyTextToClipboard(pdfUrl)
  notificationStore.showNotification({
    type: 'success',
    message: tm('general.copied_pdf_url_clipboard'),
  })
}
</script>
