<template>
  <BaseDropdown width-class="w-72">
    <template #activator>
      <BaseButton v-if="route.name === 'estimates.view'" variant="primary">
        <BaseIcon name="DotsHorizontalIcon" class="text-white" />
      </BaseButton>
      <BaseIcon v-else class="text-gray-500" name="DotsHorizontalIcon" />
    </template>

    <!-- Mark as Draft  -->
    <BaseDropdownItem
      v-if="
        row.status !== 'DRAFT' &&
        userStore.hasAbilities(abilities.EDIT_ESTIMATE)
      "
      @click="onMarkAsDraft(row.id)"
    >
      <PencilSheetIcon
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('estimates.mark_as_draft') }}
    </BaseDropdownItem>

    <!-- Mark as sent  -->
    <BaseDropdownItem
      v-if="
        row.status !== 'SENT' &&
        route.name !== 'estimates.view' &&
        userStore.hasAbilities(abilities.SEND_ESTIMATE)
      "
      @click="onMarkAsSent(row.id)"
    >
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('estimates.mark_as_sent') }}
    </BaseDropdownItem>

    <!-- Mark as Accepted -->
    <BaseDropdownItem
      v-if="
        row.status !== 'ACCEPTED' &&
        userStore.hasAbilities(abilities.EDIT_ESTIMATE)
      "
      @click="onMarkAsAccepted(row.id)"
    >
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('estimates.mark_as_accepted') }}
    </BaseDropdownItem>

    <!-- Mark as Rejected  -->
    <BaseDropdownItem
      v-if="
        row.status !== 'REJECTED' &&
        userStore.hasAbilities(abilities.EDIT_ESTIMATE)
      "
      @click="onMarkAsRejected(row.id)"
    >
      <BaseIcon
        name="XCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('estimates.mark_as_rejected') }}
    </BaseDropdownItem>

    <!-- Send Estimate  -->
    <BaseDropdownItem
      v-if="
        row.status === 'DRAFT' &&
        route.name !== 'estimates.view' &&
        userStore.hasAbilities(abilities.SEND_ESTIMATE)
      "
      @click="sendEstimate(row)"
    >
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('estimates.send_estimate') }}
    </BaseDropdownItem>

    <!-- Resend Estimate -->
    <BaseDropdownItem v-if="canResendEstimate(row)" @click="sendEstimate(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('estimates.resend_estimate') }}
    </BaseDropdownItem>

    <div
      v-if="hasStatusActions && hasDocumentActions"
      class="mx-4 my-1 border-t border-gray-200"
    />

    <!-- Edit Estimate -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_ESTIMATE)"
      :to="`/admin/estimates/${row.id}/edit`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- View Estimate -->
    <router-link
      v-if="
        route.name !== 'estimates.view' &&
        userStore.hasAbilities(abilities.VIEW_ESTIMATE)
      "
      :to="`estimates/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Copy PDF url  -->
    <BaseDropdownItem
      v-if="route.name === 'estimates.view'"
      @click="copyPdfUrl"
    >
      <BaseIcon
        name="LinkIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.copy_pdf_url') }}
    </BaseDropdownItem>

    <!-- Convert into Invoice  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
      @click="convertInToinvoice(row.id)"
    >
      <BaseIcon
        name="DocumentTextIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('estimates.convert_to_invoice') }}
    </BaseDropdownItem>

    <!-- Clone Estimate into new estimate  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.CREATE_ESTIMATE)"
      @click="cloneEstimateData(row)"
    >
      <BaseIcon
        name="DocumentDuplicateIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('estimates.clone_estimate') }}
    </BaseDropdownItem>

    <div
      v-if="(hasStatusActions || hasDocumentActions) && canDelete"
      class="mx-4 my-1 border-t border-gray-200"
    />

    <!-- Delete Estimate  -->
    <BaseDropdownItem
      v-if="canDelete"
      @click="removeEstimate(row.id)"
    >
      <BaseIcon
        name="TrashIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.delete') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useModalStore } from '@/scripts/stores/modal'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useDialogStore } from '@/scripts/stores/dialog'
