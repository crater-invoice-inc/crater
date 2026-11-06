<template>
  <BaseContentPlaceholders v-if="contentLoading">
    <BaseContentPlaceholdersBox
      :rounded="true"
      class="w-full"
      :style="`height: ${loadingPlaceholderSize}px`"
    />
  </BaseContentPlaceholders>

  <textarea
    v-else
    v-bind="$attrs"
    ref="textarea"
    :value="modelValue"
    :rows="computedRows"
    :class="[defaultInputClass, inputBorderClass]"
    :disabled="disabled"
    @input="onInput"
  />
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useEventListener, useResizeObserver } from '@vueuse/core'

const props = defineProps({
  contentLoading: {
    type: Boolean,
    default: false,
  },
  row: {
    type: Number,
    default: null,
  },
  invalid: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: [String, Number],
    default: '',
  },
  defaultInputClass: {
    type: String,
    default:
      'box-border w-full px-3 py-2 text-sm not-italic font-normal leading-snug text-left text-black placeholder-gray-400 bg-white border border-gray-200 border-solid rounded outline-none',
  },
  autosize: {
    type: Boolean,
    default: false,
  },
  borderless: {
    type: Boolean,
    default: false,
  },
})

const textarea = ref(null)

// In autosize mode the height is fully driven by the content, so the
// textarea starts at a single compact line and grows/shrinks from there.
const computedRows = computed(() => {
  if (props.autosize) {
    return 1
  }

  return props.row
})

const inputBorderClass = computed(() => {
  if (props.invalid && !props.borderless) {
    return 'border-red-400 ring-red-400 focus:ring-red-400 focus:border-red-400'
  } else if (!props.borderless) {
    return 'focus:ring-primary-400 focus:border-primary-400'
  }

  return 'border-none outline-none focus:ring-primary-400 focus:border focus:border-primary-400'
})

const loadingPlaceholderSize = computed(() => {
  switch (props.row) {
    case 2:
      return '56'
    case 4:
      return '94'
    default:
      return '56'
  }
})

const emit = defineEmits(['update:modelValue'])

function resize() {
  const el = textarea.value

  if (!el || !props.autosize) {
    return
  }

  // Resetting to auto first lets scrollHeight shrink back when text is
  // removed; scrollHeight never reports less than the content box.
  el.style.height = 'auto'
  el.style.height = `${el.scrollHeight}px`
}

function onInput(e) {
  emit('update:modelValue', e.target.value)

  if (props.autosize) {
    resize()
  }
}

// Programmatic updates (pre-filled service values, AI proposals, document
// hydration) bypass the input event, so recompute once the DOM has updated.
watch(
  () => props.modelValue,
  () => {
    if (props.autosize) {
      nextTick(resize)
    }
  }
)

// Keep the whole text visible when the available width changes (window
// resize, responsive layout, side panels). Only width changes trigger a
// recompute so the height we set ourselves does not loop the observer.
let observedWidth = 0

useResizeObserver(textarea, (entries) => {
  if (!props.autosize) {
    return
  }

  const width = entries[0].contentRect.width

  if (width && width !== observedWidth) {
    observedWidth = width
    resize()
  }
})

useEventListener(window, 'resize', resize)

onMounted(() => {
  if (textarea.value && props.autosize) {
    textarea.value.style.overflowY = 'hidden'
    textarea.value.style.resize = 'none'
    resize()
  }
})
</script>
