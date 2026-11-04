<template>
  <BaseModal :show="modalActive" @close="closeModal" @open="setData">
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalTitle }}
        <BaseIcon
          name="XIcon"
          class="h-6 w-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>
    <div class="px-8 py-8 sm:p-6">
      <div
        v-if="modalStore.data"
        class="grid grid-cols-3 gap-2 p-1 overflow-x-auto"
      >
        <div
          v-for="(template, index) in modalStore.data.templates"
          :key="index"
          :class="{
            'border border-solid border-primary-500':
              selectedTemplate === template.name,
          }"
          class="
            relative
            flex flex-col
            m-2
            border border-gray-200 border-solid
            cursor-pointer
            hover:border-primary-300
          "
          @click="selectedTemplate = template.name"
        >
          <img
            :src="template.path"
            :alt="template.name"
            class="w-full min-h-[100px]"
          />
          <img
            v-if="selectedTemplate === template.name"
            :alt="template.name"
            class="absolute z-10 w-5 h-5 text-primary-500"
            style="top: -6px; right: -5px"
            :src="getTickImage()"
          />
          <span
            :class="[
              'w-full p-1 bg-gray-200 text-sm text-center absolute bottom-0 left-0',
              {
                'text-primary-500 bg-primary-100':
                  selectedTemplate === template.name,
                'text-gray-600': selectedTemplate != template.name,
              },
            ]"
          >
            {{ template.name }}
          </span>
        </div>
      </div>

      <div v-if="!modalStore.data.store.isEdit" class="z-0 flex ml-3 pt-5">
        <BaseCheckbox
          v-model="modalStore.data.isMarkAsDefault"
          :set-initial-value="false"
          variant="primary"
          :label="$t('general.mark_as_default')"
          :description="modalStore.data.markAsDefaultDescription"
        />
      </div>
    </div>

    <div class="z-0 flex justify-end p-4 border-t border-gray-200 border-solid">
      <BaseButton class="mr-3" variant="primary-outline" @click="closeModal">
        {{ $t('general.cancel') }}
      </BaseButton>
      <BaseButton variant="primary" @click="chooseTemplate()">
        <template #left="slotProps">
          <BaseIcon name="SaveIcon" :class="slotProps.class" />
        </template>
        {{ $t('general.choose') }}
      </BaseButton>
    </div>
  </BaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useModalStore } from '@/scripts/stores/modal'
import { useUserStore } from '@/scripts/admin/stores/user'

const modalStore = useModalStore()
const userStore = useUserStore()

const selectedTemplate = ref('')

const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'SelectTemplate'
})

const modalTitle = computed(() => {
  return modalStore.title
})

function setData() {
  if (modalStore.data.store[modalStore.data.storeProp].template_name) {
    selectedTemplate.value =
      modalStore.data.store[modalStore.data.storeProp].template_name
  } else {
    selectedTemplate.value = modalStore.data.templates[0]
  }
}

async function chooseTemplate() {
  await modalStore.data.store.setTemplate(selectedTemplate.value)
  // update default estimate or invoice template
  if (!modalStore.data.store.isEdit && modalStore.data.isMarkAsDefault) {
    if (modalStore.data.storeProp == 'newEstimate') {
      await userStore.updateUserSettings({
        settings: {
          default_estimate_template: selectedTemplate.value,
        },
      })
    } else if (modalStore.data.storeProp == 'newInvoice') {
      await userStore.updateUserSettings({
        settings: {
          default_invoice_template: selectedTemplate.value,
        },
      })
    }
  }
  closeModal()
}

function getTickImage() {
  return import.meta.env.DEV ? '/img/tick.png' : '/build/img/tick.png'
}

function closeModal() {
  modalStore.closeModal()

  setTimeout(() => {
    modalStore.$reset()
  }, 300)
}
</script>
