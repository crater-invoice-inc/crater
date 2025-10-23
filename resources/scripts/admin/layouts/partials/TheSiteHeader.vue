<template>
  <header
    class="
      fixed
      top-0
      left-0
      z-20
      flex
      items-center
      justify-between
      w-full
      px-4
      py-3
      md:h-16 md:px-8
      bg-gradient-to-r
      from-primary-500
      to-primary-400
    "
  >
    <router-link
      to="/admin/dashboard"
      class="
        float-none
        text-lg
        not-italic
        font-black
        tracking-wider
        text-white
        brand-main
        md:float-left
        font-base
        hidden
        md:block
      "
    >
      <img v-if="adminLogo" :src="adminLogo" class="h-6" />

      <MainLogo v-else class="h-6" light-color="white" dark-color="white" />
    </router-link>

    <!-- toggle button-->

    <div
      :class="{ 'is-active': globalStore.isSidebarOpen }"
      class="
        flex
        float-left
        p-1
        overflow-visible
        text-sm
        ease-linear
        bg-white
        border-0
        rounded
        cursor-pointer
        md:hidden md:ml-0
        hover:bg-gray-100
      "
      @click.prevent="onToggle"
    >
      <BaseIcon name="MenuIcon" class="!w-6 !h-6 text-gray-500" />
    </div>

    <ul class="flex float-right h-8 m-0 list-none md:h-9">
      <!-- Calculadora de Divisas -->

      <li class="relative block float-left ml-2">
        <BaseDropdown width-class="w-72">
          <template #activator>
            <BaseIcon
              name="CalculatorIcon"
              class="
                flex
                items-center
                justify-center
                w-8
                h-8
                ml-2
                text-sm text-white
                rounded
                md:h-9 md:w-9
                hover:bg-indigo-600
                transition
                duration-300
              "
            />
          </template>

          <div class="p-3 space-y-3">
            <p v-if="rateError" class="text-sm text-red-500 text-center">
              {{ rateError }}
            </p>

            <p v-else-if="!bcvRate" class="text-sm text-center text-gray-500">
              Tasa no disponible para cálculo.
            </p>

            <div v-else>
              <p
                class="
                  text-xs
                  font-bold
                  text-gray-700
                  mb-3
                  text-center
                  border-b
                  pb-2
                "
              >
                Calculadora USD ↔ VES (Tasa: {{ formattedBcvRate }})
              </p>

              <div class="grid grid-rows-2 grid-cols-4 items-center gap-2">
                <div class="col-span-1 flex items-center justify-center">
                  <p class="text-lg font-bold">$</p>
                </div>

                <div class="max-w-sm space-y-3 col-span-3">
                  <input
                    v-model="usdInput"
                    type="text"
                    min="0"
                    step="0.01"
                    :placeholder="calculatedUsd"
                    :disabled="!bcvRate"
                    class="
                      py-2.5
                      sm:py-3
                      px-4
                      block
                      w-full
                      border-gray-200
                      rounded-lg
                      sm:text-sm
                      focus:border-blue-500 focus:ring-blue-500
                      disabled:opacity-50 disabled:pointer-events-none
                    "
                    @input="handleInput('usd')"
                  />
                </div>

                <div class="col-span-1 flex items-center justify-center">
                  <p class="text-lg font-bold">Bs.</p>
                </div>

                <div class="max-w-sm space-y-3 col-span-3">
                  <input
                    v-model="vesInput"
                    type="text"
                    min="0"
                    step="0.01"
                    :placeholder="calculatedVes"
                    :disabled="!bcvRate"
                    class="
                      py-2.5
                      sm:py-3
                      px-4
                      block
                      w-full
                      border-gray-200
                      rounded-lg
                      sm:text-sm
                      focus:border-blue-500 focus:ring-blue-500
                      disabled:opacity-50 disabled:pointer-events-none
                    "
                    @input="handleInput('ves')"
                  />
                </div>
              </div>

              <p class="text-xs text-gray-400 mt-4 text-center">
                <a
                  href="https://www.bcv.org.ve/"
                  target="_blank"
                  rel="noopener noreferrer"
                  >Fuente: BCV.org.ve
                </a>
              </p>
            </div>
          </div>
        </BaseDropdown>
      </li>

      <li
        v-if="hasCreateAbilities"
        class="relative hidden float-left m-0 md:block"
      >
        <BaseDropdown width-class="w-48">
          <template #activator>
            <div
              class="
                flex
                items-center
                justify-center
                w-8
                h-8
                ml-2
                text-sm text-black
                bg-white
                rounded
                md:h-9 md:w-9
              "
            >
              <BaseIcon name="PlusIcon" class="w-5 h-5 text-gray-600" />
            </div>
          </template>

          <router-link to="/admin/invoices/create">
            <BaseDropdownItem
              v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
            >
              <BaseIcon
                name="DocumentTextIcon"
                class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
                aria-hidden="true"
              />

              {{ $t('invoices.new_invoice') }}
            </BaseDropdownItem>
          </router-link>

          <router-link to="/admin/estimates/create">
            <BaseDropdownItem
              v-if="userStore.hasAbilities(abilities.CREATE_ESTIMATE)"
            >
              <BaseIcon
                name="DocumentIcon"
                class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
                aria-hidden="true"
              />

              {{ $t('estimates.new_estimate') }}
            </BaseDropdownItem>
          </router-link>

          <router-link to="/admin/customers/create">
            <BaseDropdownItem
              v-if="userStore.hasAbilities(abilities.CREATE_CUSTOMER)"
            >
              <BaseIcon
                name="UserIcon"
                class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
                aria-hidden="true"
              />

              {{ $t('customers.new_customer') }}
            </BaseDropdownItem>
          </router-link>
        </BaseDropdown>
      </li>

      <li class="ml-2">
        <GlobalSearchBar
          v-if="
            userStore.currentUser.is_owner ||
            userStore.hasAbilities(abilities.VIEW_CUSTOMER)
          "
        />
      </li>

      <li>
        <CompanySwitcher />
      </li>

      <!-- User Dropdown-->

      <li class="relative block float-left ml-2">
        <BaseDropdown width-class="w-48">
          <template #activator>
            <img
              :src="previewAvatar"
              class="block w-8 h-8 rounded md:h-9 md:w-9 object-cover"
            />
          </template>

          <router-link to="/admin/settings/account-settings">
            <BaseDropdownItem>
              <BaseIcon
                name="CogIcon"
                class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
                aria-hidden="true"
              />

              {{ $t('navigation.settings') }}
            </BaseDropdownItem>
          </router-link>

          <BaseDropdownItem @click="logout">
            <BaseIcon
              name="LogoutIcon"
              class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
              aria-hidden="true"
            />

            {{ $t('navigation.logout') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </li>
    </ul>
  </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/scripts/admin/stores/auth'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import CompanySwitcher from '@/scripts/components/CompanySwitcher.vue'
import GlobalSearchBar from '@/scripts/components/GlobalSearchBar.vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'

import axios from 'axios'
import abilities from '@/scripts/admin/stub/abilities'

const authStore = useAuthStore()
const userStore = useUserStore()
const globalStore = useGlobalStore()
const router = useRouter()
const bcvRate = ref(null)
const loadingRate = ref(true)
const rateError = ref(null)
const usdInput = ref('1.00')
const vesInput = ref(null)

async function fetchBcvRate() {
  loadingRate.value = true

  rateError.value = null

  try {
    // Llama al endpoint de la API para obtener la tasa

    const response = await axios.get('/api/v1/bcv-rate')

    if (response.data.success) {
      // Extrae la tasa del body de la respuesta

      bcvRate.value = parseFloat(response.data.rate_usd_ves)
    } else {
      rateError.value =
        response.data.error || 'Error desconocido al obtener la tasa.'
    }
  } catch (error) {
    rateError.value = 'No se pudo conectar con el servicio de BCV.'
  } finally {
    loadingRate.value = false
  }
}

// Función para limpiar el campo opuesto al escribir

function updateInput(source) {
  if (source === 'usd') {
    vesInput.value = null
  } else {
    usdInput.value = null
  }
}

// Lógica de conversión (cálculo)

const formattedBcvRate = computed(() => {
  if (bcvRate.value) {
    // 1. Usar toFixed(2) para asegurar 2 decimales y convertir a string con '.'
    const rateWithDot = bcvRate.value.toFixed(2)

    // 2. Reemplazar el punto por una coma para el formato deseado
    return rateWithDot.replace('.', ',')
  }
  return 'N/A' // O el valor que prefieras si la tasa no está disponible
})

const calculatedVes = computed(() => {
  const rate = bcvRate.value
  // Usamos parseFloat(usdInput.value) para convertir el string limpio a número
  const amount = parseFloat(usdInput.value) || 0

  if (amount > 0 && rate) {
    // 1. Realizar el cálculo
    const result = amount * rate

    // 2. Formatear el resultado usando Intl.NumberFormat
    return new Intl.NumberFormat('es-VE', {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(result)
  }

  return '0,00'
})

const calculatedUsd = computed(() => {
  const rate = bcvRate.value
  // Usamos parseFloat(vesInput.value) para convertir el string limpio a número
  const amount = parseFloat(vesInput.value) || 0

  if (amount > 0 && rate) {
    // VES / Tasa = USD
    return (amount / rate).toFixed(2)
  }
  return '0.00'
})
// Función para aplicar la conversión y mostrar el resultado en el input opuesto

function handleInput(source) {
  // Determina qué ref manipular
  let valueRef = source === 'usd' ? usdInput : vesInput
  let value = String(valueRef.value || '') // Asegura que sea un string

  // 1. Limpia: Solo permite dígitos (0-9) y el punto decimal (.).
  value = value.replace(/[^0-9.]/g, '')

  // 2. Asegura que solo haya UN punto decimal.
  const parts = value.split('.')
  if (parts.length > 2) {
    // Mantiene la primera parte y junta el resto sin puntos
    value = parts[0] + '.' + parts.slice(1).join('')
  }

  // 3. Actualiza el valor limpio en la variable reactiva
  valueRef.value = value

  // 4. Llama a la lógica de limpieza de campo opuesto
  updateInput(source)
}

// Llama a la función al montar el componente

onMounted(() => {
  fetchBcvRate()
})

// --- FIN LÓGICA DE LA CALCULADORA ---

const previewAvatar = computed(() => {
  return userStore.currentUser && userStore.currentUser.avatar !== 0
    ? userStore.currentUser.avatar
    : getDefaultAvatar()
})

const adminLogo = computed(() => {
  if (globalStore.globalSettings.admin_portal_logo) {
    return '/storage/' + globalStore.globalSettings.admin_portal_logo
  }

  return false
})

function getDefaultAvatar() {
  const imgUrl = new URL('/img/default-avatar.jpg', import.meta.url)

  return imgUrl
}

function hasCreateAbilities() {
  return userStore.hasAbilities([
    abilities.CREATE_INVOICE,

    abilities.CREATE_ESTIMATE,

    abilities.CREATE_CUSTOMER,
  ])
}

async function logout() {
  await authStore.logout()

  router.push('/login')
}

function onToggle() {
  globalStore.setSidebarVisibility(true)
}
</script>
