<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'invoices.view'" variant="primary">
        <BaseIcon name="DotsHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="DotsHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Mark as sent Invoice -->
    <BaseDropdownItem v-if="canSendInvoice(row)" @click="onMarkAsSent(row.id)">
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.mark_as_sent') }}
    </BaseDropdownItem>

    <!-- Send Invoice Mail  -->
    <BaseDropdownItem v-if="canSendInvoice(row)" @click="sendInvoice(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.send_invoice') }}
    </BaseDropdownItem>

    <!-- Resend Invoice -->
    <BaseDropdownItem v-if="canReSendInvoice(row)" @click="sendInvoice(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.resend_invoice') }}
    </BaseDropdownItem>

    <!-- Record payment  -->
    <router-link :to="`/admin/payments/${row.id}/create`">
      <BaseDropdownItem
        v-if="row.due_amount > 0 && route.name !== 'invoices.view'"
      >
        <BaseIcon
          name="CreditCardIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('invoices.record_payment') }}
      </BaseDropdownItem>
    </router-link>

    <div
      v-if="hasStatusActions && hasDocumentActions"
      class="mx-4 my-1 border-t border-gray-200"
    />

    <!-- Edit Invoice  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_INVOICE)"
      :to="`/admin/invoices/${row.id}/edit`"
    >
      <BaseDropdownItem v-show="row.allow_edit">
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- View Invoice  -->
    <router-link
      v-if="
        route.name !== 'invoices.view' &&
        userStore.hasAbilities(abilities.VIEW_INVOICE)
      "
      :to="`/admin/invoices/${row.id}/view`"
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
    <BaseDropdownItem v-if="route.name === 'invoices.view'" @click="copyPdfUrl">
      <BaseIcon
        name="LinkIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.copy_pdf_url') }}
    </BaseDropdownItem>

    <!-- Clone Invoice into new invoice  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
      @click="cloneInvoiceData(row)"
    >
      <BaseIcon
        name="DocumentDuplicateIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.clone_invoice') }}
    </BaseDropdownItem>

    <div
      v-if="(hasStatusActions || hasDocumentActions) && canDelete"
      class="mx-4 my-1 border-t border-gray-200"
    />

    <!--  Delete Invoice  -->
    <BaseDropdownItem v-if="canDelete" @click="removeInvoice(row.id)">
      <BaseIcon
        name="TrashIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.delete') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useModalStore } from '@/scripts/stores/modal'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { inject, computed } from 'vue'
import abilities from '@/scripts/admin/stub/abilities'

const props = defineProps({
  row: {
    type: Object,
    default: null,
  },
  table: {
    type: Object,
    default: null,
  },
  loadData: {
    type: Function,
    default: () => {},
  },
})

const invoiceStore = useInvoiceStore()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = inject('utils')

const hasStatusActions = computed(() => {
  return (
    canSendInvoice(props.row) ||
    canReSendInvoice(props.row) ||
    (props.row.status == 'SENT' && route.name !== 'invoices.view')
  )
})

const hasDocumentActions = computed(() => {
  return (
    (route.name !== 'invoices.view' &&
      userStore.hasAbilities(abilities.VIEW_INVOICE)) ||
    route.name === 'invoices.view' ||
    userStore.hasAbilities(abilities.CREATE_INVOICE)
  )
})

const canDelete = computed(() =>
  userStore.hasAbilities(abilities.DELETE_INVOICE)
)

function canReSendInvoice(row) {
  return (
    (row.status == 'SENT' || row.status == 'VIEWED') &&
    userStore.hasAbilities(abilities.SEND_INVOICE)
  )
}

function canSendInvoice(row) {
  return (
    row.status == 'DRAFT' &&
    route.name !== 'invoices.view' &&
    userStore.hasAbilities(abilities.SEND_INVOICE)
  )
}

async function removeInvoice(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      id = id
      if (res) {
        invoiceStore.deleteInvoice({ ids: [id] }).then((res) => {
          if (res.data.success) {
            router.push('/admin/invoices')
            props.table && props.table.refresh()

            invoiceStore.$patch((state) => {
              state.selectedInvoices = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

async function cloneInvoiceData(data) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_clone'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        invoiceStore.cloneInvoice(data).then((res) => {
          router.push(`/admin/invoices/${res.data.data.id}/edit`)
        })
      }
    })
}

async function onMarkAsSent(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.invoice_mark_as_sent'),
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
        invoiceStore.markAsSent(data).then((response) => {
          props.table && props.table.refresh()
        })
      }
    })
}

async function sendInvoice(invoice) {
  modalStore.openModal({
    title: t('invoices.send_invoice'),
    componentName: 'SendInvoiceModal',
    id: invoice.id,
    data: invoice,
    variant: 'sm',
  })
}

function copyPdfUrl() {
  let pdfUrl = `${window.location.origin}/invoices/pdf/${props.row.unique_hash}`

  utils.copyTextToClipboard(pdfUrl)

  notificationStore.showNotification({
    type: 'success',
    message: t('general.copied_pdf_url_clipboard'),
  })
}
</script>
