<template>
  <SendEstimateModal @update="updateSentEstimate" />
  <BasePage v-if="estimateData" class="xl:pl-96 xl:ml-8">
    <BasePageHeader :title="pageTitle">
      <template #actions>
        <div class="mr-3 text-sm">
          <BaseButton
            v-if="
              estimateData.status === 'DRAFT' &&
              userStore.hasAbilities(abilities.EDIT_ESTIMATE)
            "
            :disabled="isMarkAsSent"
            :content-loading="isLoadingEstimate"
            variant="primary-outline"
            @click="onMarkAsSent"
          >
            {{ $t('estimates.mark_as_sent') }}
          </BaseButton>
        </div>

        <BaseButton
          v-if="
            estimateData.status === 'DRAFT' &&
            userStore.hasAbilities(abilities.SEND_ESTIMATE)
          "
          :content-loading="isLoadingEstimate"
          variant="primary"
          class="text-sm"
          @click="onSendEstimate"
        >
          {{ $t('estimates.send_estimate') }}
        </BaseButton>

        <EstimateDropDown class="ml-3" :row="estimateData" />
      </template>
    </BasePageHeader>

    <!-- Sidebar -->
    <div
      class="
        fixed
        top-0
        left-0
        hidden
        h-full
        pt-16
        pb-[6.4rem]
        ml-56
        bg-white
        xl:ml-64
        w-88
        xl:block
      "
    >
      <div
        class="
          flex
          items-center
          justify-between
          px-4
          pt-8
          pb-2
          border border-gray-200 border-solid
          height-full
        "
      >
        <div class="mb-6">
          <BaseInput
            v-model="searchData.searchText"
            :placeholder="$t('general.search')"
            type="text"
            variant="gray"
            @input="onSearched()"
          >
            <template #right>
              <BaseIcon name="SearchIcon" class="text-gray-400" />
            </template>
          </BaseInput>
        </div>

        <div class="flex mb-6 ml-3" role="group" aria-label="First group">
          <BaseDropdown
            class="ml-3"
            position="bottom-start"
            width-class="w-45"
            position-class="left-0"
          >
            <template #activator>
              <BaseButton size="md" variant="gray">
                <BaseIcon name="FilterIcon" />
              </BaseButton>
            </template>

            <div
              class="
                px-4
                py-1
                pb-2
                mb-1 mb-2
                text-sm
                border-b border-gray-200 border-solid
              "
            >
              {{ $t('general.sort_by') }}
            </div>

            <BaseDropdownItem class="flex px-4 py-2 cursor-pointer">
              <BaseInputGroup class="-mt-3 font-normal">
                <BaseRadio
                  id="filter_estimate_date"
                  v-model="searchData.orderByField"
                  :label="$t('reports.estimates.estimate_date')"
                  size="sm"
                  name="filter"
                  value="estimate_date"
                  @update:modelValue="onSearched"
                />
              </BaseInputGroup>
            </BaseDropdownItem>

            <BaseDropdownItem class="flex px-4 py-2 cursor-pointer">
              <BaseInputGroup class="-mt-3 font-normal">
                <BaseRadio
                  id="filter_due_date"
                  v-model="searchData.orderByField"
                  :label="$t('estimates.due_date')"
                  value="expiry_date"
                  size="sm"
                  name="filter"
                  @update:modelValue="onSearched"
                />
              </BaseInputGroup>
            </BaseDropdownItem>

            <BaseDropdownItem class="flex px-4 py-2 cursor-pointer">
              <BaseInputGroup class="-mt-3 font-normal">
                <BaseRadio
                  id="filter_estimate_number"
                  v-model="searchData.orderByField"
                  :label="$t('estimates.estimate_number')"
                  value="estimate_number"
                  size="sm"
                  name="filter"
                  @update:modelValue="onSearched"
                />
              </BaseInputGroup>
            </BaseDropdownItem>
          </BaseDropdown>

          <BaseButton class="ml-1" size="md" variant="gray" @click="sortData">
            <BaseIcon v-if="getOrderBy" name="SortAscendingIcon" />
            <BaseIcon v-else name="SortDescendingIcon" />
          </BaseButton>
        </div>
      </div>

      <div
        ref="estimateListSection"
        class="
          h-full
          overflow-y-scroll
          border-l border-gray-200 border-solid
          base-scroll
        "
      >
        <div v-for="(estimate, index) in estimateList" :key="index">
          <router-link
            v-if="estimate"
            :id="'estimate-' + estimate.id"
            :to="`/admin/estimates/${estimate.id}/view`"
            :class="[
              'flex justify-between side-estimate p-4 cursor-pointer hover:bg-gray-100 items-center border-l-4 border-transparent',
              {
                'bg-gray-100 border-l-4 border-primary-500 border-solid':
                  hasActiveUrl(estimate.id),
              },
            ]"
            style="border-bottom: 1px solid rgba(185, 193, 209, 0.41)"
          >
            <div class="flex-2">
              <BaseText
                :text="estimate.customer.name"
                :length="30"
                class="
                  pr-2
                  mb-2
                  text-sm
                  not-italic
                  font-normal
                  leading-5
                  text-black
                  capitalize
                  truncate
                "
              />

              <div
                class="
                  mt-1
                  mb-2
                  text-xs
                  not-italic
                  font-medium
                  leading-5
                  text-gray-600
                "
              >
                {{ estimate.estimate_number }}
              </div>

              <BaseEstimateStatusBadge
                :status="estimate.status"
                class="px-1 text-xs"
              >
                {{ $t(`estimates.${estimate.status.toLowerCase()}`) }}
              </BaseEstimateStatusBadge>
            </div>

            <div class="flex-1 whitespace-nowrap right">
              <BaseFormatMoney
                :amount="estimate.total"
                :currency="estimate.customer.currency"
                class="
                  block
                  mb-2
                  text-xl
                  not-italic
                  font-semibold
                  leading-8
                  text-right text-gray-900
                "
              />

              <div
                class="
                  text-sm
                  not-italic
                  font-normal
                  leading-5
                  text-right text-gray-600
                  est-date
                "
              >
                {{ estimate.formatted_estimate_date }}
              </div>
            </div>
          </router-link>
        </div>
        <div v-if="isLoading" class="flex justify-center p-4 items-center">
          <LoadingIcon class="h-6 m-1 animate-spin text-primary-400" />
        </div>
        <p
          v-if="!estimateList?.length && !isLoading"
          class="flex justify-center px-4 mt-5 text-sm text-gray-600"
        >
          {{ $t('estimates.no_matching_estimates') }}
        </p>
      </div>
    </div>

    <div
      class="flex flex-col min-h-0 mt-8 overflow-hidden"
      style="height: 75vh"
    >
      <iframe
        :src="`${shareableLink}`"
        class="
          flex-1
          border border-gray-400 border-solid
          rounded-md
          bg-white
          frame-style
        "
      />
    </div>
  </BasePage>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { debounce } from 'lodash'

