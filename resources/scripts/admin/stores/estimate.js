import axios from 'axios'
import moment from 'moment'
import Guid from 'guid'
import _ from 'lodash'
import { defineStore } from 'pinia'
import { useRoute } from 'vue-router'
import { useCompanyStore } from './company'
import { useCustomerStore } from './customer'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useItemStore } from './item'
import { useTaxTypeStore } from './tax-type'
import { handleError } from '@/scripts/helpers/error-handling'
import estimateStub from '../stub/estimate'
import estimateItemStub from '../stub/estimate-item'
import taxStub from '../stub/tax'
import { useUserStore } from './user'

export const useEstimateStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'estimate',

    state: () => ({
      templates: [],

      estimates: [],
      selectAllField: false,
      selectedEstimates: [],
      totalEstimateCount: 0,
      isFetchingInitialSettings: false,
      showExchangeRate: false,

      newEstimate: {
        ...estimateStub(),
      },
    }),

    getters: {
      getSubTotal() {
        return this.newEstimate.items.reduce(function (a, b) {
          return a + b['total']
        }, 0)
      },
      getTotalSimpleTax() {
        return _.sumBy(this.newEstimate.taxes, function (tax) {
          if (!tax.compound_tax) {
            return tax.amount
          }
          return 0
        })
      },

      getTotalCompoundTax() {
        return _.sumBy(this.newEstimate.taxes, function (tax) {
          if (tax.compound_tax) {
            return tax.amount
          }
          return 0
        })
      },

      getTotalTax() {
        if (
          this.newEstimate.tax_per_item === 'NO' ||
          this.newEstimate.tax_per_item === null
        ) {
          return this.getTotalSimpleTax + this.getTotalCompoundTax
        }
        return _.sumBy(this.newEstimate.items, function (tax) {
          return tax.tax
        })
      },

      getSubtotalWithDiscount() {
        return this.getSubTotal - this.newEstimate.discount_val
      },

      getTotal() {
        return this.getSubtotalWithDiscount + this.getTotalTax
      },

      isEdit: (state) => (state.newEstimate.id ? true : false),
    },

    actions: {
      resetCurrentEstimate() {
        this.newEstimate = {
          ...estimateStub(),
        }
      },

      previewEstimate(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/estimates/${params.id}/send/preview`, { params })
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchEstimates(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/estimates`, { params })
            .then((response) => {
              this.estimates = response.data.data
              this.totalEstimateCount = response.data.meta.estimate_total_count
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      getNextNumber(params, setState = false) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/next-number?key=estimate`, { params })
            .then((response) => {
              if (setState) {
                this.newEstimate.estimate_number = response.data.nextNumber
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchEstimate(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/estimates/${id}`)
            .then((response) => {
              Object.assign(this.newEstimate, response.data.data)
              resolve(response)
            })
            .catch((err) => {
              console.log(err);
              handleError(err)
              reject(err)
            })
        })
      },

      addSalesTaxUs() {
        const taxTypeStore = useTaxTypeStore()
        let salesTax = { ...taxStub }
        let found = this.newEstimate.taxes.find((_t) => _t.name === 'Sales Tax' && _t.type === 'MODULE')
        if (found) {
          for (const key in found) {
            if (Object.prototype.hasOwnProperty.call(salesTax, key)) {
              salesTax[key] = found[key]
            }
          }
          salesTax.id = found.tax_type_id
          console.log(salesTax, 'salesTax');

          taxTypeStore.taxTypes.push(salesTax)
          console.log(taxTypeStore.taxTypes);
        }
      },

      sendEstimate(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/${data.id}/send`, data)
            .then((response) => {
              if (!data.is_preview) {
                notificationStore.showNotification({
                  type: 'success',
                  message: global.t('estimates.send_estimate_successfully'),
                })
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addEstimate(data) {
        return new Promise((resolve, reject) => {
          axios
            .post('/api/v1/estimates', data)
            .then((response) => {
              this.estimates = [...this.estimates, response.data.estimate]
              const notificationStore = useNotificationStore()

              notificationStore.showNotification({
                type: 'success',
                message: global.t('estimates.created_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteEstimate(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/delete`, id)
            .then((response) => {
              let index = this.estimates.findIndex(
                (estimate) => estimate.id === id
              )

              this.estimates.splice(index, 1)

              notificationStore.showNotification({
                type: 'success',
                message: global.t('estimates.deleted_message', 1),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteMultipleEstimates(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/delete`, { ids: this.selectedEstimates })
            .then((response) => {
              this.selectedEstimates.forEach((estimate) => {
                let index = this.estimates.findIndex(
                  (_est) => _est.id === estimate.id
                )
                this.estimates.splice(index, 1)
              })
              this.selectedEstimates = []

              notificationStore.showNotification({
                type: 'success',
                message: global.tc('estimates.deleted_message', 2),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateEstimatesStatus(status) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/change-status`, {
              ids: this.selectedEstimates,
              status,
            })
            .then((response) => {
              this.selectedEstimates = []
              this.selectAllField = false

              notificationStore.showNotification({
                type: 'success',
                message: global.t('estimates.status_updated_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateEstimate(data) {
        return new Promise((resolve, reject) => {
          axios
            .put(`/api/v1/estimates/${data.id}`, data)
            .then((response) => {
              let pos = this.estimates.findIndex(
                (estimate) => estimate.id === response.data.data.id
              )
              this.estimates[pos] = response.data.data
              const notificationStore = useNotificationStore()
              notificationStore.showNotification({
                type: 'success',
                message: global.t('estimates.updated_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsAccepted(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/${data.id}/status`, data)
            .then((response) => {
              let pos = this.estimates.findIndex(
                (estimate) => estimate.id === data.id
              )
              if (this.estimates[pos]) {
                this.estimates[pos].status = 'ACCEPTED'

                const notificationStore = useNotificationStore()

                notificationStore.showNotification({
                  type: 'success',
                  message: global.t('estimates.marked_as_accepted_message'),
                })
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsRejected(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/${data.id}/status`, data)
            .then((response) => {
              const notificationStore = useNotificationStore()

              notificationStore.showNotification({
                type: 'success',
                message: global.t('estimates.marked_as_rejected_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsSent(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/${data.id}/status`, data)
            .then((response) => {
              let pos = this.estimates.findIndex(
                (estimate) => estimate.id === data.id
              )
              if (this.estimates[pos]) {
                this.estimates[pos].status = 'SENT'

                const notificationStore = useNotificationStore()
                notificationStore.showNotification({
                  type: 'success',
                  message: global.t('estimates.mark_as_sent_successfully'),
                })
              }

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      markAsDraft(data) {
        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/${data.id}/status`, data)
            .then((response) => {
              let pos = this.estimates.findIndex(
                (estimate) => estimate.id === data.id
              )
              if (this.estimates[pos]) {
                this.estimates[pos].status = 'DRAFT'

                const notificationStore = useNotificationStore()
                notificationStore.showNotification({
                  type: 'success',
                  message: global.t('estimates.marked_as_draft_message'),
                })
              }

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      convertToInvoice(id) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/estimates/${id}/convert-to-invoice`)
            .then((response) => {
              notificationStore.showNotification({
                type: 'success',
                message: global.t('estimates.conversion_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      searchEstimate(data) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/estimates?${data}`)
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      selectEstimate(data) {
        this.selectedEstimates = data
        if (this.selectedEstimates.length === this.estimates.length) {
          this.selectAllField = true
        } else {
          this.selectAllField = false
        }
      },

      selectAllEstimates() {
        if (this.selectedEstimates.length === this.estimates.length) {
          this.selectedEstimates = []
          this.selectAllField = false
        } else {
          let allEstimateIds = this.estimates.map((estimate) => estimate.id)
          this.selectedEstimates = allEstimateIds
          this.selectAllField = true
        }
      },

      selectCustomer(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/customers/${id}`)
            .then((response) => {
              this.newEstimate.customer = response.data.data
              this.newEstimate.customer_id = response.data.data.id
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },
      fetchEstimateTemplates(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/estimates/templates`, { params })
            .then((response) => {
              this.templates = response.data.estimateTemplates
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      setTemplate(data) {
        this.newEstimate.template_name = data
      },

      resetSelectedCustomer() {
        this.newEstimate.customer = null
        this.newEstimate.customer_id = ''
      },

      selectNote(data) {
        this.newEstimate.selectedNote = null
        this.newEstimate.selectedNote = data
      },

      resetSelectedNote() {
        this.newEstimate.selectedNote = null
      },

      addItem() {
        this.newEstimate.items.push({
          ...estimateItemStub,
          id: Guid.raw(),
          taxes: [{ ...taxStub, id: Guid.raw() }],
        })
      },

      updateItem(data) {
        Object.assign(this.newEstimate.items[data.index], { ...data })
      },

      removeItem(index) {
        this.newEstimate.items.splice(index, 1)
      },

      deselectItem(index) {
        this.newEstimate.items[index] = {
          ...estimateItemStub,
          id: Guid.raw(),
          taxes: [{ ...taxStub, id: Guid.raw() }],
        }
      },

      async fetchEstimateInitialSettings(isEdit) {
        const companyStore = useCompanyStore()
        const customerStore = useCustomerStore()
        const itemStore = useItemStore()
        const taxTypeStore = useTaxTypeStore()
        const route = useRoute()
        const userStore = useUserStore()

        this.isFetchingInitialSettings = true
        this.newEstimate.selectedCurrency = companyStore.selectedCompanyCurrency

        if (route.query.customer) {
          let response = await customerStore.fetchCustomer(route.query.customer)
          this.newEstimate.customer = response.data.data
          this.newEstimate.customer_id = response.data.data.id
        }

        let editActions = []

        if (!isEdit) {
          this.newEstimate.tax_per_item =
            companyStore.selectedCompanySettings.tax_per_item
          this.newEstimate.sales_tax_type = companyStore.selectedCompanySettings.sales_tax_type
          this.newEstimate.sales_tax_address_type = companyStore.selectedCompanySettings.sales_tax_address_type
          this.newEstimate.discount_per_item =
            companyStore.selectedCompanySettings.discount_per_item
          this.newEstimate.estimate_date = moment().format('YYYY-MM-DD')
          if (companyStore.selectedCompanySettings.estimate_set_expiry_date_automatically === 'YES') {
            this.newEstimate.expiry_date = moment()
              .add(companyStore.selectedCompanySettings.estimate_expiry_date_days, 'days')
              .format('YYYY-MM-DD')
          }
        } else {
          editActions = [this.fetchEstimate(route.params.id)]
        }

        Promise.all([
          itemStore.fetchItems({
            filter: {},
            orderByField: '',
            orderBy: '',
          }),
          this.resetSelectedNote(),
          this.fetchEstimateTemplates(),
          this.getNextNumber(),
          taxTypeStore.fetchTaxTypes({ limit: 'all' }),
          ...editActions,
        ])
          .then(async ([res1, res2, res3, res4, res5, res6, res7]) => {
            // Create
            if (!isEdit) {
              if (res4.data) {
                this.newEstimate.estimate_number = res4.data.nextNumber
              }

              this.setTemplate(this.templates[0].name)
              this.newEstimate.template_name =
                userStore.currentUserSettings.default_estimate_template ?
                userStore.currentUserSettings.default_estimate_template : this.newEstimate.template_name
            }

            if (isEdit) {
              this.addSalesTaxUs()
            }
            this.isFetchingInitialSettings = false
          })
          .catch((err) => {
            handleError(err)
            this.isFetchingInitialSettings = false
          })
      },
    },
  })()
}
