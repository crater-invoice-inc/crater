<template>
  <div
    class="
      px-6
      py-5
      mt-6
      bg-surface
      border border-line-light border-solid
      rounded-2xl
      shadow-card
      md:min-w-[390px]
      min-w-[300px]
      lg:mt-7
    "
  >
    <div class="flex items-center justify-between w-full">
      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
      </BaseContentPlaceholders>
      <label
        v-else
        class="text-sm font-medium leading-5 text-muted"
      >
        {{ $t('estimates.sub_total') }}
      </label>

      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
      </BaseContentPlaceholders>

      <label
        v-else
        class="flex items-center justify-center m-0 text-lg font-semibold text-heading"
      >
        <BaseFormatMoney
          :amount="store.getSubTotal"
          :currency="defaultCurrency"
        />
      </label>
    </div>

    <div
      v-for="tax in itemWiseTaxes"
      :key="tax.tax_type_id"
      class="flex items-center justify-between w-full"
    >
      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
      </BaseContentPlaceholders>
      <label
        v-else-if="store[storeProp].tax_per_item === 'YES'"
        class="m-0 text-sm font-medium leading-5 text-muted"
      >
        {{ tax.name }} - {{ tax.percent }}%
      </label>

      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
      </BaseContentPlaceholders>

      <label
        v-else-if="store[storeProp].tax_per_item === 'YES'"
        class="flex items-center justify-center m-0 text-lg font-semibold text-heading"
      >
        <BaseFormatMoney :amount="tax.amount" :currency="defaultCurrency" />
      </label>
    </div>

    <div
      v-if="
        store[storeProp].discount_per_item === 'NO' ||
        store[storeProp].discount_per_item === null
      "
      class="flex items-center justify-between w-full mt-2"
    >
      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
      </BaseContentPlaceholders>
      <label
        v-else
        class="text-sm font-medium leading-5 text-muted"
      >
        {{ $t('estimates.discount') }}
      </label>
      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersText
          :lines="1"
          class="w-24 h-8 border rounded-md"
        />
      </BaseContentPlaceholders>
      <div v-else class="flex" style="width: 140px" role="group">
        <BaseInput
          v-model="totalDiscount"
          class="
            border-r-0
            focus:border-r-2
            rounded-tr-sm rounded-br-sm
            h-[38px]
          "
        />
        <BaseDropdown position="bottom-end">
          <template #activator>
            <BaseButton
              class="p-2 rounded-none rounded-tr-lg rounded-br-lg"
              type="button"
              variant="white"
            >
              <span class="flex items-center">
                {{
                  store[storeProp].discount_type == 'fixed'
                    ? defaultCurrency.symbol
                    : '%'
                }}

                <BaseIcon
                  name="ChevronDownIcon"
                  class="w-4 h-4 ml-1 text-gray-500"
                />
              </span>
            </BaseButton>
          </template>

          <BaseDropdownItem @click="selectFixed">
            {{ $t('general.fixed') }}
          </BaseDropdownItem>

          <BaseDropdownItem @click="selectPercentage">
            {{ $t('general.percentage') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>
    </div>

    <div
      v-if="
        store[storeProp].tax_per_item === 'NO' ||
        store[storeProp].tax_per_item === null
      "
    >
      <Tax
        v-for="(tax, index) in taxes"
        :key="tax.id"
        :index="index"
        :tax="tax"
        :taxes="taxes"
        :currency="currency"
        :store="store"
        @remove="removeTax"
        @update="updateTax"
      />
    </div>

    <div
      v-if="
        store[storeProp].tax_per_item === 'NO' ||
        store[storeProp].tax_per_item === null
      "
      ref="taxModal"
      class="float-right pt-2 pb-4"
    >
      <SelectTaxPopup
        :store-prop="storeProp"
        :store="store"
        :type="taxPopupType"
        @select:taxType="onSelectTax"
      />
    </div>

    <div
      class="flex items-center justify-between w-full pt-2 mt-5 border-t border-line-light border-solid "
    >
      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
      </BaseContentPlaceholders>
      <label
        v-else
        class="m-0 text-sm font-semibold leading-5 text-heading"
        >{{ $t('estimates.total') }} {{ $t('estimates.amount') }}:</label
      >

      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
      </BaseContentPlaceholders>
      <label
        v-else
        class="flex items-center justify-center text-lg font-bold text-primary-600"
      >
        <BaseFormatMoney :amount="store.getTotal" :currency="defaultCurrency" />
      </label>
    </div>
  </div>
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import Guid from 'guid'
import Tax from './CreateTotalTaxes.vue'
import TaxStub from '@/scripts/admin/stub/abilities'
import SelectTaxPopup from './SelectTaxPopup.vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const taxModal = ref(null)

const props = defineProps({
  store: {
    type: Object,
    default: null,
  },
  storeProp: {
    type: String,
    default: '',
  },
  taxPopupType: {
    type: String,
    default: '',
  },
  currency: {
    type: [Object, String],
    default: '',
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
})

const utils = inject('$utils')

const companyStore = useCompanyStore()

const totalDiscount = computed({
  get: () => {
    return props.store[props.storeProp].discount
  },
  set: (newValue) => {
    if (props.store[props.storeProp].discount_type === 'percentage') {
      props.store[props.storeProp].discount_val = Math.round(
        (props.store.getSubTotal * newValue) / 100
      )
    } else {
      props.store[props.storeProp].discount_val = Math.round(newValue * 100)
    }
    props.store[props.storeProp].discount = newValue
  },
})

const taxes = computed({
  get: () => props.store[props.storeProp].taxes,
  set: (value) => {
    props.store.$patch((state) => {
      state[props.storeProp].taxes = value
    })
  },
})

const itemWiseTaxes = computed(() => {
  let taxes = []
  props.store[props.storeProp].items.forEach((item) => {
    if (item.taxes) {
      item.taxes.forEach((tax) => {
        let found = taxes.find((_tax) => {
          return _tax.tax_type_id === tax.tax_type_id
        })
        if (found) {
          found.amount += tax.amount
        } else if (tax.tax_type_id) {
          taxes.push({
            tax_type_id: tax.tax_type_id,
            amount: tax.amount,
            percent: tax.percent,
            name: tax.name,
          })
        }
      })
    }
  })
  return taxes
})

const defaultCurrency = computed(() => {
  if (props.currency) {
    return props.currency
  } else {
    return companyStore.selectedCompanyCurrency
  }
})

function selectFixed() {
  if (props.store[props.storeProp].discount_type === 'fixed') {
    return
  }
  props.store[props.storeProp].discount_val = Math.round(
    props.store[props.storeProp].discount * 100
  )
  props.store[props.storeProp].discount_type = 'fixed'
}

function selectPercentage() {
  if (props.store[props.storeProp].discount_type === 'percentage') {
    return
  }
  props.store[props.storeProp].discount_val =
    (props.store.getSubTotal * props.store[props.storeProp].discount) / 100
  props.store[props.storeProp].discount_type = 'percentage'
}

function onSelectTax(selectedTax) {
  let amount = 0

  if (selectedTax.compound_tax && props.store.getSubtotalWithDiscount) {
    amount = Math.round(
      ((props.store.getSubtotalWithDiscount + props.store.getTotalSimpleTax) *
        selectedTax.percent) /
        100
    )
  } else if (props.store.getSubtotalWithDiscount && selectedTax.percent) {
    amount = Math.round(
      (props.store.getSubtotalWithDiscount * selectedTax.percent) / 100
    )
  }

  let data = {
    ...TaxStub,
    id: Guid.raw(),
    name: selectedTax.name,
    percent: selectedTax.percent,
    compound_tax: selectedTax.compound_tax,
    tax_type_id: selectedTax.id,
    amount,
  }
  props.store.$patch((state) => {
    state[props.storeProp].taxes.push({ ...data })
  })
}

function updateTax(data) {
  const tax = props.store[props.storeProp].taxes.find(
    (tax) => tax.id === data.id
  )
  if (tax) {
    Object.assign(tax, { ...data })
  }
}

function removeTax(id) {
  const index = props.store[props.storeProp].taxes.findIndex(
    (tax) => tax.id === id
  )

  props.store.$patch((state) => {
    state[props.storeProp].taxes.splice(index, 1)
  })
}
</script>
