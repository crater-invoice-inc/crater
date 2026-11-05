import axios from 'axios'
import { defineStore } from 'pinia'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

export const useCalculatorStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'calculator',
    state: () => ({
      calculators: [],
      totalCalculators: 0,
      selectedCalculators: [],
      selectAllField: false,
      templates: [],
      currentTemplate: {
        name: '',
        category: 'production',
        unit_price: 0,
      },
      isTemplateEdit: false,
      currentCalculator: {
        name: '',
        notes: '',
        customer_id: null,
        customer: null,
        calculator_items: [],
      },
    }),
    actions: {
      resetCurrentCalculator() {
        this.currentCalculator = {
          name: '',
          notes: '',
          customer_id: null,
          customer: null,
          calculator_items: [],
        }
      },

      selectCustomer(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/customers/${id}`)
            .then((response) => {
              this.currentCalculator.customer = response.data.data
              this.currentCalculator.customer_id = response.data.data.id
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      resetSelectedCustomer() {
        this.currentCalculator.customer = null
        this.currentCalculator.customer_id = null
      },
      fetchCalculators(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/calculators`, { params })
            .then((response) => {
              this.calculators = response.data.data
              this.totalCalculators =
                response.data.meta.calculator_total_count

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchCalculator(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/calculators/${id}`)
            .then((response) => {
              if (response.data) {
                this.currentCalculator = {
                  ...this.currentCalculator,
                  ...response.data.data,
                }
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addCalculator(data) {
        return new Promise((resolve, reject) => {
          axios
            .post('/api/v1/calculators', data)
            .then((response) => {
              const notificationStore = useNotificationStore()

              notificationStore.showNotification({
                type: 'success',
                message: global.t('calculator.created_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateCalculator(data) {
        return new Promise((resolve, reject) => {
          axios
            .put(`/api/v1/calculators/${data.id}`, data)
            .then((response) => {
              if (response.data) {
                const notificationStore = useNotificationStore()

                notificationStore.showNotification({
                  type: 'success',
                  message: global.t('calculator.updated_message'),
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

      deleteCalculator(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/calculators/delete`, { ids: [id] })
            .then((response) => {
              let index = this.calculators.findIndex(
                (calculator) => calculator.id === id
              )
              this.calculators.splice(index, 1)

              notificationStore.showNotification({
                type: 'success',
                message: global.t('calculator.deleted_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      selectCalculator(data) {
        this.selectedCalculators = data
        if (this.selectedCalculators.length === this.calculators.length) {
          this.selectAllField = true
        } else {
          this.selectAllField = false
        }
      },

      // Templates (predefined roles & expenses)
      fetchTemplates(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/calculator-templates`, { params })
            .then((response) => {
              this.templates = response.data.data

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addTemplate(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/calculator-templates`, data)
            .then((response) => {
              this.templates.push(response.data.data)

              notificationStore.showNotification({
                type: 'success',
                message: global.t('calculator.templates.created_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateTemplate(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .put(`/api/v1/calculator-templates/${data.id}`, data)
            .then((response) => {
              let pos = this.templates.findIndex(
                (template) => template.id === response.data.data.id
              )

              this.templates[pos] = response.data.data

              notificationStore.showNotification({
                type: 'success',
                message: global.t('calculator.templates.updated_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteTemplate(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/calculator-templates/delete`, { ids: [id] })
            .then((response) => {
              let index = this.templates.findIndex(
                (template) => template.id === id
              )
              this.templates.splice(index, 1)

              notificationStore.showNotification({
                type: 'success',
                message: global.t('calculator.templates.deleted_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      resetCurrentTemplate() {
        this.currentTemplate = {
          name: '',
          category: 'production',
          unit_price: 0,
        }
        this.isTemplateEdit = false
      },
    },
  })()
}
