<template>
  <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border"
        :class="variantClass">
    <slot />
  </span>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ variant: { type: String, default: 'subtle' } })
const variantClass = computed(() => ({
  subtle: 'bg-gray-50 text-gray-700 border-gray-200',
  solid: 'bg-primary text-white border-transparent',
  outline: 'text-gray-800 border-gray-300'
}[props.variant]))
</script>
