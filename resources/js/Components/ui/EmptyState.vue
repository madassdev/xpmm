<template>
  <div class="flex flex-col items-center justify-center text-center text-gray-600">
    <div class="mb-3">
      <slot name="icon">
        <svg class="w-16 h-16 text-gray-300" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19 21H5a2 2 0 0 1-2-2V7h18v12a2 2 0 0 1-2 2ZM3 5h18V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v1Z" />
        </svg>
      </slot>
    </div>
    <h4 class="text-base font-semibold text-gray-800">{{ title }}</h4>
    <p class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>
<script setup>
const props = defineProps({
  title: { type: String, default: 'No Results' },
  subtitle: { type: String, default: 'There is nothing to display yet.' }
})
</script>
