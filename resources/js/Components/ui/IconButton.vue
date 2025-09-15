<template>
  <button
    :type="type"
    :aria-label="ariaLabel"
    :disabled="disabled"
    class="inline-flex items-center justify-center rounded-full p-2 text-gray-600 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 disabled:opacity-50 disabled:cursor-not-allowed"
  >
    <slot />
  </button>
</template>
<script setup>
const props = defineProps({
  type: { type: String, default: 'button' },
  ariaLabel: { type: String, required: true },
  disabled: { type: Boolean, default: false },
})
</script>
