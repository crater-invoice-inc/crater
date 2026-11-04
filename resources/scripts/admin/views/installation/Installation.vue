<template>
  <div class="flex flex-col items-center justify-between w-full pt-10">
    <img
      id="logo-crater"
      :src="logoUrl"
      alt="Crater Logo"
      class="h-12 mb-5 md:mb-10"
    />

    <BaseWizard
      :steps="7"
      :current-step="currentStepNumber"
      @click="onNavClick"
    >
      <component :is="stepComponent" @next="onStepChange" />
    </BaseWizard>
  </div>
</template>

<script>
import { ref } from 'vue'
import Step1RequirementsCheck from './Step1RequirementsCheck.vue'
import Step2PermissionCheck from './Step2PermissionCheck.vue'
import Step3DatabaseConfig from './Step3DatabaseConfig.vue'
import Step4VerifyDomain from './Step4VerifyDomain.vue'
import Step5EmailConfig from './Step5EmailConfig.vue'
import Step6AccountSettings from './Step6AccountSettings.vue'
import Step7CompanyInfo from './Step7CompanyInfo.vue'
import Step8CompanyPreferences from './Step8CompanyPreferences.vue'
import { useInstallationStore } from '@/scripts/admin/stores/installation'
import { useRouter } from 'vue-router'

export default {
  components: {
    step_1: Step1RequirementsCheck,
    step_2: Step2PermissionCheck,
    step_3: Step3DatabaseConfig,
    step_4: Step4VerifyDomain,
    step_5: Step5EmailConfig,
    step_6: Step6AccountSettings,
    step_7: Step7CompanyInfo,
    step_8: Step8CompanyPreferences,
  },

  setup() {
    let stepComponent = ref('step_1')
    let currentStepNumber = ref(1)

    const router = useRouter()
    const installationStore = useInstallationStore()

    checkCurrentProgress()

    async function checkCurrentProgress() {
      let res = await installationStore.fetchInstallationStep()

      if (res.data.profile_complete === 'COMPLETED') {
        router.push('/admin/dashboard')
        return
      }

      let dbstep = parseInt(res.data.profile_complete)

      if (dbstep) {
        currentStepNumber.value = dbstep + 1
        stepComponent.value = `step_${dbstep + 1}`
      }
    }

    async function saveStepProgress(data) {
      let status = {
        profile_complete: data,
      }

      try {
        await installationStore.addInstallationStep(status)
        return true
      } catch (e) {
        if (e?.response?.data?.message === 'The MAC is invalid.') {
          window.location.reload()
        }
        return false
      }
    }

    async function onStepChange(data) {
      if (data) {
        let res = await saveStepProgress(data)
        if (!res) return false
      }

      currentStepNumber.value++

      if (currentStepNumber.value <= 8) {
        stepComponent.value = 'step_' + currentStepNumber.value
      }
    }

    function onNavClick(e) {}

    const logoUrl = import.meta.env.DEV
      ? '/img/crater-logo.png'
      : '/build/img/crater-logo.png'

    return {
      logoUrl,
      stepComponent,
      currentStepNumber,
      onStepChange,
      saveStepProgress,
      onNavClick,
    }
  },
}
</script>
