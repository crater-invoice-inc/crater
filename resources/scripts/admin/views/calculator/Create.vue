<template>
  <BasePage>
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('calculator.title')" to="/admin/calculators" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <form class="mt-6" action="submit" @submit.prevent="submitCalculator">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
          <BaseCard class="w-full">
            <BaseInputGrid layout="one-column">
              <BaseInputGroup
                :label="$t('calculator.name')"
                required
                :error="
                  v$.currentCalculator.name.$error &&
                  v$.currentCalculator.name.$errors[0].$message
                "
              >
                <BaseInput
                  v-model="calculatorStore.currentCalculator.name"
                  :invalid="v$.currentCalculator.name.$error"
                  @input="v$.currentCalculator.name.$touch()"
                />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('calculator.notes')">
                <BaseTextarea
                  v-model="calculatorStore.currentCalculator.notes"
                  name="notes"
                  rows="2"
                />
              </BaseInputGroup>
            </BaseInputGrid>
          </BaseCard>

          <BaseCard
            v-for="category in categories"
            :key="category.key"
            class="w-full"
          >
            <div
              class="
                flex
                items-center
                justify-between
                px-5
                py-4
                border-b border-gray-200 border-solid
              "
            >
              <h5 class="text-sm font-semibold text-gray-900">
                {{ $t(`calculator.categories.${category.key}`) }}
              </h5>

              <span class="text-sm font-semibold text-gray-600">
                <BaseFormatMoney
                  :amount="categoryTotal(category.key) * 100"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>

            <div
              v-for="(item, index) in itemsOf(category.key)"
              :key="index"
              class="
                grid grid-cols-12
                gap-3
                items-start
                px-5
                py-3
                border-b border-gray-100 border-solid
              "
            >
              <div class="col-span-12 sm:col-span-5">
                <BaseInput
                  v-model="item.item_name"
                  :placeholder="$t('calculator.item_name')"
                />
              </div>

              <div class="col-span-5 sm:col-span-3">
                <BaseMoney v-model="item.unit_price" />
              </div>

              <div class="col-span-4 sm:col-span-2">
                <BaseInput v-model.number="item.quantity" type="number" step="0.5" min="0" />
              </div>

              <div class="col-span-2 sm:col-span-1 text-right font-medium text-gray-700 text-sm pt-2">
                {{ itemTotal(item).toFixed(2) }}
              </div>

              <div class="col-span-1 text-right pt-1.5">
                <BaseIcon
                  name="TrashIcon"
                  class="h-5 text-gray-400 cursor-pointer hover:text-red-500"
                  @click="removeItem(category.key, index)"
                />
              </div>
            </div>

            <div class="px-5 py-4">
              <BaseButton
                type="button"
                variant="primary-outline"
                @click="addItem(category.key)"
              >
                <template #left="slotProps">
                  <BaseIcon name="PlusIcon" :class="slotProps.class" />
                </template>
                {{ $t(`calculator.add_${category.add}`) }}
              </BaseButton>
            </div>
          </BaseCard>
        </div>

        <div>
          <BaseCard class="w-full lg:sticky lg:top-20">
            <div class="px-5 py-4 border-b border-gray-200 border-solid">
              <h5 class="text-sm font-semibold text-gray-900">
                {{ $t('calculator.summary') }}
              </h5>
            </div>

            <div class="px-5 py-4 space-y-3">
              <div
                v-for="category in categories"
                :key="category.key"
                class="flex items-center justify-between text-sm"
              >
                <span class="text-gray-600">
                  {{ $t(`calculator.categories.${category.key}`) }}
                </span>
                <span class="font-medium text-gray-800">
                  <BaseFormatMoney
                    :amount="categoryTotal(category.key) * 100"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </span>
              </div>
            </div>

            <div
              class="
                flex
                items-center
                justify-between
                px-5
                py-4
                border-t border-gray-200 border-solid
                bg-gray-50
              "
            >
              <span class="font-semibold text-gray-900">
                {{ $t('calculator.total_cost') }}
              </span>
              <span class="font-semibold text-gray-900">
                <BaseFormatMoney
                  :amount="totalCost * 100"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>

            <div class="px-5 py-4">
              <BaseButton type="submit" :loading="isSaving" block>
                <template #left="slotProps">
                  <BaseIcon
                    v-if="!isSaving"
                    name="SaveIcon"
                    :class="slotProps.class"
                  />
                </template>
                {{ isEdit ? $t('calculator.update_calculator') : $t('calculator.save_calculator') }}
              </BaseButton>
            </div>
          </BaseCard>
        </div>
      </div>
    </form>
  </BasePage>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { required, helpers } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useCalculatorStore } from '@/scripts/admin/stores/calculator'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const calculatorStore = useCalculatorStore()