import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import { useModalStore } from '@/scripts/stores/modal'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useUserStore } from '@/scripts/admin/stores/user'

import EstimateDropDown from '@/scripts/admin/components/dropdowns/EstimateIndexDropdown.vue'
import SendEstimateModal from '@/scripts/admin/components/modal-components/SendEstimateModal.vue'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'

import abilities from '@/scripts/admin/stub/abilities'

const modalStore = useModalStore()
const estimateStore = useEstimateStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const estimateData = ref(null)
const route = useRoute()
const router = useRouter()

const isMarkAsSent = ref(false)
const isLoading = ref(false)
const isLoadingEstimate = ref(false)

const estimateList = ref(null)
const currentPageNumber = ref(1)
const lastPageNumber = ref(1)
const estimateListSection = ref(null)

const searchData = reactive({
  orderBy: null,
  orderByField: null,
  searchText: null,
})

const pageTitle = computed(() => estimateData.value.estimate_number)

const getOrderBy = computed(() => {
  if (searchData.orderBy === 'asc' || searchData.orderBy == null) {
    return true
  }
  return false
})

const getOrderName = computed(() => {
  if (getOrderBy.value) {
    return t('general.ascending')
  }
  return t('general.descending')
})

const shareableLink = computed(() => {
  return `/estimates/pdf/${estimateData.value.unique_hash}`
})

