<template>
  <div ref="searchBar" class="relative">
    <div>
      <BaseInput
        v-model="name"
        :placeholder="$t('global_search.search')"
        container-class="!rounded-lg shadow-soft"
        class="h-8 md:h-9 !rounded-lg bg-surface"
        @input="onSearch"
      >
        <template #left>
          <BaseIcon name="SearchIcon" class="text-gray-400" />
        </template>
        <template #right>
          <SpinnerIcon v-if="isSearching" class="h-5 text-primary-500" />
        </template>
      </BaseInput>
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
        class="
          scrollbar-thin
          scrollbar-thumb-rounded-full
          scrollbar-thumb-gray-300
          scrollbar-track-gray-100
          overflow-y-auto
          bg-surface
          rounded-lg
          border border-line-light
          mt-2
          shadow-card
          p-3
          absolute
          w-[300px]
          h-[200px]
          right-0
        "
      >
        <div
          v-if="
            usersStore.userList.length < 1 && usersStore.customerList.length < 1
          "
          class="
            flex
            items-center
            justify-center
            text-gray-400 text-base
            flex-col
            mt-4
          "
        >
          <BaseIcon name="ExclamationCircleIcon" class="text-gray-400" />

          {{ $t('global_search.no_results_found') }}
        </div>
        <div v-else>
          <div v-if="usersStore.customerList.length > 0">
            <label class="text-sm text-gray-400 mb-0.5 block px-2 uppercase">
              {{ $t('global_search.customers') }}
            </label>
            <div
              v-for="(customer, index) in usersStore.customerList"
              :key="index"
              class="p-2 hover:bg-surface-secondary cursor-pointer rounded-md"
            >
              <router-link
                :to="{ path: `/admin/customers/${customer.id}/view` }"
                class="flex items-center"
              >
                <span
                  class="
                    flex
                    items-center
                    justify-center
                    w-9
                    h-9
                    mr-3
                    text-base
                    font-semibold
                    bg-gray-200
                    rounded-full
                    text-primary-500
                  "
                >
                  {{ initGenerator(customer.name) }}
                </span>
                <div class="flex flex-col">
                  <span class="text-sm">{{ customer.name }}</span>
                  <span
                    v-if="customer.contact_name"
                    class="text-xs text-gray-400"
                  >
                    {{ customer.contact_name }}
                  </span>
                  <span v-else class="text-xs text-gray-400">{{
                    customer.email
                  }}</span>
                </div>
              </router-link>
            </div>
          </div>

          <div v-if="usersStore.userList.length > 0" class="mt-2">
            <label
              class="text-sm text-gray-400 mb-2 block px-2 mb-0.5 uppercase"
            >
              {{ $t('global_search.users') }}
            </label>
            <div
              v-for="(user, index) in usersStore.userList"
              :key="index"
              class="p-2 hover:bg-surface-secondary cursor-pointer rounded-md"
            >
              <router-link
                :to="{ path: `/admin/users/${user.id}/edit` }"
                class="flex items-center"
              >
                <span
                  class="
                    flex
                    items-center
                    justify-center
                    w-9
                    h-9
                    mr-3
                    text-base
                    font-semibold
                    bg-gray-200
                    rounded-full
                    text-primary-500
                  "
                >
                  {{ initGenerator(user.name) }}
                </span>
                <div class="flex flex-col">
                  <span class="text-sm">{{ user.name }}</span>
                  <span class="text-xs text-gray-400">{{ user.email }}</span>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useUsersStore } from '@/scripts/admin/stores/users'
import { onClickOutside } from '@vueuse/core'
import { useRoute } from 'vue-router'
import SpinnerIcon from '@/scripts/components/icons/SpinnerIcon.vue'
import { debounce } from 'lodash'

const usersStore = useUsersStore()

const isShow = ref(false)
const name = ref('')
const searchBar = ref(null)
const isSearching = ref(false)
const route = useRoute()

watch(route, () => {
  isShow.value = false
  name.value = ''
})

onSearch = debounce(onSearch, 500)

onClickOutside(searchBar, () => {
  isShow.value = false
  name.value = ''
})

function onSearch() {
  let data = {
    search: name.value,
  }

  if (name.value) {
    isSearching.value = true
    usersStore.searchUsers(data).then(() => {
      isShow.value = true
    })
    isSearching.value = false
  }
  if (name.value === '') {
    isShow.value = false
  }
}

function initGenerator(name) {
  if (name) {
    const nameSplit = name.split(' ')
    const initials = nameSplit[0].charAt(0).toUpperCase()
    return initials
  }
}
</script>
