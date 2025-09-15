<template>
  <component
    :is="as"
    class="bg-white border border-gray-200 rounded-xl shadow-sm"
    :class="paddingClass"
  >
    <slot />
  </component>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({
  as: { type: String, default: 'div' },
  padding: { type: String, default: 'md' } // xs | sm | md | lg
})
const paddingClass = computed(() => ({
  xs: 'p-3', sm: 'p-4', md: 'p-5', lg: 'p-6'
}[props.padding] || 'p-5'))
</script>
