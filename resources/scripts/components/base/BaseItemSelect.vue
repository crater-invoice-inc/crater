<template>
  <div class="flex-1 text-sm">
    <!-- Selected Item Field  -->
    <div v-if="item.item_id" class="flex items-center w-full gap-2">
      <BaseInput
        v-model="name"
        :invalid="invalid"
        :content-loading="contentLoading"
        class="w-full"
      />

      <!-- Dedicated zone next to the field: unlinks the service without
           touching the row (deletion lives in the amount column). -->
      <button
        v-tooltip="{ content: $t('items.deselect_service') }"
        type="button"
        class="
          flex
          items-center
          justify-center
          w-7
          h-7
          shrink-0
          text-gray-400
          rounded-md
          hover:text-gray-600
          hover:bg-gray-100
          focus:outline-none
          focus:ring-2
          focus:ring-primary-400
        "
        :aria-label="$t('items.deselect_service')"
        @click="deselectItem(index)"
      >
        <BaseIcon name="XCircleIcon" class="w-5 h-5" />
      </button>
    </div>

    <!-- Select Item Field -->
    <BaseMultiselect
      v-else
      v-model="itemSelect"
      :content-loading="contentLoading"
      value-prop="id"
      track-by="name"
      :invalid="invalid"
      preserve-search
      :initial-search="itemData.name"
      label="name"
      :filterResults="false"
      resolve-on-load
      :delay="500"
      searchable
      :options="searchItems"
      object
      @update:modelValue="(val) => $emit('select', val)"
      @searchChange="(val) => $emit('search', val)"
    >
      <!-- Add Item Action  -->
      <template #action>
        <BaseSelectAction
          v-if="userStore.hasAbilities(abilities.CREATE_ITEM)"
          @click="openItemModal"
        >
          <BaseIcon
            name="PlusCircleIcon"
            class="h-4 mr-2 -ml-2 text-center text-primary-400"
          />
          {{ $t('general.add_new_item') }}
        </BaseSelectAction>
      </template>
    </BaseMultiselect>

    <div class="mt-3 space-y-3">
      <!-- Item Description  -->
      <div>
        <label
          class="block mb-1 text-xs font-medium text-gray-600"
          :for="`${fieldIdPrefix}-description`"
        >
          {{ $t('items.description') }}
        </label>
        <BaseTextarea
          :id="`${fieldIdPrefix}-description`"
          v-model="description"
          :content-loading="contentLoading"
          :autosize="true"
          :placeholder="$t('estimates.item.type_item_description')"
          :invalid="invalidDescription"
        />
        <div v-if="invalidDescription">
          <span class="text-red-600">
            {{ $tc('validation.description_maxlength') }}
          </span>
        </div>
      </div>

      <!-- Service Deliverables -->
      <div>
        <label
          class="block mb-1 text-xs font-medium text-gray-600"
          :for="`${fieldIdPrefix}-deliverables`"
        >
          {{ $t('items.deliverables') }}
        </label>
        <BaseTextarea
          :id="`${fieldIdPrefix}-deliverables`"
          v-model="deliverables"
          :content-loading="contentLoading"
          :autosize="true"
          :placeholder="$t('items.deliverables')"
        />
      </div>

      <!-- Service Delivery Delay -->
      <div>
        <label
          class="block mb-1 text-xs font-medium text-gray-600"
          :for="`${fieldIdPrefix}-delivery-delay`"
        >
          {{ $t('items.delivery_delay') }}
        </label>
        <BaseTextarea
          :id="`${fieldIdPrefix}-delivery-delay`"
          v-model="deliveryDelay"
          :content-loading="contentLoading"
          :autosize="true"
          :placeholder="$t('items.delivery_delay')"
          :invalid="invalidDeliveryDelay"
        />
        <div v-if="invalidDeliveryDelay">
          <span class="text-red-600">
            {{ $t('validation.delivery_delay_maxlength') }}
          </span>
        </div>
      </div>

      <!-- Service Terms / Exclusions -->
      <div>
        <label
          class="block mb-1 text-xs font-medium text-gray-600"
          :for="`${fieldIdPrefix}-terms`"
        >
          {{ $t('items.terms') }}
        </label>
        <BaseTextarea
          :id="`${fieldIdPrefix}-terms`"
          v-model="terms"
          :content-loading="contentLoading"
          :autosize="true"
          :placeholder="$t('items.terms')"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useModalStore } from '@/scripts/stores/modal'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'

const props = defineProps({
  contentLoading: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: null,
  },
  item: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    default: 0,
  },
  invalid: {
    type: Boolean,
    required: false,
    default: false,
  },
  invalidDescription: {
    type: Boolean,
    required: false,
    default: false,
  },
  invalidDeliveryDelay: {
    type: Boolean,
    required: false,
    default: false,
  },
  taxPerItem: {
    type: String,
    default: '',
  },
  taxes: {
    type: Array,
    default: null,
  },
  store: {
    type: Object,
    default: null,
  },
  storeProp: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['search', 'select'])

const itemStore = useItemStore()
const estimateStore = useEstimateStore()
const invoiceStore = useInvoiceStore()
const modalStore = useModalStore()
const userStore = useUserStore()

let route = useRoute()
const { t } = useI18n()

const itemSelect = ref(null)
const loading = ref(false)
let itemData = reactive({ ...props.item })
Object.assign(itemData, props.item)

// Per-instance prefix wiring each permanent label to its textarea.
const fieldIdPrefix = `item-select-${Math.random().toString(36).slice(2, 10)}`

const taxAmount = computed(() => {
  return 0
})

const name = computed({
  get: () => props.item.name,
  set: (value) => {
    props.store.$patch((state) => {
      state[props.storeProp].items[props.index].name = value
    })
  },
})

const description = computed({
  get: () => props.item.description ?? '',
  set: (value) => {
    props.store.$patch((state) => {
      state[props.storeProp].items[props.index].description = value
    })
  },
})

const deliverables = computed({
  get: () => props.item.deliverables ?? '',
  set: (value) => {
    props.store.$patch((state) => {
      state[props.storeProp].items[props.index].deliverables = value
    })
  },
})

const deliveryDelay = computed({
  get: () => props.item.delivery_delay ?? '',
  set: (value) => {
    props.store.$patch((state) => {
      state[props.storeProp].items[props.index].delivery_delay = value
    })
  },
})

const terms = computed({
  get: () => props.item.terms ?? '',
  set: (value) => {
    props.store.$patch((state) => {
      state[props.storeProp].items[props.index].terms = value
    })
  },
})

async function searchItems(search) {
  let res = await itemStore.fetchItems({ search })
  return res.data.data
}

function onTextChange(val) {
  searchItems(val)
  emit('search', val)
}

function openItemModal() {
  modalStore.openModal({
    title: t('items.add_item'),
    componentName: 'ItemModal',
    refreshData: (val) => emit('select', val),
    data: {
      taxPerItem: props.taxPerItem,
      taxes: props.taxes,
      itemIndex: props.index,
      store: props.store,
      storeProps: props.storeProp,
    },
  })
}

function deselectItem(index) {
  props.store.deselectItem(index)
}
</script>
