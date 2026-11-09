<template>
  <div class="mb-6">
    <div
      v-if="showNotePopup"
      class="z-20 text-sm font-semibold leading-5 text-primary-400 float-right"
    >
      <SelectNotePopup :type="type" @select="onSelectNote" />
    </div>
    <label class="text-heading font-medium mb-4 text-sm">
      {{ $t(label) }}
    </label>
    <BaseCustomInput
      v-model="store[storeProp].notes"
      :content-loading="store.isFetchingInitialSettings"
      :fields="fields"
      :show-fields-button="showFieldsButton"
      class="mt-1"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import SelectNotePopup from '../SelectNotePopup.vue'

const props = defineProps({
  store: {
    type: Object,
    default: null,
  },
  storeProp: {
    type: String,
    default: '',
  },
  fields: {
    type: Object,
    default: null,
  },
  type: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    default: 'invoices.notes',
  },
  showNotePopup: {
    type: Boolean,
    default: true,
  },
  showFieldsButton: {
    type: Boolean,
    default: true,
  },
})

function onSelectNote(data) {
  props.store[props.storeProp].notes = '' + data.notes
}
</script>
