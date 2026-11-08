import axios from 'axios'
import { defineStore } from 'pinia'
import { useCompanyStore } from './company'
import { useUserStore } from './user'
import { useModuleStore } from './module'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'
import _ from 'lodash'

export const useGlobalStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'global',
    state: () => ({
      // Global Configuration
      config: null,
      globalSettings: null,

      // Global Lists
      timeZones: [],
      dateFormats: [],
      currencies: [],
      countries: [],
      languages: [],
      fiscalYears: [],

      // Menus
      mainMenu: [],
      settingMenu: [],

      // Boolean Flags
      isAppLoaded: false,
      isSidebarOpen: false,
      isSidebarCollapsed: localStorage.getItem('sidebar_collapsed') === 'true',
      areCurrenciesLoading: false,

      downloadReport: null,
    }),

    getters: {
      menuGroups: (state) => {
        return Object.values(_.groupBy(state.mainMenu, 'group'))
      },
    },

    actions: {
      bootstrap() {
        return new Promise((resolve, reject) => {
          axios
            .get('/api/v1/bootstrap')
            .then((response) => {
              const companyStore = useCompanyStore()
              const userStore = useUserStore()
              const moduleStore = useModuleStore()

              this.mainMenu = response.data.main_menu
              this.settingMenu = response.data.setting_menu

              this.config = response.data.config
              this.globalSettings = response.data.global_settings

              // user store
              userStore.currentUser = response.data.current_user
              userStore.currentUserSettings =
                response.data.current_user_settings
              userStore.currentAbilities = response.data.current_user_abilities

              // Module store
              moduleStore.apiToken = response.data.global_settings.api_token
              moduleStore.enableModules = response.data.modules

                // company store
                companyStore.companies = response.data.companies
              companyStore.selectedCompany = response.data.current_company
              companyStore.setSelectedCompany(response.data.current_company)
              companyStore.selectedCompanySettings =
                response.data.current_company_settings
              companyStore.selectedCompanyCurrency =
                response.data.current_company_currency

              global.locale =
                response.data.current_user_settings.language || 'en'

              this.isAppLoaded = true
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchCurrencies() {
        return new Promise((resolve, reject) => {
          if (this.currencies.length || this.areCurrenciesLoading) {
            resolve(this.currencies)
          } else {
            this.areCurrenciesLoading = true
            axios
              .get('/api/v1/currencies')
              .then((response) => {
                this.currencies = response.data.data.filter((currency) => {
                  return (currency.name = `${currency.code} - ${currency.name}`)
                })
                this.areCurrenciesLoading = false
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                this.areCurrenciesLoading = false
                reject(err)
              })
          }
        })
      },

      fetchConfig(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/config`, { params })
            .then((response) => {
              if (response.data.languages) {
                this.languages = response.data.languages
              } else {
                this.fiscalYears = response.data.fiscal_years
              }
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      fetchDateFormats() {
        return new Promise((resolve, reject) => {
          if (this.dateFormats.length) {
            resolve(this.dateFormats)
          } else {
            axios
              .get('/api/v1/date/formats')
              .then((response) => {
                this.dateFormats = response.data.date_formats
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                reject(err)
              })
          }
        })
      },

      fetchTimeZones() {
        return new Promise((resolve, reject) => {
          if (this.timeZones.length) {
            resolve(this.timeZones)
          } else {
            axios
              .get('/api/v1/timezones')
              .then((response) => {
                this.timeZones = response.data.time_zones
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                reject(err)
              })
          }
        })
      },

      fetchCountries() {
        return new Promise((resolve, reject) => {
          if (this.countries.length) {
            resolve(this.countries)
          } else {
            axios
              .get('/api/v1/countries')
              .then((response) => {
                this.countries = response.data.data
                resolve(response)
              })
              .catch((err) => {
                handleError(err)
                reject(err)
              })
          }
        })
      },

      fetchPlaceholders(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/number-placeholders`, { params })
            .then((response) => {
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      setSidebarVisibility(val) {
        this.isSidebarOpen = val
      },

      setSidebarCollapsed(val) {
        this.isSidebarCollapsed = val
        localStorage.setItem('sidebar_collapsed', val ? 'true' : 'false')
      },

      toggleSidebarCollapsed() {
        this.setSidebarCollapsed(!this.isSidebarCollapsed)
      },

      setIsAppLoaded(isAppLoaded) {
        this.isAppLoaded = isAppLoaded
      },

      updateGlobalSettings({ data, message }) {
        return new Promise((resolve, reject) => {
          axios
            .post('/api/v1/settings', data)
            .then((response) => {
              Object.assign(this.globalSettings, data.settings)

              if (message) {
                const notificationStore = useNotificationStore()

                notificationStore.showNotification({
                  type: 'success',
                  message: global.t(message),
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
