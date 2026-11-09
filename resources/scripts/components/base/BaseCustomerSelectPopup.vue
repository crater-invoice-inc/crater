<template>
  <BaseContentPlaceholders v-if="contentLoading">
    <BaseContentPlaceholdersBox
      :rounded="true"
      class="w-full"
      style="min-height: 104px"
    />
  </BaseContentPlaceholders>

  <div v-else>
    <CustomerModal />
    <!-- <SalesTax :type="type" /> -->

    <div
      v-if="selectedCustomer"
      class="p-5 bg-surface border border-line-light border-solid rounded-xl shadow-card"
      @click.stop
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center min-w-0 gap-4">
          <span
            class="
              flex
              items-center
              justify-center
              w-12
              h-12
              shrink-0
              text-base
              font-semibold
              text-white
              bg-primary-500
              rounded-xl
            "
          >
            {{ initGenerator(selectedCustomer.name) }}
          </span>
          <div class="min-w-0">
            <p class="text-xs leading-tight text-muted">
              {{ $t('customers.customer') }}
            </p>
            <BaseText
              :text="selectedCustomer.name"
              :length="30"
              class="
                block
                text-lg
                font-semibold
                leading-snug
                text-left text-heading
                truncate
              "
            />
            <p
              v-if="selectedCustomer.contact_name"
              class="text-sm leading-snug text-muted truncate"
            >
              {{ selectedCustomer.contact_name }}
            </p>
          </div>
        </div>

        <div class="flex items-center shrink-0 gap-1">
          <button
            type="button"
            :title="$t('general.edit')"
            class="
              p-2
              rounded-lg
              text-muted
              hover:bg-surface-secondary hover:text-heading
              transition-colors
              cursor-pointer
              focus:outline-none
            "
            @click.stop="editCustomer"
          >
            <BaseIcon name="PencilIcon" class="h-4 w-4" />
          </button>
          <button
            type="button"
            :title="$t('general.deselect')"
            class="
              p-2
              rounded-lg
              text-muted
              hover:bg-surface-secondary hover:text-heading
              transition-colors
              cursor-pointer
              focus:outline-none
            "
            @click="resetSelectedCustomer"
          >
            <BaseIcon name="XIcon" class="h-4 w-4" />
          </button>
        </div>
      </div>

      <div
        v-if="selectedCustomer.billing"
        class="pt-4 mt-4 border-t border-line-light border-solid"
      >
        <p class="mb-1 text-sm text-muted">
          {{ $t('general.bill_to') }}
        </p>

        <div class="text-sm leading-relaxed text-left">
          <p class="m-0">
            <span v-if="selectedCustomer.billing.city">
              {{ selectedCustomer.billing.city }}
            </span>
            <span
              v-if="
                selectedCustomer.billing.city && selectedCustomer.billing.state
              "
            >
              ,
            </span>
            <span v-if="selectedCustomer.billing.state">
              {{ selectedCustomer.billing.state }}
            </span>
          </p>
          <p v-if="selectedCustomer.billing.zip" class="m-0">
            {{ selectedCustomer.billing.zip }}
          </p>
        </div>
      </div>
    </div>

    <Popover v-else v-slot="{ open }" class="relative flex flex-col rounded-md">
      <PopoverButton
        :class="{
          'text-opacity-90': open,
          'border border-solid border-red-500 focus:ring-red-500 rounded-xl':
            valid.$error,
          'focus:ring-2 focus:ring-primary-400': !valid.$error,
        }"
        class="w-full outline-none rounded-xl"
      >
        <div
          class="
            relative
            flex
            items-center
            justify-center
            px-4
            py-6
            bg-surface
            border-2 border-dashed border-line-strong
            border-solid
            rounded-xl
            transition-colors
          "
          :class="open ? 'border-primary-400' : ''"
        >
          <BaseIcon
            name="UserIcon"
            class="
              flex
              justify-center
              !w-10
              !h-10
              p-2
              mr-4
              text-base text-muted
              bg-surface-muted
              rounded-lg
              shrink-0
              font-base
            "
          />

          <div class="text-left">
            <label class="text-base font-medium text-heading">
              {{ $t('customers.new_customer') }}
              <span class="text-red-500"> * </span>
            </label>

            <p
              v-if="valid.$error && valid.$errors[0].$message"
              class="text-red-500 text-sm absolute right-3 bottom-3"
            >
              {{ $t('estimates.errors.required') }}
            </p>
          </div>

          <BaseIcon
            name="ChevronDownIcon"
            class="!w-5 !h-5 ml-auto shrink-0 text-muted"
          />
        </div>
      </PopoverButton>

      <!-- Customer Select Popup -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-1 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-1 opacity-0"
      >
        <div v-if="open" class="absolute min-w-full z-10">
          <PopoverPanel
            v-slot="{ close }"
            focus
            static
            class="
              overflow-hidden
              rounded-xl
              shadow-card
              ring-1 ring-black ring-opacity-5
              bg-surface
            "
          >
            <div class="relative">
              <BaseInput
                v-model="search"
                container-class="m-4"
                :placeholder="$t('general.search')"
                type="text"
                icon="search"
                @update:modelValue="(val) => debounceSearchCustomer(val)"
              />

              <ul
                class="
                  max-h-80
                  flex flex-col
                  overflow-auto
                  list
                  border-t border-line-light
                "
              >
                <li
                  v-for="(customer, index) in customerStore.customers"
                  :key="index"
                  href="#"
                  class="
                    flex
                    px-6
                    py-2
                    border-b border-line-light border-solid
                    cursor-pointer
                    hover:cursor-pointer hover:bg-surface-secondary
                    focus:outline-none focus:bg-surface-secondary
                    last:border-b-0
                  "
                  @click="selectNewCustomer(customer.id, close)"
                >
                  <span
                    class="
                      flex
                      items-center
                      content-center
                      justify-center
                      w-10
                      h-10
                      mr-4
                      text-xl
                      font-semibold
                      leading-9
                      text-muted
                      bg-surface-muted
                      rounded-full
                      avatar
                    "
                  >
                    {{ initGenerator(customer.name) }}
                  </span>

                  <div class="flex flex-col justify-center text-left">
                    <BaseText
                      v-if="customer.name"
                      :text="customer.name"
                      :length="30"
                      class="
                        m-0
                        text-base
                        font-normal
                        leading-tight
                        cursor-pointer
                      "
                    />
                    <BaseText
                      v-if="customer.contact_name"
                      :text="customer.contact_name"
                      :length="30"
                      class="
                        m-0
                        text-sm
                        font-medium
                        text-gray-400
                        cursor-pointer
                      "
                    />
                  </div>
                </li>
                <div
                  v-if="customerStore.customers.length === 0"
                  class="flex justify-center p-5 text-gray-400"
                >
                  <label class="text-base text-gray-500 cursor-pointer">
                    {{ $t('customers.no_customers_found') }}
                  </label>
                </div>
              </ul>
            </div>

            <button
              v-if="userStore.hasAbilities(abilities.CREATE_CUSTOMER)"
              type="button"
              class="
                h-10
                flex
                items-center
                justify-center
                w-full
                px-2
                py-3
                bg-surface-secondary
                border-none
                outline-none
                hover:bg-surface-muted
                transition-colors
                text-primary-500
              "
              @click="openCustomerModal"
            >
              <BaseIcon name="UserAddIcon" class="text-primary-500" />

              <label
                class="
                  m-0
                  ml-3
                  text-sm
                  leading-none
                  cursor-pointer
                  font-base
                  text-primary-400
                "
              >
                {{ $t('customers.add_new_customer') }}
              </label>
            </button>
          </PopoverPanel>
        </div>
      </transition>
    </Popover>
  </div>
