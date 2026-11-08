<template>
  <div :class="classObject">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  rounded: {
    type: Boolean,
    default: false,
  },
  centered: {
    type: Boolean,
    default: false,
  },
  animated: {
    type: Boolean,
    default: true,
  },
})

const classObject = computed(() => {
  return {
    'base-content-placeholders': true,
    'base-content-placeholders-is-rounded': props.rounded,
    'base-content-placeholders-is-centered': props.centered,
    'base-content-placeholders-is-animated': props.animated,
  }
})
</script>

<style lang="scss">
$base-content-placeholders-primary-color: #dedee8 !default;
$base-content-placeholders-secondary-color: #f0f0f6 !default;
$base-content-placeholders-border-radius: 6px !default;
$base-content-placeholders-line-height: 15px !default;
$base-content-placeholders-spacing: 10px !default;

// Animations
@keyframes vueContentPlaceholdersAnimation {
  0% {
    transform: translate3d(-30%, 0, 0);
  }

  100% {
    transform: translate3d(100%, 0, 0);
  }
}

// Mixins
@mixin base-content-placeholders {
  position: relative;
  overflow: hidden;
  min-height: $base-content-placeholders-line-height;
  background: $base-content-placeholders-secondary-color;

  .base-content-placeholders-is-rounded & {
    border-radius: $base-content-placeholders-border-radius;
  }

  .base-content-placeholders-is-centered & {
    margin-left: auto;
    margin-right: auto;
  }

  .base-content-placeholders-is-animated &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100vw;
    max-width: 1000px;
    height: 100%;
    background: linear-gradient(
      to right,
      transparent 0%,
      darken($base-content-placeholders-secondary-color, 5%) 15%,
      transparent 30%
    );
    animation-duration: 1.5s;
    animation-fill-mode: forwards;
    animation-iteration-count: infinite;
    animation-name: vueContentPlaceholdersAnimation;
    animation-timing-function: linear;
  }
}

@mixin base-content-placeholders-spacing {
  [class^='base-content-placeholders-'] + & {
    margin-top: 2 * $base-content-placeholders-spacing;
  }
}

// Styles
.base-content-placeholders-heading {
  @include base-content-placeholders-spacing;
  display: flex;

  &__img {
    @include base-content-placeholders;
    margin-right: 1.5 * $base-content-placeholders-spacing;
  }

  &__content {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: center;
  }

  &__title {
    @include base-content-placeholders;
    width: 85%;
    margin-bottom: $base-content-placeholders-spacing;
    background: $base-content-placeholders-primary-color;
  }

  &__subtitle {
    @include base-content-placeholders;
    width: 90%;
  }
}

.base-content-placeholders-text {
  @include base-content-placeholders-spacing;

  &__line {
    @include base-content-placeholders;
    width: 100%;
    margin-bottom: $base-content-placeholders-spacing;

    &:first-child {
      width: 100%;
    }

    &:nth-child(2) {
      width: 90%;
    }

    &:nth-child(3) {
      width: 80%;
    }

    &:nth-child(4) {
      width: 70%;
    }
  }
}

.base-content-placeholders-box {
  position: relative;
  overflow: hidden;
  min-height: $base-content-placeholders-line-height;
  background: $base-content-placeholders-secondary-color;

  .base-content-placeholders-is-animated &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100vw;
    max-width: 1000px;
    height: 100%;
    background: linear-gradient(
      to right,
      transparent 0%,
      darken($base-content-placeholders-secondary-color, 5%) 15%,
      transparent 30%
    );
    animation-duration: 1.5s;
    animation-fill-mode: forwards;
    animation-iteration-count: infinite;
    animation-name: vueContentPlaceholdersAnimation;
    animation-timing-function: linear;
  }

  // @include base-content-placeholders-spacing;
}

.base-content-circle {
  border-radius: 100%;
}

.base-content-placeholders-is-rounded {
  border-radius: $base-content-placeholders-border-radius;
}
</style>
