<template>
  <span
    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium"
    :class="colorClass"
  >
    <slot name="icon" />
    <slot />
  </span>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ color: { type: String, default: 'neutral' } })
const colorClass = computed(() => ({
  neutral: 'bg-gray-100 text-gray-700',
  success: 'bg-green-100 text-green-700',
  danger: 'bg-red-100 text-red-700',
  warning: 'bg-yellow-100 text-yellow-800',
  info: 'bg-blue-100 text-blue-700'
}[props.color] || 'bg-gray-100 text-gray-700'))
</script>
