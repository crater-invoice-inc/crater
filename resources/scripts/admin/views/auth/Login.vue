<template>
  <form id="loginForm" class="text-left" @submit.prevent="onSubmit">
    <BaseInputGroup
      :error="v$.email.$error && v$.email.$errors[0].$message"
      :label="$t('login.email')"
      class="mb-4"
      required
    >
      <BaseInput
        v-model="authStore.loginData.email"
        :invalid="v$.email.$error"
        focus
        type="email"
        name="email"
        @input="v$.email.$touch()"
      />
    </BaseInputGroup>

    <BaseInputGroup
      :error="v$.password.$error && v$.password.$errors[0].$message"
      :label="$t('login.password')"
      class="mb-4"
      required
    >
      <BaseInput
        v-model="authStore.loginData.password"
        :invalid="v$.password.$error"
        :type="getInputType"
        name="password"
        @input="v$.password.$touch()"
      >
        <template #right>
          <BaseIcon
            v-if="isShowPassword"
            name="EyeOffIcon"
            class="w-5 h-5 mr-1 text-gray-500 cursor-pointer"
            @click="isShowPassword = !isShowPassword"
          />
          <BaseIcon
            v-else
            name="EyeIcon"
            class="w-5 h-5 mr-1 text-gray-500 cursor-pointer"
            @click="isShowPassword = !isShowPassword"
          /> </template
      ></BaseInput>
    </BaseInputGroup>

    <div class="mt-5 mb-8">
      <div class="mb-4">
        <router-link
          to="forgot-password"
          class="text-sm font-medium text-primary-500 hover:text-primary-600"
        >
          {{ $t('login.forgot_password') }}
        </router-link>
      </div>
    </div>
    <BaseButton :loading="isLoading" type="submit" class="w-full justify-center">
      {{ $t('login.login') }}
    </BaseButton>
  </form>
</template>

<script setup>
import axios from 'axios'
import { ref, computed } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useRouter } from 'vue-router'
import { required, email, helpers } from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/scripts/admin/stores/auth'
import { handleError } from '@/scripts/helpers/error-handling'

const notificationStore = useNotificationStore()
const authStore = useAuthStore()
const { t } = useI18n()
const router = useRouter()
const isLoading = ref(false)
let isShowPassword = ref(false)

const rules = {
  email: {
    required: helpers.withMessage(t('validation.required'), required),
    email: helpers.withMessage(t('validation.email_incorrect'), email),
  },
  password: {
    required: helpers.withMessage(t('validation.required'), required),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => authStore.loginData)
)

const getInputType = computed(() => {
  if (isShowPassword.value) {
    return 'text'
  }
  return 'password'
})

async function onSubmit() {
  axios.defaults.withCredentials = true

  v$.value.$touch()

  if (v$.value.$invalid) {
    return true
  }

  isLoading.value = true

  try {
    isLoading.value = true
    await authStore.login(authStore.loginData)

    router.push('/admin/dashboard')

    notificationStore.showNotification({
      type: 'success',
      message: 'Logged in successfully.',
    })
  } catch (error) {
    isLoading.value = false
  }
}
</script>
