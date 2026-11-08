<template>
  <div ref="companySwitchBar" class="relative rounded-lg">
    <CompanyModal />

    <div
      :title="collapsed && companyStore.selectedCompany ? companyStore.selectedCompany.name : null"
      :class="[
        'flex items-center w-full px-2.5 py-2 text-sm bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100',
        collapsed ? 'justify-center' : '',
      ]"
      @click="isShow = !isShow"
    >
      <span
        v-if="companyStore.selectedCompany"
        class="
          flex
          items-center
          justify-center
          shrink-0
          overflow-hidden
          text-base
          font-semibold
          bg-gray-200
          rounded-md
          w-9
          h-9
          text-primary-500
        "
      >
        <span v-if="!companyStore.selectedCompany.logo">
          {{ initGenerator(companyStore.selectedCompany.name) }}
        </span>
        <img
          v-else
          :src="companyStore.selectedCompany.logo"
          alt="Company logo"
          class="w-full h-full object-contain"
        />
      </span>

      <span
        v-if="companyStore.selectedCompany && !collapsed"
        class="flex-1 ml-3 font-medium text-gray-900 truncate"
      >
        {{ companyStore.selectedCompany.name }}
      </span>

      <BaseIcon
        v-if="!collapsed"
        name="ChevronDownIcon"
        class="h-5 ml-2 shrink-0 text-gray-400"
      />
    </div>

    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    >
      <div
        v-if="isShow"
        class="absolute left-0 z-30 mt-2 bg-white rounded-lg shadow-lg"
      >
        <div
          class="
            overflow-y-auto
            scrollbar-thin scrollbar-thumb-rounded-full
            w-full
            min-w-[200px]
            max-h-[350px]
            scrollbar-thumb-gray-300 scrollbar-track-gray-10
            pb-2
          "
        >
          <label
            class="
              px-3
              py-2
              text-xs
              font-semibold
              text-gray-400
              mb-0.5
              block
              uppercase
            "
          >
            {{ $t('company_switcher.label') }}
          </label>

          <div
            v-if="companyStore.companies.length < 1"
            class="
              flex flex-col
              items-center
              justify-center
              p-2
              px-3
              mt-4
              text-base text-gray-400
            "
          >
            <BaseIcon name="ExclamationCircleIcon" class="h-5 text-gray-400" />
            {{ $t('company_switcher.no_results_found') }}
          </div>
          <div v-else>
            <div v-if="companyStore.companies.length > 0">
              <div
                v-for="(company, index) in companyStore.companies"
                :key="index"
                class="
                  flex
                  items-center
                  mx-1.5
                  p-2
                  px-2
                  rounded-md
                  cursor-pointer
                  hover:bg-gray-100
                "
                :class="{
                  'bg-gray-100':
                    companyStore.selectedCompany.id === company.id,
                }"
                @click="changeCompany(company)"
              >
                <span
                  class="
                    flex
                    items-center
                    justify-center
                    mr-3
                    overflow-hidden
                    text-base
                    font-semibold
                    bg-gray-200
                    rounded-md
                    w-9
                    h-9
                    text-primary-500
                  "
                >
                  <span v-if="!company.logo">
                    {{ initGenerator(company.name) }}
                  </span>
                  <img
                    v-else
                    :src="company.logo"
                    alt="Company logo"
                    class="w-full h-full object-contain"
                  />
                </span>
                <div class="flex flex-col">
                  <span class="text-sm text-gray-900">{{ company.name }}</span>
                </div>
                <BaseIcon
                  v-if="companyStore.selectedCompany.id === company.id"
                  name="CheckIcon"
                  class="h-5 ml-auto text-primary-500"
                />
              </div>
            </div>
          </div>
        </div>
        <div
          v-if="userStore.currentUser.is_owner"
          class="
            flex
            items-center
            p-3
            border-t border-gray-100
            cursor-pointer
            text-primary-400
            hover:text-primary-500
          "
          @click="addNewCompany"
        >
          <BaseIcon name="PlusCircleIcon" class="h-5 mr-2" />

          <span class="font-medium">
            {{ $t('company_switcher.add_new_company') }}
          </span>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { onClickOutside } from '@vueuse/core'
import { useRoute, useRouter } from 'vue-router'
import { useModalStore } from '../stores/modal'
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/admin//stores/global'
import { useUserStore } from '@/scripts/admin/stores/user'

import CompanyModal from '@/scripts/admin/components/modal-components/CompanyModal.vue'

defineProps({
  collapsed: { type: Boolean, default: false },
})

const companyStore = useCompanyStore()
const modalStore = useModalStore()
const route = useRoute()
const router = useRouter()
const globalStore = useGlobalStore()
const { t } = useI18n()
const userStore = useUserStore()
const isShow = ref(false)
const name = ref('')
const companySwitchBar = ref(null)

watch(route, () => {
  isShow.value = false
  name.value = ''
})

onClickOutside(companySwitchBar, () => {
  isShow.value = false
})

function initGenerator(name) {
  if (name) {
    const nameSplit = name.split(' ')
    const initials = nameSplit[0].charAt(0).toUpperCase()
    return initials
  }
}

function addNewCompany() {
  modalStore.openModal({
    title: t('company_switcher.new_company'),
    componentName: 'CompanyModal',
    size: 'sm',
  })
}

async function changeCompany(company) {
  await companyStore.setSelectedCompany(company)
  router.push('/admin/dashboard')
  await globalStore.setIsAppLoaded(false)
  await globalStore.bootstrap()
}
</script>
