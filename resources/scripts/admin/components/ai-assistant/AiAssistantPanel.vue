<template>
  <!--
    Panel content only: it is rendered inside the single morph container
    owned by AiAssistantBubble, which provides the size, the anchoring and
    the open/close animation. No positioning of its own here.
  -->
  <div class="flex flex-col overflow-hidden">
    <!-- Header -->
    <div
      class="
        flex items-center justify-between px-4 py-3 bg-gray-50 border-b
        border-gray-200 border-solid shrink-0
      "
    >
      <div class="flex items-center">
        <BaseIcon
          name="SparklesIcon"
          class="mr-2 text-primary-500"
        />
        <h3 class="text-sm font-semibold text-gray-800">
          {{ $t('ai_assistant.title') }}
        </h3>
      </div>

      <button
        type="button"
        class="text-gray-500 hover:text-gray-700 focus:outline-none"
        :aria-label="$t('ai_assistant.close')"
        @click="aiStore.closePanel()"
      >
        <BaseIcon name="XCircleIcon" />
      </button>
    </div>

    <!-- Scrollable body -->
    <div class="flex-1 p-4 space-y-4 overflow-y-auto">
      <!-- Conversation history (per document, loaded on open) -->
      <div
        v-if="aiStore.messages.length"
        class="pb-3 mb-1 border-b border-gray-100 border-solid"
      >
        <p class="text-xs font-semibold text-gray-400 uppercase">
          {{ $t('ai_assistant.conversation') }}
        </p>

        <div class="mt-2 space-y-2 max-h-40 overflow-y-auto">
          <div
            v-for="(message, index) in aiStore.messages"
            :key="index"
            class="flex"
            :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[85%] px-3 py-2 text-sm rounded-lg break-words"
              :class="messageClass(message)"
            >
              {{ message.content }}
            </div>
          </div>
        </div>
      </div>

      <!-- Step: idle -->
      <div
        v-if="aiStore.step === 'idle'"
        class="space-y-2"
      >
        <p class="text-sm text-gray-600">
          {{ $t('ai_assistant.choose_action') }}
        </p>

        <button
          v-for="option in options"
          :key="option.action"
          type="button"
          class="
            flex items-start w-full p-3 text-left bg-white border border-gray-200
            border-solid rounded-lg hover:border-primary-500 focus:outline-none
            focus:ring-1 focus:ring-primary-400
          "
          @click="aiStore.chooseAction(option.action)"
        >
          <BaseIcon
            :name="option.icon"
            class="mt-0.5 mr-3 text-primary-500 shrink-0"
          />
          <span>
            <span class="block text-sm font-medium text-gray-800">
              {{ $t(option.label) }}
            </span>
            <span class="block mt-0.5 text-xs text-gray-500">
              {{ $t(option.description) }}
            </span>
          </span>
        </button>
      </div>

      <!-- Step: service selection (add_existing_services) -->
      <div v-if="aiStore.step === 'service_selection'">
        <p class="mb-2 text-sm text-gray-600">
          {{ $t('ai_assistant.select_services') }}
        </p>

        <BaseContentPlaceholders
          v-if="aiStore.loadingServices"
          class="space-y-2"
        >
          <BaseContentPlaceholdersBox
            v-for="n in 4"
            :key="n"
            class="w-full h-10 rounded"
          />
        </BaseContentPlaceholders>

        <p
          v-else-if="!aiStore.services.length"
          class="text-sm text-gray-500"
        >
          {{ $t('ai_assistant.no_active_services') }}
        </p>

        <div
          v-else
          class="max-h-56 overflow-y-auto border border-gray-200 border-solid rounded-lg divide-y divide-gray-100"
        >
          <label
            v-for="service in aiStore.services"
            :key="service.id"
            class="flex items-center px-3 py-2 cursor-pointer hover:bg-gray-50"
          >
            <input
              type="checkbox"
              class="w-4 h-4 mr-3 text-primary-500 border-gray-300 rounded focus:ring-primary-400"
              :checked="aiStore.selectedServiceIds.includes(service.id)"
              @change="aiStore.toggleService(service.id)"
            />

            <span class="flex-1 min-w-0">
              <span class="block text-sm text-gray-800 truncate">
                {{ service.name }}
              </span>
              <span
                v-if="service.unit"
                class="block text-xs text-gray-400"
              >
                {{ service.unit.name }}
              </span>
            </span>

            <BaseFormatMoney
              :amount="service.price"
              :currency="currency"
              class="ml-2 text-sm font-medium text-gray-700 shrink-0"
            />
          </label>
        </div>

        <BaseButton
          type="button"
          variant="primary"
          class="mt-3 w-full"
          :disabled="!aiStore.services.length || aiStore.selectedServicesCount === 0"
          @click="aiStore.step = 'brief_input'"
        >
          {{ $t('ai_assistant.continue') }}
        </BaseButton>
      </div>

      <!-- Step: line selection (update_line) -->
      <div v-if="aiStore.step === 'line_selection'">
        <p class="mb-2 text-sm text-gray-600">
          {{ $t('ai_assistant.select_line') }}
        </p>

        <p
          v-if="!aiStore.documentLines.length"
          class="text-sm text-gray-500"
        >
          {{ $t('ai_assistant.empty_document_lines') }}
        </p>

        <div
          v-else
          class="max-h-56 overflow-y-auto border border-gray-200 border-solid rounded-lg divide-y divide-gray-100"
        >
          <button
            v-for="(line, index) in aiStore.documentLines"
            :key="index"
            type="button"
            class="
              flex items-center justify-between w-full px-3 py-2 text-left
              hover:bg-gray-50 focus:outline-none
            "
            @click="aiStore.selectLine(index)"
          >
            <span class="min-w-0 mr-2 truncate text-sm text-gray-800">
              {{ index + 1 }}. {{ line.name }}
            </span>

            <span class="text-xs text-gray-500 whitespace-nowrap shrink-0">
              {{ line.quantity }} ×
              <BaseFormatMoney
                :amount="line.price"
                :currency="currency"
              />
            </span>
          </button>
        </div>
      </div>

      <!-- Step: brief input -->
      <div v-if="aiStore.step === 'brief_input'">
        <div
          v-if="aiStore.action === 'update_line'"
          class="flex items-center justify-between p-2 mb-2 bg-gray-50 border border-gray-200 border-solid rounded"
        >
          <span class="text-xs text-gray-600 truncate">
            {{ $t('ai_assistant.line_number', { index: aiStore.selectedLineIndex + 1 }) }}
            — {{ currentLineName }}
          </span>

          <button
            type="button"
            class="ml-2 text-xs text-primary-500 hover:text-primary-600 shrink-0"
            @click="aiStore.chooseAction('update_line')"
          >
            {{ $t('ai_assistant.change_line') }}
          </button>
        </div>

        <label class="block mb-1 text-xs font-medium text-gray-500">
          {{ $t('ai_assistant.brief_label') }}
        </label>

        <BaseTextarea
          v-model="aiStore.brief"
          :row="4"
          :placeholder="$t('ai_assistant.brief_placeholder')"
        />

        <p class="mt-1 text-xs text-gray-400">
          {{ $t('ai_assistant.brief_hint') }}
        </p>

        <BaseButton
          type="button"
          variant="primary"
          class="mt-3 w-full"
          :loading="aiStore.submitting"
          :disabled="!aiStore.brief.trim() || aiStore.submitting"
          @click="aiStore.submitBrief()"
        >
          {{ $t('ai_assistant.generate') }}
        </BaseButton>

        <BaseButton
          type="button"
          variant="white"
          class="mt-2 w-full"
          :disabled="aiStore.submitting"
          @click="aiStore.resetFlow()"
        >
          {{ $t('ai_assistant.back') }}
        </BaseButton>
      </div>

      <!-- Step: loading -->
      <div
        v-if="aiStore.step === 'loading'"
        class="flex flex-col items-center justify-center py-12"
      >
        <BaseSpinner class="w-8 h-8 text-primary-500" />
        <p class="mt-3 text-sm text-gray-500">
          {{ $t('ai_assistant.generating') }}
        </p>
      </div>

      <!-- Step: proposal -->
      <div v-if="aiStore.step === 'proposal'">
        <p class="text-sm text-gray-700">
          {{ aiStore.summary }}
        </p>

        <div
          v-if="aiStore.warnings.length"
          class="p-3 mt-2 bg-gray-50 border border-gray-200 border-solid rounded-lg"
        >
          <div class="flex items-center mb-1">
            <BaseIcon
              name="ExclamationIcon"
              class="mr-1.5 text-gray-500"
            />
            <span class="text-xs font-semibold text-gray-600">
              {{ $t('ai_assistant.warnings') }}
            </span>
          </div>

          <ul class="pl-4 list-disc">
            <li
              v-for="(warning, index) in aiStore.warnings"
              :key="index"
              class="text-xs text-gray-600"
            >
              {{ warning }}
            </li>
          </ul>
        </div>

        <div class="mt-3 space-y-3">
          <AiProposalLine
            v-for="(line, index) in aiStore.proposalLines"
            :key="index"
            :line="line"
            :currency="currency"
            :line-ref="aiStore.action === 'update_line' ? aiStore.selectedLineIndex + 1 : null"
            :fields-to-show="aiStore.action === 'update_line' ? aiStore.changedFields(line) : null"
          />
        </div>

        <p
          v-if="unchangedFieldLabels.length"
          class="mt-2 text-xs text-gray-400"
        >
          {{ $t('ai_assistant.unchanged_fields', { fields: unchangedFieldLabels.join(', ') }) }}
        </p>

        <div
          v-if="aiStore.confirming"
          class="p-3 mt-3 bg-gray-50 border border-gray-200 border-solid rounded-lg"
        >
          <p class="text-sm text-gray-700">
            {{ $t('ai_assistant.confirm_overwrite') }}
          </p>

          <div class="flex mt-2 space-x-2">
            <BaseButton
              type="button"
              variant="primary"
              class="flex-1"
              @click="aiStore.applySelection()"
            >
              {{ $t('ai_assistant.confirm_apply') }}
            </BaseButton>

            <BaseButton
              type="button"
              variant="white"
              class="flex-1"
              @click="aiStore.cancelConfirm()"
            >
              {{ $t('ai_assistant.cancel') }}
            </BaseButton>
          </div>
        </div>

        <div
          v-else
          class="flex mt-3 space-x-2"
        >
          <BaseButton
            type="button"
            variant="white"
            class="flex-1"
            @click="aiStore.goBackToBrief()"
          >
            {{ $t('ai_assistant.back') }}
          </BaseButton>

          <BaseButton
            type="button"
            variant="primary"
            class="flex-1"
            :disabled="aiStore.checkedCount === 0"
            @click="aiStore.applySelection()"
          >
            {{ $t('ai_assistant.apply') }}
          </BaseButton>
        </div>
      </div>

      <!-- Step: applied -->
      <div v-if="aiStore.step === 'applied'">
        <div class="p-4 bg-teal-50 border border-teal-200 border-solid rounded-lg">
          <div class="flex items-center">
            <BaseIcon
              name="CheckCircleIcon"
              class="mr-2 text-teal-500"
            />
            <span class="text-sm font-semibold text-teal-700">
              {{ $t('ai_assistant.applied_title') }}
            </span>
          </div>

          <p class="mt-2 text-sm text-teal-700">
            {{ $t('ai_assistant.applied_count', { count: aiStore.appliedCount }) }}
          </p>

          <p class="mt-1 text-xs text-teal-600">
            {{ $t('ai_assistant.applied_hint') }}
          </p>
        </div>

        <div class="flex mt-3 space-x-2">
          <BaseButton
            type="button"
            variant="white"
            class="flex-1"
            @click="aiStore.resetFlow()"
          >
            {{ $t('ai_assistant.new_request') }}
          </BaseButton>

          <BaseButton
            type="button"
            variant="primary"
            class="flex-1"
            @click="aiStore.closePanel()"
          >
            {{ $t('ai_assistant.close') }}
          </BaseButton>
        </div>
      </div>

      <!-- Step: error -->
      <div v-if="aiStore.step === 'error'">
        <div class="p-4 bg-red-50 border border-red-200 border-solid rounded-lg">
          <div class="flex items-start">
            <BaseIcon
              name="ExclamationIcon"
              class="mr-2 mt-0.5 text-red-500 shrink-0"
            />
            <p class="text-sm text-red-600">
              {{ aiStore.error || $t('ai_assistant.error_generic') }}
            </p>
          </div>
        </div>

        <div class="flex mt-3 space-x-2">
          <BaseButton
            type="button"
            variant="white"
            class="flex-1"
            @click="aiStore.resetFlow()"
          >
            {{ $t('ai_assistant.back') }}
          </BaseButton>

          <BaseButton
            type="button"
            variant="primary"
            class="flex-1"
            @click="aiStore.goBackToBrief()"
          >
            {{ $t('ai_assistant.retry') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAiAssistantStore } from '@/scripts/admin/stores/ai-assistant'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import AiProposalLine from './AiProposalLine.vue'

const { t } = useI18n()

const aiStore = useAiAssistantStore()
const companyStore = useCompanyStore()

const options = [
  {
    action: 'add_existing_services',
    icon: 'PlusCircleIcon',
    label: 'ai_assistant.add_services',
    description: 'ai_assistant.add_services_desc',
  },
  {
    action: 'update_line',
    icon: 'PencilIcon',
    label: 'ai_assistant.update_line',
    description: 'ai_assistant.update_line_desc',
  },
  {
    action: 'create_custom_line',
    icon: 'DocumentTextIcon',
    label: 'ai_assistant.custom_line',
    description: 'ai_assistant.custom_line_desc',
  },
]

const documentRef = computed(() => aiStore.documentStoreRef())

const currency = computed(() => {
  const { store, prop } = documentRef.value

  return store[prop].selectedCurrency || companyStore.selectedCompanyCurrency
})

const currentLineName = computed(() => {
  const { store, prop } = documentRef.value
  const line = store[prop].items[aiStore.selectedLineIndex]

  return line && line.name ? line.name : ''
})

const unchangedFieldLabels = computed(() => {
  if (aiStore.action !== 'update_line' || !aiStore.proposalLines.length) {
    return []
  }

  const changed = aiStore.changedFields(aiStore.proposalLines[0])
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

  return allFields
    .filter((field) => !changed.includes(field))
    .map((field) => t('ai_assistant.field.' + field))
})

function messageClass(message) {
  if (message.metadata && message.metadata.is_error) {
    return 'bg-red-50 text-red-600 border border-red-200 border-solid'
  }

  return message.role === 'user'
    ? 'bg-primary-500 text-white'
    : 'bg-gray-100 text-gray-800'
}
</script>
