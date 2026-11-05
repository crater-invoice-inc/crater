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
      selectAllField: false,
      selectedCalculators: [],
      currentCalculator: {
        name: '',
        notes: '',
        calculator_items: [],
      },
    }),
    actions: {
      resetCurrentCalculator() {
        this.currentCalculator = {
          name: '',
          notes: '',
          calculator_items: [],
        }
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

      deleteMultipleCalculators() {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/calculators/delete`, { ids: this.selectedCalculators })
            .then((response) => {
              this.selectedCalculators.forEach((calculator) => {
                let index = this.calculators.findIndex(
                  (_calculator) => _calculator.id === calculator.id
                )
                this.calculators.splice(index, 1)
              })

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

      selectAllCalculators() {
        if (this.selectedCalculators.length === this.calculators.length) {
          this.selectedCalculators = []
          this.selectAllField = false
        } else {
          let allCalculatorIds = this.calculators.map(
            (calculator) => calculator.id
          )
          this.selectedCalculators = allCalculatorIds
          this.selectAllField = true
        }
      },
    },
  })()
}
