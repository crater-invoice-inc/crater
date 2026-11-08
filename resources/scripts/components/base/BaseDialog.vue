<template>
  <TransitionRoot as="template" :show="dialogStore.active">
    <Dialog
      as="div"
      static
      class="fixed inset-0 z-20 overflow-y-auto"
      :open="dialogStore.active"
      @close="dialogStore.closeDialog"
    >
      <div
        class="
          flex
          items-end
          justify-center
          min-h-screen
          px-4
          pt-4
          pb-20
          text-center
          sm:block sm:p-0
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
            class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
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
            class="
              inline-block
              px-4
              pt-5
              pb-4
              overflow-hidden
              text-left
              align-bottom
              transition-all
              bg-surface
              rounded-2xl
              shadow-card
              sm:my-8 sm:align-middle sm:w-full sm:p-6
              relative
            "
            :class="dialogSizeClasses"
          >
            <div>
              <div
                class="
                  flex
                  items-center
                  justify-center
                  w-12
                  h-12
                  mx-auto
                  bg-status-green-bg
                  rounded-full
                "
                :class="{
                  'bg-status-green-bg': dialogStore.variant === 'primary',
                  'bg-status-red-bg': dialogStore.variant === 'danger',
                }"
              >
                <BaseIcon
                  v-if="dialogStore.variant === 'primary'"
                  name="CheckIcon"
                  class="w-6 h-6 text-status-green"
                />
                <BaseIcon
                  v-else
                  name="ExclamationIcon"
                  class="w-6 h-6 text-status-red"
                  aria-hidden="true"
                />
              </div>
              <div class="mt-3 text-center sm:mt-5">
                <DialogTitle
                  as="h3"
                  class="text-lg font-medium leading-6 text-heading"
                >
                  {{ dialogStore.title }}
                </DialogTitle>
                <div class="mt-2">
                  <p class="text-sm text-muted">
                    {{ dialogStore.message }}
                  </p>
                </div>
              </div>
            </div>
            <div
              class="mt-5 sm:mt-6 grid gap-3"
              :class="{
                'sm:grid-cols-2 sm:grid-flow-row-dense':
                  !dialogStore.hideNoButton,
              }"
            >
              <base-button
                class="justify-center"
                :variant="dialogStore.variant"
                :class="{ 'w-full': dialogStore.hideNoButton }"
                @click="resolveDialog(true)"
              >
                {{ dialogStore.yesLabel }}
              </base-button>

              <base-button
                v-if="!dialogStore.hideNoButton"
                class="justify-center"
                variant="white"
                @click="resolveDialog(false)"
              >
                {{ dialogStore.noLabel }}
              </base-button>
            </div>
          </div>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed } from 'vue'
import { useDialogStore } from '@/scripts/stores/dialog'
import {
  Dialog,
  DialogOverlay,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

const dialogStore = useDialogStore()

function resolveDialog(resValue) {
  dialogStore.resolve(resValue)
  dialogStore.closeDialog()
}

const dialogSizeClasses = computed(() => {
  const size = dialogStore.size

  switch (size) {
    case 'sm':
      return 'sm:max-w-sm'
    case 'md':
      return 'sm:max-w-md'
    case 'lg':
      return 'sm:max-w-lg'

    default:
      return 'sm:max-w-md'
  }
})
</script>
