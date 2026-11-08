<template>
  <BaseModal :show="modalActive" @close="closeNoteModal" @open="setFields">
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalStore.title }}
        <BaseIcon
          name="XIcon"
          class="h-6 w-6 text-gray-500 cursor-pointer"
          @click="closeNoteModal"
        />
      </div>
    </template>
    <form action="" @submit.prevent="submitNote">
      <div class="px-8 py-8 sm:p-6">
        <BaseInputGrid layout="one-column">
          <BaseInputGroup
            :label="$t('settings.customization.notes.name')"
            variant="vertical"
            :error="
              v$.currentNote.name.$error &&
              v$.currentNote.name.$errors[0].$message
            "
            required
          >
            <BaseInput
              v-model="noteStore.currentNote.name"
              :invalid="v$.currentNote.name.$error"
              type="text"
              @input="v$.currentNote.name.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('settings.customization.notes.type')"
            :error="
              v$.currentNote.type.$error &&
              v$.currentNote.type.$errors[0].$message
            "
            required
          >
            <BaseMultiselect
              v-model="noteStore.currentNote.type"
              :options="types"
              value-prop="type"
              class="mt-2"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('settings.customization.notes.notes')"
            :error="
              v$.currentNote.notes.$error &&
              v$.currentNote.notes.$errors[0].$message
            "
            required
          >
            <BaseCustomInput
              v-model="noteStore.currentNote.notes"
              :invalid="v$.currentNote.notes.$error"
              :fields="fields"
              @input="v$.currentNote.notes.$touch()"
            />
          </BaseInputGroup>
        </BaseInputGrid>
      </div>
      <div
        class="
          z-0
          flex
          justify-end
          px-4
          py-4
          border-t border-solid border-gray-light
        "
      >
        <BaseButton
          class="mr-2"
          variant="primary-outline"
          type="button"
          @click="closeNoteModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>

        <BaseButton
          :loading="isSaving"
          :disabled="isSaving"
          variant="primary"
          type="submit"
        >
          <template #left="slotProps">
            <BaseIcon name="SaveIcon" :class="slotProps.class" />
          </template>
          {{ noteStore.isEdit ? $t('general.update') : $t('general.save') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { required, minLength, helpers } from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useNotesStore } from '@/scripts/admin/stores/note'
import { usePaymentStore } from '@/scripts/admin/stores/payment'

const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const noteStore = useNotesStore()
const paymentStore = usePaymentStore()

const route = useRoute()
const { t } = useI18n()

let isSaving = ref(false)
const types = reactive(['Payment'])
let fields = ref(['customer', 'customerCustom'])

const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'NoteModal'
})

const rules = computed(() => {
  return {
    currentNote: {
      name: {
        required: helpers.withMessage(t('validation.required'), required),
        minLength: helpers.withMessage(
          t('validation.name_min_length', { count: 3 }),
          minLength(3)
        ),
      },
      notes: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      type: {
        required: helpers.withMessage(t('validation.required'), required),
      },
    },
  }
})

const v$ = useVuelidate(
  rules,
  computed(() => noteStore)
)

watch(
  () => noteStore.currentNote.type,
  (val) => {
    setFields()
  }
)

onMounted(() => {
  noteStore.currentNote.type = 'Payment'
})

function setFields() {
  fields.value = ['customer', 'customerCustom']

  if (noteStore.currentNote.type == 'Payment') {
    fields.value.push('payment', 'paymentCustom')
  }
}

async function submitNote() {
  v$.value.currentNote.$touch()
  if (v$.value.currentNote.$invalid) {
    return true
  }

  isSaving.value = true
  if (noteStore.isEdit) {
    let data = {
      id: noteStore.currentNote.id,
      ...noteStore.currentNote,
    }
    await noteStore
      .updateNote(data)
      .then((res) => {
        isSaving.value = false
        if (res.data) {
          notificationStore.showNotification({
            type: 'success',
            message: t('settings.customization.notes.note_updated'),
          })
          modalStore.refreshData ? modalStore.refreshData() : ''
          closeNoteModal()
        }
      })
      .catch((err) => {
        isSaving.value = false
      })
  } else {
        await noteStore
          .addNote(noteStore.currentNote)
          .then((res) => {
            isSaving.value = false
            if (res.data) {
              notificationStore.showNotification({
                type: 'success',
                message: t('settings.customization.notes.note_added'),
              })

              if (
                (route.name === 'payments.create' &&
                  res.data.data.type === 'Payment') ||
                (route.name === 'payments.edit' && res.data.data.type === 'Payment')
              ) {
                paymentStore.selectNote(res.data.data)
              }
            }

            modalStore.refreshData ? modalStore.refreshData() : ''
            closeNoteModal()
          })
          .catch((err) => {
            isSaving.value = false
          })
  }
}

function closeNoteModal() {
  modalStore.closeModal()

  setTimeout(() => {
    noteStore.resetCurrentNote()
    v$.value.$reset()
  }, 300)
}
</script>

<style lang="scss">
.note-modal {
  .header-editior .editor-menu-bar {
    margin-left: 0.5px;
    margin-right: 0px;
  }
}
</style>