</template>

<script setup>
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useRecurringInvoiceStore } from '@/scripts/admin/stores/recurring-invoice'
import { useModalStore } from '@/scripts/stores/modal'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDebounceFn } from '@vueuse/core'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import { useRoute } from 'vue-router'
import CustomerModal from '@/scripts/admin/components/modal-components/CustomerModal.vue'
import { useCalculatorStore } from '@/scripts/admin/stores/calculator'

const props = defineProps({
  valid: {
    type: Object,
    default: () => {},
  },
  customerId: {
    type: Number,
    default: null,
  },
  type: {
    type: String,
    default: null,
  },
  contentLoading: {
    type: Boolean,
    default: false,
  },
})

const modalStore = useModalStore()
const estimateStore = useEstimateStore()
const customerStore = useCustomerStore()
const globalStore = useGlobalStore()
const invoiceStore = useInvoiceStore()
const recurringInvoiceStore = useRecurringInvoiceStore()
const calculatorStore = useCalculatorStore()
const userStore = useUserStore()
const routes = useRoute()
const { t } = useI18n()
const search = ref(null)
const isSearchingCustomer = ref(false)

const selectedCustomer = computed(() => {
  switch (props.type) {
    case 'estimate':
      return estimateStore.newEstimate.customer
    case 'invoice':
      return invoiceStore.newInvoice.customer
    case 'recurring-invoice':
      return recurringInvoiceStore.newRecurringInvoice.customer
    case 'calculator':
      return calculatorStore.currentCalculator.customer
    default:
      return ''
  }
})

