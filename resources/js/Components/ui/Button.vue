<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 disabled:opacity-60 disabled:cursor-not-allowed"
    :class="classes"
  >
    <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
    <slot />
  </button>
</template>
<script setup>
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'
const props = defineProps({
  type: { type: String, default: 'button' },
  variant: { type: String, default: 'primary' }, // primary, secondary, outline, ghost
  size: { type: String, default: 'md' }, // sm, md, lg
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})
const sizeClasses = computed(() => ({
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-5 py-2.5 text-base'
}[props.size]))
const classes = computed(() => {
  const base = sizeClasses.value
  switch (props.variant) {
    case 'secondary':
      return [base, 'bg-gray-900 text-white hover:bg-black']
    case 'outline':
      return [base, 'border border-gray-300 text-gray-800 hover:bg-gray-50']
    case 'ghost':
      return [base, 'text-gray-700 hover:bg-gray-100']
    default:
      return [base, 'bg-primary text-white hover:bg-primary/90']
  }
})
</script>
