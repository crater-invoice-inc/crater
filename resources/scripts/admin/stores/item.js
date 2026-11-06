import axios from 'axios'
import { defineStore } from 'pinia'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

export const useItemStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'item',
    state: () => ({
      items: [],
      totalItems: 0,
      selectAllField: false,
      selectedItems: [],
      itemUnits: [],
      currentItemUnit: {
        id: null,
        name: '',
      },
      currentItem: {
        name: '',
        description: '',
        deliverables: '',
        delivery_delay: '',
        terms: '',
        price: 0,
        default_quantity: null,
        unit_id: '',
        unit: null,
        taxes: [],
        tax_per_item: false,
        is_active: false,
      },
    }),
    getters: {
      isItemUnitEdit: (state) => (state.currentItemUnit.id ? true : false),
    },
    actions: {
      resetCurrentItem() {
        this.currentItem = {
          name: '',
          description: '',
          deliverables: '',
          delivery_delay: '',
          terms: '',
          price: 0,
          default_quantity: null,
          unit_id: '',
          unit: null,
          taxes: [],
          is_active: false,
        }
      },
      fetchItems(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/items`, { params })
            .then((response) => {
              this.items = response.data.data
              this.totalItems = response.data.meta.item_total_count

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchItem(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/items/${id}`)
            .then((response) => {
              if (response.data) {
                Object.assign(this.currentItem, response.data.data)

                // Keep the form inputs usable when a service has no service
                // fields set yet (API returns null for those columns).
                ;['deliverables', 'delivery_delay', 'terms'].forEach((field) => {
                  this.currentItem[field] = this.currentItem[field] ?? ''
                })
                this.currentItem.default_quantity =
                  this.currentItem.default_quantity ?? null
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      addItem(data) {
        return new Promise((resolve, reject) => {
          axios
            .post('/api/v1/items', data)
            .then((response) => {
              const notificationStore = useNotificationStore()

              this.items.push(response.data.data)

              notificationStore.showNotification({
                type: 'success',
                message: global.t('items.created_message'),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      updateItem(data) {
        return new Promise((resolve, reject) => {
          axios
            .put(`/api/v1/items/${data.id}`, data)
            .then((response) => {
              if (response.data) {
                const notificationStore = useNotificationStore()

                let pos = this.items.findIndex(
                  (item) => item.id === response.data.data.id
                )

                this.items[pos] = data.item

                notificationStore.showNotification({
                  type: 'success',
                  message: global.t('items.updated_message'),
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

      deleteItem(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/items/delete`, id)
            .then((response) => {
              let index = this.items.findIndex((item) => item.id === id)
              this.items.splice(index, 1)

              notificationStore.showNotification({
                type: 'success',
                message: global.tc('items.deleted_message', 1),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteMultipleItems() {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/items/delete`, { ids: this.selectedItems })
            .then((response) => {
              this.selectedItems.forEach((item) => {
                let index = this.items.findIndex(
                  (_item) => _item.id === item.id
                )
                this.items.splice(index, 1)
              })

              notificationStore.showNotification({
                type: 'success',
                message: global.tc('items.deleted_message', 2),
              })

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      selectItem(data) {
        this.selectedItems = data
        if (this.selectedItems.length === this.items.length) {
          this.selectAllField = true
        } else {
          this.selectAllField = false
        }
      },

      selectAllItems(data) {
        if (this.selectedItems.length === this.items.length) {
          this.selectedItems = []
          this.selectAllField = false
        } else {
          let allItemIds = this.items.map((item) => item.id)
          this.selectedItems = allItemIds
          this.selectAllField = true
        }
      },

      addItemUnit(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .post(`/api/v1/units`, data)
            .then((response) => {
              this.itemUnits.push(response.data.data)

              if (response.data.data) {
                notificationStore.showNotification({
                  type: 'success',
                  message: global.t(
                    'settings.customization.items.item_unit_added'
                  ),
                })
              }

              if (response.data.errors) {
                notificationStore.showNotification({
                  type: 'error',
                  message: err.response.data.errors[0],
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

      updateItemUnit(data) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .put(`/api/v1/units/${data.id}`, data)
            .then((response) => {
              let pos = this.itemUnits.findIndex(
                (unit) => unit.id === response.data.data.id
              )

              this.itemUnits[pos] = data

              if (response.data.data) {
                notificationStore.showNotification({
                  type: 'success',
                  message: global.t(
                    'settings.customization.items.item_unit_updated'
                  ),
                })
              }

              if (response.data.errors) {
                notificationStore.showNotification({
                  type: 'error',
                  message: err.response.data.errors[0],
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

      fetchItemUnits(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/units`, { params })
            .then((response) => {
              this.itemUnits = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchItemUnit(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/units/${id}`)
            .then((response) => {
              this.currentItemUnit = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      deleteItemUnit(id) {
        const notificationStore = useNotificationStore()

        return new Promise((resolve, reject) => {
          axios
            .delete(`/api/v1/units/${id}`)
            .then((response) => {
              if (!response.data.error) {
                let index = this.itemUnits.findIndex((unit) => unit.id === id)
                this.itemUnits.splice(index, 1)
              }

              if (response.data.success) {
                notificationStore.showNotification({
                  type: 'success',
                  message: global.t(
                    'settings.customization.items.deleted_message'
                  ),
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
    },
  })()
}
