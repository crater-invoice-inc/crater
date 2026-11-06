<template>
  <!--
    Single morph container: the floating bubble and the chat panel are two
    faces of the same box. Opening animates width, height and border-radius
    from a 56px circle anchored bottom-right up to the panel size, while the
    circle button and the panel content crossfade inside it. Nothing is
    mounted or unmounted during the animation.
  -->
  <div
    v-if="isVisible"
    ref="morphRoot"
    class="ai-morph fixed z-30 overflow-hidden bottom-6 right-6 border"
    :class="
      aiStore.isOpen
        ? 'ai-morph--open bg-white border-gray-200 border-solid shadow-xl'
        : 'ai-morph--closed border-transparent shadow-lg'
    "
    :role="aiStore.isOpen ? 'dialog' : undefined"
    :aria-label="aiStore.isOpen ? $t('ai_assistant.title') : undefined"
  >
    <!-- Closed face: the round action button, sitting on the exact corner
         the panel grows from. It fades out while the container expands. -->
    <button
      type="button"
      class="
        bubble-face absolute bottom-0 right-0 w-14 h-14 flex items-center
        justify-center text-white rounded-full bg-primary-500
        hover:bg-primary-600 focus:outline-none focus:ring-2
        focus:ring-primary-300
      "
      :class="{ 'bubble-face--hidden': aiStore.isOpen }"
      :title="$t('ai_assistant.title')"
      :aria-label="$t('ai_assistant.title')"
      :tabindex="aiStore.isOpen ? -1 : 0"
      @click="aiStore.openPanel()"
    >
      <BaseIcon name="SparklesIcon" />
    </button>

    <!-- Open face: panel content, laid out at the final panel size from the
         same corner (no reflow during the morph); the expanding container
         clips and reveals it while it fades in. -->
    <AiAssistantPanel
      class="panel-face absolute bottom-0 right-0"
      :class="{ 'panel-face--visible': aiStore.isOpen }"
    />
  </div>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAiAssistantStore } from '@/scripts/admin/stores/ai-assistant'
import { useModalStore } from '@/scripts/stores/modal'
import { useUserStore } from '@/scripts/admin/stores/user'
import AiAssistantPanel from './AiAssistantPanel.vue'

const route = useRoute()

const aiStore = useAiAssistantStore()
const modalStore = useModalStore()
const userStore = useUserStore()

const morphRoot = ref(null)

const EDIT_ROUTES = [
  'estimates.create',
  'estimates.edit',
  'invoices.create',
  'invoices.edit',
]

// v1: owner (company owner_id) and Crater super admin only, matching the
// backend authorization rule.
const isVisible = computed(() => {
  return (
    EDIT_ROUTES.includes(route.name) &&
    !modalStore.active &&
    !!userStore.currentUser &&
    (userStore.currentUser.is_owner ||
      userStore.currentUser.role === 'super admin')
  )
})

// The conversation store lives for the whole admin session: on every route
// change it drops the conversation of the previously opened document so
// another document's history can never leak into the current one.
watch(
  [() => route.name, () => route.params.id],
  () => {
    aiStore.onRouteChange()
  }
)

// Outside tap/click closes the panel. A passive capture listener keeps the
// page behind fully interactive (no blocking overlay) and pointerdown
// covers mouse, touch and pen. Everything inside the container (history,
// buttons, inner scrolling) is intentionally ignored.
function onPointerDown(event) {
  if (
    aiStore.isOpen &&
    morphRoot.value &&
    !morphRoot.value.contains(event.target)
  ) {
    aiStore.closePanel()
  }
}

watch(
  () => aiStore.isOpen,
  (open) => {
    if (open) {
      document.addEventListener('pointerdown', onPointerDown, {
        capture: true,
        passive: true,
      })
    } else {
      document.removeEventListener('pointerdown', onPointerDown, {
        capture: true,
      })
    }
  }
)

onUnmounted(() => {
  document.removeEventListener('pointerdown', onPointerDown, {
    capture: true,
  })
})
</script>

<style scoped>
/*
 * Morph geometry: both faces read the same panel box through the custom
 * properties below, so the container and its content never disagree during
 * the animation. Desktop opens a 400px panel capped to the viewport height;
 * mobile opens near full screen, still anchored bottom-right.
 */
.ai-morph {
  --ai-panel-w: 400px;
  --ai-panel-h: min(560px, calc(100vh - 72px));

  /* Opening timing (the destination state's transition drives it). */
  transition:
    width 250ms cubic-bezier(0.22, 1, 0.36, 1),
    height 250ms cubic-bezier(0.22, 1, 0.36, 1),
    border-radius 250ms cubic-bezier(0.22, 1, 0.36, 1),
    background-color 180ms ease,
    border-color 180ms ease,
    box-shadow 180ms ease;
}

@media (max-width: 639px) {
  .ai-morph {
    --ai-panel-w: calc(100vw - 16px);
    --ai-panel-h: calc(100vh - 88px);
  }

  @supports (height: 100dvh) {
    .ai-morph {
      --ai-panel-h: calc(100dvh - 88px);
    }
  }
}

/* Closed: a perfect 56px circle. Declaring the transition here too gives
   the collapse its own (slightly faster) timing. */
.ai-morph--closed {
  width: 56px;
  height: 56px;
  border-radius: 9999px;
  transition:
    width 220ms cubic-bezier(0.22, 1, 0.36, 1),
    height 220ms cubic-bezier(0.22, 1, 0.36, 1),
    border-radius 220ms cubic-bezier(0.22, 1, 0.36, 1),
    background-color 180ms ease,
    border-color 180ms ease,
    box-shadow 180ms ease;
}

/* Open: the chat panel, still anchored on the bottom-right corner. */
.ai-morph--open {
  width: var(--ai-panel-w);
  height: var(--ai-panel-h);
  border-radius: 8px;
}

/*
 * Circle button face: fades out quickly as soon as the expansion starts,
 * and comes back slightly delayed during the collapse so the panel folds
 * onto a still-visible bubble.
 */
.bubble-face {
  transition: opacity 160ms ease-out 140ms;
}

.bubble-face--hidden {
  opacity: 0;
  pointer-events: none;
  transition: opacity 140ms ease-in;
}

/*
 * Panel face: stays laid out at the open-state size while the container is
 * smaller (it is only clipped, never squeezed into an oval). It fades in
 * after the expansion started and fades out as soon as the collapse starts;
 * visibility flips only after the fade completes so the content stays
 * untabbable while hidden.
 */
.panel-face {
  width: var(--ai-panel-w);
  height: var(--ai-panel-h);
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition:
    opacity 150ms ease-in,
    visibility 0s linear 150ms;
}

.panel-face--visible {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
  transition:
    opacity 220ms ease-out 80ms,
    visibility 0s;
}
</style>
