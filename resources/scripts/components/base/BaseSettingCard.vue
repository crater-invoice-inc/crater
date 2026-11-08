<template>
  <BaseCard>
    <div class="flex flex-wrap justify-between lg:flex-nowrap mb-5">
      <div>
        <h6 class="font-medium text-lg text-left">
          {{ title }}
        </h6>

        <p
          class="
            mt-2
            text-sm
            leading-snug
            text-left text-muted
            max-w-[680px]
          "
        >
          {{ description }}
        </p>
      </div>

      <div class="mt-4 lg:mt-0 lg:ml-2">
        <slot name="action" />
      </div>
    </div>

    <slot />
  </BaseCard>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
})
</script>
