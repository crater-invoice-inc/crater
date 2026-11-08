<script setup>
import { computed, ref } from 'vue'
import SpinnerIcon from '@/scripts/components/icons/SpinnerIcon.vue'
const props = defineProps({
  contentLoading: {
    type: Boolean,
    default: false,
  },
  defaultClass: {
    type: String,
    default:
      'inline-flex whitespace-nowrap items-center border font-medium focus:outline-none focus:ring-2 focus:ring-offset-2',
  },
  tag: {
    type: String,
    default: 'button',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  rounded: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
    validator: function (value) {
      return ['xs', 'sm', 'md', 'lg', 'xl'].indexOf(value) !== -1
    },
  },
  variant: {
    type: String,
    default: 'primary',
    validator: function (value) {
      return (
        [
          'primary',
          'secondary',
          'primary-outline',
          'white',
          'danger',
          'gray',
        ].indexOf(value) !== -1
      )
    },
  },
})

const sizeClass = computed(() => {
  return {
    'px-2.5 py-1.5 text-xs leading-4 rounded-lg': props.size === 'xs',
    'px-3 py-2 text-sm leading-4 rounded-lg': props.size == 'sm',
    'px-4 py-2 text-sm leading-5 rounded-lg': props.size === 'md',
    'px-4 py-2 text-base leading-6 rounded-lg': props.size === 'lg',
    'px-6 py-3 text-base leading-6 rounded-lg': props.size === 'xl',
  }
})

const placeHolderSize = computed(() => {
  switch (props.size) {
    case 'xs':
      return '32'
    case 'sm':
      return '38'
    case 'md':
      return '42'
    case 'lg':
      return '42'
    case 'xl':
      return '46'
    default:
      return ''
  }
})

const variantClass = computed(() => {
  return {
    'border-transparent shadow-soft text-white bg-primary-600 hover:bg-primary-700 focus:ring-primary-500':
      props.variant === 'primary',
    'border-transparent text-primary-700 bg-primary-100 hover:bg-primary-200 focus:ring-primary-500':
      props.variant === 'secondary',
    'border-transparent  border-solid border-primary-500 font-normal transition ease-in-out duration-150 text-primary-500 hover:bg-primary-100 focus:ring-primary-500':
      props.variant == 'primary-outline',
    'border-line-light text-heading bg-surface hover:bg-surface-secondary focus:ring-primary-500 focus:ring-offset-0':
      props.variant == 'white',
    'border-transparent shadow-soft text-white bg-danger hover:bg-red-700 focus:ring-red-500':
      props.variant === 'danger',
    'border-transparent bg-surface-muted border border-line-light hover:bg-opacity-60 focus:ring-gray-500 focus:ring-offset-0 text-heading':
      props.variant === 'gray',
  }
})

const roundedClass = computed(() => {
  return props.rounded ? '!rounded-full' : ''
})

const iconLeftClass = computed(() => {
  return {
    '-ml-0.5 mr-2 h-4 w-4': props.size == 'sm',
    '-ml-1 mr-2 h-5 w-5': props.size === 'md',
    '-ml-1 mr-3 h-5 w-5': props.size === 'lg' || props.size === 'xl',
  }
})

const iconVariantClass = computed(() => {
  return {
    'text-white': props.variant === 'primary',
    'text-primary-700': props.variant === 'secondary',
    'text-gray-700': props.variant === 'white',
    'text-gray-400': props.variant === 'gray',
  }
})

const iconRightClass = computed(() => {
  return {
    'ml-2 -mr-0.5 h-4 w-4': props.size == 'sm',
    'ml-2 -mr-1 h-5 w-5': props.size === 'md',
    'ml-3 -mr-1 h-5 w-5': props.size === 'lg' || props.size === 'xl',
  }
})
</script>

<template>
  <BaseContentPlaceholders
    v-if="contentLoading"
    class="disabled cursor-normal pointer-events-none"
  >
    <BaseContentPlaceholdersBox
      :rounded="true"
      style="width: 96px"
      :style="`height: ${placeHolderSize}px;`"
    />
  </BaseContentPlaceholders>

  <BaseCustomTag
    v-else
    :tag="tag"
    :disabled="disabled"
    :class="[defaultClass, sizeClass, variantClass, roundedClass]"
  >
    <SpinnerIcon v-if="loading" :class="[iconLeftClass, iconVariantClass]" />

    <slot v-else name="left" :class="iconLeftClass"></slot>

    <slot />

    <slot name="right" :class="[iconRightClass, iconVariantClass]"></slot>
  </BaseCustomTag>
</template>
