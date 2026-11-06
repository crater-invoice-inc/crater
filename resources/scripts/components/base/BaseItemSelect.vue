<template>
  <div class="flex-1 text-sm">
    <!-- Selected Item Field  -->
    <div v-if="item.item_id" class="relative flex items-center w-full">
      <BaseInput
        v-model="name"
        :invalid="invalid"
        :content-loading="contentLoading"
        class="w-full pr-7"
      />

      <span
        class="absolute text-gray-400 cursor-pointer top-[9px] right-[10px]"
        @click="deselectItem(index)"
      >
        <BaseIcon name="XCircleIcon" />
      </span>
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

    <!-- Item Description  -->
    <div class="w-full pt-1 text-xs text-light">
      <BaseTextarea
        v-model="description"
        :content-loading="contentLoading"
        :autosize="true"
        class="text-xs"
        :borderless="true"
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
    <div class="w-full pt-1 text-xs text-light">
      <BaseTextarea
        v-model="deliverables"
        :content-loading="contentLoading"
        :autosize="true"
        class="text-xs"
        :borderless="true"
        :placeholder="$t('items.deliverables')"
      />
    </div>

    <!-- Service Delivery Delay -->
    <div class="w-full pt-1 text-xs text-light">
      <BaseTextarea
        v-model="deliveryDelay"
        :content-loading="contentLoading"
        :autosize="true"
        class="text-xs"
        :borderless="true"
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
    <div class="w-full pt-1 text-xs text-light">
      <BaseTextarea
        v-model="terms"
        :content-loading="contentLoading"
        :autosize="true"
        class="text-xs"
        :borderless="true"
        :placeholder="$t('items.terms')"
      />
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
