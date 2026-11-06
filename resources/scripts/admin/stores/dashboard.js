import axios from 'axios'
import { defineStore } from 'pinia'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { handleError } from '@/scripts/helpers/error-handling'

export const useDashboardStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'dashboard',

    state: () => ({
      stats: {
        totalAmountDue: 0,
        totalCustomerCount: 0,
        totalInvoiceCount: 0,
        totalEstimateCount: 0,
      },

      chartData: {
        months: [],
        invoiceTotals: [],
        expenseTotals: [],
        receiptTotals: [],
        netIncomeTotals: [],
      },

      totalSales: null,
      totalReceipts: null,
      totalExpenses: null,
      totalNetIncome: null,

      previousYear: {
        sales: 0,
        receipts: 0,
        expenses: 0,
        netIncome: 0,
      },

      availableYears: [],

      recentDueInvoices: [],
      recentEstimates: [],

      isDashboardDataLoaded: false,
    }),

    actions: {
      // Lightweight counts for the sidebar badges; does not touch
      // chart data or isDashboardDataLoaded like loadData does.
      loadCounts() {
        return new Promise((resolve, reject) => {
          axios
            .get('/api/v1/sidebar/counts')
            .then((response) => {
              this.stats.totalCustomerCount =
                response.data.total_customer_count
              this.stats.totalInvoiceCount = response.data.total_invoice_count
              this.stats.totalEstimateCount =
                response.data.total_estimate_count

              resolve(response)
            })
            .catch((err) => {
              reject(err)
            })
        })
      },

      loadData(params) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/api/v1/dashboard`, { params })
            .then((response) => {
              // Stats
              this.stats.totalAmountDue = response.data.total_amount_due
              this.stats.totalCustomerCount = response.data.total_customer_count
              this.stats.totalInvoiceCount = response.data.total_invoice_count
              this.stats.totalEstimateCount = response.data.total_estimate_count

              // Dashboard Chart
              if (this.chartData && response.data.chart_data) {
                this.chartData.months = response.data.chart_data.months
                this.chartData.invoiceTotals =
                  response.data.chart_data.invoice_totals
                this.chartData.expenseTotals =
                  response.data.chart_data.expense_totals
                this.chartData.receiptTotals =
                  response.data.chart_data.receipt_totals
                this.chartData.netIncomeTotals =
                  response.data.chart_data.net_income_totals
              }

              // Dashboard Chart Labels
              this.totalSales = response.data.total_sales
              this.totalReceipts = response.data.total_receipts
              this.totalExpenses = response.data.total_expenses
              this.totalNetIncome = response.data.total_net_income

              this.previousYear.sales = response.data.previous_year_sales
              this.previousYear.receipts = response.data.previous_year_receipts
              this.previousYear.expenses = response.data.previous_year_expenses
              this.previousYear.netIncome =
                response.data.previous_year_net_income

              this.availableYears = response.data.available_years

              // Dashboard Table Data
              this.recentDueInvoices = response.data.recent_due_invoices
              this.recentEstimates = response.data.recent_estimates

              this.isDashboardDataLoaded = true

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
