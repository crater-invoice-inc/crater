<template>
  <form
    class="relative"
    novalidate
    @submit.prevent="saveSettings"
  >
    <BaseSettingCard
      :title="$t('settings.ai_assistant.title')"
      :description="$t('settings.ai_assistant.description')"
    >
      <BaseErrorAlert
        v-if="loadError"
        class="mt-5"
        :error-title="$t('settings.ai_assistant.load_error_title')"
        :errors="[loadError]"
      />

      <BaseContentPlaceholders
        v-if="isLoading"
        class="mt-5"
      >
        <BaseContentPlaceholdersBox class="h-16 w-full rounded" />
        <BaseContentPlaceholdersBox class="mt-4 h-10 w-full rounded" />
        <BaseContentPlaceholdersBox class="mt-4 h-24 w-full rounded" />
      </BaseContentPlaceholders>

      <template v-else>
        <!-- API key -->
        <div class="mt-5">
          <div
            v-if="keyConfigured && !isReplacingKey"
            class="
              flex items-center justify-between p-4 bg-gray-50 border
              border-gray-200 border-solid rounded-md
            "
          >
            <div class="flex items-center min-w-0">
              <BaseIcon
                name="ShieldCheckIcon"
                class="mr-3 text-teal-500 shrink-0"
              />
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800">
                  {{ $t('settings.ai_assistant.key_configured') }}
                </p>
                <p class="mt-0.5 text-sm text-gray-500 font-mono truncate">
                  {{ keyHint }}
                </p>
              </div>
            </div>

            <BaseButton
              type="button"
              variant="primary-outline"
              class="ml-4 shrink-0"
              @click="startReplacingKey"
            >
              {{ $t('settings.ai_assistant.replace_key') }}
            </BaseButton>
          </div>

          <BaseInputGroup
            v-else
            :label="$t('settings.ai_assistant.api_key')"
            :help-text="$t('settings.ai_assistant.api_key_help')"
          >
            <BaseInput
              v-model="form.apiKey"
              type="password"
              autocomplete="new-password"
              :placeholder="$t('settings.ai_assistant.api_key_placeholder')"
              class="font-mono"
            />
          </BaseInputGroup>
        </div>

        <BaseInputGrid class="mt-5">
          <BaseInputGroup
            :label="$t('settings.ai_assistant.model')"
            :help-text="$t('settings.ai_assistant.model_help')"
            required
          >
            <BaseInput
              v-model="form.model"
              type="text"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('settings.ai_assistant.base_url')"
            :help-text="$t('settings.ai_assistant.base_url_help')"
          >
            <BaseInput
              v-model="form.baseUrl"
              type="text"
              placeholder="https://api.perplexity.ai"
            />
          </BaseInputGroup>
        </BaseInputGrid>

        <BaseInputGroup
          class="mt-5"
          :label="$t('settings.ai_assistant.system_instruction')"
          :help-text="$t('settings.ai_assistant.system_instruction_help')"
        >
          <BaseTextarea
            v-model="form.systemInstruction"
            :row="4"
          />
        </BaseInputGroup>

        <div class="mt-6">
          <BaseButton
            :loading="isSaving"
            :disabled="isSaving || isLoading"
            variant="primary"
            type="submit"
          >
            <template #left="slotProps">
              <BaseIcon
                name="SaveIcon"
                :class="slotProps.class"
              />
            </template>
            {{ $t('settings.ai_assistant.save') }}
          </BaseButton>
        </div>
      </template>
    </BaseSettingCard>
  </form>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t, global } = useI18n()

const notificationStore = useNotificationStore()

const DEFAULT_BASE_URL = 'https://api.perplexity.ai'

const isLoading = ref(false)
const isSaving = ref(false)
const loadError = ref(null)
const keyConfigured = ref(false)
const keyHint = ref(null)
const isReplacingKey = ref(false)

// The API key lives only in this component-local state: it is never
// stored in Pinia, localStorage or any global, and never prefilled.
const form = reactive({
  apiKey: '',
  model: 'sonar',
  baseUrl: '',
  systemInstruction: '',
})

onMounted(() => {
  fetchSettings()
})

async function fetchSettings() {
  isLoading.value = true
  loadError.value = null

  try {
    const response = await window.axios.get('/api/v1/ai/settings')

    const data = response.data

    keyConfigured.value = !!data.api_key_configured
    keyHint.value = data.api_key_hint

    form.model = data.model || 'sonar'
    // An unset base URL is reported as the effective default; keep the
    // field empty in that case so the placeholder shows the default.
    form.baseUrl = data.base_url && data.base_url !== DEFAULT_BASE_URL ? data.base_url : ''
    form.systemInstruction = data.system_instruction || ''
  } catch (err) {
    const response = err && err.response

    loadError.value =
      (response && response.data && (response.data.error || response.data.message)) ||
      t('settings.ai_assistant.load_error_generic')
  } finally {
    isLoading.value = false
  }
}

function startReplacingKey() {
  isReplacingKey.value = true
  form.apiKey = ''
}

async function saveSettings() {
  isSaving.value = true

  // An omitted api_key keeps the stored one (backend contract).
  const payload = {
    model: form.model,
    base_url: form.baseUrl || '',
    system_instruction: form.systemInstruction || '',
  }

  if (form.apiKey.trim() !== '') {
    payload.api_key = form.apiKey.trim()
  }

  try {
    const response = await window.axios.post('/api/v1/ai/settings', payload)

    const data = response.data

    keyConfigured.value = !!data.api_key_configured
    keyHint.value = data.api_key_hint
    form.model = data.model || 'sonar'
    form.baseUrl = data.base_url && data.base_url !== DEFAULT_BASE_URL ? data.base_url : ''
    form.systemInstruction = data.system_instruction || ''

    // Discard the entered key immediately after saving.
    form.apiKey = ''
    isReplacingKey.value = false

    notificationStore.showNotification({
      type: 'success',
      message: global.t('settings.ai_assistant.saved'),
    })
  } catch (err) {
    // Server errors (validation, authorization) come back in French and
    // are displayed as notifications by the shared helper.
    handleError(err)
  } finally {
    isSaving.value = false
  }
}
</script>