const companyStore = useCompanyStore()
const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isSaving = ref(false)
let isFetchingInitialData = ref(false)

const categories = [
  { key: 'pre_production', type: 'task', add: 'task' },
  { key: 'production', type: 'role', add: 'role' },
  { key: 'post_production', type: 'role', add: 'role' },
  { key: 'travel', type: 'cost', add: 'cost' },
  { key: 'production_expenses', type: 'cost', add: 'cost' },
]

calculatorStore.$reset()
loadData()

const isEdit = computed(() => route.name === 'calculators.edit')

const pageTitle = computed(() =>
  isEdit.value ? t('calculator.edit_calculator') : t('calculator.new_calculator')
)

const rules = computed(() => {
  return {
    currentCalculator: {
      name: {
        required: helpers.withMessage(t('validation.required'), required),
      },
    },
  }
})

const v$ = useVuelidate(rules, calculatorStore)

const totalCost = computed(() =>
  categories.reduce(
    (sum, category) => sum + categoryTotal(category.key),
    0
  )
)

function itemsOf(categoryKey) {
  return calculatorStore.currentCalculator.calculator_items.filter(
    (item) => item.category === categoryKey
  )
}

function itemTotal(item) {
  return (item.unit_price || 0) * (item.quantity || 0)
}

function categoryTotal(categoryKey) {
  return itemsOf(categoryKey).reduce((sum, item) => sum + itemTotal(item), 0)
}

function addItem(categoryKey) {
  const category = categories.find((c) => c.key === categoryKey)

  calculatorStore.currentCalculator.calculator_items.push({
    category: categoryKey,
    type: category.type,
    item_name: '',
    description: '',
    unit_price: 0,
    quantity: 1,
  })
}

function removeItem(categoryKey, index) {
  const items = itemsOf(categoryKey)
  const item = items[index]

  const position =
    calculatorStore.currentCalculator.calculator_items.indexOf(item)

  if (position !== -1) {
    calculatorStore.currentCalculator.calculator_items.splice(position, 1)
  }
}

async function loadData() {
  isFetchingInitialData.value = true

  if (isEdit.value) {
    await calculatorStore.fetchCalculator(route.params.id)

    calculatorStore.currentCalculator.calculator_items =
      calculatorStore.currentCalculator.calculator_items.map((item) => ({
        ...item,
        unit_price: item.unit_price / 100,
      }))
  }

  isFetchingInitialData.value = false
}

async function submitCalculator() {
  v$.value.currentCalculator.$touch()

  if (v$.value.currentCalculator.$invalid) {
    return false
  }

  isSaving.value = true

  try {
    let data = {
      id: route.params.id,
      name: calculatorStore.currentCalculator.name,
      notes: calculatorStore.currentCalculator.notes,
      calculator_items: calculatorStore.currentCalculator.calculator_items.map(
        (item) => ({
          category: item.category,
          type: item.type,
          item_name: item.item_name,
          description: item.description,
          unit_price: Math.round((item.unit_price || 0) * 100),
          quantity: item.quantity || 0,
        })
      ),
    }

    const action = isEdit.value
      ? calculatorStore.updateCalculator
      : calculatorStore.addCalculator

    await action(data)

    isSaving.value = false
    router.push('/admin/calculators')
    calculatorStore.resetCurrentCalculator()
    v$.value.$reset()
  } catch (err) {
    isSaving.value = false
    return
  }
}
</script>
