<template>
  <div
    class="p-3 bg-white border border-gray-200 border-solid rounded-lg"
    :class="{ 'opacity-60': !line.checked }"
  >
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center min-w-0">
        <BaseCheckbox
          v-model="line.checked"
          class="mr-2"
        />
        <span
          v-if="lineRef"
          class="mr-2 text-xs font-medium text-gray-400 whitespace-nowrap"
        >
          {{ $t('ai_assistant.line_number', { index: lineRef }) }}
        </span>
      </div>

      <div class="flex flex-col items-end">
        <span class="text-xs text-gray-400">{{ $t('ai_assistant.field.total') }}</span>
        <BaseFormatMoney
          :amount="lineTotal"
          :currency="currency"
          class="text-sm font-semibold"
        />
      </div>
    </div>

    <div
      v-for="field in displayFields"
      :key="field"
      class="mt-2"
    >
      <label class="block mb-1 text-xs text-gray-500">
        {{ $t('ai_assistant.field.' + field) }}
      </label>

      <BaseTextarea
        v-if="textareaFields.includes(field)"
        v-model="line[field]"
        :row="2"
      />

      <BaseInput
        v-else-if="field === 'price'"
        v-model="priceInput"
        type="number"
        min="0"
        step="0.01"
        small
      />

      <BaseInput
        v-else-if="field === 'quantity'"
        v-model="quantityInput"
        type="number"
        min="0"
        step="any"
        small
      />

      <BaseInput
        v-else
        v-model="line[field]"
        type="text"
        small
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Reactive proposal line coming from the ai-assistant store.
  line: {
    type: Object,
    required: true,
  },
  currency: {
    type: [Object, String],
    default: () => '',
  },
  // When updating a line, only the changed fields are proposed.
  fieldsToShow: {
    type: Array,
    default: null,
  },
  // 1-based index of the document line (update_line display).
  lineRef: {
    type: Number,
    default: null,
  },
})

const textareaFields = ['description', 'deliverables', 'terms']

const allFields = [
  'name',
  'description',
  'deliverables',
  'delivery_delay',
  'terms',
  'unit_name',
  'quantity',
  'price',
]

const displayFields = computed(() => props.fieldsToShow || allFields)

// The proposal price is stored in cents; the input works in currency units,
// like the document form does.
const priceInput = computed({
  get: () => {
    return props.line.price > 0 ? props.line.price / 100 : props.line.price
  },
  set: (newValue) => {
    const parsed = parseFloat(newValue)

    if (Number.isFinite(parsed) && parsed > 0) {
      props.line.price = Math.round(parsed * 100)
    } else if (newValue === '' || parsed === 0) {
      props.line.price = 0
    }
  },
})

const quantityInput = computed({
  get: () => props.line.quantity,
  set: (newValue) => {
    const parsed = parseFloat(newValue)

    props.line.quantity = Number.isFinite(parsed) && parsed >= 0 ? parsed : 0
  },
})

const lineTotal = computed(() => {
  return Math.round(
    (Number(props.line.price) || 0) * (Number(props.line.quantity) || 0)
  )
})
</script>
