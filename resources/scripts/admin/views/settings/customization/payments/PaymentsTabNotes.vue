<template>
  <NoteModal />

  <BaseSettingCard
    :title="$t('settings.customization.notes.title')"
    :description="$t('settings.customization.notes.description')"
  >
    <template #action>
      <BaseButton
        v-if="userStore.hasAbilities(abilities.MANAGE_NOTE)"
        variant="primary-outline"
        @click="openNoteSelectModal"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ $t('settings.customization.notes.add_note') }}
      </BaseButton>
    </template>

    <BaseTable
      ref="table"
      :data="fetchData"
      :columns="notesColumns"
      class="mt-14"
    >
      <template #cell-actions="{ row }">
        <NoteDropdown
          :row="row.data"
          :table="table"
          :load-data="refreshTable"
        />
      </template>
    </BaseTable>
  </BaseSettingCard>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotesStore } from '@/scripts/admin/stores/note'
import NoteDropdown from '@/scripts/admin/components/dropdowns/NoteIndexDropdown.vue'
import NoteModal from '@/scripts/admin/components/modal-components/NoteModal.vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'

const { t } = useI18n()

const modalStore = useModalStore()
const noteStore = useNotesStore()
const userStore = useUserStore()

const table = ref('')

const notesColumns = computed(() => {
  return [
    {
      key: 'name',
      label: t('settings.customization.notes.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'type',
      label: t('settings.customization.notes.type'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },

    {
      key: 'actions',
      label: '',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})

async function fetchData({ page, filter, sort }) {
  let data = reactive({
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  })

  let response = await noteStore.fetchNotes(data)

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 5,
    },
  }
}

async function openNoteSelectModal() {
  await modalStore.openModal({
    title: t('settings.customization.notes.add_note'),
    componentName: 'NoteModal',
    size: 'md',
    refreshData: table.value && table.value.refresh,
  })
}

async function refreshTable() {
  table.value && table.value.refresh()
}
</script>
