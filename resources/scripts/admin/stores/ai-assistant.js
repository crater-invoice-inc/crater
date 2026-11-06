import { defineStore } from 'pinia'
import Guid from 'guid'
import router from '@/scripts/router'
import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useItemStore } from '@/scripts/admin/stores/item'

// Step machine: idle | service_selection | line_selection | brief_input |
// loading | proposal | applied | error.
//
// The store never talks to Perplexity directly: it only calls the local
// backend (POST /api/v1/ai/document-assistant), which returns a validated
// proposal. Nothing is applied to the document without the user checking
// the lines and clicking "Appliquer la sélection", and the document itself
// is saved afterwards through the regular save flow.

const LINE_FIELDS = [
  'name',
  'description',
  'deliverables',
  'delivery_delay',
  'terms',
  'unit_name',
  'quantity',
  'price',
]

const TEXT_FIELDS = [
  'name',
  'description',
  'deliverables',
  'delivery_delay',
  'terms',
  'unit_name',
]

const normalizeText = (value) => (value === null || value === undefined ? '' : String(value).trim())

const normalizeNumber = (value) => {
  const parsed = Number(value)

  return Number.isFinite(parsed) ? parsed : 0
}

export const useAiAssistantStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'aiAssistant',

    state: () => ({
      isOpen: false,
      step: 'idle',
      action: null,

      brief: '',

      // Catalog (add_existing_services)
      services: [],
      loadingServices: false,
      selectedServiceIds: [],

      // Document lines snapshot (update_line + context)
      documentLines: [],
      selectedLineIndex: null,

      // Proposal
      submitting: false,
      summary: '',
      warnings: [],
      proposalLines: [],
      needsConfirm: false,
      confirming: false,
      appliedCount: 0,

      // Errors
      error: null,

      // Conversation. This store lives for the whole admin session, so the
      // loaded history is tagged with the document it belongs to
      // (historyKey): any document switch drops it and reloads, otherwise
      // the panel could show another document's conversation (enforced
      // server side too).
      conversationId: null,
      draftKey: null,
      messages: [],
      historyLoaded: false,
      historyKey: null,
      loadingHistory: false,
      historyRequestId: 0,
    }),

    getters: {
      checkedCount() {
        return this.proposalLines.filter((line) => line.checked).length
      },

      selectedServicesCount() {
        return this.selectedServiceIds.length
      },
    },

    actions: {
      // Current editing context derived from the route. The router
      // instance is used directly: actions run outside component setup,
      // where useRoute() is not available.
      documentContext() {
        const current = router.currentRoute.value
        const name = current.name

        if (name === 'estimates.create' || name === 'estimates.edit') {
          return {
            type: 'estimate',
            id: name === 'estimates.edit' ? Number(current.params.id) : null,
          }
        }

        if (name === 'invoices.create' || name === 'invoices.edit') {
          return {
            type: 'invoice',
            id: name === 'invoices.edit' ? Number(current.params.id) : null,
          }
        }

        return null
      },

      // { store, prop } of the document being edited.
      documentStoreRef() {
        const context = this.documentContext()

        if (context.type === 'estimate') {
          return { store: useEstimateStore(), prop: 'newEstimate' }
        }

        return { store: useInvoiceStore(), prop: 'newInvoice' }
      },

      // Identity of the document a conversation belongs to: type + id for
      // a saved document, type + "draft" for a not-yet-saved one.
      contextKey(context) {
        return context.id ? `${context.type}:${context.id}` : `${context.type}:draft`
      },

      async openPanel() {
        const context = this.documentContext()

        if (!context) {
          return
        }

        this.isOpen = true

        if (!this.historyLoaded || this.historyKey !== this.contextKey(context)) {
          await this.loadHistory()
        }
      },

      // Route watcher entry point (see AiAssistantPanel): the store is a
      // singleton living for the whole admin session, so without this a
      // conversation loaded on one document would still be in memory (and
      // displayed) on the next one.
      onRouteChange() {
        const context = this.documentContext()

        if (!context) {
          // Left the estimate/invoice editors: forget the draft key so a
          // new document never reuses the previous draft conversation, and
          // close the panel (the bubble is hidden outside the editors).
          this.resetConversationState()
          this.closePanel()

          return
        }

        const key = this.contextKey(context)

        if (this.historyKey !== key) {
          // Switched to another document (or from a draft to its saved
          // id): drop the previous document's conversation and flow so
          // nothing leaks into the new one.
          this.resetConversationState()
          this.historyKey = key

          if (this.isOpen) {
            this.loadHistory()
          }
        }
      },

      // Drop every trace of the previously edited document: conversation
      // history, draft key and in-progress flow (a proposal reviewed on
      // one document must never be appliable onto another one).
      resetConversationState() {
        this.resetFlow()
        this.conversationId = null
        this.draftKey = null
        this.messages = []
        this.historyLoaded = false
        this.historyKey = null
        this.loadingHistory = false
        // Invalidate any in-flight history request.
        this.historyRequestId += 1
      },

      closePanel() {
        // Closing keeps the conversation state: reopening on the same
        // document shows the same history without a reload.
        this.isOpen = false
      },

      // Back to the initial choice; conversation history is kept.
      resetFlow() {
        this.step = 'idle'
        this.action = null
        this.brief = ''
        this.services = []
        this.selectedServiceIds = []
        this.documentLines = []
        this.selectedLineIndex = null
        this.summary = ''
        this.warnings = []
        this.proposalLines = []
        this.needsConfirm = false
        this.confirming = false
        this.appliedCount = 0
        this.error = null
      },

      chooseAction(action) {
        this.action = action
        this.error = null
        this.brief = ''
        this.selectedServiceIds = []
        this.selectedLineIndex = null
        this.proposalLines = []
        this.summary = ''
        this.warnings = []
        this.needsConfirm = false
        this.confirming = false

        if (action === 'add_existing_services') {
          this.step = 'service_selection'
          this.fetchServices()
        } else if (action === 'update_line') {
          this.snapshotDocumentLines()
          this.step = 'line_selection'
        } else {
          this.step = 'brief_input'
        }
      },

      async fetchServices() {
        const itemStore = useItemStore()

        this.loadingServices = true

        try {
          const response = await itemStore.fetchItems({ limit: 'all' })

          this.services = response.data.data
        } catch {
          this.error = 'Impossible de charger la liste des services.'
        } finally {
          this.loadingServices = false
        }
      },

      toggleService(serviceId) {
        const index = this.selectedServiceIds.indexOf(serviceId)

        if (index === -1) {
          this.selectedServiceIds.push(serviceId)
        } else {
          this.selectedServiceIds.splice(index, 1)
        }
      },

      snapshotDocumentLines() {
        const { store, prop } = this.documentStoreRef()

        this.documentLines = store[prop].items.slice(0, 30).map((item) => ({
          name: item.name,
          quantity: item.quantity,
          // Prices are stored in cents and the backend expects cents here.
          price: item.price,
          unit_name: item.unit_name,
        }))
      },

      selectLine(index) {
        this.selectedLineIndex = index
        this.step = 'brief_input'
      },

      goBackToBrief() {
        this.step = 'brief_input'
        this.proposalLines = []
        this.summary = ''
        this.warnings = []
        this.needsConfirm = false
        this.confirming = false
      },

      ensureDraftKey() {
        if (!this.draftKey) {
          this.draftKey = Guid.raw()
        }

        return this.draftKey
      },

      async loadHistory() {
        const context = this.documentContext()

        if (!context) {
          return
        }

        const params = { document_type: context.type }

        if (context.id) {
          params.document_id = context.id
        } else {
          params.draft_key = this.ensureDraftKey()
        }

        this.loadingHistory = true

        // Token guard: a response arriving after a document switch must
        // never populate the panel with another document's messages.
        const requestId = ++this.historyRequestId
        const requestKey = this.contextKey(context)

        try {
          const response = await window.axios.get('/api/v1/ai/conversations', { params })

          if (requestId !== this.historyRequestId) {
            return
          }

          if (response.data.data) {
            this.conversationId = response.data.data.id
            this.messages = response.data.data.messages
          } else {
            // No conversation for this document yet: never keep a
            // previously displayed one around.
            this.conversationId = null
            this.messages = []
          }

          this.historyKey = requestKey
        } catch {
          if (requestId !== this.historyRequestId) {
            return
          }

          // History is a nice-to-have: never block the panel on it.
          this.historyKey = requestKey
        } finally {
          if (requestId === this.historyRequestId) {
            this.loadingHistory = false
            this.historyLoaded = true
          }
        }
      },

      currentLine() {
        const { store, prop } = this.documentStoreRef()
        const line = store[prop].items[this.selectedLineIndex]

        if (!line) {
          return null
        }

        return {
          item_id: line.item_id,
          name: line.name,
          description: line.description,
          deliverables: line.deliverables,
          delivery_delay: line.delivery_delay,
          terms: line.terms,
          unit_name: line.unit_name,
          quantity: line.quantity,
          price: line.price,
        }
      },

      async submitBrief() {
        const context = this.documentContext()

        if (!context || !this.brief.trim()) {
          return
        }

        this.step = 'loading'
        this.submitting = true
        this.error = null

        const payload = {
          document_type: context.type,
          action: this.action,
          brief: this.brief,
          document_lines: this.documentLines,
        }

        if (context.id) {
          payload.document_id = context.id
        } else {
          payload.draft_key = this.ensureDraftKey()
        }

        if (this.action === 'add_existing_services') {
          payload.service_ids = this.selectedServiceIds
        }

        if (this.action === 'update_line') {
          payload.line_index = this.selectedLineIndex
          payload.line = this.currentLine()
        }

        try {
          const response = await window.axios.post('/api/v1/ai/document-assistant', payload)

          this.conversationId = response.data.conversation_id
          this.summary = response.data.summary
          this.warnings = response.data.warnings || []
          this.proposalLines = (response.data.lines || []).map((line) =>
            this.hydrateProposalLine(line)
          )
          this.evaluateOverwrites()

          this.messages.push(
            { role: 'user', content: this.brief, metadata: null },
            { role: 'assistant', content: this.summary, metadata: null }
          )

          this.step = 'proposal'
        } catch (err) {
          this.error = this.extractError(err)
          this.step = 'error'
        } finally {
          this.submitting = false
        }
      },

      // Backend returns cents; build an editable copy with checked=true.
      hydrateProposalLine(line) {
        return {
          checked: true,
          line_index: line.line_index ?? null,
          item_id: line.item_id ?? null,
          name: line.name ?? '',
          description: line.description ?? '',
          deliverables: line.deliverables ?? '',
          delivery_delay: line.delivery_delay ?? '',
          terms: line.terms ?? '',
          unit_name: line.unit ?? '',
          quantity: normalizeNumber(line.quantity ?? 1),
          price: normalizeNumber(line.price ?? 0),
        }
      },

      // Fields of a proposal line that differ from the current document line.
      changedFields(line) {
        const current = this.currentLine()

        if (!current) {
          return []
        }

        return LINE_FIELDS.filter((field) => {
          if (TEXT_FIELDS.includes(field)) {
            return normalizeText(line[field]) !== normalizeText(current[field])
          }

          return normalizeNumber(line[field]) !== normalizeNumber(current[field])
        })
      },

      // An update_line proposal must be confirmed when it would replace a
      // non-empty value or change the price/quantity of the line.
      evaluateOverwrites() {
        if (this.action !== 'update_line' || !this.proposalLines.length) {
          this.needsConfirm = false

          return
        }

        const line = this.proposalLines[0]
        const current = this.currentLine()
        const changed = this.changedFields(line)

        this.needsConfirm = changed.some((field) => {
          if (TEXT_FIELDS.includes(field)) {
            return normalizeText(current[field]) !== ''
          }

          return true
        })
      },

      async applySelection() {
        const checked = this.proposalLines.filter((line) => line.checked)

        if (!checked.length) {
          return
        }

        if (this.needsConfirm && !this.confirming) {
          this.confirming = true

          return
        }

        this.confirming = false

        const { store, prop } = this.documentStoreRef()

        if (this.action === 'update_line') {
          const documentLine = store[prop].items[this.selectedLineIndex]

          if (documentLine) {
            this.assignProposalToLine(documentLine, checked[0])
          }
        } else {
          checked.forEach((line) => {
            // A brand new document starts with one pristine empty line:
            // fill the first empty line instead of stacking the proposal
            // after it (which would leave a blank line on the document).
            const emptyIndex = store[prop].items.findIndex((item) =>
              this.isLineEmpty(item)
            )

            let documentLine

            if (emptyIndex !== -1) {
              documentLine = store[prop].items[emptyIndex]
            } else {
              store.addItem()
              documentLine = store[prop].items[store[prop].items.length - 1]
            }

            this.assignProposalToLine(documentLine, line)
          })
        }

        this.appliedCount = checked.length
        this.step = 'applied'
      },

      // A pristine document line (default stub): no linked service, no
      // name and no price. Filling it beats appending a new row.
      isLineEmpty(line) {
        return (
          !line.item_id &&
          (!line.name || !String(line.name).trim()) &&
          !(Number(line.price) > 0)
        )
      },

      cancelConfirm() {
        this.confirming = false
      },

      // Copy a (possibly edited) proposal line onto a document line and
      // refresh the computed fields exactly like CreateItemRow does.
      assignProposalToLine(documentLine, proposalLine) {
        documentLine.item_id = proposalLine.item_id
        documentLine.name = proposalLine.name
        documentLine.description = proposalLine.description || null
        documentLine.deliverables = proposalLine.deliverables || null
        documentLine.delivery_delay = proposalLine.delivery_delay || null
        documentLine.terms = proposalLine.terms || null
        documentLine.unit_name = proposalLine.unit_name || null
        documentLine.quantity = normalizeNumber(proposalLine.quantity)
        documentLine.price = Math.round(normalizeNumber(proposalLine.price))

        const subTotal = documentLine.price * documentLine.quantity

        documentLine.sub_total = subTotal
        documentLine.total = subTotal - (Number(documentLine.discount_val) || 0)
      },

      // Called by the create views after a successful save so a draft
      // conversation becomes the document's conversation.
      async bindAfterSave(documentId) {
        const context = this.documentContext()

        if (!context || context.id || !this.draftKey) {
          return
        }

        try {
          await window.axios.post('/api/v1/ai/conversations/bind', {
            draft_key: this.draftKey,
            document_type: context.type,
            document_id: documentId,
          })

          this.draftKey = null
          this.historyLoaded = false
        } catch {
          // Non-blocking: the conversation stays draft-keyed.
        }
      },

      extractError(err) {
        const response = err && err.response

        if (!response) {
          return 'Impossible de contacter le serveur. Vérifiez votre connexion.'
        }

        if (response.data && response.data.error) {
          return response.data.error
        }

        if (response.data && response.data.errors) {
          const first = Object.values(response.data.errors)[0]

          if (first) {
            return Array.isArray(first) ? first[0] : String(first)
          }
        }

        if (response.status === 429) {
          return 'Trop de demandes. Veuillez patienter une minute.'
        }

        return (
          (response.data && response.data.message) ||
          'Une erreur est survenue. Veuillez réessayer.'
        )
      },
    },
  })()
}