function resetSelectedCustomer() {
  if (props.type === 'estimate') {
    estimateStore.resetSelectedCustomer()
  } else if (props.type === 'invoice') {
    invoiceStore.resetSelectedCustomer()
  } else if (props.type === 'calculator') {
    calculatorStore.resetSelectedCustomer()
  } else {
    recurringInvoiceStore.resetSelectedCustomer()
  }
}

if (props.customerId && props.type === 'estimate') {
  estimateStore.selectCustomer(props.customerId)
} else if (props.customerId && props.type === 'invoice') {
  invoiceStore.selectCustomer(props.customerId)
} else if (props.customerId && props.type === 'calculator') {
  calculatorStore.selectCustomer(props.customerId)
} else {
  if (props.customerId) recurringInvoiceStore.selectCustomer(props.customerId)
}

async function editCustomer() {
  await customerStore.fetchCustomer(selectedCustomer.value.id)
  modalStore.openModal({
    title: t('customers.edit_customer'),
    componentName: 'CustomerModal',
  })
}

async function fetchInitialCustomers() {
  await customerStore.fetchCustomers({
    filter: {},
    orderByField: '',
    orderBy: '',
    customer_id: props.customerId,
  })
}

const debounceSearchCustomer = useDebounceFn(() => {
  isSearchingCustomer.value = true

  searchCustomer()
}, 500)

async function searchCustomer() {
  let data = {
    display_name: search.value,
    page: 1,
  }

  await customerStore.fetchCustomers(data)
  isSearchingCustomer.value = false
}

function openCustomerModal() {
  modalStore.openModal({
    title: t('customers.add_customer'),
    componentName: 'CustomerModal',
    variant: 'md',
  })
}

function initGenerator(name) {
  if (name) {
    let nameSplit = name.split(' ')
    let initials = nameSplit[0].charAt(0).toUpperCase()
    return initials
  }
}

function selectNewCustomer(id, close) {
  let params = {
    userId: id,
  }
  if (routes.params.id) params.model_id = routes.params.id

  if (props.type === 'estimate') {
    estimateStore.getNextNumber(params, true)
    estimateStore.selectCustomer(id)
  } else if (props.type === 'invoice') {
    invoiceStore.getNextNumber(params, true)
    invoiceStore.selectCustomer(id)
  } else if (props.type === 'calculator') {
    calculatorStore.selectCustomer(id)
  } else {
    recurringInvoiceStore.selectCustomer(id)
  }
  close()
  search.value = null
}

globalStore.fetchCurrencies()
globalStore.fetchCountries()
fetchInitialCustomers()
</script>