const getCurrentEstimateId = computed(() => {
  if (estimateData.value && estimateData.value.id) {
    return estimate.value.id
  }
  return null
})

watch(route, (to, from) => {
  if (to.name === 'estimates.view') {
    loadEstimate()
  }
})

loadEstimates()
loadEstimate()

onSearched = debounce(onSearched, 500)

function hasActiveUrl(id) {
  return route.params.id == id
}

async function loadEstimates(pageNumber, fromScrollListener = false) {
  if (isLoading.value) {
    return
  }

  let params = {}
  if (
    searchData.searchText !== '' &&
    searchData.searchText !== null &&
    searchData.searchText !== undefined
  ) {
    params.search = searchData.searchText
  }

  if (searchData.orderBy !== null && searchData.orderBy !== undefined) {
    params.orderBy = searchData.orderBy
  }

  if (
    searchData.orderByField !== null &&
    searchData.orderByField !== undefined
  ) {
    params.orderByField = searchData.orderByField
  }

  isLoading.value = true
  let response = await estimateStore.fetchEstimates({
    page: pageNumber,
    ...params,
  })
  isLoading.value = false

  estimateList.value = estimateList.value ? estimateList.value : []
  estimateList.value = [...estimateList.value, ...response.data.data]

  currentPageNumber.value = pageNumber ? pageNumber : 1
  lastPageNumber.value = response.data.meta.last_page
  let estimateFound = estimateList.value.find(
    (est) => est.id == route.params.id
  )

  if (
    fromScrollListener == false &&
    !estimateFound &&
    currentPageNumber.value < lastPageNumber.value &&
    Object.keys(params).length === 0
  ) {
    loadEstimates(++currentPageNumber.value)
  }

  if (estimateFound) {
    setTimeout(() => {
      if (fromScrollListener == false) {
        scrollToEstimate()
      }
    }, 500)
  }
}

function scrollToEstimate() {
  const el = document.getElementById(`estimate-${route.params.id}`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
    el.classList.add('shake')
    addScrollListener()
  }
}

function addScrollListener() {
  estimateListSection.value.addEventListener('scroll', (ev) => {
    if (
      ev.target.scrollTop > 0 &&
      ev.target.scrollTop + ev.target.clientHeight >
        ev.target.scrollHeight - 200
    ) {
      if (currentPageNumber.value < lastPageNumber.value) {
        loadEstimates(++currentPageNumber.value, true)
      }
    }
  })
}

async function loadEstimate() {
  isLoadingEstimate.value = true
  let response = await estimateStore.fetchEstimate(route.params.id)

  if (response.data) {
    isLoadingEstimate.value = false
    estimateData.value = { ...response.data.data }
  }
}

async function onSearched() {
  estimateList.value = []
  loadEstimates()
}

function sortData() {
  if (searchData.orderBy === 'asc') {
    searchData.orderBy = 'desc'
    onSearched()
    return true
  }
  searchData.orderBy = 'asc'
  onSearched()
  return true
}

async function onMarkAsSent() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_mark_as_sent'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((response) => {
      isMarkAsSent.value = false
      if (response) {
        estimateStore.markAsSent({
          id: estimateData.value.id,
          status: 'SENT',
        })
        estimateData.value.status = 'SENT'
        isMarkAsSent.value = true
      }
      isMarkAsSent.value = false
    })
}

async function onSendEstimate(id) {
  modalStore.openModal({
    title: t('estimates.send_estimate'),
    componentName: 'SendEstimateModal',
    id: estimateData.value.id,
    data: estimateData.value,
  })
}

async function removeEstimate(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        estimateStore
          .deleteEstimate({ ids: [id] })
          .then(() => {
            router.push('/admin/estimates')
          })
          .catch((err) => {
            console.error(err)
          })
      }
    })
}

function updateSentEstimate() {
  let pos = estimateList.value.findIndex(
    (estimate) => estimate.id === estimateData.value.id
  )

  if (estimateList.value[pos]) {
    estimateList.value[pos].status = 'SENT'
    estimateData.value.status = 'SENT'
  }
}
</script>
