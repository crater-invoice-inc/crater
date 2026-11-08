<template>
  <Teleport to="body">
    <TransitionRoot appear as="template" :show="show">
      <Dialog
        as="div"
        static
        class="fixed inset-0 z-20 overflow-y-auto"
        :open="show"
        @close="$emit('close')"
      >
        <div
          class="
            flex
            items-end
            justify-center
            min-h-screen
            px-4
            text-center
            sm:block sm:px-2
          "
        >
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="ease-in duration-200"
            leave-from="opacity-100"
            leave-to="opacity-0"
          >
            <DialogOverlay
              class="fixed inset-0 transition-opacity bg-gray-700 bg-opacity-25"
            />
          </TransitionChild>

          <!-- This element is to trick the browser into centering the modal contents. -->
          <span
            class="hidden sm:inline-block sm:align-middle sm:h-screen"
            aria-hidden="true"
            >&#8203;</span
          >
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              :class="`inline-block
              align-middle
              bg-surface
              rounded-2xl
              text-left
              overflow-hidden
              relative
              shadow-card
              transition-all
              my-4
              ${modalSize}
              sm:w-full`"
            >
              <div
                v-if="hasHeaderSlot"
                class="
                  flex
                  items-center
                  justify-between
                  px-6
                  py-4
                  text-lg
                  font-medium
                  text-heading
                  border-b border-line-light border-solid
                "
              >
                <slot name="header" />
              </div>

              <slot />

              <slot name="footer" />
            </div>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>
  </Teleport>
</template>

<script setup>
import { useModalStore } from '@/scripts/stores/modal'
import { computed, watchEffect, useSlots } from 'vue'
import {
  Dialog,
  DialogOverlay,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})
const slots = useSlots()

const emit = defineEmits(['close', 'open'])

const modalStore = useModalStore()

watchEffect(() => {
  if (props.show) {
    emit('open', props.show)
  }
})

const modalSize = computed(() => {
  const size = modalStore.size
  switch (size) {
    case 'sm':
      return 'sm:max-w-2xl w-full'
    case 'md':
      return 'sm:max-w-4xl w-full'
    case 'lg':
      return 'sm:max-w-6xl w-full'

    default:
      return 'sm:max-w-2xl w-full'
  }
})

const hasHeaderSlot = computed(() => {
  return !!slots.header
})
</script>
