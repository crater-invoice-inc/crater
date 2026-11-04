<template>
  <BaseSettingCard
    :title="$t('settings.menu_title.modules')"
    :description="$t('settings.modules.description')"
  >
    <ul>
      <li v-for="module in companyModules" :key="module.key" class="mb-4">
        <BaseSwitchSection
          v-model="moduleStates[module.key]"
          :title="$t(`navigation.${module.key.replace('_', '-')}`)"
          :description="$t('settings.modules.toggle_description')"
        />
      </li>
    </ul>
  </BaseSettingCard>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const globalStore = useGlobalStore()
const companyStore = useCompanyStore()

const companyModules = globalStore.config.company_modules

const moduleStates = reactive({})

companyModules.forEach((module) => {
  moduleStates[module.key] =
    companyStore.selectedCompanySettings[`module_${module.key}`] !== 'NO'
})

watch(moduleStates, async () => {
  const settings = {}

  companyModules.forEach((module) => {
    settings[`module_${module.key}`] = moduleStates[module.key] ? 'YES' : 'NO'
  })

  await companyStore.updateCompanySettings({
    data: { settings },
    message: 'general.setting_updated',
  })
})
</script>
