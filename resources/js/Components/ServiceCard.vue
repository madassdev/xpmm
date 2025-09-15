<script setup>
defineProps({
  title: String
})
</script>

<template>
  <div class="flex flex-col items-center gap-2 bg-white rounded-lg p-4 border w-28 h-28 justify-center">
    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
      <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none"><path d="M4 12h16" stroke="currentColor"/></svg>
    </div>
    <div class="text-sm text-gray-600 text-center">{{ title }}</div>
  </div>
</template>