import { inject, computed } from 'vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import PencilSheetIcon from '@/scripts/components/icons/PencilSheetIcon.vue'

const props = defineProps({
  row: {
    type: Object,
    default: null,
  },

  table: {
    type: Object,
    default: null,
  },
})

const utils = inject('utils')

const estimateStore = useEstimateStore()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const hasStatusActions = computed(() => {
  return (
    (route.name !== 'estimates.view' &&
      userStore.hasAbilities(abilities.SEND_ESTIMATE)) ||
    userStore.hasAbilities(abilities.EDIT_ESTIMATE)
  )
})

const hasDocumentActions = computed(() => {
  return (
    (route.name !== 'estimates.view' &&
      userStore.hasAbilities(abilities.VIEW_ESTIMATE)) ||
    route.name === 'estimates.view' ||
    userStore.hasAbilities([
      abilities.CREATE_INVOICE,
      abilities.CREATE_ESTIMATE,
    ])
  )
})

const canDelete = computed(() =>
  userStore.hasAbilities(abilities.DELETE_ESTIMATE)
)

function canResendEstimate(row) {
  return (
    (row.status == 'SENT' || row.status == 'VIEWED') &&
    route.name !== 'estimates.view' &&
    userStore.hasAbilities(abilities.SEND_ESTIMATE)
  )
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
      id = id
      if (res) {
        estimateStore.deleteEstimate({ ids: [id] }).then((res) => {
          if (res) {
            props.table && props.table.refresh()

            if (res.data) {
              router.push('/admin/estimates')
            }
            estimateStore.$patch((state) => {
              state.selectedEstimates = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

function cloneEstimateData(data) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_clone'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        estimateStore.cloneEstimate(data).then((res) => {
          if (res.data) {
            router.push(`/admin/estimates/${res.data.data.id}/edit`)
          }
        })
      }
    })
}

function convertInToinvoice(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_conversion'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        estimateStore.convertToInvoice(id).then((res) => {
          if (res.data) {
            router.push(`/admin/invoices/${res.data.data.id}/edit`)
          }
        })
      }
    })
}

async function onMarkAsSent(id) {
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
      const data = {
        id: id,
        status: 'SENT',
      }
      if (response) {
        estimateStore.markAsSent(data).then((response) => {
          props.table && props.table.refresh()
        })
      }
    })
}

async function sendEstimate(estimate) {
  modalStore.openModal({
    title: t('estimates.send_estimate'),
    componentName: 'SendEstimateModal',
    id: estimate.id,
    data: estimate,
    variant: 'lg',
  })
}

async function onMarkAsAccepted(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_mark_as_accepted'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((response) => {
      const data = {
        id: id,
        status: 'ACCEPTED',
      }
      if (response) {
        estimateStore.markAsAccepted(data).then((response) => {
          props.table && props.table.refresh()
        })
      }
    })
}

async function onMarkAsRejected(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_mark_as_rejected'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((response) => {
      const data = {
        id: id,
        status: 'REJECTED',
      }
      if (response) {
        estimateStore.markAsRejected(data).then((response) => {
          props.table && props.table.refresh()
        })
      }
    })
}

async function onMarkAsDraft(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('estimates.confirm_mark_as_draft'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((response) => {
      const data = {
        id: id,
        status: 'DRAFT',
      }
      if (response) {
        estimateStore.markAsDraft(data).then((response) => {
          props.table && props.table.refresh()
        })
      }
    })
}

function copyPdfUrl() {
  let pdfUrl = `${window.location.origin}/estimates/pdf/${props.row.unique_hash}`

  let response = utils.copyTextToClipboard(pdfUrl)
  notificationStore.showNotification({
    type: 'success',
    message: t('general.copied_pdf_url_clipboard'),
  })
}
</script>
